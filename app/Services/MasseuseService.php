<?php

namespace App\Services;

use App\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MasseuseService
{
    private const STATUS_LABELS = [
        'available' => 'พร้อมรับงาน',
        'busy' => 'ติดคิว',
        'on_break' => 'พัก',
        'off_duty' => 'ไม่ประจำกะ',
        'day_off' => 'หยุดงาน',
    ];

    private BookingService $bookingService;
    private StaffDirectoryService $staffDirectory;

    private array $tableExistsCache = [];

    public function __construct(BookingService $bookingService, StaffDirectoryService $staffDirectory)
    {
        $this->bookingService = $bookingService;
        $this->staffDirectory = $staffDirectory;
    }

    public function getPageData(User $user, ?int $requestedBranchId, string $date): array
    {
        $pageData = $this->bookingService->getStaffPageData($user, $requestedBranchId, $date);
        $activeBranchId = (int) ($pageData['activeBranchId'] ?? 0);
        $staffWithMonthlySummary = $this->appendMonthlySummary(
            isset($pageData['staff']) && is_array($pageData['staff']) ? $pageData['staff'] : [],
            $activeBranchId,
            $date
        );
        $staffWithYesterday = $this->appendYesterdaySummary($staffWithMonthlySummary, $activeBranchId, $date);

        $staffRecords = $this->tableExists('masseuses')
            ? $this->getStaffRecords($activeBranchId, $staffWithYesterday)
            : [];

        // Inject shift times back into staff array for index view
        foreach ($staffWithYesterday as &$s) {
            foreach ($staffRecords as $record) {
                if (($record['id'] ?? 0) === (int) ($s['id'] ?? 0)) {
                    $s['shift_start'] = $record['shift_start'] ?? null;
                    $s['shift_end'] = $record['shift_end'] ?? null;
                    break;
                }
            }
        }
        unset($s);

        return array_merge($pageData, [
            'staff' => $staffWithYesterday,
            'moduleReady' => $this->tableExists('masseuses'),
            'canManage' => $this->canManage($user),
            'canManageAttendance' => $this->canManageAttendance($user),
            'statusOptions' => $this->getStatusOptions(),
            'staffRecords' => $staffRecords,
        ]);
    }

    public function getSelfDashboardData(User $user, string $date): array
    {
        $selectedDate = $this->normalizeSelectedDate($date);
        $activeBranchId = $this->bookingService->resolveBranchIdForUser($user, null);
        $profile = $this->staffDirectory->resolveUserProfile($user);
        $selfMasseuseId = $this->resolveSelfMasseuseId($user, $activeBranchId);

        $record = null;
        if ($selfMasseuseId !== null) {
            $pageData = $this->getPageData($user, null, $selectedDate);
            foreach (($pageData['staff'] ?? []) as $staffRow) {
                if ((int) ($staffRow['id'] ?? 0) === $selfMasseuseId) {
                    $record = $staffRow;
                    break;
                }
            }
        }

        return [
            'moduleReady' => $this->tableExists('masseuses'),
            'selectedDate' => $selectedDate,
            'activeBranchId' => $activeBranchId,
            'profile' => $profile,
            'record' => $record,
            'isLinked' => $record !== null,
        ];
    }

    public function getEmptyFormRecord(): array
    {
        return [
            'id' => null,
            'display_id' => 'NEW',
            'nickname' => '',
            'full_name' => '',
            'name' => '',
            'profile_image' => '',
            'avatar' => '',
            'skills_description' => '',
            'status_value' => 'available',
            'status_label' => $this->formatStatusLabel('available'),
            'income' => 0.0,
            'commission' => 0.0,
            'queue_count' => 0,
            'queue_load' => 0,
            'is_working_today' => false,
            'performance_status' => $this->formatStatusLabel('available'),
            'latest_queue' => null,
        ];
    }

    public function createMasseuse(User $user, ?int $requestedBranchId, array $payload, ?UploadedFile $profileImage): void
    {
        $this->assertModuleReady();

        $branchId = $this->bookingService->resolveBranchIdForUser($user, $requestedBranchId);

        $row = [
            'branch_id' => $branchId,
            'nickname' => $this->normalizeRequiredString($payload['nickname'] ?? null, 'nickname', 'กรุณาระบุชื่อเล่นหมอนวด'),
            'full_name' => $this->normalizeNullableString($payload['full_name'] ?? null),
            'skills_description' => $this->normalizeNullableString($payload['skills_description'] ?? null),
            'status' => $this->normalizeStatus($payload['status'] ?? null),
            'shift_start' => $payload['shift_start'] ?? null,
            'shift_end' => $payload['shift_end'] ?? null,
            'guarantee_amount' => isset($payload['guarantee_amount']) ? (float) $payload['guarantee_amount'] : 0.0,
        ];

        if ($profileImage !== null) {
            $row['profile_image'] = $this->storeProfileImage($profileImage);
        }

        DB::table('masseuses')->insert($row);
    }

    public function updateStaffAttendance(
        User $user,
        ?int $requestedBranchId,
        string $date,
        int $staffId,
        bool $isWorking
    ): array {
        return $this->bookingService->updateStaffAttendance(
            $user,
            $requestedBranchId,
            $date,
            $staffId,
            $isWorking
        );
    }

    public function updateMasseuse(
        User $user,
        ?int $requestedBranchId,
        int $staffId,
        array $payload,
        ?UploadedFile $profileImage
    ): void {
        $this->assertModuleReady();

        $branchId = $this->bookingService->resolveBranchIdForUser($user, $requestedBranchId);
        $existing = $this->findStaffRow($branchId, $staffId);

        if ($existing === null) {
            throw ValidationException::withMessages([
                'staff' => ['ไม่พบข้อมูลหมอนวดที่ต้องการแก้ไข'],
            ]);
        }

        $updates = [
            'nickname' => $this->normalizeRequiredString($payload['nickname'] ?? null, 'nickname', 'กรุณาระบุชื่อเล่นหมอนวด'),
            'full_name' => $this->normalizeNullableString($payload['full_name'] ?? null),
            'skills_description' => $this->normalizeNullableString($payload['skills_description'] ?? null),
            'status' => $this->normalizeStatus($payload['status'] ?? null),
            'shift_start' => $payload['shift_start'] ?? null,
            'shift_end' => $payload['shift_end'] ?? null,
            'guarantee_amount' => isset($payload['guarantee_amount']) ? (float) $payload['guarantee_amount'] : 0.0,
        ];

        $removeProfileImage = !empty($payload['remove_profile_image']);
        if ($removeProfileImage) {
            $updates['profile_image'] = null;
        }

        if ($profileImage !== null) {
            $updates['profile_image'] = $this->storeProfileImage($profileImage);
        }

        DB::table('masseuses')
            ->where('id', $staffId)
            ->where('branch_id', $branchId)
            ->update($updates);

        if ($removeProfileImage && $existing->profile_image !== null) {
            $this->deleteManagedProfileImage((string) $existing->profile_image);
        }

        if ($profileImage !== null && $existing->profile_image !== null) {
            $this->deleteManagedProfileImage((string) $existing->profile_image);
        }
    }

    public function deleteMasseuse(User $user, ?int $requestedBranchId, int $staffId): void
    {
        $this->assertModuleReady();

        $branchId = $this->bookingService->resolveBranchIdForUser($user, $requestedBranchId);
        $existing = $this->findStaffRow($branchId, $staffId);

        if ($existing === null) {
            throw ValidationException::withMessages([
                'staff' => ['ไม่พบข้อมูลหมอนวดที่ต้องการลบ'],
            ]);
        }

        if ($this->tableExists('bookings')) {
            $hasBookings = DB::table('bookings')
                ->where('masseuse_id', $staffId)
                ->exists();

            if ($hasBookings) {
                throw ValidationException::withMessages([
                    'staff' => ['ไม่สามารถลบหมอนวดที่มีประวัติคิวงานได้'],
                ]);
            }
        }

        if ($this->tableExists('order_items')) {
            $hasSales = DB::table('order_items')
                ->where('masseuse_id', $staffId)
                ->exists();

            if ($hasSales) {
                throw ValidationException::withMessages([
                    'staff' => ['ไม่สามารถลบหมอนวดที่มีประวัติรายการขายได้'],
                ]);
            }
        }

        if ($this->tableExists('staff_attendance')) {
            DB::table('staff_attendance')
                ->where('masseuse_id', $staffId)
                ->delete();
        }

        DB::table('masseuses')
            ->where('id', $staffId)
            ->where('branch_id', $branchId)
            ->delete();

        if ($existing->profile_image !== null) {
            $this->deleteManagedProfileImage((string) $existing->profile_image);
        }
    }

    public function getStatusOptions(): array
    {
        $options = [];

        foreach (self::STATUS_LABELS as $value => $label) {
            $options[] = [
                'value' => $value,
                'label' => $label,
            ];
        }

        return $options;
    }

    private function getStaffRecords(int $branchId, array $staffStats): array
    {
        $statsById = [];
        foreach ($staffStats as $staff) {
            $statsById[(string) ($staff['id'] ?? '')] = $staff;
        }

        return DB::table('masseuses')
            ->where('branch_id', $branchId)
            ->orderBy('id')
            ->get([
                'id',
                'branch_id',
                'nickname',
                'full_name',
                'profile_image',
                'skills_description',
                'status',
                'base_salary',
                'shift_start',
                'shift_end',
                'guarantee_amount',
            ])
            ->map(function ($row) use ($statsById): array {
                $staffId = (string) $row->id;
                $stats = $statsById[$staffId] ?? [];
                $queue = isset($stats['queue']) && is_array($stats['queue']) ? $stats['queue'] : [];

                return [
                    'id' => (int) $row->id,
                    'display_id' => 'MS' . str_pad($staffId, 3, '0', STR_PAD_LEFT),
                    'nickname' => (string) ($row->nickname ?? ''),
                    'full_name' => $row->full_name !== null ? (string) $row->full_name : '',
                    'name' => (string) ($stats['name'] ?? $row->nickname ?? ''),
                    'profile_image' => $row->profile_image !== null ? (string) $row->profile_image : '',
                    'avatar' => $this->resolveAvatar(
                        $row->profile_image !== null ? (string) $row->profile_image : '',
                        (string) ($row->nickname ?? '')
                    ),
                    'shift_start' => $row->shift_start !== null ? \Carbon\Carbon::parse($row->shift_start)->format('H:i') : null,
                    'shift_end' => $row->shift_end !== null ? \Carbon\Carbon::parse($row->shift_end)->format('H:i') : null,
                    'guarantee_amount' => (float) ($row->guarantee_amount ?? 0),
                    'skills_description' => $row->skills_description !== null ? (string) $row->skills_description : '',
                    'status_value' => $row->status !== null ? (string) $row->status : 'off_duty',
                    'status_label' => $this->formatStatusLabel($row->status !== null ? (string) $row->status : 'off_duty'),
                    'base_salary' => $row->base_salary !== null ? (float) $row->base_salary : 0.0,
                    'income' => (float) ($stats['income'] ?? 0),
                    'commission' => (float) ($stats['commission'] ?? 0),
                    'queue_count' => count($queue),
                    'queue_load' => (int) ($stats['queueLoad'] ?? 0),
                    'is_working_today' => (bool) ($stats['isWorkingToday'] ?? true),
                    'performance_status' => (string) ($stats['status'] ?? $this->formatStatusLabel($row->status !== null ? (string) $row->status : 'off_duty')),
                    'latest_queue' => $queue[0] ?? null,
                ];
            })
            ->all();
    }

    private function appendMonthlySummary(array $staffStats, int $branchId, string $date): array
    {
        $monthlySummaryByStaff = $this->getMonthlySummaryByStaff($branchId, $date);

        return array_map(static function (array $staff) use ($monthlySummaryByStaff): array {
            $staffId = (string) ($staff['id'] ?? '');
            $queue = isset($staff['queue']) && is_array($staff['queue']) ? $staff['queue'] : [];
            $monthlySummary = $monthlySummaryByStaff[$staffId] ?? [
                'income' => 0.0,
                'commission' => 0.0,
                'queue_count' => 0,
            ];

            $staff['daily_queue_count'] = count($queue);
            $staff['monthly_income'] = (float) ($monthlySummary['income'] ?? 0.0);
            $staff['monthly_commission'] = (float) ($monthlySummary['commission'] ?? 0.0);
            $staff['monthly_queue_count'] = (int) ($monthlySummary['queue_count'] ?? 0);

            return $staff;
        }, $staffStats);
    }

    private function appendYesterdaySummary(array $staffStats, int $branchId, string $date): array
    {
        $yesterdaySummaryByStaff = $this->getYesterdaySummaryByStaff($branchId, $date);

        return array_map(static function (array $staff) use ($yesterdaySummaryByStaff): array {
            $staffId = (string) ($staff['id'] ?? '');
            $yesterdaySummary = $yesterdaySummaryByStaff[$staffId] ?? [
                'income' => 0.0,
                'commission' => 0.0,
                'queue_count' => 0,
            ];

            $staff['yesterday_income'] = (float) ($yesterdaySummary['income'] ?? 0.0);
            $staff['yesterday_commission'] = (float) ($yesterdaySummary['commission'] ?? 0.0);
            $staff['yesterday_queue_count'] = (int) ($yesterdaySummary['queue_count'] ?? 0);

            return $staff;
        }, $staffStats);
    }

    private function getYesterdaySummaryByStaff(int $branchId, string $date): array
    {
        // เปลี่ยนมาเช็คตารางที่เกี่ยวข้องจริง
        if (!$this->tableExists('commissions') || !$this->tableExists('orders')) {
            return [];
        }

        $selectedDate = Carbon::parse($date);
        $yesterday = $selectedDate->copy()->subDay()->startOfDay();
        $yesterdayEnd = $yesterday->copy()->addDay();

        // ดึงยอดรวมจากฐานข้อมูลโดยตรง (แม่นยำและเร็วกว่า)
        return DB::table('commissions as c')
            ->join('order_items as oi', 'oi.id', '=', 'c.order_item_id')
            ->join('orders as o', 'o.id', '=', 'oi.order_id')
            ->where('o.branch_id', $branchId)
            ->where('o.status', 'paid') // นับเฉพาะบิลที่จ่ายเงินแล้ว
            ->whereBetween('o.created_at', [$yesterday->toDateTimeString(), $yesterdayEnd->toDateTimeString()])
            ->select(
                'c.masseuse_id',
                DB::raw('SUM(c.amount) as total_commission'), // ยอดคอมมิชชันจริง
                DB::raw('COUNT(oi.id) as queue_count'),       // จำนวนรอบนวดจริง
                DB::raw('SUM(oi.unit_price * oi.qty) as total_income') // ยอดขายที่พนักงานทำได้
            )
            ->groupBy('c.masseuse_id')
            ->get()
            ->keyBy('masseuse_id')
            ->map(function ($row) {
                return [
                    'income' => (float) $row->total_income,
                    'commission' => (float) $row->total_commission,
                    'queue_count' => (int) $row->queue_count,
                ];
            })
            ->toArray();
    }

    private function getMonthlySummaryByStaff(int $branchId, string $date): array
    {
        if (!$this->tableExists('commissions') || !$this->tableExists('orders')) {
            return [];
        }

        $selectedDate = Carbon::parse($date);
        $monthStart = $selectedDate->copy()->startOfMonth()->startOfDay();
        $nextMonthStart = $selectedDate->copy()->addMonthNoOverflow()->startOfMonth()->startOfDay();

        return DB::table('commissions as c')
            ->join('order_items as oi', 'oi.id', '=', 'c.order_item_id')
            ->join('orders as o', 'o.id', '=', 'oi.order_id')
            ->where('o.branch_id', $branchId)
            ->where('o.status', 'paid')
            ->whereBetween('o.created_at', [$monthStart->toDateTimeString(), $nextMonthStart->toDateTimeString()])
            ->select(
                'c.masseuse_id',
                DB::raw('SUM(c.amount) as total_commission'),
                DB::raw('COUNT(oi.id) as queue_count'),
                DB::raw('SUM(oi.unit_price * oi.qty) as total_income')
            )
            ->groupBy('c.masseuse_id')
            ->get()
            ->keyBy('masseuse_id')
            ->map(function ($row) {
                return [
                    'income' => (float) $row->total_income,
                    'commission' => (float) $row->total_commission,
                    'queue_count' => (int) $row->queue_count,
                ];
            })
            ->toArray();
    }

    private function loadBookingServicesMap($bookingRows): array
    {
        $bookingIds = [];
        $fallbackRows = [];

        foreach ($bookingRows as $row) {
            $bookingId = (int) ($row->id ?? 0);
            if ($bookingId <= 0) {
                continue;
            }

            $bookingIds[] = $bookingId;
            $fallbackRows[$bookingId] = $row;
        }

        if (empty($bookingIds)) {
            return [];
        }

        $servicesByBooking = [];

        if ($this->tableExists('booking_services')) {
            $rows = DB::table('booking_services as bs')
                ->join('services as s', 's.id', '=', 'bs.service_id')
                ->whereIn('bs.booking_id', $bookingIds)
                ->orderBy('bs.booking_id')
                ->orderBy('bs.sort_order')
                ->orderBy('bs.id')
                ->get([
                    'bs.booking_id',
                    'bs.service_id',
                    's.name as service_name',
                    's.price as service_price',
                ]);

            foreach ($rows as $row) {
                $bookingId = (int) $row->booking_id;
                if (!isset($servicesByBooking[$bookingId])) {
                    $servicesByBooking[$bookingId] = [];
                }

                $servicesByBooking[$bookingId][] = [
                    'id' => (int) $row->service_id,
                    'name' => (string) ($row->service_name ?? ''),
                    'price' => (float) ($row->service_price ?? 0.0),
                ];
            }
        }

        foreach ($bookingIds as $bookingId) {
            if (isset($servicesByBooking[$bookingId]) && !empty($servicesByBooking[$bookingId])) {
                continue;
            }

            $fallbackRow = $fallbackRows[$bookingId] ?? null;
            if ($fallbackRow === null || $fallbackRow->service_id === null) {
                $servicesByBooking[$bookingId] = [];
                continue;
            }

            $servicesByBooking[$bookingId] = [[
                'id' => (int) $fallbackRow->service_id,
                'name' => (string) ($fallbackRow->service_name ?? ''),
                'price' => (float) ($fallbackRow->service_price ?? 0.0),
            ]];
        }

        return $servicesByBooking;
    }

    private function getCommissionConfigsByService(): array
    {
        if (!$this->tableExists('commission_configs')) {
            return [];
        }

        return DB::table('commission_configs')
            ->where('item_type', 'service')
            ->get(['item_id', 'type', 'value', 'deduct_cost'])
            ->keyBy('item_id')
            ->map(static function ($row): array {
                return [
                    'type' => (string) $row->type,
                    'value' => (float) $row->value,
                    'deduct_cost' => (float) $row->deduct_cost,
                ];
            })
            ->all();
    }

    private function estimateServicesCommission(array $services, array $commissionConfigs): float
    {
        $total = 0.0;

        foreach ($services as $service) {
            $serviceId = isset($service['id']) ? (int) $service['id'] : 0;
            if ($serviceId <= 0 || !isset($commissionConfigs[$serviceId])) {
                continue;
            }

            $config = $commissionConfigs[$serviceId];
            $servicePrice = isset($service['price']) ? (float) $service['price'] : 0.0;

            if (($config['type'] ?? '') === 'fixed') {
                $total += (float) ($config['value'] ?? 0.0);
                continue;
            }

            $baseAmount = max(0.0, $servicePrice - (float) ($config['deduct_cost'] ?? 0.0));
            $total += $baseAmount * ((float) ($config['value'] ?? 0.0) / 100);
        }

        return $total;
    }

    private function findStaffRow(int $branchId, int $staffId): ?object
    {
        return DB::table('masseuses')
            ->where('branch_id', $branchId)
            ->where('id', $staffId)
            ->first([
                'id',
                'branch_id',
                'profile_image',
            ]);
    }

    private function resolveAvatar(string $profileImage, string $staffId): string
    {
        if ($profileImage === '') {
            return 'https://i.pravatar.cc/160?u=' . rawurlencode('masseuse-' . $staffId);
        }

        if (preg_match('#^https?://#i', $profileImage) === 1) {
            return $profileImage;
        }

        return '/' . ltrim($profileImage, '/');
    }

    private function storeProfileImage(UploadedFile $profileImage): string
    {
        $directory = public_path('uploads/masseuses');
        if (!File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $extension = strtolower($profileImage->getClientOriginalExtension());
        $safeExtension = $extension !== '' ? $extension : 'jpg';
        $filename = 'masseuse-' . now()->format('YmdHis') . '-' . Str::random(8) . '.' . $safeExtension;

        $profileImage->move($directory, $filename);

        return 'uploads/masseuses/' . $filename;
    }

    private function deleteManagedProfileImage(string $profileImage): void
    {
        if (preg_match('#^https?://#i', $profileImage) === 1) {
            return;
        }

        $normalizedPath = ltrim($profileImage, '/');
        if (strpos($normalizedPath, 'uploads/masseuses/') !== 0) {
            return;
        }

        $absolutePath = public_path($normalizedPath);
        if (File::exists($absolutePath)) {
            File::delete($absolutePath);
        }
    }

    private function formatStatusLabel(string $status): string
    {
        return self::STATUS_LABELS[$status] ?? ucfirst(str_replace('_', ' ', $status));
    }

    private function normalizeRequiredString($value, string $field, string $message): string
    {
        $normalized = trim((string) $value);
        if ($normalized === '') {
            throw ValidationException::withMessages([
                $field => [$message],
            ]);
        }

        return $normalized;
    }

    private function normalizeNullableString($value): ?string
    {
        $normalized = trim((string) $value);
        return $normalized !== '' ? $normalized : null;
    }

    private function normalizeStatus($value): string
    {
        $status = trim((string) $value);
        if (!array_key_exists($status, self::STATUS_LABELS)) {
            throw ValidationException::withMessages([
                'status' => ['สถานะหมอนวดไม่ถูกต้อง'],
            ]);
        }

        return $status;
    }

    private function assertModuleReady(): void
    {
        if ($this->tableExists('masseuses')) {
            return;
        }

        throw ValidationException::withMessages([
            'masseuses' => ['ยังไม่พบตาราง masseuses ในฐานข้อมูล'],
        ]);
    }

    private function canManage(User $user): bool
    {
        return in_array((string) ($user->role ?? ''), ['super_admin', 'shop_owner', 'branch_manager'], true);
    }

    private function canManageAttendance(User $user): bool
    {
        return in_array((string) ($user->role ?? ''), ['super_admin', 'shop_owner', 'branch_manager', 'cashier'], true);
    }

    private function normalizeSelectedDate(string $date): string
    {
        try {
            return Carbon::createFromFormat('Y-m-d', $date)->toDateString();
        } catch (\Throwable $e) {
            return Carbon::today()->toDateString();
        }
    }

    private function resolveSelfMasseuseId(User $user, int $branchId): ?int
    {
        if (!$this->tableExists('masseuses')) {
            return null;
        }

        if ($this->hasColumn('masseuses', 'user_id')) {
            $linkedId = DB::table('masseuses')
                ->where('user_id', (int) $user->id)
                ->when($this->hasColumn('masseuses', 'branch_id'), function ($query) use ($branchId) {
                    $query->where('branch_id', $branchId);
                })
                ->value('id');

            if ($linkedId !== null) {
                return (int) $linkedId;
            }
        }

        $staffId = isset($user->staff_id) ? (int) $user->staff_id : 0;
        if ($staffId <= 0 || !$this->tableExists('staff')) {
            return null;
        }

        $staff = DB::table('staff')
            ->where('id', $staffId)
            ->first(['name', 'nickname', 'branch_id']);

        if ($staff === null) {
            return null;
        }

        $names = array_values(array_unique(array_filter([
            trim((string) ($staff->name ?? '')),
            trim((string) ($staff->nickname ?? '')),
        ], static function (string $value): bool {
            return $value !== '';
        })));

        if (empty($names)) {
            return null;
        }

        $candidates = DB::table('masseuses')
            ->when($this->hasColumn('masseuses', 'branch_id'), function ($query) use ($branchId) {
                $query->where('branch_id', $branchId);
            })
            ->where(function ($query) use ($names): void {
                foreach ($names as $index => $name) {
                    if ($index === 0) {
                        $query->where('nickname', $name)
                            ->orWhere('full_name', $name);
                        continue;
                    }

                    $query->orWhere('nickname', $name)
                        ->orWhere('full_name', $name);
                }
            })
            ->get(['id']);

        if ($candidates->count() !== 1) {
            return null;
        }

        $resolvedId = (int) $candidates->first()->id;

        if ($resolvedId > 0 && $this->hasColumn('masseuses', 'user_id')) {
            DB::table('masseuses')
                ->where('id', $resolvedId)
                ->whereNull('user_id')
                ->update(['user_id' => (int) $user->id]);
        }

        return $resolvedId > 0 ? $resolvedId : null;
    }

    private function tableExists(string $table): bool
    {
        if (!array_key_exists($table, $this->tableExistsCache)) {
            $this->tableExistsCache[$table] = Schema::hasTable($table);
        }

        return (bool) $this->tableExistsCache[$table];
    }

    private function hasColumn(string $table, string $column): bool
    {
        return $this->tableExists($table) && Schema::hasColumn($table, $column);
    }
}
