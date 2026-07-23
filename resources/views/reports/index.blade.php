@extends('layouts.main')

@section('title', 'รายงานวิเคราะห์ - PlayFlow')

@push('head')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<style>
    .reports-page .card { border-radius: 1.05rem; }

    .reports-page .hero-card {
        border: 1px solid rgba(31, 115, 224, 0.16) !important;
        background: linear-gradient(165deg, #eef9ff 0%, #f6fdff 100%);
        box-shadow: 0 16px 28px rgba(18, 85, 150, 0.1) !important;
    }

    .reports-page .section-title {
        font-weight: 700;
        color: #1e5f9d;
        margin-bottom: 0.55rem;
    }

    .reports-page .soft-box {
        border: 1px solid rgba(31, 115, 224, 0.13);
        border-radius: 0.92rem;
        background: #ffffff;
        box-shadow: 0 7px 14px rgba(14, 72, 133, 0.06);
        padding: 0.9rem;
        height: 100%;
    }

    .reports-page .table-card {
        border: 1px solid rgba(31, 115, 224, 0.14) !important;
        box-shadow: 0 14px 30px rgba(17, 81, 146, 0.08) !important;
    }

    .reports-page .table thead th {
        color: #1e5f9d;
        background: linear-gradient(180deg, #eef6ff 0%, #e8f3ff 100%);
        border-bottom-color: rgba(31, 115, 224, 0.15);
        font-size: 0.82rem;
        white-space: nowrap;
    }

    .reports-page .gradient-btn {
        background: linear-gradient(135deg, #2d8ff0, #14b89a) !important;
        border-color: #2d8ff0 !important;
        color: #ffffff !important;
        box-shadow: 0 10px 18px rgba(21, 101, 181, 0.24);
    }

    .reports-page .gradient-btn:hover { filter: brightness(0.96); }

    .reports-page .icon-chip {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 1.9rem;
        height: 1.9rem;
        border-radius: 999px;
        font-size: 0.95rem;
        box-shadow: 0 2px 6px rgba(16, 76, 136, 0.18);
    }
    .reports-page .icon-chip i { font-size: 0.9rem; }

    .reports-page .icon-chip--blue {
        color: #0f67bf;
        background: linear-gradient(145deg, rgba(55, 153, 246, 0.28), rgba(72, 173, 248, 0.16));
    }
    .reports-page .icon-chip--mint {
        color: #0c907d;
        background: linear-gradient(145deg, rgba(20, 184, 154, 0.26), rgba(111, 222, 203, 0.16));
    }
    .reports-page .icon-chip--violet {
        color: #6d59d8;
        background: linear-gradient(145deg, rgba(129, 96, 255, 0.22), rgba(180, 159, 255, 0.14));
    }
    .reports-page .icon-chip--pink {
        color: #bf4d8a;
        background: linear-gradient(145deg, rgba(242, 113, 188, 0.24), rgba(255, 166, 219, 0.16));
    }
    .reports-page .icon-chip--amber {
        color: #b27a1a;
        background: linear-gradient(145deg, rgba(245, 180, 50, 0.28), rgba(255, 210, 100, 0.16));
    }

    .reports-page .seg-control {
        display: inline-flex;
        background: rgba(31, 115, 224, 0.06);
        border-radius: 0.75rem;
        padding: 3px;
        gap: 2px;
        overflow-x: auto;
        width: 100%;
    }

    .reports-page .seg-btn {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.35rem;
        padding: 0.52rem 0.7rem;
        border-radius: 0.6rem;
        border: none;
        background: transparent;
        color: #4a7eb5;
        font-weight: 600;
        font-size: 0.82rem;
        text-decoration: none;
        transition: all 0.22s ease;
        white-space: nowrap;
        cursor: pointer;
    }

    .reports-page .seg-btn:hover {
        background: rgba(45, 143, 240, 0.1);
        color: #1a5aa0;
    }

    .reports-page .seg-btn.active {
        background: linear-gradient(135deg, #2d8ff0, #14b89a) !important;
        color: #ffffff !important;
        box-shadow: 0 2px 8px rgba(14, 72, 133, 0.13);
        font-weight: 700;
    }

    .reports-page .seg-btn i { font-size: 0.85rem; }

    .reports-page .preset-bar {
        display: flex;
        gap: 0.4rem;
        flex-wrap: wrap;
    }

    .reports-page .preset-chip {
        padding: 0.32rem 0.75rem;
        border-radius: 999px;
        border: 1.5px solid rgba(31, 115, 224, 0.16);
        background: #fff;
        color: #4a7eb5;
        font-weight: 600;
        font-size: 0.78rem;
        cursor: pointer;
        transition: all 0.18s ease;
        text-decoration: none;
    }

    .reports-page .preset-chip:hover {
        border-color: #2d8ff0;
        color: #1a5aa0;
        background: rgba(45, 143, 240, 0.06);
    }

    .reports-page .preset-chip.active {
        background: linear-gradient(135deg, #2d8ff0, #14b89a);
        color: #fff;
        border-color: transparent;
        box-shadow: 0 3px 10px rgba(31, 115, 224, 0.22);
    }

    .reports-page .stat-card {
        border: 1px solid rgba(31, 115, 224, 0.1);
        border-radius: 1rem;
        background: #ffffff;
        padding: 1rem;
        text-align: center;
        box-shadow: 0 6px 14px rgba(14, 72, 133, 0.05);
        transition: transform 0.18s ease, box-shadow 0.18s ease;
    }

    .reports-page .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 22px rgba(14, 72, 133, 0.1);
    }

    .reports-page .stat-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1a4a7a;
        line-height: 1.2;
    }

    .reports-page .stat-label {
        font-size: 0.78rem;
        color: #5c728a;
        font-weight: 500;
        margin-top: 0.25rem;
    }

    .reports-page .chart-container {
        position: relative;
        width: 100%;
        max-height: 320px;
    }

    .reports-page .export-btn {
        background: linear-gradient(135deg, #34a853, #0d8043);
        color: #fff !important;
        border: none;
        border-radius: 999px;
        padding: 0.48rem 1.2rem;
        font-size: 0.84rem;
        font-weight: 600;
        box-shadow: 0 6px 14px rgba(13, 128, 67, 0.22);
        transition: all 0.2s ease;
    }

    .reports-page .export-btn:hover {
        filter: brightness(0.94);
        transform: translateY(-1px);
        box-shadow: 0 10px 20px rgba(13, 128, 67, 0.28);
    }

    .reports-page .badge-soft {
        border-radius: 999px;
        padding: 0.26rem 0.6rem;
        font-size: 0.75rem;
        font-weight: 700;
        color: #0f65b8;
        background: rgba(45, 143, 240, 0.12);
    }

    .reports-page .progress {
        height: 0.5rem;
        border-radius: 999px;
        background: rgba(31, 115, 224, 0.08);
    }

    .reports-page .progress-bar-blue {
        background: linear-gradient(90deg, #2d8ff0, #5aa8f5);
    }

    .reports-page .progress-bar-mint {
        background: linear-gradient(90deg, #14b89a, #42d4b8);
    }

    .reports-page .progress-bar-violet {
        background: linear-gradient(90deg, #6d59d8, #9b8ae8);
    }

    .reports-page .progress-bar-pink {
        background: linear-gradient(90deg, #e05b97, #f08cbf);
    }

    .reports-page .progress-bar-amber {
        background: linear-gradient(90deg, #d4982a, #f0bf4a);
    }

    @media (max-width: 767.98px) {
        .reports-page .card-body { padding: 0.9rem; }
        .reports-page .stat-value { font-size: 1.2rem; }
        .reports-page .seg-btn { font-size: 0.75rem; padding: 0.42rem 0.45rem; gap: 0.2rem; }
        .reports-page .seg-btn i { font-size: 0.78rem; }
        .reports-page .preset-chip { font-size: 0.72rem; padding: 0.28rem 0.6rem; }
    }
</style>
@endpush

@section('content')
    <div class="row g-3 reports-page">

        {{-- ═══ Hero Card: Tab Navigation + Filters ═══ --}}
        <div class="col-12">
            <div class="card border-0 shadow-sm hero-card">
                <div class="card-body p-3 p-lg-4">
                    {{-- Segmented Tab Control --}}
                    <div class="seg-control mb-3">
                        <a href="{{ route('reports', ['tab' => 'sales', 'date_from' => $dateFrom, 'date_to' => $dateTo]) }}"
                           class="seg-btn {{ $activeTab === 'sales' ? 'active' : '' }}">
                            <i class="fa-solid fa-chart-line"></i> ยอดขาย
                        </a>
                        <a href="{{ route('reports', ['tab' => 'services', 'date_from' => $dateFrom, 'date_to' => $dateTo]) }}"
                           class="seg-btn {{ $activeTab === 'services' ? 'active' : '' }}">
                            <i class="fa-solid fa-spa"></i> บริการ
                        </a>
                        <a href="{{ route('reports', ['tab' => 'masseuse', 'date_from' => $dateFrom, 'date_to' => $dateTo]) }}"
                           class="seg-btn {{ $activeTab === 'masseuse' ? 'active' : '' }}">
                            <i class="fa-solid fa-user-nurse"></i> หมอ
                        </a>
                        <a href="{{ route('reports', ['tab' => 'products', 'date_from' => $dateFrom, 'date_to' => $dateTo]) }}"
                           class="seg-btn {{ $activeTab === 'products' ? 'active' : '' }}">
                            <i class="fa-solid fa-boxes-stacked"></i> สินค้า
                        </a>
                    </div>

                    {{-- Quick Date Presets + Custom --}}
                    @php
                        $today = now()->toDateString();
                        $weekStart = now()->startOfWeek()->toDateString();
                        $monthStart = now()->startOfMonth()->toDateString();
                        $yearStart = now()->startOfYear()->toDateString();
                        $activePreset = ($dateFrom === $today && $dateTo === $today) ? 'today'
                            : (($dateFrom === $weekStart && $dateTo === $today) ? 'week'
                            : (($dateFrom === $monthStart && $dateTo === $today) ? 'month'
                            : (($dateFrom === $yearStart && $dateTo === $today) ? 'year' : 'custom')));
                    @endphp
                    <div class="soft-box">
                        <div class="preset-bar mb-2">
                            <a href="{{ route('reports', ['tab' => $activeTab, 'date_from' => $today, 'date_to' => $today]) }}"
                               class="preset-chip {{ $activePreset === 'today' ? 'active' : '' }}">วันนี้</a>
                            <a href="{{ route('reports', ['tab' => $activeTab, 'date_from' => $weekStart, 'date_to' => $today]) }}"
                               class="preset-chip {{ $activePreset === 'week' ? 'active' : '' }}">สัปดาห์นี้</a>
                            <a href="{{ route('reports', ['tab' => $activeTab, 'date_from' => $monthStart, 'date_to' => $today]) }}"
                               class="preset-chip {{ $activePreset === 'month' ? 'active' : '' }}">เดือนนี้</a>
                            <a href="{{ route('reports', ['tab' => $activeTab, 'date_from' => $yearStart, 'date_to' => $today]) }}"
                               class="preset-chip {{ $activePreset === 'year' ? 'active' : '' }}">ปีนี้</a>
                        </div>
                        <form method="GET" action="{{ route('reports') }}" class="row g-2 align-items-end">
                            <input type="hidden" name="tab" value="{{ $activeTab }}">
                            <div class="col-5 col-md-4">
                                <label class="form-label small fw-bold mb-1">จากวันที่</label>
                                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ $dateFrom }}">
                            </div>
                            <div class="col-5 col-md-4">
                                <label class="form-label small fw-bold mb-1">ถึงวันที่</label>
                                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ $dateTo }}">
                            </div>
                            <div class="col-2 col-md-2 d-grid">
                                <button type="submit" class="btn gradient-btn btn-sm rounded-3"><i class="fa-solid fa-filter"></i></button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- ═══ Tab Content ═══ --}}

        @if($activeTab === 'sales' && $salesReport)
        {{-- ── Sales Report ── --}}
        <div class="col-12">
            <div class="row g-3">
                <div class="col-6 col-md-3">
                    <div class="stat-card">
                        <div class="icon-chip icon-chip--blue mx-auto mb-2"><i class="fa-solid fa-coins"></i></div>
                        <div class="stat-value">{{ number_format($salesReport['summary']['total_sales']) }}</div>
                        <div class="stat-label">ยอดขายรวม (฿)</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-card">
                        <div class="icon-chip icon-chip--mint mx-auto mb-2"><i class="fa-solid fa-receipt"></i></div>
                        <div class="stat-value">{{ number_format($salesReport['summary']['total_orders']) }}</div>
                        <div class="stat-label">จำนวนออเดอร์</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-card">
                        <div class="icon-chip icon-chip--violet mx-auto mb-2"><i class="fa-solid fa-calculator"></i></div>
                        <div class="stat-value">{{ number_format($salesReport['summary']['avg_per_order']) }}</div>
                        <div class="stat-label">เฉลี่ย/ออเดอร์ (฿)</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-card">
                        <div class="icon-chip icon-chip--pink mx-auto mb-2"><i class="fa-solid fa-tags"></i></div>
                        <div class="stat-value">{{ number_format($salesReport['summary']['total_discount']) }}</div>
                        <div class="stat-label">ส่วนลดรวม (฿)</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sales Chart --}}
        <div class="col-12 col-xl-8">
            <div class="card border-0 shadow-sm table-card">
                <div class="card-header bg-white border-0 pt-3 pb-2">
                    <h6 class="fw-bold mb-0"><span class="icon-chip icon-chip--blue me-2"><i class="fa-solid fa-chart-area"></i></span>กราฟยอดขาย</h6>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="salesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        {{-- Payment Method Breakdown --}}
        <div class="col-12 col-xl-4">
            <div class="card border-0 shadow-sm table-card h-100">
                <div class="card-header bg-white border-0 pt-3 pb-2">
                    <h6 class="fw-bold mb-0"><span class="icon-chip icon-chip--mint me-2"><i class="fa-solid fa-credit-card"></i></span>ช่องทางชำระเงิน</h6>
                </div>
                <div class="card-body d-flex flex-column justify-content-center">
                    <div class="chart-container" style="max-height: 220px;">
                        <canvas id="paymentChart"></canvas>
                    </div>
                    <div class="mt-3">
                        @php
        $pmLabels = ['cash' => 'เงินสด', 'transfer' => 'โอนเงิน', 'credit_card' => 'บัตรเครดิต', 'package_redeem' => 'แพ็กเกจ'];
                        @endphp
                        @foreach($salesReport['summary']['payment_methods'] as $pm)
                        <div class="d-flex justify-content-between align-items-center py-1">
                            <span class="small fw-bold">{{ $pmLabels[$pm['method']] ?? $pm['method'] }}</span>
                            <span class="badge-soft">{{ number_format($pm['total']) }} ฿ ({{ $pm['count'] }})</span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Sales Data Table --}}
        <div class="col-12">
            <div class="card border-0 shadow-sm table-card">
                <div class="card-header bg-white border-0 pt-3 pb-2 d-flex align-items-center justify-content-between">
                    <h6 class="fw-bold mb-0"><span class="icon-chip icon-chip--blue me-2"><i class="fa-solid fa-table"></i></span>ตารางยอดขาย</h6>
                    <span class="badge-soft">{{ count($salesReport['data']) }} รายการ</span>
                </div>
                <div class="card-body p-2 p-lg-3">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>ช่วงเวลา</th>
                                    <th class="text-end">ยอดขาย (฿)</th>
                                    <th class="text-end">จำนวนออเดอร์</th>
                                    <th class="text-end">ส่วนลด (฿)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($salesReport['data'] as $row)
                                <tr>
                                    <td class="fw-bold">{{ $row['period'] }}</td>
                                    <td class="text-end">{{ number_format($row['total_sales']) }}</td>
                                    <td class="text-end">{{ number_format($row['total_orders']) }}</td>
                                    <td class="text-end">{{ number_format($row['total_discount']) }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="4" class="text-center text-muted py-4">ไม่มีข้อมูลในช่วงที่เลือก</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        @endif

        @if($activeTab === 'services' && $serviceReport)
        {{-- ── Service Report ── --}}
        <div class="col-12 col-xl-7">
            <div class="card border-0 shadow-sm table-card">
                <div class="card-header bg-white border-0 pt-3 pb-2 d-flex align-items-center justify-content-between">
                    <h6 class="fw-bold mb-0"><span class="icon-chip icon-chip--mint me-2"><i class="fa-solid fa-spa"></i></span>บริการขายดี</h6>
                    <span class="badge-soft">{{ count($serviceReport['top_services']) }} รายการ</span>
                </div>
                <div class="card-body p-2 p-lg-3">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th style="min-width: 150px;">ชื่อบริการ</th>
                                    <th class="text-end">จำนวน (ครั้ง)</th>
                                    <th class="text-end">รายได้ (฿)</th>
                                    <th style="min-width: 120px;">สัดส่วน</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
        $maxServiceRevenue = collect($serviceReport['top_services'])->max('total_revenue') ?: 1;
        $progressColors = ['progress-bar-blue', 'progress-bar-mint', 'progress-bar-violet', 'progress-bar-pink', 'progress-bar-amber'];
                                @endphp
                                @forelse($serviceReport['top_services'] as $idx => $svc)
                                <tr>
                                    <td class="fw-bold text-muted">{{ $idx + 1 }}</td>
                                    <td class="fw-bold">{{ $svc['name'] }}</td>
                                    <td class="text-end">{{ number_format($svc['total_qty']) }}</td>
                                    <td class="text-end">{{ number_format($svc['total_revenue']) }}</td>
                                    <td>
                                        <div class="progress">
                                            <div class="progress-bar {{ $progressColors[$idx % count($progressColors)] }}" style="width: {{ round($svc['total_revenue'] / $maxServiceRevenue * 100) }}%"></div>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="5" class="text-center text-muted py-4">ไม่มีข้อมูล</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-5">
            <div class="card border-0 shadow-sm table-card h-100">
                <div class="card-header bg-white border-0 pt-3 pb-2">
                    <h6 class="fw-bold mb-0"><span class="icon-chip icon-chip--violet me-2"><i class="fa-solid fa-chart-pie"></i></span>สัดส่วนรายได้ต่อประเภท</h6>
                </div>
                <div class="card-body d-flex flex-column justify-content-center">
                    @if(count($serviceReport['category_revenue']) > 0)
                    <div class="chart-container" style="max-height: 250px;">
                        <canvas id="categoryRevenueChart"></canvas>
                    </div>
                    <div class="mt-3">
                        @foreach($serviceReport['category_revenue'] as $cat)
                        <div class="d-flex justify-content-between align-items-center py-1">
                            <span class="small fw-bold">{{ $cat['category_name'] }}</span>
                            <span class="badge-soft">{{ number_format($cat['total_revenue']) }} ฿ ({{ $cat['total_qty'] }} ครั้ง)</span>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="text-center text-muted py-4">ไม่มีข้อมูล</div>
                    @endif
                </div>
            </div>
        </div>
        @endif

        @if($activeTab === 'masseuse' && $masseuseReport)
        {{-- ── Masseuse Report ── --}}
        <div class="col-12">
            <div class="row g-3 mb-3">
                @php
        $totalMasseuseRevenue = collect($masseuseReport['masseuses'])->sum('total_revenue');
        $totalMasseuseCommission = collect($masseuseReport['masseuses'])->sum('total_commission');
        $totalMasseuseTopUp = collect($masseuseReport['masseuses'])->sum('top_up');
        $totalMasseusePaid = collect($masseuseReport['masseuses'])->sum('total_paid');
        $totalQueues = collect($masseuseReport['masseuses'])->sum('queue_count');
                @endphp
                <div class="col-6 col-md-3">
                    <div class="stat-card">
                        <div class="icon-chip icon-chip--blue mx-auto mb-2"><i class="fa-solid fa-coins"></i></div>
                        <div class="stat-value">{{ number_format($totalMasseuseRevenue) }}</div>
                        <div class="stat-label">รายได้รวม (฿)</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-card">
                        <div class="icon-chip icon-chip--pink mx-auto mb-2"><i class="fa-solid fa-hand-holding-dollar"></i></div>
                        <div class="stat-value">{{ number_format($totalMasseuseCommission) }}</div>
                        <div class="stat-label">คอมมิชชันรวม (฿)</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-card">
                        <div class="icon-chip icon-chip--amber mx-auto mb-2"><i class="fa-solid fa-arrow-trend-up"></i></div>
                        <div class="stat-value">{{ number_format($totalMasseuseTopUp) }}</div>
                        <div class="stat-label">เงินสมทบรวม (฿)</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-card">
                        <div class="icon-chip icon-chip--mint mx-auto mb-2"><i class="fa-solid fa-sack-dollar"></i></div>
                        <div class="stat-value text-success">{{ number_format($totalMasseusePaid) }}</div>
                        <div class="stat-label fw-bold">ยอดที่ต้องจ่ายรวม (฿)</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-8">
            <div class="card border-0 shadow-sm table-card">
                <div class="card-header bg-white border-0 pt-3 pb-2 d-flex align-items-center justify-content-between">
                    <h6 class="fw-bold mb-0"><span class="icon-chip icon-chip--violet me-2"><i class="fa-solid fa-user-nurse"></i></span>รายละเอียดการจ่ายเงินหมอนวด</h6>
                    <span class="badge-soft">{{ count($masseuseReport['masseuses']) }} คน</span>
                </div>
                <div class="card-body p-2 p-lg-3">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th style="min-width: 150px;">ชื่อ</th>
                                    <th class="text-end" style="min-width: 80px;">รายได้ (฿)</th>
                                    <th class="text-end" style="min-width: 80px;">คอมมิชชัน (฿)</th>
                                    <th class="text-end" style="min-width: 80px;">เงินสมทบ (฿)</th>
                                    <th class="text-end" style="min-width: 80px;">รวมจ่าย (฿)</th>
                                    <th class="text-end" style="min-width: 60px;">รอบ</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($masseuseReport['masseuses'] as $idx => $ms)
                                <tr>
                                    <td class="fw-bold text-muted">{{ $idx + 1 }}</td>
                                    <td class="fw-bold">{{ $ms['name'] }}</td>
                                    <td class="text-end">{{ number_format($ms['total_revenue']) }}</td>
                                    <td class="text-end">{{ number_format($ms['total_commission']) }}</td>
                                    <td class="text-end text-warning">{{ number_format($ms['top_up']) }}</td>
                                    <td class="text-end text-success fw-bold">{{ number_format($ms['total_paid']) }}</td>
                                    <td class="text-end">{{ number_format($ms['queue_count']) }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="7" class="text-center text-muted py-4">ไม่มีข้อมูล</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="card border-0 shadow-sm table-card h-100">
                <div class="card-header bg-white border-0 pt-3 pb-2">
                    <h6 class="fw-bold mb-0"><span class="icon-chip icon-chip--amber me-2"><i class="fa-solid fa-ranking-star"></i></span>รายได้ Top 5</h6>
                </div>
                <div class="card-body d-flex flex-column justify-content-center">
                    @if(count($masseuseReport['masseuses']) > 0)
                    <div class="chart-container" style="max-height: 280px;">
                        <canvas id="masseuseChart"></canvas>
                    </div>
                    @else
                    <div class="text-center text-muted py-4">ไม่มีข้อมูล</div>
                    @endif
                </div>
            </div>
        </div>
        @endif

        @if($activeTab === 'products' && $productReport)
        {{-- ── Product Report ── --}}
        <div class="col-12 col-xl-7">
            <div class="card border-0 shadow-sm table-card">
                <div class="card-header bg-white border-0 pt-3 pb-2 d-flex align-items-center justify-content-between">
                    <h6 class="fw-bold mb-0"><span class="icon-chip icon-chip--blue me-2"><i class="fa-solid fa-fire"></i></span>สินค้าขายดี</h6>
                    <span class="badge-soft">{{ count($productReport['top_products']) }} รายการ</span>
                </div>
                <div class="card-body p-2 p-lg-3">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th style="min-width: 150px;">ชื่อสินค้า</th>
                                    <th style="min-width: 80px;">SKU</th>
                                    <th class="text-end" style="min-width: 70px;">จำนวนขาย</th>
                                    <th class="text-end" style="min-width: 80px;">รายได้ (฿)</th>
                                    <th style="min-width: 100px;">สัดส่วน</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
        $maxProductRevenue = collect($productReport['top_products'])->max('total_revenue') ?: 1;
                                @endphp
                                @forelse($productReport['top_products'] as $idx => $prod)
                                <tr>
                                    <td class="fw-bold text-muted">{{ $idx + 1 }}</td>
                                    <td class="fw-bold">{{ $prod['name'] }}</td>
                                    <td class="text-muted small">{{ $prod['sku'] }}</td>
                                    <td class="text-end">{{ number_format($prod['total_qty']) }}</td>
                                    <td class="text-end">{{ number_format($prod['total_revenue']) }}</td>
                                    <td>
                                        <div class="progress">
                                            <div class="progress-bar progress-bar-blue" style="width: {{ round($prod['total_revenue'] / $maxProductRevenue * 100) }}%"></div>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="6" class="text-center text-muted py-4">ไม่มีข้อมูลสินค้าขาย</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-5">
            <div class="card border-0 shadow-sm table-card">
                <div class="card-header bg-white border-0 pt-3 pb-2 d-flex align-items-center justify-content-between">
                    <h6 class="fw-bold mb-0"><span class="icon-chip icon-chip--amber me-2"><i class="fa-solid fa-warehouse"></i></span>มูลค่าสต็อกคงเหลือ</h6>
                    @php $totalStockValue = collect($productReport['stock_value'])->sum('stock_value'); @endphp
                    <span class="badge-soft">รวม {{ number_format($totalStockValue) }} ฿</span>
                </div>
                <div class="card-body p-2 p-lg-3">
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th style="min-width: 150px;">ชื่อสินค้า</th>
                                    <th class="text-end" style="min-width: 80px;">คงเหลือ</th>
                                    <th class="text-end" style="min-width: 80px;">ต้นทุน/หน่วย</th>
                                    <th class="text-end" style="min-width: 80px;">มูลค่า (฿)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($productReport['stock_value'] as $sv)
                                <tr>
                                    <td class="fw-bold small">{{ $sv['name'] }}</td>
                                    <td class="text-end">{{ number_format($sv['stock_qty']) }}</td>
                                    <td class="text-end small text-muted">{{ number_format($sv['cost_price'], 2) }}</td>
                                    <td class="text-end fw-bold">{{ number_format($sv['stock_value']) }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="4" class="text-center text-muted py-4">ไม่มีข้อมูลสต็อก</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        @endif

        {{-- Export CSV Button --}}
        <form method="GET" action="{{ route('reports.export-csv') }}" class="d-inline">
            <input type="hidden" name="tab" value="{{ $activeTab }}">
            <input type="hidden" name="period" value="{{ $period }}">
            @if($dateFrom)<input type="hidden" name="date_from" value="{{ $dateFrom }}">@endif
            @if($dateTo)<input type="hidden" name="date_to" value="{{ $dateTo }}">@endif
            <button type="submit" class="export-btn">
                <i class="fa-solid fa-file-csv me-1"></i>Export CSV
            </button>
        </form>

    </div>
@endsection

@push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var chartDefaults = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
            },
        };

        @if($activeTab === 'sales' && $salesReport)
        // Sales Line Chart
        (function () {
            var ctx = document.getElementById('salesChart');
            if (!ctx) return;
            var data = @json($salesReport['data']);
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.map(function (d) { return d.period; }),
                    datasets: [{
                        label: 'ยอดขาย (฿)',
                        data: data.map(function (d) { return d.total_sales; }),
                        borderColor: '#2d8ff0',
                        backgroundColor: 'rgba(45, 143, 240, 0.08)',
                        borderWidth: 2.5,
                        fill: true,
                        tension: 0.35,
                        pointBackgroundColor: '#ffffff',
                        pointBorderColor: '#2d8ff0',
                        pointBorderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                    }],
                },
                options: Object.assign({}, chartDefaults, {
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { callback: function (v) { return v.toLocaleString() + ' ฿'; } },
                            grid: { color: 'rgba(31,115,224,0.06)' },
                        },
                        x: { grid: { display: false } },
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function (ctx) { return ctx.parsed.y.toLocaleString() + ' ฿'; },
                            },
                        },
                    },
                }),
            });
        })();

        // Payment Doughnut
        (function () {
            var ctx = document.getElementById('paymentChart');
            if (!ctx) return;
            var pm = @json($salesReport['summary']['payment_methods']);
            var labels = { cash: 'เงินสด', transfer: 'โอนเงิน', credit_card: 'บัตรเครดิต', package_redeem: 'แพ็กเกจ' };
            var colors = ['#2d8ff0', '#14b89a', '#6d59d8', '#e05b97', '#d4982a'];
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: pm.map(function (p) { return labels[p.method] || p.method; }),
                    datasets: [{
                        data: pm.map(function (p) { return p.total; }),
                        backgroundColor: colors.slice(0, pm.length),
                        borderWidth: 2,
                        borderColor: '#ffffff',
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '62%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { font: { size: 12, family: 'Prompt' }, padding: 14 },
                        },
                    },
                },
            });
        })();
        @endif

        @if($activeTab === 'services' && $serviceReport && count($serviceReport['category_revenue']) > 0)
        // Category Revenue Pie
        (function () {
            var ctx = document.getElementById('categoryRevenueChart');
            if (!ctx) return;
            var cats = @json($serviceReport['category_revenue']);
            var colors = ['#2d8ff0', '#14b89a', '#6d59d8', '#e05b97', '#d4982a', '#4dc9f6', '#f67019', '#537bc4', '#acc236'];
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: cats.map(function (c) { return c.category_name; }),
                    datasets: [{
                        data: cats.map(function (c) { return c.total_revenue; }),
                        backgroundColor: colors.slice(0, cats.length),
                        borderWidth: 2,
                        borderColor: '#ffffff',
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '55%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { font: { size: 11, family: 'Prompt' }, padding: 12 },
                        },
                    },
                },
            });
        })();
        @endif

        @if($activeTab === 'masseuse' && $masseuseReport && count($masseuseReport['masseuses']) > 0)
        // Masseuse Bar Chart
        (function () {
            var ctx = document.getElementById('masseuseChart');
            if (!ctx) return;
            var top5 = @json(array_slice($masseuseReport['masseuses'], 0, 5));
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: top5.map(function (m) { return m.name; }),
                    datasets: [
                        {
                            label: 'รายได้',
                            data: top5.map(function (m) { return m.total_revenue; }),
                            backgroundColor: 'rgba(45, 143, 240, 0.75)',
                            borderRadius: 6,
                            barPercentage: 0.6,
                        },
                        {
                            label: 'คอมมิชชัน',
                            data: top5.map(function (m) { return m.total_commission; }),
                            backgroundColor: 'rgba(20, 184, 154, 0.75)',
                            borderRadius: 6,
                            barPercentage: 0.6,
                        },
                    ],
                },
                options: Object.assign({}, chartDefaults, {
                    indexAxis: 'y',
                    scales: {
                        x: {
                            beginAtZero: true,
                            ticks: { callback: function (v) { return v.toLocaleString(); } },
                            grid: { color: 'rgba(31,115,224,0.06)' },
                        },
                        y: { grid: { display: false } },
                    },
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                            labels: { font: { size: 11, family: 'Prompt' } },
                        },
                    },
                }),
            });
        })();
        @endif
    });
    </script>
@endpush
