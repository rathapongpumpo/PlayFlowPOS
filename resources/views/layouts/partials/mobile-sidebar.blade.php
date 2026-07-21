@php
    $mobileSidebarRole = (string) (auth()->user()->role ?? '');
    $isSuperAdminMobileSidebar = $mobileSidebarRole === 'super_admin';
    $isAdminMobileSidebar = in_array($mobileSidebarRole, ['super_admin', 'shop_owner', 'branch_manager'], true);
    $isMasseuseMobileSidebar = $mobileSidebarRole === 'masseuse';

    if ($isMasseuseMobileSidebar) {
        $mobileMenus = [
            [
                'route' => 'masseuse.self',
                'active' => ['masseuse.self', 'masseuse'],
                'icon' => 'bi-wallet2',
                'title' => 'ค่ามือของฉัน',
                'subtitle' => 'ดูรายได้ ค่ามือ และคิวของตัวเอง',
            ],
        ];
    } elseif ($isSuperAdminMobileSidebar) {
        $mobileMenus = [
            [
                'route' => 'system.shops.index',
                'active' => ['system.shops.*'],
                'icon' => 'bi-grid-fill',
                'title' => 'พอร์ทัลร้าน',
                'subtitle' => 'เลือกร้านและเข้าไปจัดการข้อมูลของร้านนั้น',
            ],
            [
                'route' => 'branches.index',
                'active' => ['branches.*'],
                'icon' => 'bi-building-fill',
                'title' => 'สาขา',
                'subtitle' => 'จัดการสาขาของร้านที่เลือก',
            ],
            [
                'route' => 'staff.index',
                'active' => ['staff.*'],
                'icon' => 'bi-person-badge',
                'title' => 'พนักงาน',
                'subtitle' => 'จัดการข้อมูลพนักงานของร้านที่เลือก',
            ],
            [
                'route' => 'users.index',
                'active' => ['users.*'],
                'icon' => 'bi-shield-check',
                'title' => 'ผู้ใช้งานระบบ',
                'subtitle' => 'จัดการบัญชีผู้ใช้ของร้านที่เลือก',
            ],
        ];
    } else {
        $mobileMenus = [
            [
                'route' => 'dashboard',
                'active' => ['dashboard'],
                'icon' => 'bi-speedometer2',
                'title' => 'แดชบอร์ด',
                'subtitle' => 'ดูภาพรวมยอดขายและสรุปประจำวัน',
            ],
            [
                'route' => 'crm.index',
                'active' => ['crm.*'],
                'icon' => 'bi-bar-chart-line-fill',
                'title' => 'CRM Analytics',
                'subtitle' => 'วิเคราะห์ข้อมูลลูกค้าและแต้มสะสม',
            ],
            [
                'route' => 'operations.index',
                'active' => ['operations.*'],
                'icon' => 'bi-shop-window',
                'title' => 'เปิด/ปิดลิ้นชัก',
                'subtitle' => 'ระบบจัดการลิ้นชักเงินสดประจำวัน',
            ],
            [
                'route' => 'pos',
                'active' => ['pos', 'pos.checkout'],
                'icon' => 'bi-cart-fill',
                'title' => 'หน้าขาย',
                'subtitle' => 'ชำระเงินและขายสินค้า/บริการ',
            ],
            [
                'route' => 'booking',
                'active' => ['booking', 'booking.*'],
                'icon' => 'bi-calendar-check',
                'title' => 'คิว',
                'subtitle' => 'จัดการคิวและการจอง',
            ],
            [
                'route' => 'masseuse',
                'active' => ['masseuse', 'masseuse.*'],
                'icon' => 'bi-person-badge',
                'title' => 'หมอนวด',
                'subtitle' => $isAdminMobileSidebar ? 'จัดการข้อมูลหมอนวด' : 'ดูข้อมูลหมอนวด',
            ],
        ];

        if ($isAdminMobileSidebar) {
            $adminMenus = [
                [
                    'route' => 'receipts',
                    'active' => ['receipts*'],
                    'icon' => 'bi-receipt-cutoff',
                    'title' => 'ใบเสร็จ',
                    'subtitle' => 'ตรวจสอบย้อนหลังและพิมพ์บิล',
                ],
                [
                    'route' => 'customers',
                    'active' => ['customers*'],
                    'icon' => 'bi-people',
                    'title' => 'ลูกค้า',
                    'subtitle' => 'แก้ไขข้อมูลลูกค้าและประวัติการใช้บริการ',
                ],
                [
                    'route' => 'membership-levels',
                    'active' => ['membership-levels*'],
                    'icon' => 'bi-sliders',
                    'title' => 'สมาชิก',
                    'subtitle' => 'จัดการระดับสมาชิก',
                ],
                [
                    'route' => 'packages',
                    'active' => ['packages*'],
                    'icon' => 'bi-box2-heart',
                    'title' => 'แพ็กเกจ',
                    'subtitle' => 'จัดการแพ็กเกจและโปรโมชั่น',
                ],
                [
                    'route' => 'services.index',
                    'active' => ['services.*'],
                    'icon' => 'bi-list-stars',
                    'title' => 'บริการ',
                    'subtitle' => 'จัดการบริการและราคา',
                ],
                [
                    'route' => 'products',
                    'active' => ['products*'],
                    'icon' => 'bi-box-seam-fill',
                    'title' => 'สินค้าและสต็อก',
                    'subtitle' => 'ดูแลสินค้า จำนวนคงเหลือ และหมวดหมู่',
                ],
                [
                    'route' => 'massage-rooms',
                    'active' => ['massage-rooms*'],
                    'icon' => 'bi-door-open',
                    'title' => 'ห้องนวด',
                    'subtitle' => 'จัดการห้อง เตียง และความพร้อมใช้งาน',
                ],
                [
                    'route' => 'reports',
                    'active' => ['reports*'],
                    'icon' => 'bi-bar-chart-line-fill',
                    'title' => 'รายงานวิเคราะห์',
                    'subtitle' => 'ติดตามยอดขาย บริการ หมอนวด และสินค้า',
                ],

                [
                    'route' => 'admin.commission.index',
                    'active' => ['admin.commission.*'],
                    'icon' => 'bi-percent',
                    'title' => 'ค่าคอมมิชชั่น',
                    'subtitle' => 'กำหนดค่าคอมมิชชั่นของร้าน',
                ],
                [
                    'route' => 'branches.index',
                    'active' => ['branches.*'],
                    'icon' => 'bi-building-fill',
                    'title' => 'สาขา',
                    'subtitle' => 'จัดการข้อมูลสาขา',
                ],
                [
                    'route' => 'staff.index',
                    'active' => ['staff.*'],
                    'icon' => 'bi-person-badge',
                    'title' => 'พนักงาน',
                    'subtitle' => 'จัดการข้อมูลพนักงาน',
                ],
                [
                    'route' => 'users.index',
                    'active' => ['users.*'],
                    'icon' => 'bi-shield-check',
                    'title' => 'ผู้ใช้งานระบบ',
                    'subtitle' => 'จัดการบัญชีและสิทธิ์เข้าใช้งาน',
                ],
            ];

            $mobileMenus = array_merge($mobileMenus, $adminMenus);
        }
    }
