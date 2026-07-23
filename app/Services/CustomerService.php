<?php

namespace App\Services;

use App\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class CustomerService
{
    private const PRESSURE_LEVELS = [
        ['value' => 'light', 'label' => 'เบา (Light)'],
        ['value' => 'medium', 'label' => 'ปานกลาง (Medium)'],
        ['value' => 'firm', 'label' => 'หนัก (Firm)'],
    ];

    private BranchContextService $branchContext;
    private array $tableExistsCache = [];
    private array $columnExistsCache = [];

    public function __construct(BranchContextService $branchContext)
    {
        $this->branchContext = $branchContext;
    }

    public function getPageData(User $user, ?int $selectedCustomerId, string $search): array
    {
        $branchId = $this->branchContext->resolveAuthorizedBranchId($user);
        $normalizedSearch = trim($search);
        $customers = $this->getCustomers($branchId, $normalizedSearch);
        $selectedCustomer = null;
        $history = [];

        if ($selectedCustomerId !== null && $selectedCustomerId > 0) {
            $selectedCustomer = $this->findCustomerById($branchId, $selectedCustomerId);
            if ($selectedCustomer !== null) {
                $history = $this->getCustomerOrderHistory($branchId, (int) $selectedCustomer['id']);
            }
        }

        return [
            'search' => $normalizedSearch,
            'customers' => $customers,
            'selectedCustomer' => $selectedCustomer,
            'history' => $history,
            'summary' => $this->buildSummary($branchId, $customers),
            'pressureLevels' => self::PRESSURE_LEVELS,
            'membershipTiers' => $this->getMembershipTiers(),
        ];
    }

    public function getCustomerById(User $user, int $customerId): ?array
    {
        return $this->findCustomerById($this->branchContext->resolveAuthorizedBranchId($user), $customerId);
    }

    public function getHistoryByCustomerId(User $user, int $customerId): array
    {
        return $this->getCustomerOrderHistory($this->branchContext->resolveAuthorizedBranchId($user), $customerId);
    }

    public function createCustomer(User $user, array $payload): array
    {
        $this->assertCustomersTableExists();
        $branchId = $this->branchContext->resolveAuthorizedBranchId($user);
        $data = $this->sanitizeCustomerData($payload);

        if (empty($data)) {
            throw ValidationException::withMessages([
                'customer' => ['ไม่สามารถบันทึกข้อมูลลูกค้าได้ เพราะโครงสร้างตารางยังไม่พร้อม'],
            ]);
        }

        if ($this->hasColumn('customers', 'branch_id')) {
            $data['branch_id'] = $branchId;
        }

        if ($this->hasColumn('customers', 'created_at')) {
            $data['created_at'] = now();
        }

        if ($this->hasColumn('customers', 'updated_at')) {
            $data['updated_at'] = now();
        }

        $customerId = (int) DB::table('customers')->insertGetId($data);
        $customer = $this->findCustomerById($branchId, $customerId);

        if ($customer === null) {
            throw ValidationException::withMessages([
                'customer' => ['ไม่พบข้อมูลลูกค้าที่เพิ่งบันทึก'],
            ]);
        }

        return $customer;
    }

    public function updateCustomer(User $user, int $customerId, array $payload): array
    {
        $this->assertCustomersTableExists();
        $branchId = $this->branchContext->resolveAuthorizedBranchId($user);
        $existing = $this->findCustomerById($branchId, $customerId);

        if ($existing === null) {
            throw ValidationException::withMessages([
                'customer' => ['ไม่พบข้อมูลลูกค้าที่ต้องการแก้ไข'],
            ]);
        }

        $data = $this->sanitizeCustomerData($payload);
        if ($this->hasColumn('customers', 'updated_at')) {
            $data['updated_at'] = now();
        }

        if (empty($data)) {
            return $existing;
        }

        $query = DB::table('customers')->where('id', $customerId);
        if ($this->hasColumn('customers', 'branch_id')) {
            $query->where('branch_id', $branchId);
        }

        $query->update($data);

        $updated = $this->findCustomerById($branchId, $customerId);
        return $updated !== null ? $updated : $existing;
    }

    public function deleteCustomer(User $user, int $customerId): void
    {
        $this->assertCustomersTableExists();
        $branchId = $this->branchContext->resolveAuthorizedBranchId($user);

        if ($this->findCustomerRecord($branchId, $customerId) === null) {
            throw ValidationException::withMessages([
                'customer' => ['ไม่พบข้อมูลลูกค้าที่ต้องการลบ'],
            ]);
        }

        if ($this->tableExists('orders')) {
            $ordersQuery = DB::table('orders')->where('customer_id', $customerId);
            if ($this->hasColumn('orders', 'branch_id')) {
                $ordersQuery->where('branch_id', $branchId);
            }

            if ($ordersQuery->exists()) {
                throw ValidationException::withMessages([
                    'customer' => ['ลูกค้ารายนี้มีประวัติการชำระเงินแล้ว จึงไม่สามารถลบได้'],
                ]);
            }
        }

        if ($this->tableExists('bookings')) {
            $bookingsQuery = DB::table('bookings')->where('customer_id', $customerId);
            if ($this->hasColumn('bookings', 'branch_id')) {
                $bookingsQuery->where('branch_id', $branchId);
            }

            if ($bookingsQuery->exists()) {
                throw ValidationException::withMessages([
                    'customer' => ['ลูกค้ารายนี้มีประวัติคิวจองแล้ว จึงไม่สามารถลบได้'],
                ]);
            }
        }

        $query = DB::table('customers')->where('id', $customerId);
        if ($this->hasColumn('customers', 'branch_id')) {
            $query->where('branch_id', $branchId);
        }

        $query->delete();
    }

    private function getCustomers(int $branchId, string $search): array
    {
        if (!$this->tableExists('customers')) {
            return [];
        }

        $query = $this->buildCustomersBaseQuery($branchId)
            ->orderBy('name')
            ->orderBy('c.id');

        if ($search !== '') {
            $searchDigits = preg_replace('/\D+/', '', $search);

            $query->where(function ($where) use ($search, $searchDigits): void {
                if ($this->hasColumn('customers', 'name')) {
                    $where->where('c.name', 'like', '%' . $search . '%');
                } else {
                    $where->whereRaw("CONCAT('Customer #', c.id) LIKE ?", ['%' . $search . '%']);
                }

                if ($this->hasColumn('customers', 'phone')) {
                    $where->orWhere('c.phone', 'like', '%' . $search . '%');
                }

                if ($this->hasColumn('customers', 'line_id')) {
                    $where->orWhere('c.line_id', 'like', '%' . $search . '%');
                }

                if ($searchDigits !== '' && $this->hasColumn('customers', 'phone')) {
                    $where->orWhereRaw(
                        "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(c.phone, '-', ''), ' ', ''), '+', ''), '(', ''), ')', '') LIKE ?",
                        ['%' . $searchDigits . '%']
                    );
                }
            });
        }

        $tiers = $this->getMembershipTiers();

        return $query
            ->limit(500)
            ->get()
            ->map(function ($row) use ($tiers): array {
                return $this->appendNextTierInfo($this->mapCustomerRow($row), $tiers);
            })
            ->all();
    }

    private function findCustomerById(int $branchId, int $customerId): ?array
    {
        if (!$this->tableExists('customers')) {
            return null;
        }

        $row = $this->buildCustomersBaseQuery($branchId)
            ->where('c.id', $customerId)
            ->first();

        if ($row === null) {
            return null;
        }

        return $this->appendNextTierInfo($this->mapCustomerRow($row), $this->getMembershipTiers());
    }

    private function getCustomerOrderHistory(int $branchId, int $customerId): array
    {
        if (!$this->tableExists('orders')) {
            return [];
        }

        $hasOrderItems = $this->tableExists('order_items');
        $query = DB::table('orders as o')
            ->where('o.customer_id', $customerId)
            ->when($this->hasColumn('orders', 'branch_id'), function ($builder) use ($branchId): void {
                $builder->where('o.branch_id', $branchId);
            })
            ->orderByDesc('o.created_at')
            ->limit(50);

        if ($hasOrderItems) {
            $query
                ->leftJoin('order_items as oi', 'oi.order_id', '=', 'o.id')
                ->leftJoin('services as s', function ($join): void {
                    $join->on('s.id', '=', 'oi.item_id')
                        ->where('oi.item_type', '=', 'service');
                })
                ->leftJoin('products as p', function ($join): void {
                    $join->on('p.id', '=', 'oi.item_id')
                        ->where('oi.item_type', '=', 'product');
                })
                ->leftJoin('packages as pkg', function ($join): void {
                    $join->on('pkg.id', '=', 'oi.item_id')
                        ->where('oi.item_type', '=', 'package');
                })
                ->groupBy(
                    'o.id',
                    'o.order_no',
                    'o.created_at',
                    'o.payment_method',
                    'o.status',
                    'o.grand_total'
                );
        }

        $select = [
            'o.id',
            'o.order_no',
            'o.created_at',
            'o.payment_method',
            'o.status',
            'o.grand_total',
        ];

        if ($hasOrderItems) {
            $select[] = DB::raw('COUNT(oi.id) as item_count');
            $select[] = DB::raw(
                "GROUP_CONCAT(DISTINCT COALESCE(s.name, p.name, pkg.name, CONCAT('Item #', oi.item_id)) SEPARATOR ', ') as item_summary"
            );
        } else {
            $select[] = DB::raw('0 as item_count');
            $select[] = DB::raw("'' as item_summary");
        }

        return $query
            ->get($select)
            ->map(function ($row): array {
                return [
                    'id' => (int) $row->id,
                    'order_no' => $row->order_no !== null ? (string) $row->order_no : 'Order #' . (int) $row->id,
                    'created_at' => $this->formatDateTime($row->created_at),
                    'payment_method' => (string) ($row->payment_method ?? ''),
                    'payment_method_label' => $this->translatePaymentMethod((string) ($row->payment_method ?? '')),
                    'status' => (string) ($row->status ?? ''),
                    'status_label' => $this->translateOrderStatus((string) ($row->status ?? '')),
                    'grand_total' => (float) ($row->grand_total ?? 0),
                    'item_count' => (int) ($row->item_count ?? 0),
                    'item_summary' => (string) ($row->item_summary ?? ''),
                ];
            })
            ->all();
    }

    private function buildSummary(int $branchId, array $customers): array
    {
        $totalCustomers = count($customers);
        $activeCustomers30d = 0;

        if ($this->tableExists('orders')) {
            $query = DB::table('orders')
                ->where('created_at', '>=', now()->subDays(30)->toDateTimeString())
                ->whereNotNull('customer_id');

            if ($this->hasColumn('orders', 'branch_id')) {
                $query->where('branch_id', $branchId);
            }

            $activeCustomers30d = (int) $query
                ->distinct('customer_id')
                ->count('customer_id');
        }

        return [
            'total_customers' => $totalCustomers,
            'active_customers_30d' => $activeCustomers30d,
        ];
    }

    private function buildCustomersBaseQuery(int $branchId)
    {
        $query = DB::table('customers as c')
            ->select('c.id');

        if ($this->hasColumn('customers', 'branch_id')) {
            $query->where('c.branch_id', $branchId);
        }

        $this->addCustomerColumnSelect($query, 'name', "CONCAT('Customer #', c.id)");
        $this->addCustomerColumnSelect($query, 'phone', "''");
        $this->addCustomerColumnSelect($query, 'line_id', "''");
        $this->addCustomerColumnSelect($query, 'tier_id', 'NULL');
        $this->addCustomerColumnSelect($query, 'preferred_pressure_level', 'NULL');
        $this->addCustomerColumnSelect($query, 'health_notes', 'NULL');
        $this->addCustomerColumnSelect($query, 'contraindications', 'NULL');
        $this->addCustomerColumnSelect($query, 'total_points', '0');
        $this->addCustomerColumnSelect($query, 'wallet_balance', '0');
        $this->addCustomerColumnSelect($query, 'total_stamps', '0');

        if ($this->tableExists('membership_tiers') && $this->hasColumn('customers', 'tier_id')) {
            $query->leftJoin('membership_tiers as mt', 'mt.id', '=', 'c.tier_id');
            $query->addSelect(DB::raw("COALESCE(mt.name, '') as tier_name"));
            $query->addSelect(DB::raw('COALESCE(mt.discount_percent, 0) as tier_discount_percent'));
        } else {
            $query->addSelect(DB::raw("'' as tier_name"));
            $query->addSelect(DB::raw('0 as tier_discount_percent'));
        }

        if ($this->tableExists('orders')) {
            $hasOrdersBranchId = $this->hasColumn('orders', 'branch_id');

            $query->selectSub(function ($subQuery) use ($branchId, $hasOrdersBranchId): void {
                $subQuery->from('orders as o')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('o.customer_id', 'c.id');

                if ($hasOrdersBranchId) {
                    $subQuery->where('o.branch_id', $branchId);
                }
            }, 'visit_count');

            $query->selectSub(function ($subQuery) use ($branchId, $hasOrdersBranchId): void {
                $subQuery->from('orders as o')
                    ->selectRaw('COALESCE(SUM(CASE WHEN o.status = ? THEN o.grand_total ELSE 0 END), 0)', ['paid'])
                    ->whereColumn('o.customer_id', 'c.id');

                if ($hasOrdersBranchId) {
                    $subQuery->where('o.branch_id', $branchId);
                }
            }, 'total_spent');

            $query->selectSub(function ($subQuery) use ($branchId, $hasOrdersBranchId): void {
                $subQuery->from('orders as o')
                    ->selectRaw('MAX(o.created_at)')
                    ->whereColumn('o.customer_id', 'c.id');

                if ($hasOrdersBranchId) {
                    $subQuery->where('o.branch_id', $branchId);
                }
            }, 'last_visit_at');
        } else {
            $query->selectRaw('0 as visit_count');
            $query->selectRaw('0 as total_spent');
            $query->selectRaw('NULL as last_visit_at');
        }

        return $query;
    }

    private function findCustomerRecord(int $branchId, int $customerId): ?object
    {
        if (!$this->tableExists('customers')) {
            return null;
        }

        $query = DB::table('customers')->where('id', $customerId);
        if ($this->hasColumn('customers', 'branch_id')) {
            $query->where('branch_id', $branchId);
        }

        return $query->first(['id']);
    }

    private function mapCustomerRow($row): array
    {
        return [
            'id' => (int) $row->id,
            'name' => (string) ($row->name ?? ''),
            'phone' => (string) ($row->phone ?? ''),
            'line_id' => (string) ($row->line_id ?? ''),
            'tier_id' => $row->tier_id !== null ? (int) $row->tier_id : null,
            'tier_name' => (string) ($row->tier_name ?? ''),
            'tier_discount_percent' => (float) ($row->tier_discount_percent ?? 0),
            'preferred_pressure_level' => $row->preferred_pressure_level !== null ? (string) $row->preferred_pressure_level : null,
            'health_notes' => $row->health_notes !== null ? (string) $row->health_notes : '',
            'contraindications' => $row->contraindications !== null ? (string) $row->contraindications : '',
            'total_points' => (int) ($row->total_points ?? 0),
            'wallet_balance' => (float) ($row->wallet_balance ?? 0),
            'total_stamps' => (int) ($row->total_stamps ?? 0),
            'visit_count' => (int) ($row->visit_count ?? 0),
            'total_spent' => (float) ($row->total_spent ?? 0),
            'last_visit_at' => $this->formatDateTime($row->last_visit_at),
        ];
    }

    private function addCustomerColumnSelect($query, string $column, string $fallbackExpression): void
    {
        if ($this->hasColumn('customers', $column)) {
            $query->addSelect('c.' . $column);
            return;
        }

        $query->selectRaw($fallbackExpression . ' as ' . $column);
    }

    private function appendNextTierInfo(array $customer, array $tiers): array
    {
        $totalSpent = (float) ($customer['total_spent'] ?? 0);
        $nextTier = null;

        foreach ($tiers as $tier) {
            $minSpend = (float) ($tier['min_spend'] ?? 0);
            if ($minSpend > $totalSpent) {
                $nextTier = $tier;
                break;
            }
        }

        if ($nextTier === null) {
            $customer['next_tier_id'] = null;
            $customer['next_tier_name'] = null;
            $customer['next_tier_min_spend'] = null;
            $customer['amount_to_next_tier'] = 0.0;
            $customer['is_top_tier'] = !empty($tiers);
            return $customer;
        }

        $nextMinSpend = (float) ($nextTier['min_spend'] ?? 0);
        $customer['next_tier_id'] = (int) $nextTier['id'];
        $customer['next_tier_name'] = (string) ($nextTier['name'] ?? '');
        $customer['next_tier_min_spend'] = $nextMinSpend;
        $customer['amount_to_next_tier'] = max(0.0, $nextMinSpend - $totalSpent);
        $customer['is_top_tier'] = false;

        return $customer;
    }

    private function sanitizeCustomerData(array $payload): array
    {
        $data = [];

        if ($this->hasColumn('customers', 'name') && array_key_exists('name', $payload)) {
            $data['name'] = trim((string) $payload['name']);
        }

        if ($this->hasColumn('customers', 'phone') && array_key_exists('phone', $payload)) {
            $data['phone'] = trim((string) $payload['phone']);
        }

        if ($this->hasColumn('customers', 'line_id') && array_key_exists('line_id', $payload)) {
            $lineId = trim((string) ($payload['line_id'] ?? ''));
            $data['line_id'] = $lineId !== '' ? $lineId : null;
        }

        if ($this->hasColumn('customers', 'tier_id') && array_key_exists('tier_id', $payload)) {
            $tierId = $payload['tier_id'];
            $data['tier_id'] = ($tierId === null || $tierId === '') ? null : (int) $tierId;
        }

        if ($this->hasColumn('customers', 'preferred_pressure_level') && array_key_exists('preferred_pressure_level', $payload)) {
            $pressure = trim((string) ($payload['preferred_pressure_level'] ?? ''));
            $data['preferred_pressure_level'] = $pressure !== '' ? $pressure : null;
        }

        if ($this->hasColumn('customers', 'health_notes') && array_key_exists('health_notes', $payload)) {
            $notes = trim((string) ($payload['health_notes'] ?? ''));
            $data['health_notes'] = $notes !== '' ? $notes : null;
        }

        if ($this->hasColumn('customers', 'contraindications') && array_key_exists('contraindications', $payload)) {
            $contraindications = trim((string) ($payload['contraindications'] ?? ''));
            $data['contraindications'] = $contraindications !== '' ? $contraindications : null;
        }

        return $data;
    }

    private function assertCustomersTableExists(): void
    {
        if ($this->tableExists('customers')) {
            return;
        }

        throw ValidationException::withMessages([
            'customer' => ['ยังไม่พบตาราง customers ในฐานข้อมูล'],
        ]);
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
        $cacheKey = $table . '.' . $column;

        if (!array_key_exists($cacheKey, $this->columnExistsCache)) {
            $this->columnExistsCache[$cacheKey] = $this->tableExists($table) && Schema::hasColumn($table, $column);
        }

        return (bool) $this->columnExistsCache[$cacheKey];
    }

    private function formatDateTime($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse((string) $value)->format('Y-m-d H:i');
        } catch (\Throwable $e) {
            return (string) $value;
        }
    }

    private function translatePaymentMethod(string $method): string
    {
        if ($method === 'cash') {
            return 'เงินสด';
        }
        if ($method === 'transfer') {
            return 'โอนเงิน';
        }
        if ($method === 'credit_card' || $method === 'card') {
            return 'บัตรเครดิต';
        }
        if ($method === 'package_redeem') {
            return 'ตัดแพ็กเกจ';
        }

        return $method !== '' ? $method : '-';
    }

    private function translateOrderStatus(string $status): string
    {
        if ($status === 'paid') {
            return 'ชำระแล้ว';
        }
        if ($status === 'refunded') {
            return 'คืนเงิน';
        }
        if ($status === 'void') {
            return 'ยกเลิกบิล';
        }

        return $status !== '' ? $status : '-';
    }

    private function getMembershipTiers(): array
    {
        if (!$this->tableExists('membership_tiers')) {
            return [];
        }

        return DB::table('membership_tiers')
            ->orderBy('min_spend')
            ->orderBy('id')
            ->get(['id', 'name', 'discount_percent', 'min_spend'])
            ->map(static function ($row): array {
                return [
                    'id' => (int) $row->id,
                    'name' => (string) $row->name,
                    'discount_percent' => (float) ($row->discount_percent ?? 0),
                    'min_spend' => (float) ($row->min_spend ?? 0),
                ];
            })
            ->all();
    }
}
