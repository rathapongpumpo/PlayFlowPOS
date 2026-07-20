<style>
    .pf-sidebar .nav-link.link-dark {
        color: #2e3f55 !important;
    }

    .pf-sidebar .nav-link.link-dark:hover {
        color: #1f73e0 !important;
        background-color: rgba(31, 115, 224, 0.08) !important;
    }

    .pf-sidebar .text-muted {
        color: #5c728a !important;
    }

    .pf-sidebar .sidebar-logout-btn {
        border-radius: 0.85rem;
        border: 1px solid rgba(220, 53, 69, 0.16);
        background: linear-gradient(135deg, rgba(255, 241, 243, 0.98), rgba(255, 250, 250, 0.98));
        color: #bf3147 !important;
        font-weight: 700;
        box-shadow: 0 8px 18px rgba(176, 53, 71, 0.08);
    }

    .pf-sidebar .sidebar-logout-btn:hover {
        color: #a82940 !important;
        background: linear-gradient(135deg, rgba(255, 231, 235, 0.98), rgba(255, 247, 248, 0.98));
    }
</style>

@php
    $sidebarRole = (string) (auth()->user()->role ?? '');
    $isSuperAdminSidebar = $sidebarRole === 'super_admin';
    $isAdminSidebar = in_array($sidebarRole, ['super_admin', 'shop_owner', 'branch_manager'], true);
    $isMasseuseSidebar = $sidebarRole === 'masseuse';
@endphp