@endphp

<div class="mobile-menu-panel">
    <p class="mobile-menu-heading mb-3">{{ $isMasseuseMobileSidebar ? 'เมนูของฉัน' : 'เมนูหลัก' }}</p>
    <div class="d-flex flex-column gap-2">
        @foreach($mobileMenus as $item)
            @php
                $activePatterns = $item['active'] ?? [$item['route']];
                $isActive = false;

                foreach ($activePatterns as $pattern) {
                    if (request()->routeIs($pattern)) {
                        $isActive = true;
                        break;
                    }
                }
            @endphp
            <a href="{{ route($item['route']) }}" class="mobile-menu-link {{ $isActive ? 'active' : '' }}">
                <span class="mobile-menu-icon"><i class="bi {{ $item['icon'] }}"></i></span>
                <span class="mobile-menu-content">
                    <span class="mobile-menu-title">{{ $item['title'] }}</span>
                    <span class="mobile-menu-subtitle">{{ $item['subtitle'] }}</span>
                </span>
                <i class="bi bi-chevron-right mobile-menu-arrow"></i>
            </a>
        @endforeach

        <form method="POST" action="{{ route('logout') }}" class="mt-2">
            @csrf
            <button type="submit" class="mobile-menu-link mobile-menu-link--logout w-100 text-start">
                <span class="mobile-menu-icon"><i class="bi bi-box-arrow-right"></i></span>
                <span class="mobile-menu-content">
                    <span class="mobile-menu-title">ออกจากระบบ</span>
                    <span class="mobile-menu-subtitle">ออกจากบัญชีผู้ใช้ปัจจุบัน</span>
                </span>
            </button>
        </form>
    </div>
</div>
