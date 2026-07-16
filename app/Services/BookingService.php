<?php

namespace App\Services;

use App\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class BookingService
{
    private const MAX_SERVICES_PER_BOOKING = 3;
    private const STATUSES = [
        'waiting' => 'รอรับบริการ',
        'in_service' => 'กำลังนวด',
        'completed' => 'เสร็จสิ้น',
        'cancelled' => 'ยกเลิก',
    ];

    private BranchContextService $branchContext;
    private StaffAttendanceService $staffAttendanceService;
    private CommissionService $commissionService;
    private array $tableExistsCache = [];
    private array $columnExistsCache = [];

    public function __construct(StaffAttendanceService $staffAttendanceService, BranchContextService $branchContext, CommissionService $commissionService)
    {
        $this->staffAttendanceService = $staffAttendanceService;
        $this->branchContext = $branchContext;
        $this->commissionService = $commissionService;
    }

    public function getPageData(User $user, ?int $requestedBranchId, string $date): array
    {
        $branchId = $this->resolveAuthorizedBranchId($user, $requestedBranchId);
        $selectedDate = $this->normalizeDate($date);
        $branchHours = $this->getBranchOperatingHours($branchId);

        return [
            'activeBranchId' => $branchId,
            'selectedDate' => $selectedDate,
            'branchOpenTime' => $branchHours['open_time'],
            'branchCloseTime' => $branchHours['close_time'],
            'staff' => $this->getStaff($branchId, $selectedDate, true),
            'serviceItems' => $this->getServiceItems($branchId),
            'customers' => $this->getCustomers($branchId),
            'beds' => $this->getBeds($branchId),
            'statuses' => [
                'waiting' => self::STATUSES['waiting'],
                'in_service' => self::STATUSES['in_service'],
                'completed' => self::STATUSES['completed'],
                'cancelled' => self::STATUSES['cancelled'],
            ],
            'bookings' => $this->getBookingsByDate($branchId, $selectedDate),
        ];
    }

    public function getStaffRoster(User $user, ?int $requestedBranchId, string $date, bool $onlyWorking = false): array
    {
        $branchId = $this->resolveAuthorizedBranchId($user, $requestedBranchId);
        $selectedDate = $this->normalizeDate($date);

        return $this->getStaff($branchId, $selectedDate, $onlyWorking);
    }

    public function getStaffPageData(User $user, ?int $requestedBranchId, string $date): array
    {
        $branchId = $this->resolveAuthorizedBranchId($user, $requestedBranchId);
        $selectedDate = $this->normalizeDate($date);

        return [
            'activeBranchId' => $branchId,
            'selectedDate' => $selectedDate,
            'staff' => $this->buildStaffPageStaff($branchId, $selectedDate),
        ];
    }

    public function resolveBranchIdForUser(User $user, ?int $requestedBranchId): int
    {
        return $this->resolveAuthorizedBranchId($user, $requestedBranchId);
    }

    public function updateStaffAttendance(User $user, ?int $requestedBranchId, string $date, int $staffId, bool $isWorking): array
    {
        $branchId = $this->resolveAuthorizedBranchId($user, $requestedBranchId);
        $selectedDate = $this->normalizeDate($date);
        $staff = $this->findStaffById($branchId, $staffId);
        if ($staff === null) {
            throw ValidationException::withMessages([
                'staff_id' => ['ไม่พบหมอนวดในสาขานี้'],
            ]);
        }

        $currentStatus = (string) ($staff->status ?? '');
        $nextStatus = $isWorking
            ? ($currentStatus === 'day_off' || $currentStatus === '' ? 'available' : $currentStatus)
            : 'day_off';

        DB::table('masseuses')
            ->where('branch_id', $branchId)
            ->where('id', $staffId)
            ->update(['status' => $nextStatus]);

        $this->staffAttendanceService->setAttendance($branchId, $selectedDate, (string) $staffId, $isWorking);

        return [
            'staff_id' => $staffId,
            'date' => $selectedDate,
            'isWorkingToday' => $isWorking,
            'statusValue' => $nextStatus,
        ];
    }

    public function getBookingsDataForDate(User $user, ?int $requestedBranchId, string $date): array
    {
        $branchId = $this->resolveAuthorizedBranchId($user, $requestedBranchId);
        $selectedDate = $this->normalizeDate($date);

        return [
            'branch_id' => $branchId,
            'date' => $selectedDate,
            'bookings' => $this->getBookingsByDate($branchId, $selectedDate),
        ];
    }

    public function saveBooking(?int $bookingId, array $payload, User $user): array
    {
        $requestedBranchId = isset($payload['branch_id']) ? (int) $payload['branch_id'] : null;
        $branchId = $this->resolveAuthorizedBranchId($user, $requestedBranchId);
        $queueDate = $this->normalizeDate((string) $payload['queue_date']);
        $startAt = Carbon::createFromFormat('Y-m-d H:i', $queueDate . ' ' . $payload['start_time']);
        $endAt = Carbon::createFromFormat('Y-m-d H:i', $queueDate . ' ' . $payload['end_time']);
        $customerId = (int) $payload['customer_id'];
        $serviceIds = $this->normalizeServiceIdsFromPayload($payload);
        $serviceId = (int) $serviceIds[0];
        $masseuseId = isset($payload['masseuse_id']) ? (int) $payload['masseuse_id'] : null;
        $bedId = isset($payload['bed_id']) ? (int) $payload['bed_id'] : null;
        $status = (string) $payload['status'];
        $cancelReason = isset($payload['cancel_reason']) ? (string) $payload['cancel_reason'] : null;

        $existingOrderId = null;
        if ($bookingId !== null) {
            $existing = DB::table('bookings')
                ->where('id', $bookingId)
                ->where('branch_id', $branchId)
                ->first();

            if ($existing === null) {
                throw ValidationException::withMessages([
                    'booking' => ['ไม่พบคิวที่ต้องการแก้ไขในสาขานี้'],
                ]);
            }

            // เก็บ order_id ของ booking เดิมไว้สำหรับอัปเดต order เมื่อแก้ไข
            if ($this->hasColumn('bookings', 'order_id')) {
                $existingOrderId = isset($existing->order_id) ? (int) $existing->order_id : null;
                if ($existingOrderId <= 0) {
                    $existingOrderId = null;
                }
            }
        }

        foreach ($serviceIds as $selectedServiceId) {
            $this->ensureServiceExists((int) $selectedServiceId, $branchId);
        }
        $this->ensureCustomerExists($customerId, $branchId);
        $this->ensureMasseuseInBranch($masseuseId, $branchId);
        $this->ensureBedInBranch($bedId, $branchId);
        $this->ensureWithinOperatingHours($branchId, $startAt, $endAt);
        $this->ensureNoTimeConflict($branchId, $startAt, $endAt, $masseuseId, $bedId, $bookingId);

        $data = [
            'branch_id' => $branchId,
            'customer_id' => $customerId,
            'service_id' => $serviceId,
            'masseuse_id' => $masseuseId,
            'bed_id' => $bedId,
            'start_time' => $startAt->format('Y-m-d H:i:s'),
            'end_time' => $endAt->format('Y-m-d H:i:s'),
            'status' => $status,
            'cancel_reason' => $status === 'cancelled' ? $cancelReason : null,
        ];

        DB::transaction(function () use (&$bookingId, $data, $serviceIds, $existingOrderId, $branchId, $masseuseId): void {
            if ($bookingId === null) {
                $bookingId = (int) DB::table('bookings')->insertGetId($data);
            } else {
                DB::table('bookings')
                    ->where('id', $bookingId)
                    ->update($data);
            }

            $this->syncBookingServices($bookingId, $serviceIds);

            // อัปเดต order ที่เชื่อมโยงเมื่อแก้ไข booking ที่ชำระเงินแล้ว
            if ($existingOrderId !== null) {
                $this->recalculateLinkedOrder($existingOrderId, $branchId, $serviceIds, $masseuseId);
            }
        });

        return $this->findBookingById($bookingId, $branchId);
    }

    public function cancelBooking(int $bookingId, User $user, ?int $requestedBranchId = null, ?string $cancelReason = null): array
    {
        $branchId = $this->resolveAuthorizedBranchId($user, $requestedBranchId);
        $booking = DB::table('bookings')
            ->where('id', $bookingId)
            ->where('branch_id', $branchId)
            ->first();

        if ($booking === null) {
            throw ValidationException::withMessages([
                'booking' => ['ไม่พบคิวที่ต้องการยกเลิกในสาขานี้'],
            ]);
        }

        DB::table('bookings')
            ->where('id', $bookingId)
            ->update([
                'status' => 'cancelled',
                'cancel_reason' => $cancelReason !== null && $cancelReason !== '' ? $cancelReason : 'ยกเลิกจากหน้าจองคิว',
            ]);

        return $this->findBookingById($bookingId, $branchId);
    }

    public function deleteBooking(int $bookingId, User $user, ?int $requestedBranchId = null): void
    {
        $branchId = $this->resolveAuthorizedBranchId($user, $requestedBranchId);
        $booking = DB::table('bookings')
            ->where('id', $bookingId)
            ->where('branch_id', $branchId)
            ->first();

        if ($booking === null) {
            throw ValidationException::withMessages([
                'booking' => ['ไม่พบคิวที่ต้องการลบในสาขานี้'],
            ]);
        }

        if ((string) $booking->status === 'completed' && !in_array($user->role, ['shop_owner', 'branch_manager'])) {
            throw ValidationException::withMessages([
                'booking' => ['คิวที่ชำระเงินแล้วไม่สามารถยกเลิกหรือลบได้'],
            ]);
        }

        DB::transaction(function () use ($bookingId, $booking): void {
            // ลบ order ที่เชื่อมโยงกับ booking นี้ เพื่อให้ยอดใน Dashboard อัปเดตตาม
            $linkedOrderId = $this->hasColumn('bookings', 'order_id')
                ? (isset($booking->order_id) ? (int) $booking->order_id : null)
                : null;

            if ($linkedOrderId !== null && $linkedOrderId > 0) {
                $this->deleteLinkedOrder($linkedOrderId);
            }

            if ($this->tableExists('booking_services')) {
                DB::table('booking_services')
                    ->where('booking_id', $bookingId)
                    ->delete();
            }

            DB::table('bookings')
                ->where('id', $bookingId)
                ->delete();
        });
    }

    public function getBookingContextForCheckout(User $user, int $bookingId, ?int $requestedBranchId = null): array
    {
        $branchId = $this->resolveAuthorizedBranchId($user, $requestedBranchId);
        $booking = $this->enrichBookingRows($this->buildBookingQuery($branchId)
            ->where('b.id', $bookingId)
            ->get())
            ->first();

        if ($booking === null) {
            throw ValidationException::withMessages([
                'booking' => ['ไม่พบคิวที่เลือกสำหรับการชำระเงิน'],
            ]);
        }

        return $this->mapBookingToCheckoutContext($this->mapBookingRow($booking));
    }

    private function getStaff(int $branchId, string $date, bool $onlyWorking = false): array
    {
        return DB::table('masseuses as m')
            ->where('m.branch_id', $branchId)
            ->selectRaw(
                "m.id, " .
                "COALESCE(NULLIF(m.full_name, ''), NULLIF(m.nickname, ''), CONCAT('Masseuse #', m.id)) as name, " .
                "m.status, m.profile_image"
            )
            ->orderBy('m.id')
            ->get()
            ->map(function ($row) use ($branchId, $date): array {
                $staffId = (string) $row->id;
                $statusValue = (string) ($row->status ?? '');
                $isWorkingToday = $statusValue !== 'day_off'
                    && $this->staffAttendanceService->isWorking($branchId, $date, $staffId);

                return [
                    'id' => $staffId,
                    'name' => (string) $row->name,
                    'statusValue' => $statusValue,
                    'profileImage' => $row->profile_image !== null ? (string) $row->profile_image : '',
                    'isWorkingToday' => $isWorkingToday,
                    'attendanceLabel' => $isWorkingToday ? 'มาทำงานวันนี้' : 'ไม่มาทำงานวันนี้',
                ];
            })
            ->filter(static function (array $staff) use ($onlyWorking): bool {
                if (!$onlyWorking) {
                    return true;
                }

                return (bool) ($staff['isWorkingToday'] ?? false);
            })
            ->values()
            ->all();
    }

    private function buildStaffPageStaff(int $branchId, string $date): array
    {
        $staffRoster = $this->getStaff($branchId, $date, false);
        $queueByStaff = $this->getQueueByStaff($branchId, $date);
        $commissionConfigs = $this->getCommissionConfigsByService($branchId);

        return array_map(function (array $staff) use ($queueByStaff, $commissionConfigs, $date): array {
            $staffId = (string) ($staff['id'] ?? '');
            $queue = $queueByStaff[$staffId]['items'] ?? [];
            $bookedValue = (float) ($queueByStaff[$staffId]['bookedValue'] ?? 0);
            $bookedMinutes = (int) ($queueByStaff[$staffId]['bookedMinutes'] ?? 0);

            return [
                'id' => $staffId,
                'display_id' => 'MS' . str_pad($staffId, 3, '0', STR_PAD_LEFT),
                'name' => (string) ($staff['name'] ?? ''),
                'statusValue' => (string) ($staff['statusValue'] ?? ''),
                'status' => $this->resolveStaffPageStatus($staff, $queue, $date),
                'isWorkingToday' => (bool) ($staff['isWorkingToday'] ?? true),
                'income' => $bookedValue,
                'commission' => $this->estimateQueueCommission($queue, $commissionConfigs),
                'avatar' => $this->resolveStaffAvatar((string) ($staff['profileImage'] ?? ''), $staffId),
                'shift' => '-',
                'break' => '-',
                'queueLoad' => (int) min(100, round(($bookedMinutes / 600) * 100)),
                'queue' => $queue,
            ];
        }, $staffRoster);
    }

    private function getQueueByStaff(int $branchId, string $date): array
    {
        $rows = $this->enrichBookingRows($this->buildBookingQuery($branchId)
            ->whereDate('b.start_time', $date)
            ->whereNotNull('b.masseuse_id')
            ->where('b.status', '!=', 'cancelled')
            ->orderBy('b.start_time')
            ->get());

        $queueByStaff = [];

        foreach ($rows as $row) {
            $staffId = (string) $row->masseuse_id;
            if (!isset($queueByStaff[$staffId])) {
                $queueByStaff[$staffId] = [
                    'items' => [],
                    'bookedValue' => 0.0,
                    'bookedMinutes' => 0,
                ];
            }

            $startAt = Carbon::parse((string) $row->start_time);
            $endAt = Carbon::parse((string) $row->end_time);
            $services = $this->extractServicesFromRow($row);
            $servicePrice = array_reduce($services, static function (float $sum, array $service): float {
                return $sum + (float) ($service['price'] ?? 0);
            }, 0.0);

            $queueByStaff[$staffId]['items'][] = [
                'booking_id' => (string) $row->id,
                'customer' => $row->customer_name !== null ? (string) $row->customer_name : '',
                'service' => $this->formatServiceSummary($services),
                'service_id' => !empty($services) ? (int) $services[0]['id'] : null,
                'service_ids' => array_map(static function (array $service): int {
                    return (int) ($service['id'] ?? 0);
                }, $services),
                'services' => $services,
                'service_price' => $servicePrice,
                'start' => $startAt->format('H:i'),
                'end' => $endAt->format('H:i'),
                'status' => (string) $row->status,
            ];
            $queueByStaff[$staffId]['bookedValue'] += $servicePrice;
            $queueByStaff[$staffId]['bookedMinutes'] += max(0, $startAt->diffInMinutes($endAt, false));
        }

        return $queueByStaff;
    }

    private function getCommissionConfigsByService(int $branchId): array
    {
        if (!$this->tableExists('commission_configs')) {
            return [];
        }

        $query = DB::table('commission_configs')
            ->where('item_type', 'service');

        if (Schema::hasColumn('commission_configs', 'branch_id')) {
            $query->where(function ($inner) use ($branchId): void {
                $inner->whereNull('branch_id')
                    ->orWhere('branch_id', $branchId);
            });
        }

        return $query
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

    private function estimateQueueCommission(array $queue, array $commissionConfigs): float
    {
        $total = 0.0;

        foreach ($queue as $item) {
            $services = isset($item['services']) && is_array($item['services'])
                ? $item['services']
                : [[
                    'id' => isset($item['service_id']) ? (int) $item['service_id'] : 0,
                    'price' => isset($item['service_price']) ? (float) $item['service_price'] : 0.0,
                ]];

            foreach ($services as $service) {
                $serviceId = isset($service['id']) ? (int) $service['id'] : 0;
                if ($serviceId <= 0 || !isset($commissionConfigs[$serviceId])) {
                    continue;
                }

                $config = $commissionConfigs[$serviceId];
                $servicePrice = isset($service['price']) ? (float) $service['price'] : 0.0;

                if (($config['type'] ?? '') === 'fixed') {
                    $total += (float) ($config['value'] ?? 0);
                    continue;
                }

                $baseAmount = max(0.0, $servicePrice - (float) ($config['deduct_cost'] ?? 0));
                $total += $baseAmount * ((float) ($config['value'] ?? 0) / 100);
            }
        }

        return $total;
    }

    private function resolveStaffPageStatus(array $staff, array $queue, string $date): string
    {
        if (($staff['statusValue'] ?? '') === 'day_off') {
            return 'หยุดงาน';
        }

        $isWorkingToday = (bool) ($staff['isWorkingToday'] ?? true);
        if (!$isWorkingToday) {
            return 'ไม่มาทำงานวันนี้';
        }

        foreach ($queue as $item) {
            if (($item['status'] ?? '') === 'in_service') {
                return self::STATUSES['in_service'];
            }
        }

        if (!empty($queue)) {
            return $date === Carbon::today()->toDateString() ? 'มีคิววันนี้' : 'มีคิวในวันที่เลือก';
        }

        return 'มาทำงานวันนี้';
    }

    private function resolveStaffAvatar(string $profileImage, string $staffId): string
    {
        if ($profileImage === '') {
            return 'https://i.pravatar.cc/150?u=' . rawurlencode($staffId);
        }

        if (preg_match('#^https?://#i', $profileImage) === 1) {
            return $profileImage;
        }

        return '/' . ltrim($profileImage, '/');
    }

    private function findStaffById(int $branchId, int $staffId): ?object
    {
        return DB::table('masseuses')
            ->where('branch_id', $branchId)
            ->where('id', $staffId)
            ->first(['id', 'status']);
    }

    private function getServiceItems(int $branchId): array
    {
        $query = DB::table('services')
            ->where('is_active', 1)
            ->orderBy('id')
            ;

        if ($this->tableExists('services') && Schema::hasColumn('services', 'branch_id')) {
            $query->where('branch_id', $branchId);
        }

        return $query
            ->get(['id', 'name', 'duration_minutes', 'price'])
            ->map(static function ($row): array {
                return [
                    'id' => (string) $row->id,
                    'name' => (string) $row->name,
                    'duration' => (int) $row->duration_minutes,
                    'price' => (float) $row->price,
                ];
            })
            ->all();
    }

    private function getCustomers(int $branchId): array
    {
        $query = DB::table('customers')
            ->orderBy('name');

        if ($this->tableExists('customers') && Schema::hasColumn('customers', 'branch_id')) {
            $query->where('branch_id', $branchId);
        }

        return $query
            ->get(['id', 'name', 'phone', 'line_id'])
            ->map(static function ($row): array {
                return [
                    'id' => (string) $row->id,
                    'name' => (string) $row->name,
                    'phone' => (string) $row->phone,
                    'line_id' => $row->line_id !== null ? (string) $row->line_id : '',
                ];
            })
            ->all();
    }

    private function getBeds(int $branchId): array
    {
        return DB::table('beds as b')
            ->join('rooms as r', 'r.id', '=', 'b.room_id')
            ->where('r.branch_id', $branchId)
            ->orderBy('r.id')
            ->orderBy('b.id')
            ->get(['b.id', 'b.name as bed_name', 'r.name as room_name'])
            ->map(static function ($row): array {
                return [
                    'id' => (string) $row->id,
                    'name' => (string) $row->room_name . ' / ' . (string) $row->bed_name,
                ];
            })
            ->all();
    }

    private function getBookingsByDate(int $branchId, string $date): array
    {
        return $this->enrichBookingRows($this->buildBookingQuery($branchId)
            ->whereDate('b.start_time', $date)
            ->orderBy('b.start_time')
            ->get())
            ->map(function ($row): array {
                return $this->mapBookingRow($row);
            })
            ->all();
    }

    private function findBookingById(int $bookingId, int $branchId): array
    {
        $booking = $this->enrichBookingRows($this->buildBookingQuery($branchId)
            ->where('b.id', $bookingId)
            ->get())
            ->first();

        if ($booking === null) {
            throw ValidationException::withMessages([
                'booking' => ['ไม่พบข้อมูลคิวที่บันทึก'],
            ]);
        }

        return $this->mapBookingRow($booking);
    }

    private function buildBookingQuery(int $branchId)
    {
        return DB::table('bookings as b')
            ->leftJoin('customers as c', 'c.id', '=', 'b.customer_id')
            ->leftJoin('services as s', 's.id', '=', 'b.service_id')
            ->leftJoin('masseuses as m', 'm.id', '=', 'b.masseuse_id')
            ->leftJoin('beds as bed', 'bed.id', '=', 'b.bed_id')
            ->leftJoin('rooms as room', 'room.id', '=', 'bed.room_id')
            ->where('b.branch_id', $branchId)
            ->selectRaw(
                "b.id, b.customer_id, b.service_id, b.masseuse_id, b.bed_id, " .
                "b.start_time, b.end_time, b.status, b.cancel_reason, " .
                ($this->hasColumn('bookings', 'order_id') ? "b.order_id, " : "") .
                "c.name as customer_name, s.name as service_name, s.price as service_price, " .
                "COALESCE(NULLIF(m.full_name, ''), NULLIF(m.nickname, ''), '') as staff_name, " .
                "bed.name as bed_name, room.name as room_name"
            );
    }

    private function enrichBookingRows($rows)
    {
        $bookingIds = $rows->pluck('id')
            ->map(static function ($id): int {
                return (int) $id;
            })
            ->filter(static function (int $id): bool {
                return $id > 0;
            })
            ->values()
            ->all();

        if (empty($bookingIds)) {
            return $rows;
        }

        $fallbackRows = [];
        foreach ($rows as $row) {
            $fallbackRows[(int) $row->id] = $row;
        }

        $serviceMap = $this->loadBookingServicesMap($bookingIds, $fallbackRows);

        return $rows->map(function ($row) use ($serviceMap) {
            $bookingId = (int) $row->id;
            $services = $serviceMap[$bookingId] ?? [];
            $row->service_details = $services;
            $row->service_ids = array_map(static function (array $service): string {
                return (string) ($service['id'] ?? '');
            }, $services);
            $row->service_names = array_map(static function (array $service): string {
                return (string) ($service['name'] ?? '');
            }, $services);
            $row->service_total_price = array_reduce($services, static function (float $sum, array $service): float {
                return $sum + (float) ($service['price'] ?? 0);
            }, 0.0);

            if (!empty($services)) {
                $row->service_id = (int) $services[0]['id'];
                $row->service_name = (string) $services[0]['name'];
                $row->service_price = (float) $services[0]['price'];
            }

            return $row;
        });
    }

    private function loadBookingServicesMap(array $bookingIds, array $fallbackRows = []): array
    {
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
                    'price' => (float) ($row->service_price ?? 0),
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
                'price' => (float) ($fallbackRow->service_price ?? 0),
            ]];
        }

        return $servicesByBooking;
    }

    private function extractServicesFromRow(object $row): array
    {
        if (isset($row->service_details) && is_array($row->service_details)) {
            return $row->service_details;
        }

        if ($row->service_id === null) {
            return [];
        }

        return [[
            'id' => (int) $row->service_id,
            'name' => (string) ($row->service_name ?? ''),
            'price' => (float) ($row->service_price ?? 0),
        ]];
    }

    private function formatServiceSummary(array $services): string
    {
        if (empty($services)) {
            return '-';
        }

        $firstName = (string) ($services[0]['name'] ?? '-');
        $extraCount = count($services) - 1;

        if ($extraCount <= 0) {
            return $firstName;
        }

        return $firstName . ' +' . $extraCount;
    }

    private function mapBookingRow(object $row): array
    {
        $start = Carbon::parse((string) $row->start_time)->format('H:i');
        $queueDate = Carbon::parse((string) $row->start_time)->toDateString();
        $end = Carbon::parse((string) $row->end_time)->format('H:i');
        $services = $this->extractServicesFromRow($row);
        $serviceIds = array_values(array_filter(array_map(static function (array $service): string {
            return isset($service['id']) ? (string) $service['id'] : '';
        }, $services)));
        $serviceId = $serviceIds[0] ?? '';

        return [
            'id' => (string) $row->id,
            'queueDate' => $queueDate,
            'customerId' => $row->customer_id !== null ? (string) $row->customer_id : '',
            'customerName' => $row->customer_name !== null ? (string) $row->customer_name : '',
            'serviceId' => $serviceId,
            'serviceIds' => $serviceIds,
            'serviceNames' => array_values(array_filter(array_map(static function (array $service): string {
                return (string) ($service['name'] ?? '');
            }, $services))),
            'serviceSummary' => $this->formatServiceSummary($services),
            'staffId' => $row->masseuse_id !== null ? (string) $row->masseuse_id : '',
            'staffName' => $row->staff_name !== null ? (string) $row->staff_name : '',
            'bedId' => $row->bed_id !== null ? (string) $row->bed_id : '',
            'bedName' => $this->formatBedName($row->room_name, $row->bed_name),
            'start' => $start,
            'end' => $end,
            'status' => (string) $row->status,
            'cancelReason' => $row->cancel_reason !== null ? (string) $row->cancel_reason : null,
            'paid' => (string) $row->status === 'completed',
            'orderId' => isset($row->order_id) ? (int) $row->order_id : null,
        ];
    }

    private function mapBookingToCheckoutContext(array $booking): array
    {
        $serviceIds = isset($booking['serviceIds']) && is_array($booking['serviceIds'])
            ? array_values(array_filter(array_map(static function ($serviceId): int {
                return (int) $serviceId;
            }, $booking['serviceIds'])))
            : [];

        return [
            'bookingId' => isset($booking['id']) ? (int) $booking['id'] : null,
            'queueDate' => (string) ($booking['queueDate'] ?? Carbon::today()->toDateString()),
            'startTime' => (string) ($booking['start'] ?? '10:00'),
            'endTime' => (string) ($booking['end'] ?? '11:00'),
            'customerId' => isset($booking['customerId']) && $booking['customerId'] !== '' ? (int) $booking['customerId'] : null,
            'staffId' => isset($booking['staffId']) && $booking['staffId'] !== '' ? (int) $booking['staffId'] : null,
            'serviceId' => $serviceIds[0] ?? (isset($booking['serviceId']) && $booking['serviceId'] !== '' ? (int) $booking['serviceId'] : null),
            'serviceIds' => $serviceIds,
            'bedId' => isset($booking['bedId']) && $booking['bedId'] !== '' ? (int) $booking['bedId'] : null,
            'isPaid' => (bool) ($booking['paid'] ?? false),
        ];
    }

    private function formatBedName($roomName, $bedName): string
    {
        if ($bedName === null || $bedName === '') {
            return '';
        }

        if ($roomName === null || $roomName === '') {
            return (string) $bedName;
        }

        return (string) $roomName . ' / ' . (string) $bedName;
    }

    private function ensureNoTimeConflict(
        int $branchId,
        Carbon $startAt,
        Carbon $endAt,
        ?int $masseuseId,
        ?int $bedId,
        ?int $excludeBookingId = null
    ): void {
        $baseQuery = DB::table('bookings as b')
            ->where('b.branch_id', $branchId)
            ->where('b.status', '!=', 'cancelled')
            ->where('b.start_time', '<', $endAt->format('Y-m-d H:i:s'))
            ->where('b.end_time', '>', $startAt->format('Y-m-d H:i:s'));
        if ($excludeBookingId !== null) {
            $baseQuery->where('b.id', '!=', $excludeBookingId);
        }
        $messages = [];
        if ($masseuseId !== null) {
            $staffConflict = (clone $baseQuery)
                ->where('b.masseuse_id', $masseuseId)
                ->exists();
            if ($staffConflict) {
                $messages[] = 'หมอนวดคนนี้มีคิวซ้อนในช่วงเวลาเดียวกัน';
            }
        }
        if ($bedId !== null) {
            $bedConflict = (clone $baseQuery)
                ->where('b.bed_id', $bedId)
                ->exists();
            if ($bedConflict) {
                $messages[] = 'ห้อง/เตียงนี้มีคิวซ้อนในช่วงเวลาเดียวกัน';
            }
        }
        if (!empty($messages)) {
            throw ValidationException::withMessages([
                'start_time' => [implode(' และ ', $messages)],
            ]);
        }
    }
    private function ensureWithinOperatingHours(int $branchId, Carbon $startAt, Carbon $endAt): void
    {
        $branchHours = $this->getBranchOperatingHours($branchId);
        $openAt = Carbon::createFromFormat('Y-m-d H:i', $startAt->toDateString() . ' ' . $branchHours['open_time']);
        $closeAt = Carbon::createFromFormat('Y-m-d H:i', $startAt->toDateString() . ' ' . $branchHours['close_time']);

        if ($startAt->lt($openAt) || $endAt->gt($closeAt)) {
            throw ValidationException::withMessages([
                'start_time' => ['เวลาจองต้องอยู่ในช่วงเวลาเปิดร้าน ' . $branchHours['open_time'] . ' - ' . $branchHours['close_time']],
            ]);
        }
    }

    private function normalizeServiceIdsFromPayload(array $payload): array
    {
        $rawIds = [];
        if (isset($payload['service_ids']) && is_array($payload['service_ids'])) {
            $rawIds = $payload['service_ids'];
        } elseif (isset($payload['service_id'])) {
            $rawIds = [$payload['service_id']];
        }

        $serviceIds = [];
        foreach ($rawIds as $rawId) {
            $serviceId = (int) $rawId;
            if ($serviceId <= 0) {
                continue;
            }

            if (!in_array($serviceId, $serviceIds, true)) {
                $serviceIds[] = $serviceId;
            }
        }

        if (empty($serviceIds)) {
            throw ValidationException::withMessages([
                'service_id' => ['กรุณาเลือกอย่างน้อย 1 บริการ'],
            ]);
        }

        if (count($serviceIds) > self::MAX_SERVICES_PER_BOOKING) {
            throw ValidationException::withMessages([
                'service_ids' => ['เลือกบริการได้สูงสุด ' . self::MAX_SERVICES_PER_BOOKING . ' รายการ'],
            ]);
        }

        return $serviceIds;
    }

    private function syncBookingServices(int $bookingId, array $serviceIds): void
    {
        if (!$this->tableExists('booking_services')) {
            return;
        }

        DB::table('booking_services')
            ->where('booking_id', $bookingId)
            ->delete();

        $now = now();
        $rows = [];
        foreach (array_values($serviceIds) as $index => $serviceId) {
            $rows[] = [
                'booking_id' => $bookingId,
                'service_id' => (int) $serviceId,
                'sort_order' => $index + 1,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (!empty($rows)) {
            DB::table('booking_services')->insert($rows);
        }
    }

    private function ensureCustomerExists(int $customerId, int $branchId): void
    {
        $query = DB::table('customers')->where('id', $customerId);

        if ($this->tableExists('customers') && Schema::hasColumn('customers', 'branch_id')) {
            $query->where('branch_id', $branchId);
        }

        if (!$query->exists()) {
            throw ValidationException::withMessages([
                'customer_id' => ['ไม่พบข้อมูลลูกค้า'],
            ]);
        }
    }

    private function ensureServiceExists(int $serviceId, int $branchId): void
    {
        $query = DB::table('services')
            ->where('id', $serviceId)
            ->where('is_active', 1);

        if ($this->tableExists('services') && Schema::hasColumn('services', 'branch_id')) {
            $query->where('branch_id', $branchId);
        }

        $exists = $query->exists();

        if (!$exists) {
            throw ValidationException::withMessages([
                'service_id' => ['ไม่พบข้อมูลบริการ หรือบริการถูกปิดใช้งาน'],
            ]);
        }
    }

    private function tableExists(string $table): bool
    {
        if (!array_key_exists($table, $this->tableExistsCache)) {
            $this->tableExistsCache[$table] = Schema::hasTable($table);
        }

        return $this->tableExistsCache[$table];
    }

    private function hasColumn(string $table, string $column): bool
    {
        $cacheKey = $table . '.' . $column;
        if (!array_key_exists($cacheKey, $this->columnExistsCache)) {
            $this->columnExistsCache[$cacheKey] = $this->tableExists($table) && Schema::hasColumn($table, $column);
        }

        return $this->columnExistsCache[$cacheKey];
    }

    private function ensureMasseuseInBranch(?int $masseuseId, int $branchId): void
    {
        if ($masseuseId === null) {
            return;
        }
        $exists = DB::table('masseuses')
            ->where('id', $masseuseId)
            ->where('branch_id', $branchId)
            ->exists();
        if (!$exists) {
            throw ValidationException::withMessages([
                'masseuse_id' => ['หมอนวดไม่อยู่ในสาขาที่เลือก'],
            ]);
        }
    }
    private function ensureBedInBranch(?int $bedId, int $branchId): void
    {
        if ($bedId === null) {
            return;
        }

        $exists = DB::table('beds as b')
            ->join('rooms as r', 'r.id', '=', 'b.room_id')
            ->where('b.id', $bedId)
            ->where('r.branch_id', $branchId)
            ->exists();

        if (!$exists) {
            throw ValidationException::withMessages([
                'bed_id' => ['ห้อง/เตียงไม่อยู่ในสาขาที่เลือก'],
            ]);
        }
    }

    private function resolveAuthorizedBranchId(User $user, ?int $requestedBranchId): int
    {
        return $this->branchContext->resolveAuthorizedBranchId($user, $requestedBranchId);
    }

    private function branchExists(int $branchId): bool
    {
        return DB::table('branches')
            ->where('id', $branchId)
            ->exists();
    }

    private function getDefaultBranchId(): int
    {
        $activeBranch = DB::table('branches')
            ->where('is_active', 1)
            ->orderBy('id')
            ->value('id');

        if ($activeBranch !== null) {
            return (int) $activeBranch;
        }

        $firstBranch = DB::table('branches')
            ->orderBy('id')
            ->value('id');

        if ($firstBranch !== null) {
            return (int) $firstBranch;
        }

        return 1;
    }

    private function getBranchOperatingHours(int $branchId): array
    {
        $columns = ['id'];
        if ($this->hasColumn('branches', 'open_time')) {
            $columns[] = 'open_time';
        }
        if ($this->hasColumn('branches', 'close_time')) {
            $columns[] = 'close_time';
        }

        $row = DB::table('branches')
            ->where('id', $branchId)
            ->first($columns);

        return [
            'open_time' => $this->normalizeBranchTimeForDisplay($row->open_time ?? null, '10:00'),
            'close_time' => $this->normalizeBranchTimeForDisplay($row->close_time ?? null, '20:00'),
        ];
    }

    private function normalizeBranchTimeForDisplay($value, string $fallback): string
    {
        $normalized = trim((string) $value);
        if ($normalized === '') {
            return $fallback;
        }

        return substr($normalized, 0, 5);
    }

    private function normalizeDate(string $date): string
    {
        try {
            return Carbon::createFromFormat('Y-m-d', $date)->toDateString();
        } catch (\Throwable $e) {
            return Carbon::today()->toDateString();
        }
    }

    /**
     * อัปเดต order ที่เชื่อมโยงกับ booking เมื่อแก้ไขบริการ/หมอนวด
     * → ยอดใน Dashboard จะอัปเดตตาม
     */
    private function recalculateLinkedOrder(int $orderId, int $branchId, array $serviceIds, ?int $masseuseId): void
    {
        $order = DB::table('orders')->where('id', $orderId)->first();
        if ($order === null) {
            return;
        }

        // ลบ commissions เก่าของ service items
        $oldServiceItemIds = DB::table('order_items')
            ->where('order_id', $orderId)
            ->where('item_type', 'service')
            ->pluck('id');

        if ($this->tableExists('commissions') && $oldServiceItemIds->isNotEmpty()) {
            DB::table('commissions')
                ->whereIn('order_item_id', $oldServiceItemIds->all())
                ->delete();
        }

        // ลบ order_items ประเภท service เก่า
        DB::table('order_items')
            ->where('order_id', $orderId)
            ->where('item_type', 'service')
            ->delete();

        // สร้าง order_items ใหม่ตามบริการที่แก้ไข
        $newServiceTotal = 0.0;
        foreach ($serviceIds as $svcId) {
            $service = DB::table('services')
                ->where('id', (int) $svcId)
                ->first(['id', 'price']);

            if ($service === null) {
                continue;
            }

            $unitPrice = (float) $service->price;
            DB::table('order_items')->insert([
                'branch_id' => $branchId,
                'order_id' => $orderId,
                'item_type' => 'service',
                'item_id' => (int) $service->id,
                'qty' => 1,
                'unit_price' => $unitPrice,
                'masseuse_id' => $masseuseId,
            ]);
            $newServiceTotal += $unitPrice;
        }

        // ยอดรวม non-service items ที่ยังอยู่ (product, package)
        $nonServiceTotal = (float) DB::table('order_items')
            ->where('order_id', $orderId)
            ->where('item_type', '!=', 'service')
            ->sum(DB::raw('qty * unit_price'));

        $subtotal = $newServiceTotal + $nonServiceTotal;
        $discount = (float) $order->discount_amount;
        $grandTotal = max(0.0, $subtotal - $discount);

        DB::table('orders')
            ->where('id', $orderId)
            ->update([
                'total_amount' => $subtotal,
                'grand_total' => $grandTotal,
            ]);

        // คำนวณคอมมิชชันใหม่
        $this->commissionService->processOrderCommissions($orderId);
    }

    /**
     * ลบ order ที่เชื่อมโยงกับ booking (พร้อม order_items + commissions)
     * → ยอดใน Dashboard จะลดลงตาม
     */
    private function deleteLinkedOrder(int $orderId): void
    {
        // ลบ commissions
        if ($this->tableExists('commissions')) {
            $itemIds = DB::table('order_items')
                ->where('order_id', $orderId)
                ->pluck('id');

            if ($itemIds->isNotEmpty()) {
                DB::table('commissions')
                    ->whereIn('order_item_id', $itemIds->all())
                    ->delete();
            }
        }

        // ลบ order_items
        DB::table('order_items')
            ->where('order_id', $orderId)
            ->delete();

        // ลบ order
        DB::table('orders')
            ->where('id', $orderId)
            ->delete();
    }

    /**
     * Public wrapper สำหรับ PosService เรียกใช้เมื่อชำระเงินใหม่ (re-checkout)
     */
    public function deleteLinkedOrderPublic(int $orderId): void
    {
        $this->deleteLinkedOrder($orderId);
    }
}
