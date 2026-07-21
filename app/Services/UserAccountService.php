<?php

namespace App\Services;

use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class UserAccountService
{
    private const STANDALONE_ROLES = ['super_admin', 'shop_owner'];

    private BranchContextService $branchContext;
    private ShopContextService $shopContext;
    private StaffDirectoryService $staffDirectory;
    private array $tableExistsCache = [];
    private array $columnExistsCache = [];

    public function __construct(
        BranchContextService $branchContext,
        ShopContextService $shopContext,
        StaffDirectoryService $staffDirectory
    ) {
        $this->branchContext = $branchContext;
        $this->shopContext = $shopContext;
        $this->staffDirectory = $staffDirectory;
    }

    public function getPageData(User $actor, string $search = '', ?int $branchFilter = null): array
    {
        $normalizedSearch = trim($search);
        $activeShop = $this->shopContext->getActiveShop($actor);
        $branches = $this->branchContext->getAccessibleBranches($actor, true);
        $accessibleBranchIds = array_column($branches, 'id');
        $resolvedBranchFilter = $this->resolveBranchFilter($actor, $branchFilter);
        $activeShopOwnerUserId = (string) ($actor->role ?? '') === 'super_admin'
            ? $this->getActiveShopOwnerUserId($actor)
            : null;

        if (!$this->tableExists('users')) {
            return [
                'moduleReady' => false,
                'search' => $normalizedSearch,
                'branchFilter' => $resolvedBranchFilter,
                'users' => [],
                'branches' => $branches,
                'roles' => $this->getAvailableRoles($actor),
                'staffOptions' => [],
                'standaloneRoles' => self::STANDALONE_ROLES,
                'supportsActiveToggle' => false,
                'activeShop' => $activeShop,
                'shopSelected' => !$this->branchContext->canManageAllBranches($actor) || $activeShop !== null,
                'requiresBranchSetup' => false,
                'canManageAllBranches' => $this->branchContext->canManageAllBranches($actor),
                'activeShopOwnerUserId' => $activeShopOwnerUserId,
            ];
        }

        if (
            (string) ($actor->role ?? '') === 'super_admin'
            && $this->branchContext->canManageAllBranches($actor)
            && $activeShop === null
        ) {
            return [
                'moduleReady' => true,
                'search' => $normalizedSearch,
                'branchFilter' => null,
                'users' => [],
                'branches' => [],
                'roles' => $this->getAvailableRoles($actor),
                'staffOptions' => [],
                'standaloneRoles' => self::STANDALONE_ROLES,
                'supportsActiveToggle' => $this->hasColumn('users', 'is_active'),
                'activeShop' => null,
                'shopSelected' => false,
                'requiresBranchSetup' => false,
                'canManageAllBranches' => true,
                'activeShopOwnerUserId' => null,
            ];
        }

        $query = DB::table('users as u')
            ->leftJoin('staff as s', 'u.staff_id', '=', 's.id')
            ->leftJoin('branches as b', 'u.branch_id', '=', 'b.id')
            ->orderBy('u.id');

        if ($normalizedSearch !== '') {
            $query->where(function ($builder) use ($normalizedSearch): void {
                $builder->where('u.username', 'like', '%' . $normalizedSearch . '%')
                    ->orWhere('s.name', 'like', '%' . $normalizedSearch . '%')
                    ->orWhere('s.nickname', 'like', '%' . $normalizedSearch . '%');

                if ($this->tableExists('masseuses') && $this->hasColumn('masseuses', 'user_id')) {
                    $builder->orWhereExists(function ($sub) use ($normalizedSearch): void {
                        $sub->select(DB::raw(1))
                            ->from('masseuses as m')
                            ->whereColumn('m.user_id', 'u.id')
                            ->where(function ($match) use ($normalizedSearch): void {
                                $match->where('m.full_name', 'like', '%' . $normalizedSearch . '%')
                                    ->orWhere('m.nickname', 'like', '%' . $normalizedSearch . '%');
                            });
                    });
                }
            });
        }

        if ($resolvedBranchFilter !== null && $this->hasColumn('users', 'branch_id')) {
            $query->where('u.branch_id', $resolvedBranchFilter);
        } elseif ($this->branchContext->canManageAllBranches($actor)) {
            $actorRole = (string) ($actor->role ?? '');

            $query->where(function ($builder) use ($accessibleBranchIds, $activeShopOwnerUserId, $actorRole): void {
                if (!empty($accessibleBranchIds)) {
                    $builder->whereIn('u.branch_id', $accessibleBranchIds);
                }

                if ($activeShopOwnerUserId !== null) {
                    if (!empty($accessibleBranchIds)) {
                        $builder->orWhere('u.id', $activeShopOwnerUserId);
                    } else {
                        $builder->where('u.id', $activeShopOwnerUserId);
                    }
                }

                if ($actorRole === 'super_admin') {
                    if (!empty($accessibleBranchIds) || $activeShopOwnerUserId !== null) {
                        $builder->orWhere('u.role', 'super_admin');
                    } else {
                        $builder->where('u.role', 'super_admin');
                    }
                }
            });
        } elseif ($this->hasColumn('users', 'branch_id')) {
            $query->where('u.branch_id', $this->branchContext->resolveAuthorizedBranchId($actor));
        }

        if ((string) ($actor->role ?? '') !== 'super_admin') {
            $query->where('u.role', '!=', 'super_admin');
        }

        if (!in_array((string) ($actor->role ?? ''), ['super_admin', 'shop_owner'], true)) {
            $query->where('u.role', '!=', 'shop_owner');
        }

        $selectCols = [
            'u.id',
            'u.username',
            'u.role',
            'u.branch_id',
            'u.staff_id',
            'u.last_login',
            's.name as staff_name',
            's.nickname as staff_nickname',
            's.position as staff_position',
            'b.name as branch_name',
        ];

        if ($this->hasColumn('users', 'is_active')) {
            $selectCols[] = 'u.is_active';
        }

        $users = $query->get($selectCols)->map(function ($row): array {
            $staffId = isset($row->staff_id) && $row->staff_id !== null ? (int) $row->staff_id : null;

            $linkedUser = new User();
            $linkedUser->id = (int) $row->id;
            $linkedUser->username = (string) ($row->username ?? '');
            $linkedUser->role = (string) ($row->role ?? '');
            $linkedUser->branch_id = isset($row->branch_id) && $row->branch_id !== null ? (int) $row->branch_id : null;
            $linkedUser->staff_id = $staffId;

            $profile = $this->staffDirectory->resolveUserProfile($linkedUser);
            $displayName = trim((string) ($profile['display_name'] ?? ''));
            $displayMeta = trim((string) ($profile['position'] ?? ''));
            $displaySubmeta = trim((string) ($profile['nickname'] ?? ''));

            if ($displayName === '') {
                $displayName = (string) ($row->staff_name ?? $row->username ?? '-');
            }

            if ($displayMeta === '' || $displayMeta === '-') {
                $displayMeta = $this->getRoleLabel((string) ($row->role ?? ''));
            }

            if ($displaySubmeta === '' || $displaySubmeta === '-') {
                $displaySubmeta = (string) ($row->branch_name ?? '');
            }

            return [
                'id' => (int) $row->id,
                'username' => (string) ($row->username ?? ''),
                'role' => (string) ($row->role ?? ''),
                'branch_id' => isset($row->branch_id) && $row->branch_id !== null ? (int) $row->branch_id : null,
                'branch_name' => (string) ($row->branch_name ?? '-'),
                'is_active' => (bool) ($row->is_active ?? true),
                'last_login' => $row->last_login ?? null,
                'staff_id' => $staffId,
                'staff_name' => (string) ($row->staff_name ?? ''),
                'staff_nickname' => (string) ($row->staff_nickname ?? ''),
                'staff_position' => (string) ($row->staff_position ?? ''),
                'staff_avatar' => (string) ($profile['avatar'] ?? $this->staffDirectory->getStaffAvatar($staffId, 'user-' . (string) $row->id)),
                'is_staff_linked' => $staffId !== null && $staffId > 0,
                'display_name' => $displayName,
                'display_meta' => $displayMeta,
                'display_submeta' => $displaySubmeta,
                'profile_kind' => (string) ($profile['kind'] ?? 'user'),
            ];
        })->all();

        return [
            'moduleReady' => true,
            'search' => $normalizedSearch,
            'branchFilter' => $resolvedBranchFilter,
            'users' => $users,
            'branches' => $branches,
            'roles' => $this->getAvailableRoles($actor),
            'staffOptions' => $this->getAvailableAccountOptions($actor),
            'standaloneRoles' => self::STANDALONE_ROLES,
            'supportsActiveToggle' => $this->hasColumn('users', 'is_active'),
            'activeShop' => $activeShop,
            'shopSelected' => !$this->branchContext->canManageAllBranches($actor) || $activeShop !== null,
            'requiresBranchSetup' => $this->branchContext->canManageAllBranches($actor) && $activeShop !== null && empty($accessibleBranchIds),
            'canManageAllBranches' => $this->branchContext->canManageAllBranches($actor),
            'activeShopOwnerUserId' => $activeShopOwnerUserId,
        ];
    }

    public function createUser(User $actor, array $payload): void
    {
        $this->assertModuleReady();

        $username = trim((string) ($payload['username'] ?? ''));
        if ($username === '') {
            throw ValidationException::withMessages([
                'username' => ['กรุณาระบุ Username'],
            ]);
        }

        $password = (string) ($payload['password'] ?? '');
        if (strlen($password) < 4) {
            throw ValidationException::withMessages([
                'password' => ['รหัสผ่านต้องมีอย่างน้อย 4 ตัวอักษร'],
            ]);
        }

        if (DB::table('users')->where('username', $username)->exists()) {
            throw ValidationException::withMessages([
                'username' => ['Username นี้ถูกใช้งานแล้ว'],
            ]);
        }

        $role = $this->normalizeAssignableRole($actor, (string) ($payload['role'] ?? 'cashier'));

        DB::transaction(function () use ($actor, $payload, $username, $password, $role): void {
            if ($this->isStandaloneRole($role)) {
                $row = [
                    'username' => $username,
                    'password' => Hash::make($password),
                    'role' => $role,
                    'branch_id' => null,
                    'staff_id' => null,
                ];

                if ($this->hasColumn('users', 'is_active')) {
                    $row['is_active'] = true;
                }
                if ($this->hasColumn('users', 'created_at')) {
                    $row['created_at'] = now();
                }
                if ($this->hasColumn('users', 'updated_at')) {
                    $row['updated_at'] = now();
                }

                $userId = (int) DB::table('users')->insertGetId($row);

                if ($role === 'shop_owner') {
                    $this->assignOwnerToCurrentShop($actor, $userId);
                }

                return;
            }

            [$sourceType, $sourceId] = $this->parseAccountSourcePayload($payload);
            if ($sourceId <= 0 || !in_array($sourceType, ['staff', 'masseuse'], true)) {
                throw ValidationException::withMessages([
                    'staff_id' => ['กรุณาเลือกพนักงานหรือหมอนวด'],
                ]);
            }

            $source = $this->findSelectableAccountSource($actor, $sourceType, $sourceId);
            if ($source === null) {
                throw ValidationException::withMessages([
                    'staff_id' => ['ไม่พบพนักงานหรือหมอนวดที่เลือก กรุณาเลือกใหม่อีกครั้ง'],
                ]);
            }

            $requestedBranchId = $this->normalizeNullableId($payload['branch_id'] ?? null);
            $defaultBranchId = isset($source['branch_id']) && (int) $source['branch_id'] > 0 ? (int) $source['branch_id'] : null;
            $branchId = $this->resolveAssignableBranchId($actor, $requestedBranchId ?? $defaultBranchId, $role);
            $staffId = $this->resolveOrCreateStaffIdForSource($source);

            if ($this->hasColumn('users', 'staff_id')) {
                $exists = DB::table('users')
                    ->where('staff_id', $staffId)
                    ->exists();

                if ($exists) {
                    throw ValidationException::withMessages([
                        'staff_id' => ['พนักงานหรือหมอนวดคนนี้มีบัญชีผู้ใช้แล้ว'],
                    ]);
                }
            }

            $row = [
                'username' => $username,
                'password' => Hash::make($password),
                'role' => $role,
                'branch_id' => $branchId,
            ];

            if ($this->hasColumn('users', 'staff_id')) {
                $row['staff_id'] = $staffId;
            }
            if ($this->hasColumn('users', 'is_active')) {
                $row['is_active'] = true;
            }
            if ($this->hasColumn('users', 'created_at')) {
                $row['created_at'] = now();
            }
            if ($this->hasColumn('users', 'updated_at')) {
                $row['updated_at'] = now();
            }

            $userId = (int) DB::table('users')->insertGetId($row);

            if (($source['type'] ?? '') === 'masseuse' && $this->tableExists('masseuses') && $this->hasColumn('masseuses', 'user_id')) {
                DB::table('masseuses')
                    ->where('id', (int) $source['id'])
                    ->update(['user_id' => $userId]);
            }
        });
    }

    public function updateUser(User $actor, int $userId, array $payload): void
    {
        $this->assertModuleReady();

        $existing = $this->findScopedUser($actor, $userId);
        if ($existing === null) {
            throw ValidationException::withMessages([
                'user' => ['ไม่พบบัญชีผู้ใช้งานที่ต้องการแก้ไข'],
            ]);
        }

        $role = $this->normalizeAssignableRole($actor, (string) ($payload['role'] ?? 'cashier'), (string) ($existing->role ?? ''));
        $updates = ['role' => $role];

        if ($this->isStandaloneRole($role)) {
            $updates['branch_id'] = null;
        } else {
            $requestedBranchId = $this->normalizeNullableId($payload['branch_id'] ?? null);
            $defaultBranchId = isset($existing->staff_branch_id) && (int) $existing->staff_branch_id > 0 ? (int) $existing->staff_branch_id : null;
            $updates['branch_id'] = $this->resolveAssignableBranchId($actor, $requestedBranchId ?? $defaultBranchId, $role);
        }

        if ($this->hasColumn('users', 'is_active')) {
            $updates['is_active'] = !empty($payload['is_active']);
        }
        if ($this->hasColumn('users', 'updated_at')) {
            $updates['updated_at'] = now();
        }

        DB::transaction(function () use ($actor, $userId, $existing, $role, $updates): void {
            DB::table('users')
                ->where('id', $userId)
                ->update($updates);

            if ((string) ($existing->role ?? '') === 'shop_owner' && $role !== 'shop_owner') {
                $this->clearShopOwnerIfMatches($userId);
            }

            if ($role === 'shop_owner') {
                $this->assignOwnerToCurrentShop($actor, $userId);
            }
        });
    }

    public function resetPassword(User $actor, int $userId, string $newPassword): void
    {
        $this->assertModuleReady();

        if (strlen($newPassword) < 4) {
            throw ValidationException::withMessages([
                'new_password' => ['รหัสผ่านต้องมีอย่างน้อย 4 ตัวอักษร'],
            ]);
        }

        $existing = $this->findScopedUser($actor, $userId);
        if ($existing === null) {
            throw ValidationException::withMessages([
                'user' => ['ไม่พบบัญชีผู้ใช้งานที่ต้องการรีเซ็ตรหัสผ่าน'],
            ]);
        }

        $updates = ['password' => Hash::make($newPassword)];
        if ($this->hasColumn('users', 'updated_at')) {
            $updates['updated_at'] = now();
        }

        DB::table('users')
            ->where('id', $userId)
            ->update($updates);
    }

    public function deleteUser(User $actor, int $userId, ?int $currentUserId = null): void
    {
        $this->assertModuleReady();

        if ($currentUserId !== null && $userId === $currentUserId) {
            throw ValidationException::withMessages([
                'user' => ['ไม่สามารถลบบัญชีที่กำลังใช้งานอยู่ได้'],
            ]);
        }

        $existing = $this->findScopedUser($actor, $userId);
        if ($existing === null) {
            throw ValidationException::withMessages([
                'user' => ['ไม่พบบัญชีผู้ใช้งานที่ต้องการลบ'],
            ]);
        }

        DB::transaction(function () use ($userId): void {
            if ($this->tableExists('masseuses') && $this->hasColumn('masseuses', 'user_id')) {
                DB::table('masseuses')
                    ->where('user_id', $userId)
                    ->update(['user_id' => null]);
            }

            $this->clearShopOwnerIfMatches($userId);

            DB::table('users')
                ->where('id', $userId)
                ->delete();
        });
    }

    public function getAvailableRoles(?User $actor = null): array
    {
        $actorRole = (string) ($actor->role ?? '');

        if ($actorRole === 'super_admin') {
            return [
                ['value' => 'super_admin', 'label' => 'Super Admin'],
                ['value' => 'shop_owner', 'label' => 'เจ้าของร้าน'],
                ['value' => 'branch_manager', 'label' => 'ผู้จัดการสาขา'],
                ['value' => 'cashier', 'label' => 'แคชเชียร์'],
                ['value' => 'masseuse', 'label' => 'หมอนวด'],
            ];
        }

        if ($actorRole === 'shop_owner') {
            return [
                ['value' => 'branch_manager', 'label' => 'ผู้จัดการสาขา'],
                ['value' => 'cashier', 'label' => 'แคชเชียร์'],
                ['value' => 'masseuse', 'label' => 'หมอนวด'],
            ];
        }

        return [
            ['value' => 'cashier', 'label' => 'แคชเชียร์'],
            ['value' => 'masseuse', 'label' => 'หมอนวด'],
        ];
    }

    private function getAvailableAccountOptions(User $actor): array
    {
        $options = array_merge(
            $this->getAvailableStaffOptions($actor),
            $this->getAvailableMasseuseOptions($actor)
        );

        usort($options, static function (array $left, array $right): int {
            return strcasecmp((string) ($left['name'] ?? ''), (string) ($right['name'] ?? ''));
        });

        return $options;
    }

    private function getAvailableStaffOptions(User $actor): array
    {
        if (!$this->tableExists('staff')) {
            return [];
        }

        $query = DB::table('staff as s')
            ->leftJoin('branches as b', 's.branch_id', '=', 'b.id')
            ->leftJoin('users as u', 'u.staff_id', '=', 's.id')
            ->whereNull('u.id')
            ->orderBy('s.name');

        if ($this->branchContext->canManageAllBranches($actor)) {
            $accessibleBranchIds = array_column($this->branchContext->getAccessibleBranches($actor, false), 'id');
            if (empty($accessibleBranchIds)) {
                return [];
            }

            $query->whereIn('s.branch_id', $accessibleBranchIds);
        } else {
            $query->where('s.branch_id', $this->branchContext->resolveAuthorizedBranchId($actor));
        }

        if ($this->hasColumn('staff', 'is_active')) {
            $query->where('s.is_active', 1);
        }

        return $query
            ->get([
                's.id',
                's.name',
                's.nickname',
                's.position',
                's.branch_id',
                'b.name as branch_name',
            ])
            ->map(function ($row): array {
                $staffId = (int) $row->id;

                return [
                    'id' => 'staff:' . $staffId,
                    'source_type' => 'staff',
                    'type_label' => 'พนักงาน',
                    'name' => (string) ($row->name ?? ''),
                    'nickname' => (string) ($row->nickname ?? ''),
                    'position' => (string) ($row->position ?? ''),
                    'branch_id' => $row->branch_id !== null ? (int) $row->branch_id : null,
                    'branch_name' => (string) ($row->branch_name ?? '-'),
                    'avatar' => $this->staffDirectory->getStaffAvatar($staffId, 'staff-option-' . $staffId),
                ];
            })
            ->all();
    }

    private function getAvailableMasseuseOptions(User $actor): array
    {
        if (!$this->tableExists('masseuses')) {
            return [];
        }

        $query = DB::table('masseuses as m')
            ->leftJoin('branches as b', 'm.branch_id', '=', 'b.id')
            ->orderByRaw("COALESCE(NULLIF(m.full_name, ''), m.nickname)");

        if ($this->branchContext->canManageAllBranches($actor)) {
            $accessibleBranchIds = array_column($this->branchContext->getAccessibleBranches($actor, false), 'id');
            if (empty($accessibleBranchIds)) {
                return [];
            }

            $query->whereIn('m.branch_id', $accessibleBranchIds);
        } else {
            $query->where('m.branch_id', $this->branchContext->resolveAuthorizedBranchId($actor));
        }

        if ($this->hasColumn('masseuses', 'user_id')) {
            $query->whereNull('m.user_id');
        }

        return $query
            ->get([
                'm.id',
                'm.nickname',
                'm.full_name',
                'm.branch_id',
                'b.name as branch_name',
            ])
            ->map(function ($row): array {
                $masseuseId = (int) $row->id;
                $fullName = trim((string) ($row->full_name ?? ''));
                $nickname = trim((string) ($row->nickname ?? ''));
                $displayName = $fullName !== '' ? $fullName : ($nickname !== '' ? $nickname : ('หมอนวด #' . $masseuseId));

                return [
                    'id' => 'masseuse:' . $masseuseId,
                    'source_type' => 'masseuse',
                    'type_label' => 'หมอนวด',
                    'name' => '[หมอนวด] ' . $displayName,
                    'nickname' => $nickname,
                    'position' => 'หมอนวด',
                    'branch_id' => $row->branch_id !== null ? (int) $row->branch_id : null,
                    'branch_name' => (string) ($row->branch_name ?? '-'),
                    'avatar' => $this->staffDirectory->getMasseuseAvatar($masseuseId, 'masseuse-option-' . $masseuseId),
                ];
            })
            ->all();
    }

    private function findSelectableAccountSource(User $actor, string $sourceType, int $sourceId): ?array
    {
        if ($sourceType === 'staff') {
            $staff = $this->findSelectableStaff($actor, $sourceId);
            if ($staff === null) {
                return null;
            }

            return [
                'type' => 'staff',
                'id' => (int) $staff->id,
                'branch_id' => isset($staff->branch_id) && $staff->branch_id !== null ? (int) $staff->branch_id : null,
                'name' => (string) ($staff->name ?? ''),
                'nickname' => (string) ($staff->nickname ?? ''),
            ];
        }

        $masseuse = $this->findSelectableMasseuse($actor, $sourceId);
        if ($masseuse === null) {
            return null;
        }

        $fullName = trim((string) ($masseuse->full_name ?? ''));
        $nickname = trim((string) ($masseuse->nickname ?? ''));

        return [
            'type' => 'masseuse',
            'id' => (int) $masseuse->id,
            'branch_id' => isset($masseuse->branch_id) && $masseuse->branch_id !== null ? (int) $masseuse->branch_id : null,
            'name' => $fullName !== '' ? $fullName : ($nickname !== '' ? $nickname : ('หมอนวด #' . (int) $masseuse->id)),
            'nickname' => $nickname,
        ];
    }

    private function findSelectableStaff(User $actor, int $staffId, bool $activeOnly = true): ?object
    {
        if (!$this->tableExists('staff')) {
            return null;
        }

        $query = DB::table('staff')->where('id', $staffId);

        if ($this->branchContext->canManageAllBranches($actor)) {
            $accessibleBranchIds = array_column($this->branchContext->getAccessibleBranches($actor, false), 'id');
            if (empty($accessibleBranchIds)) {
                return null;
            }

            $query->whereIn('branch_id', $accessibleBranchIds);
        } else {
            $query->where('branch_id', $this->branchContext->resolveAuthorizedBranchId($actor));
        }

        if ($activeOnly && $this->hasColumn('staff', 'is_active')) {
            $query->where('is_active', 1);
        }

        return $query->first([
            'id',
            'name',
            'nickname',
            'position',
            'branch_id',
            'is_active',
        ]);
    }

    private function findSelectableMasseuse(User $actor, int $masseuseId): ?object
    {
        if (!$this->tableExists('masseuses')) {
            return null;
        }

        $query = DB::table('masseuses')->where('id', $masseuseId);

        if ($this->branchContext->canManageAllBranches($actor)) {
            $accessibleBranchIds = array_column($this->branchContext->getAccessibleBranches($actor, false), 'id');
            if (empty($accessibleBranchIds)) {
                return null;
            }

            $query->whereIn('branch_id', $accessibleBranchIds);
        } else {
            $query->where('branch_id', $this->branchContext->resolveAuthorizedBranchId($actor));
        }

        if ($this->hasColumn('masseuses', 'user_id')) {
            $query->whereNull('user_id');
        }

        return $query->first([
            'id',
            'branch_id',
            'nickname',
            'full_name',
        ]);
    }

    private function resolveOrCreateStaffIdForSource(array $source): int
    {
        if (($source['type'] ?? '') === 'staff') {
            return (int) ($source['id'] ?? 0);
        }

        $branchId = isset($source['branch_id']) ? (int) $source['branch_id'] : null;
        $name = trim((string) ($source['name'] ?? ''));
        $nickname = trim((string) ($source['nickname'] ?? ''));
        $matchedStaff = $this->findMatchingStaffForMasseuse($branchId, $name, $nickname);

        if ($matchedStaff !== null) {
            return (int) $matchedStaff->id;
        }

        $row = [
            'branch_id' => $branchId,
            'name' => $name !== '' ? $name : ($nickname !== '' ? $nickname : 'หมอนวด'),
            'nickname' => $nickname,
            'phone' => '',
            'position' => 'หมอนวด',
            'is_active' => true,
        ];

        if ($this->hasColumn('staff', 'created_at')) {
            $row['created_at'] = now();
        }
        if ($this->hasColumn('staff', 'updated_at')) {
            $row['updated_at'] = now();
        }

        return (int) DB::table('staff')->insertGetId($row);
    }

    private function findMatchingStaffForMasseuse(?int $branchId, string $name, string $nickname): ?object
    {
        if (!$this->tableExists('staff')) {
            return null;
        }

        $candidates = array_values(array_unique(array_filter([
            trim($name),
            trim($nickname),
        ], static function (string $value): bool {
            return $value !== '';
        })));

        if (empty($candidates)) {
            return null;
        }

        $query = DB::table('staff')
            ->where(function ($builder) use ($candidates): void {
                foreach ($candidates as $index => $candidate) {
                    if ($index === 0) {
                        $builder->where('name', $candidate)
                            ->orWhere('nickname', $candidate);
                        continue;
                    }

                    $builder->orWhere('name', $candidate)
                        ->orWhere('nickname', $candidate);
                }
            });

        if ($branchId !== null && $branchId > 0) {
            $query->where('branch_id', $branchId);
        }

        $matches = $query->get(['id']);
        if ($matches->count() !== 1) {
            return null;
        }

        return $matches->first();
    }

    private function findScopedUser(User $actor, int $userId): ?object
    {
        $query = DB::table('users as u')
            ->leftJoin('staff as s', 'u.staff_id', '=', 's.id')
            ->where('u.id', $userId);

        if ($this->branchContext->canManageAllBranches($actor)) {
            $accessibleBranchIds = array_column($this->branchContext->getAccessibleBranches($actor, false), 'id');
            $activeShopOwnerUserId = (string) ($actor->role ?? '') === 'super_admin'
                ? $this->getActiveShopOwnerUserId($actor)
                : null;
            $actorRole = (string) ($actor->role ?? '');

            $query->where(function ($builder) use ($accessibleBranchIds, $activeShopOwnerUserId, $actorRole): void {
                if (!empty($accessibleBranchIds)) {
                    $builder->whereIn('u.branch_id', $accessibleBranchIds);
                }

                if ($activeShopOwnerUserId !== null) {
                    if (!empty($accessibleBranchIds)) {
                        $builder->orWhere('u.id', $activeShopOwnerUserId);
                    } else {
                        $builder->where('u.id', $activeShopOwnerUserId);
                    }
                }

                if ($actorRole === 'super_admin') {
                    if (!empty($accessibleBranchIds) || $activeShopOwnerUserId !== null) {
                        $builder->orWhere('u.role', 'super_admin');
                    } else {
                        $builder->where('u.role', 'super_admin');
                    }
                }
            });
        } else {
            $query->where('u.branch_id', $this->branchContext->resolveAuthorizedBranchId($actor));
        }

        if ((string) ($actor->role ?? '') !== 'super_admin') {
            $query->where('u.role', '!=', 'super_admin');
        }

        if (!in_array((string) ($actor->role ?? ''), ['super_admin', 'shop_owner'], true)) {
            $query->where('u.role', '!=', 'shop_owner');
        }

        return $query->first([
            'u.id',
            'u.branch_id',
            'u.role',
            'u.staff_id',
            's.branch_id as staff_branch_id',
        ]);
    }

    private function normalizeAssignableRole(User $actor, string $requestedRole, string $existingRole = ''): string
    {
        if ($existingRole !== '' && $requestedRole === $existingRole) {
            return $existingRole;
        }

        $validRoles = array_column($this->getAvailableRoles($actor), 'value');
        if (!in_array($requestedRole, $validRoles, true)) {
            return $validRoles[0] ?? 'cashier';
        }

        return $requestedRole;
    }

    private function resolveAssignableBranchId(User $actor, ?int $branchId, string $role): ?int
    {
        if ($this->isStandaloneRole($role)) {
            return null;
        }

        if ($this->branchContext->canManageAllBranches($actor)) {
            $accessibleBranchIds = array_column($this->branchContext->getAccessibleBranches($actor, false), 'id');

            if (empty($accessibleBranchIds)) {
                throw ValidationException::withMessages([
                    'branch_id' => ['กรุณาสร้างสาขาก่อนเพิ่มผู้ใช้งานของร้าน'],
                ]);
            }

            if ($branchId !== null && in_array($branchId, $accessibleBranchIds, true)) {
                return $branchId;
            }

            return (int) $accessibleBranchIds[0];
        }

        return $this->branchContext->resolveAuthorizedBranchId($actor);
    }

    private function resolveBranchFilter(User $actor, ?int $branchFilter): ?int
    {
        if ($this->branchContext->canManageAllBranches($actor)) {
            if ($branchFilter === null) {
                return null;
            }

            $accessibleIds = array_column($this->branchContext->getAccessibleBranches($actor, false), 'id');
            return in_array($branchFilter, $accessibleIds, true) ? $branchFilter : null;
        }

        return $this->branchContext->resolveAuthorizedBranchId($actor);
    }

    private function normalizeNullableId($value): ?int
    {
        if ($value === null || $value === '' || $value === '0') {
            return null;
        }

        $parsed = is_numeric($value) ? (int) $value : 0;

        return $parsed > 0 ? $parsed : null;
    }

    private function parseAccountSourcePayload(array $payload): array
    {
        $sourceType = trim((string) ($payload['source_type'] ?? ''));
        $sourceId = $this->normalizeNullableId($payload['source_id'] ?? null);
        $legacySelection = trim((string) ($payload['staff_id'] ?? ''));

        if ($sourceType !== '' && $sourceId !== null) {
            return [$sourceType, $sourceId];
        }

        if ($legacySelection !== '' && strpos($legacySelection, ':') !== false) {
            [$legacyType, $legacyId] = array_pad(explode(':', $legacySelection, 2), 2, '');

            return [
                trim((string) $legacyType),
                $this->normalizeNullableId($legacyId) ?? 0,
            ];
        }

        return [
            'staff',
            $this->normalizeNullableId($legacySelection) ?? 0,
        ];
    }

    private function getRoleLabel(string $role): string
    {
        $labels = [
            'super_admin' => 'Super Admin',
            'shop_owner' => 'เจ้าของร้าน',
            'branch_manager' => 'ผู้จัดการสาขา',
            'cashier' => 'แคชเชียร์',
            'masseuse' => 'หมอนวด',
        ];

        return $labels[$role] ?? $role;
    }

    private function isStandaloneRole(string $role): bool
    {
        return in_array($role, self::STANDALONE_ROLES, true);
    }

    private function getActiveShopOwnerUserId(User $actor): ?int
    {
        if (!$this->tableExists('shops') || !$this->hasColumn('shops', 'owner_user_id')) {
            return null;
        }

        $shopId = $this->shopContext->getActiveShopId($actor);
        if ($shopId === null) {
            return null;
        }

        $ownerUserId = DB::table('shops')
            ->where('id', $shopId)
            ->value('owner_user_id');

        return $ownerUserId !== null ? (int) $ownerUserId : null;
    }

    private function assignOwnerToCurrentShop(User $actor, int $userId): void
    {
        if ((string) ($actor->role ?? '') !== 'super_admin') {
            throw ValidationException::withMessages([
                'role' => ['เฉพาะ Super Admin เท่านั้นที่กำหนดเจ้าของร้านได้'],
            ]);
        }

        if (!$this->tableExists('shops') || !$this->hasColumn('shops', 'owner_user_id')) {
            throw ValidationException::withMessages([
                'shop_owner' => ['ฐานข้อมูลยังไม่รองรับการผูกเจ้าของร้าน กรุณารัน SQL setup เพิ่มเติมก่อน'],
            ]);
        }

        $shopId = $this->shopContext->getActiveShopId($actor);
        if ($shopId === null) {
            throw ValidationException::withMessages([
                'shop_owner' => ['กรุณาเลือกร้านที่ต้องการจัดการก่อน'],
            ]);
        }

        $existingOwnerUserId = $this->getActiveShopOwnerUserId($actor);
        if ($existingOwnerUserId !== null && $existingOwnerUserId !== $userId) {
            throw ValidationException::withMessages([
                'shop_owner' => ['ร้านนี้มีเจ้าของร้านอยู่แล้ว กรุณาเปลี่ยนบัญชีเดิมก่อน'],
            ]);
        }

        $updates = ['owner_user_id' => $userId];
        if ($this->hasColumn('shops', 'updated_at')) {
            $updates['updated_at'] = now();
        }

        DB::table('shops')
            ->where('id', $shopId)
            ->update($updates);
    }

    private function clearShopOwnerIfMatches(int $userId): void
    {
        if (!$this->tableExists('shops') || !$this->hasColumn('shops', 'owner_user_id')) {
            return;
        }

        $updates = ['owner_user_id' => null];
        if ($this->hasColumn('shops', 'updated_at')) {
            $updates['updated_at'] = now();
        }

        DB::table('shops')
            ->where('owner_user_id', $userId)
            ->update($updates);
    }

    private function assertModuleReady(): void
    {
        if ($this->tableExists('users')) {
            return;
        }

        throw ValidationException::withMessages([
            'users' => ['ไม่พบตาราง users ในฐานข้อมูล'],
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
}