<div class="pf-sidebar">
    <div class="mb-4 px-2">
        <h4 class="fw-bold text-primary mb-0"><i class="bi bi-flower1"></i> PlayFlow</h4>
        <small class="text-muted text-uppercase fw-bold" style="font-size: 0.65rem; letter-spacing: 1px;">Spa Management System</small>
    </div>

    <div class="nav flex-column nav-pills">
        @if($isMasseuseSidebar)
            <small class="text-muted fw-bold mb-2 px-2" style="font-size: 0.75rem;">เมนูของฉัน</small>
            <a href="{{ route('masseuse.self') }}" class="nav-link {{ request()->routeIs('masseuse.self') || request()->routeIs('masseuse') ? 'active' : 'link-dark' }}">
                <i class="bi bi-wallet2 me-2"></i> ค่ามือของฉัน
            </a>
        @elseif($isSuperAdminSidebar)
            <small class="text-muted fw-bold mb-2 px-2" style="font-size: 0.75rem;">พอร์ทัลเจ้าของระบบ</small>
            <a href="{{ route('system.shops.index') }}" class="nav-link {{ request()->routeIs('system.shops.*') ? 'active' : 'link-dark' }}">
                <i class="bi bi-grid-fill me-2"></i> พอร์ทัลร้าน
            </a>
            <a href="{{ route('branches.index') }}" class="nav-link {{ request()->routeIs('branches.*') ? 'active' : 'link-dark' }}">
                <i class="bi bi-building-fill me-2"></i> สาขา
            </a>
            <a href="{{ route('staff.index') }}" class="nav-link {{ request()->routeIs('staff.*') ? 'active' : 'link-dark' }}">
                <i class="bi bi-person-badge-fill me-2"></i> พนักงาน
            </a>
            <a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.*') ? 'active' : 'link-dark' }}">
                <i class="bi bi-shield-lock-fill me-2"></i> ผู้ใช้งานระบบ
            </a>
        @else
            <small class="text-muted fw-bold mb-2 px-2" style="font-size: 0.75rem;">งานหลัก</small>
            <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : 'link-dark' }}">
                <i class="bi bi-speedometer2 me-2"></i> แดชบอร์ด
            </a>
            <a href="{{ route('crm.index') }}" class="nav-link {{ request()->routeIs('crm.*') ? 'active' : 'link-dark' }}">
                <i class="bi bi-bar-chart-line-fill me-2"></i> CRM Analytics
            </a>
            <a href="{{ route('pos') }}" class="nav-link {{ request()->routeIs('pos*') ? 'active' : 'link-dark' }}">
                <i class="bi bi-cart-fill me-2"></i> หน้าขาย
            </a>
            <a href="{{ route('operations.index') }}" class="nav-link {{ request()->routeIs('operations.*') ? 'active' : 'link-dark' }}">
                <i class="bi bi-shop-window me-2"></i> เปิด/ปิดลิ้นชัก
            </a>
            <a href="{{ route('booking') }}" class="nav-link {{ request()->routeIs('booking*') ? 'active' : 'link-dark' }}">
                <i class="bi bi-calendar-event-fill me-2"></i> คิว
            </a>
            <a href="{{ route('masseuse') }}" class="nav-link {{ request()->routeIs('masseuse') || request()->routeIs('masseuse.create') || request()->routeIs('masseuse.edit') ? 'active' : 'link-dark' }}">
                <i class="bi bi-person-badge-fill me-2"></i> หมอนวด
            </a>
            <a href="{{ route('masseuse.shifts') }}" class="nav-link {{ request()->routeIs('masseuse.shifts*') ? 'active' : 'link-dark' }}">
                <i class="bi bi-calendar-check me-2"></i> ตารางงาน
            </a>

            @if($isAdminSidebar)
                <small class="text-muted fw-bold mt-4 mb-2 px-2" style="font-size: 0.75rem;">งานขายและบริการ</small>
                <a href="{{ route('receipts') }}" class="nav-link {{ request()->routeIs('receipts*') ? 'active' : 'link-dark' }}">
                    <i class="bi bi-receipt-cutoff me-2"></i> ใบเสร็จ
                </a>
                <a href="{{ route('customers') }}" class="nav-link {{ request()->routeIs('customers*') ? 'active' : 'link-dark' }}">
                    <i class="bi bi-people-fill me-2"></i> ลูกค้า
                </a>
                <a href="{{ route('membership-levels') }}" class="nav-link {{ request()->routeIs('membership-levels*') ? 'active' : 'link-dark' }}">
                    <i class="bi bi-sliders me-2"></i> สมาชิก
                </a>
                <a href="{{ route('packages') }}" class="nav-link {{ request()->routeIs('packages*') ? 'active' : 'link-dark' }}">
                    <i class="bi bi-box2-heart me-2"></i> แพ็กเกจ
                </a>
                <a href="{{ route('services.index') }}" class="nav-link {{ request()->routeIs('services.*') ? 'active' : 'link-dark' }}">
                    <i class="bi bi-list-stars me-2"></i> บริการ
                </a>
                <a href="{{ route('products') }}" class="nav-link {{ request()->routeIs('products*') ? 'active' : 'link-dark' }}">
                    <i class="bi bi-box-seam-fill me-2"></i> สินค้า (ขาย)
                </a>
                <a href="{{ route('store-assets.index') }}" class="nav-link {{ request()->routeIs('store-assets.*') ? 'active' : 'link-dark' }}">
                    <i class="bi bi-boxes me-2"></i> ของใช้ในร้าน
                </a>
                <a href="{{ route('massage-rooms') }}" class="nav-link {{ request()->routeIs('massage-rooms*') ? 'active' : 'link-dark' }}">
                    <i class="bi bi-door-open me-2"></i> ห้องนวด
                </a>

                <small class="text-muted fw-bold mt-4 mb-2 px-2" style="font-size: 0.75rem;">วิเคราะห์และการเงิน</small>
                <a href="{{ route('reports') }}" class="nav-link {{ request()->routeIs('reports*') ? 'active' : 'link-dark' }}">
                    <i class="bi bi-bar-chart-line-fill me-2"></i> รายงานวิเคราะห์
                </a>

                <a href="{{ route('admin.commission.index') }}" class="nav-link {{ request()->routeIs('admin.commission.*') ? 'active' : 'link-dark' }}">
                    <i class="bi bi-cash-stack me-2"></i> ค่าคอมมิชชั่น
                </a>

                <small class="text-muted fw-bold mt-4 mb-2 px-2" style="font-size: 0.75rem;">ตั้งค่าระบบ</small>
                <a href="{{ route('branches.index') }}" class="nav-link {{ request()->routeIs('branches.*') ? 'active' : 'link-dark' }}">
                    <i class="bi bi-building-fill me-2"></i> สาขา
                </a>
                <a href="{{ route('staff.index') }}" class="nav-link {{ request()->routeIs('staff.*') ? 'active' : 'link-dark' }}">
                    <i class="bi bi-person-badge-fill me-2"></i> พนักงาน
                </a>
                <a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.*') ? 'active' : 'link-dark' }}">
                    <i class="bi bi-shield-lock-fill me-2"></i> ผู้ใช้งานระบบ
                </a>
            @endif
        @endif

        <form method="POST" action="{{ route('logout') }}" class="mt-1">
            @csrf
            <button type="submit" class="nav-link sidebar-logout-btn w-100 text-start">
                <i class="bi bi-box-arrow-right me-2"></i> ออกจากระบบ
            </button>
        </form>
    </div>
</div>
