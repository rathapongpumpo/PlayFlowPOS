@extends('layouts.main')

@section('title', 'Dashboard | PlayFlow POS')
@section('page_title', 'Dashboard')
@section('page_subtitle', $stats['branch_name'] ?? '-')

@php
    $selectedRange = $stats['selected_range'] ?? 'today';
    $selectedRangeLabel = $stats['selected_range_label'] ?? 'วันนี้';
    $rangeButtons = [
        ['value' => 'today', 'label' => 'วันนี้'],
        ['value' => 'yesterday', 'label' => 'เมื่อวาน'],
        ['value' => '7d', 'label' => '7 วันย้อนหลัง'],
    ];
    $todaySales = (int) ($stats['today_sales'] ?? 0);
    $monthlySales = (int) ($stats['monthly_sales'] ?? 0);
    $todayOrders = (int) ($stats['today_orders'] ?? 0);
    $todayClients = (int) ($stats['today_clients'] ?? 0);
    $dailyMasseuseFee = (int) ($stats['daily_masseuse_fee'] ?? 0);
    $monthlyMasseuseFee = (int) ($stats['monthly_masseuse_fee'] ?? 0);
    $todayServiceSales = (int) ($stats['today_service_sales'] ?? 0);
    $todayPackageSales = (int) ($stats['today_package_sales'] ?? 0);
    $netProfit = (int) ($stats['net_profit'] ?? 0);
    $topServices = $stats['top_services'] ?? [];
    $topMasseuses = $stats['top_masseuses'] ?? [];
    $salesChart = $stats['sales_chart'] ?? ['labels' => [], 'data' => []];
@endphp

@push('head')
<style>
    .dashboard-page {
        --dash-navy: #123b63;
        --dash-ink: #20486f;
        --dash-muted: #6f89a3;
        --dash-line: rgba(32, 84, 130, 0.12);
        --dash-glass: rgba(255, 255, 255, 0.82);
        --dash-panel: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(245, 250, 255, 0.96));
        --dash-shadow: 0 18px 38px rgba(18, 68, 122, 0.1);
    }

    .dashboard-page .dash-card,
    .dashboard-page .hero-shell,
    .dashboard-page .spotlight-card,
    .dashboard-page .chart-card,
    .dashboard-page .service-card,
    .dashboard-page .ranking-card {
        border: 1px solid var(--dash-line);
        border-radius: 1.6rem;
        background: var(--dash-panel);
        box-shadow: var(--dash-shadow);
    }

    .dashboard-page .hero-shell {
        overflow: hidden;
        position: relative;
        background:
            radial-gradient(circle at top right, rgba(255, 255, 255, 0.22), transparent 28%),
            radial-gradient(circle at bottom left, rgba(255, 255, 255, 0.16), transparent 24%),
            linear-gradient(140deg, #123f69 0%, #1f73e0 44%, #14b89a 100%);
        color: #ffffff;
        border-color: rgba(255, 255, 255, 0.12);
    }

    .dashboard-page .hero-shell::before,
    .dashboard-page .hero-shell::after {
        content: '';
        position: absolute;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.08);
        filter: blur(1px);
    }

    .dashboard-page .hero-shell::before {
        width: 18rem;
        height: 18rem;
        right: -6rem;
        top: -7rem;
    }

    .dashboard-page .hero-shell::after {
        width: 12rem;
        height: 12rem;
        left: -4rem;
        bottom: -4.5rem;
    }

    .dashboard-page .hero-body {
        position: relative;
        z-index: 1;
        padding: 1.4rem;
    }

    .dashboard-page .hero-kicker {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.42rem 0.78rem;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.14);
        border: 1px solid rgba(255, 255, 255, 0.18);
        color: rgba(255, 255, 255, 0.94);
        font-size: 0.78rem;
        font-weight: 700;
        letter-spacing: 0.02em;
    }

    .dashboard-page .hero-title {
        margin-top: 0.9rem;
        margin-bottom: 0.5rem;
        font-size: clamp(1.8rem, 1.35rem + 2vw, 3rem);
        font-weight: 700;
        line-height: 1.05;
        max-width: 13ch;
    }

    .dashboard-page .hero-copy {
        max-width: 34rem;
        color: rgba(255, 255, 255, 0.82);
        font-size: 0.98rem;
        line-height: 1.55;
    }

    .dashboard-page .hero-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 0.6rem;
        margin-top: 1rem;
    }

    .dashboard-page .hero-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.48rem 0.8rem;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.13);
        border: 1px solid rgba(255, 255, 255, 0.14);
        color: #ffffff;
        font-size: 0.82rem;
        font-weight: 600;
    }

    .dashboard-page .hero-metrics {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.8rem;
    }

    .dashboard-page .hero-metric {
        min-height: 100%;
        padding: 1rem;
        border-radius: 1.25rem;
        background: rgba(255, 255, 255, 0.12);
        border: 1px solid rgba(255, 255, 255, 0.14);
        backdrop-filter: blur(6px);
    }

    .dashboard-page .hero-metric-label {
        display: block;
        color: rgba(255, 255, 255, 0.76);
        font-size: 0.78rem;
        margin-bottom: 0.28rem;
    }

    .dashboard-page .hero-metric-value {
        color: #ffffff;
        font-size: clamp(1.25rem, 1rem + 1.2vw, 2rem);
        line-height: 1.1;
        font-weight: 700;
    }

    .dashboard-page .hero-metric-note {
        margin-top: 0.3rem;
        color: rgba(255, 255, 255, 0.72);
        font-size: 0.78rem;
    }

    .dashboard-page .focus-shell {
        padding: 1.1rem;
    }

    .dashboard-page .focus-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 0.9rem;
    }

    .dashboard-page .mini-stat-card {
        padding: 1.25rem 1.15rem;
        border-radius: 1.35rem;
        background: linear-gradient(180deg, rgba(248, 252, 255, 0.98), rgba(239, 247, 255, 0.94));
        border: 1px solid rgba(30, 95, 155, 0.1);
        min-height: 100%;
    }

    .dashboard-page .mini-stat-head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 0.85rem;
        margin-bottom: 0.75rem;
    }

    .dashboard-page .mini-stat-label {
        color: var(--dash-muted);
        font-size: 0.82rem;
        font-weight: 700;
        margin-bottom: 0;
    }

    .dashboard-page .mini-stat-icon {
        width: 2.6rem;
        height: 2.6rem;
        border-radius: 0.95rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #ffffff;
        font-size: 1rem;
        flex-shrink: 0;
        box-shadow: 0 10px 18px rgba(22, 92, 160, 0.16);
    }

    .dashboard-page .mini-stat-icon.is-clients {
        background: linear-gradient(135deg, #2d8ff0, #6cbcff);
    }

    .dashboard-page .mini-stat-icon.is-orders {
        background: linear-gradient(135deg, #4c71e6, #6f87ff);
    }

    .dashboard-page .mini-stat-value {
        font-size: clamp(2rem, 1.55rem + 1.8vw, 3rem);
        line-height: 1.02;
        font-weight: 700;
        color: var(--dash-navy);
        margin-bottom: 0.45rem;
    }

    .dashboard-page .mini-stat-meta {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.45rem;
        font-size: 0.84rem;
        color: var(--dash-muted);
    }

    .dashboard-page .report-card {
        position: relative;
        overflow: hidden;
        padding: 1.2rem;
        background:
            radial-gradient(circle at top right, rgba(120, 220, 245, 0.24), transparent 24%),
            radial-gradient(circle at bottom left, rgba(114, 214, 195, 0.18), transparent 22%),
            linear-gradient(180deg, rgba(255, 255, 255, 0.76), rgba(241, 248, 255, 0.68));
        border-color: rgba(255, 255, 255, 0.46);
        box-shadow:
            0 22px 42px rgba(17, 83, 143, 0.12),
            inset 0 1px 0 rgba(255, 255, 255, 0.56);
        backdrop-filter: blur(16px);
    }

    .dashboard-page .report-card::before {
        content: '';
        position: absolute;
        inset: 0;
        background:
            linear-gradient(135deg, rgba(255, 255, 255, 0.22), transparent 38%),
            linear-gradient(180deg, rgba(255, 255, 255, 0.16), transparent 48%);
        pointer-events: none;
    }

    .dashboard-page .report-shell {
        position: relative;
        z-index: 1;
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 0.9rem;
    }

    .dashboard-page .report-metric {
        padding: 1.05rem 1.1rem;
        border-radius: 1.35rem;
        background:
            linear-gradient(145deg, rgba(123, 211, 246, 0.78), rgba(101, 220, 201, 0.72));
        border: 1px solid rgba(255, 255, 255, 0.42);
        box-shadow:
            inset 0 1px 0 rgba(255, 255, 255, 0.42),
            0 16px 28px rgba(31, 121, 169, 0.12);
        min-height: 100%;
        backdrop-filter: blur(14px);
    }

    .dashboard-page .report-metric-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        margin-bottom: 0.7rem;
    }

    .dashboard-page .report-metric-label {
        color: rgba(18, 59, 99, 0.74);
        font-size: 0.8rem;
        font-weight: 700;
        margin-bottom: 0;
        letter-spacing: 0.01em;
    }

    .dashboard-page .report-metric-icon {
        width: 2.55rem;
        height: 2.55rem;
        border-radius: 1rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.34), rgba(255, 255, 255, 0.2));
        color: #123b63;
        font-size: 1rem;
        border: 1px solid rgba(255, 255, 255, 0.28);
        box-shadow:
            inset 0 1px 0 rgba(255, 255, 255, 0.52),
            0 10px 18px rgba(42, 132, 170, 0.12);
        flex-shrink: 0;
        backdrop-filter: blur(10px);
    }

    .dashboard-page .report-metric-value {
        font-weight: 700;
        line-height: 1.02;
        color: #123b63;
        font-size: clamp(1.8rem, 1.3rem + 1.5vw, 2.8rem);
        margin-bottom: 0.25rem;
    }

    .dashboard-page .report-metric-note {
        color: rgba(18, 59, 99, 0.7);
        font-size: 0.82rem;
        line-height: 1.35;
    }

    .dashboard-page .report-meta-row {
        position: relative;
        z-index: 1;
        display: flex;
        flex-wrap: wrap;
        gap: 0.55rem;
        margin-top: 1rem;
    }

    .dashboard-page .report-meta-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.42rem;
        padding: 0.44rem 0.74rem;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.42);
        border: 1px solid rgba(255, 255, 255, 0.38);
        color: #1b5fae;
        font-size: 0.77rem;
        font-weight: 700;
        box-shadow:
            inset 0 1px 0 rgba(255, 255, 255, 0.48),
            0 8px 18px rgba(31, 115, 224, 0.08);
        backdrop-filter: blur(10px);
    }

    .dashboard-page .spotlight-card {
        padding: 1.2rem;
        height: 100%;
    }

    .dashboard-page .spotlight-top {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 0.75rem;
    }

    .dashboard-page .spotlight-icon {
        width: 3rem;
        height: 3rem;
        border-radius: 1rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.15rem;
        color: #ffffff;
        box-shadow: 0 12px 20px rgba(17, 86, 151, 0.18);
    }

    .dashboard-page .spotlight-icon.is-blue {
        background: linear-gradient(135deg, #2d8ff0, #69b7ff);
    }

    .dashboard-page .spotlight-icon.is-mint {
        background: linear-gradient(135deg, #12a58a, #4dd7bd);
    }

    .dashboard-page .spotlight-icon.is-violet {
        background: linear-gradient(135deg, #4c71e6, #7da0ff);
    }

    .dashboard-page .spotlight-icon.is-gold {
        background: linear-gradient(135deg, #c7922e, #f2c86d);
    }

    .dashboard-page .spotlight-label {
        color: var(--dash-muted);
        font-size: 0.82rem;
        font-weight: 700;
        margin-bottom: 0.5rem;
    }

    .dashboard-page .spotlight-value {
        font-size: clamp(1.7rem, 1.2rem + 1.3vw, 2.5rem);
        line-height: 1.02;
        font-weight: 700;
        color: var(--dash-navy);
        margin-bottom: 0.45rem;
    }

    .dashboard-page .spotlight-subtext {
        margin: 0;
        font-size: 0.84rem;
        color: var(--dash-muted);
    }

    .dashboard-page .trend-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.38rem;
        padding: 0.34rem 0.64rem;
        border-radius: 999px;
        font-size: 0.76rem;
        font-weight: 700;
    }

    .dashboard-page .trend-pill.is-up {
        background: rgba(20, 184, 154, 0.14);
        color: #118671;
    }

    .dashboard-page .trend-pill.is-down {
        background: rgba(220, 83, 102, 0.12);
        color: #be4255;
    }

    .dashboard-page .trend-pill.is-flat {
        background: rgba(111, 137, 163, 0.12);
        color: #63778d;
    }

    .dashboard-page .section-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 0.8rem;
        margin-bottom: 1rem;
    }

    .dashboard-page .section-title {
        margin: 0;
        font-size: 1.15rem;
        font-weight: 700;
        color: var(--dash-navy);
    }

    .dashboard-page .section-note {
        color: var(--dash-muted);
        font-size: 0.82rem;
    }

    .dashboard-page .range-shell {
        position: relative;
        z-index: 1;
        display: inline-flex;
        flex-wrap: wrap;
        gap: 0.35rem;
        padding: 0.3rem;
        border-radius: 999px;
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.56), rgba(236, 245, 255, 0.44));
        border: 1px solid rgba(255, 255, 255, 0.52);
        box-shadow:
            inset 0 1px 0 rgba(255, 255, 255, 0.72),
            0 12px 24px rgba(22, 88, 152, 0.08);
        backdrop-filter: blur(14px);
    }

    .dashboard-page .range-btn {
        border: 0;
        padding: 0.48rem 0.95rem;
        border-radius: 999px;
        text-decoration: none;
        color: #486887;
        font-size: 0.82rem;
        font-weight: 700;
        transition: all 0.22s ease;
    }

    .dashboard-page .range-btn:hover {
        color: #1d4f7f;
        background: rgba(255, 255, 255, 0.36);
    }

    .dashboard-page .range-btn.is-active {
        color: #ffffff;
        background: linear-gradient(135deg, rgba(20, 64, 111, 0.96), rgba(31, 115, 224, 0.94) 54%, rgba(20, 184, 154, 0.92));
        box-shadow:
            0 12px 22px rgba(17, 71, 128, 0.24),
            inset 0 1px 0 rgba(255, 255, 255, 0.2);
    }

    .dashboard-page .chart-card,
    .dashboard-page .service-card,
    .dashboard-page .ranking-card {
        padding: 1.2rem;
        height: 100%;
    }

    .dashboard-page .chart-stage {
        height: 320px;
        position: relative;
    }

    .dashboard-page .service-list,
    .dashboard-page .masseuse-list {
        display: flex;
        flex-direction: column;
        gap: 0.95rem;
    }

    .dashboard-page .service-item,
    .dashboard-page .masseuse-item {
        padding: 0.85rem 0.9rem;
        border-radius: 1.15rem;
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(242, 248, 255, 0.94));
        border: 1px solid rgba(25, 95, 155, 0.08);
    }

    .dashboard-page .service-row {
        display: flex;
        justify-content: space-between;
        gap: 0.7rem;
        align-items: center;
        margin-bottom: 0.5rem;
    }

    .dashboard-page .service-name,
    .dashboard-page .masseuse-name {
        font-weight: 700;
        color: var(--dash-ink);
    }

    .dashboard-page .service-meta,
    .dashboard-page .masseuse-meta {
        font-size: 0.8rem;
        color: var(--dash-muted);
    }

    .dashboard-page .service-progress {
        height: 0.5rem;
        border-radius: 999px;
        background: #eaf2f9;
        overflow: hidden;
    }

    .dashboard-page .service-progress-bar {
        height: 100%;
        border-radius: inherit;
        background: linear-gradient(90deg, #2d8ff0, #14b89a);
    }

    .dashboard-page .masseuse-item {
        display: flex;
        align-items: center;
        gap: 0.85rem;
    }

    .dashboard-page .rank-badge {
        width: 2rem;
        height: 2rem;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.82rem;
        font-weight: 700;
        color: #ffffff;
        background: linear-gradient(135deg, #1f73e0, #14b89a);
        flex-shrink: 0;
    }

    .dashboard-page .masseuse-avatar {
        width: 3rem;
        height: 3rem;
        border-radius: 999px;
        object-fit: cover;
        border: 2px solid rgba(31, 115, 224, 0.12);
        flex-shrink: 0;
    }

    .dashboard-page .masseuse-copy {
        min-width: 0;
        flex: 1;
    }

    .dashboard-page .masseuse-amount {
        text-align: right;
        color: var(--dash-navy);
        font-weight: 700;
        flex-shrink: 0;
    }

    .dashboard-page .soft-tag {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.42rem 0.72rem;
        border-radius: 999px;
        background: rgba(31, 115, 224, 0.08);
        color: #1b5fae;
        font-size: 0.78rem;
        font-weight: 700;
    }

    .dashboard-page .empty-state {
        border: 1px dashed rgba(31, 115, 224, 0.16);
        border-radius: 1.2rem;
        padding: 1.4rem 1rem;
        text-align: center;
        color: var(--dash-muted);
        background: rgba(251, 253, 255, 0.82);
    }

    @media (max-width: 991.98px) {
        .dashboard-page .hero-metrics {
            margin-top: 0.8rem;
        }

        .dashboard-page .focus-grid {
            grid-template-columns: 1fr 1fr;
        }

        .dashboard-page .report-shell {
            grid-template-columns: 1fr 1fr;
        }

        .dashboard-page .chart-stage {
            height: 280px;
        }
    }

    @media (max-width: 575.98px) {
        .dashboard-page .hero-body,
        .dashboard-page .spotlight-card,
        .dashboard-page .chart-card,
        .dashboard-page .service-card,
        .dashboard-page .ranking-card {
            padding: 1rem;
        }

        .dashboard-page .hero-metrics {
            grid-template-columns: 1fr;
        }

        .dashboard-page .focus-shell,
        .dashboard-page .report-card {
            padding: 1rem;
        }

        .dashboard-page .focus-grid {
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
        }

        .dashboard-page .mini-stat-card {
            padding: 1rem 0.9rem;
        }

        .dashboard-page .mini-stat-icon {
            width: 2.35rem;
            height: 2.35rem;
        }

        .dashboard-page .report-shell {
            grid-template-columns: 1fr;
        }

        .dashboard-page .section-head {
            flex-direction: column;
            align-items: flex-start;
        }

        .dashboard-page .range-shell {
            width: 100%;
            border-radius: 1rem;
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            align-items: stretch;
            gap: 0.25rem;
            padding: 0.25rem;
        }

        .dashboard-page .range-btn {
            min-width: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            min-height: 1.1rem;
            padding: 0.45rem 0.45rem;
            font-size: 0.76rem;
            line-height: 1.15;
            white-space: nowrap;
        }

        .dashboard-page .chart-stage {
            height: 240px;
        }
    }
</style>
@endpush

@section('content')
@php
    $isShopOwnerDashboard = (string) (auth()->user()->role ?? '') === 'shop_owner';
@endphp
<div class="dashboard-page">
    <div class="row g-3 g-xl-4">
        @if(!$isShopOwnerDashboard)
        <div class="col-12">
            <section class="dash-card focus-shell">
                <div class="focus-grid">
                    <article class="mini-stat-card">
                        <div class="mini-stat-head">
                            <div class="mini-stat-label">ลูกค้าวันนี้</div>
                            <span class="mini-stat-icon is-clients"><i class="fa-solid fa-users"></i></span>
                        </div>
                        <div class="mini-stat-value">{{ number_format($todayClients) }} คน</div>
                        @php
                            $trendClass = 'is-flat';
                            $trendIcon = 'fa-solid fa-minus';
                            $trendText = $stats['client_trend']['text'] ?? '0% จากเมื่อวาน';
                            if (($stats['client_trend']['class'] ?? '') === 'text-success') {
                                $trendClass = 'is-up';
                                $trendIcon = 'fa-solid fa-arrow-trend-up';
                            } elseif (($stats['client_trend']['class'] ?? '') === 'text-danger') {
                                $trendClass = 'is-down';
                                $trendIcon = 'fa-solid fa-arrow-trend-down';
                            }
                        @endphp
                        <div class="mini-stat-meta">
                            <span class="trend-pill {{ $trendClass }}"><i class="{{ $trendIcon }}"></i>{{ $trendText }}</span>
                        </div>
                    </article>

                    <article class="mini-stat-card">
                        <div class="mini-stat-head">
                            <div class="mini-stat-label">บิลวันนี้</div>
                            <span class="mini-stat-icon is-orders"><i class="fa-solid fa-receipt"></i></span>
                        </div>
                        <div class="mini-stat-value">{{ number_format($todayOrders) }}</div>
                        <div class="mini-stat-meta">
                            <span>อัปเดต {{ $stats['last_sync'] ?? '-' }}</span>
                        </div>
                    </article>

                    <article class="mini-stat-card">
                        <div class="mini-stat-head">
                            <div class="mini-stat-label">ลูกค้าใหม่วันนี้</div>
                            <span class="mini-stat-icon" style="background: rgba(46, 204, 113, 0.15); color: #27ae60;"><i class="fa-solid fa-user-plus"></i></span>
                        </div>
                        <div class="mini-stat-value">{{ number_format($stats['new_customers_today'] ?? 0) }} คน</div>
                        <div class="mini-stat-meta">
                            <span class="trend-pill is-up"><i class="fa-solid fa-star"></i> ลูกค้าใหม่ที่มาใช้บริการ</span>
                        </div>
                    </article>

                    <article class="mini-stat-card">
                        <div class="mini-stat-head">
                            <div class="mini-stat-label">ลูกค้าเก่าวันนี้</div>
                            <span class="mini-stat-icon" style="background: rgba(52, 152, 219, 0.15); color: #2980b9;"><i class="fa-solid fa-user-clock"></i></span>
                        </div>
                        <div class="mini-stat-value">{{ number_format($stats['old_customers_today'] ?? 0) }} คน</div>
                        <div class="mini-stat-meta">
                            <span class="trend-pill is-flat"><i class="fa-solid fa-rotate-right"></i> ลูกค้าเก่ากลับมาใช้บริการ</span>
                        </div>
                    </article>
                </div>
            </section>
        </div>

        <div class="col-12">
            <section class="chart-card report-card">
                <div class="section-head">
                    <div>
                        <h2 class="section-title">รายงานยอดขาย</h2>
                        <div class="section-note"></div>
                    </div>
                    <div class="range-shell" role="group" aria-label="ช่วงเวลารายงานยอดขาย">
                        @foreach($rangeButtons as $button)
                        <a
                            href="{{ route('dashboard', array_filter(['range' => $button['value'], 'branch_id' => request('branch_id')])) }}"
                            class="range-btn {{ $selectedRange === $button['value'] ? 'is-active' : '' }}"
                        >
                            {{ $button['label'] }}
                        </a>
                        @endforeach
                    </div>
                </div>

                <div class="report-shell">
                    <article class="report-metric">
                        <div class="report-metric-top">
                            <div class="report-metric-label">{{ $selectedRangeLabel }}</div>
                            <span class="report-metric-icon"><i class="fa-solid fa-bolt"></i></span>
                        </div>
                        <div class="report-metric-value">{{ number_format((int) ($stats['selected_range_sales'] ?? 0)) }} บ.</div>
                        <div class="report-metric-note">ยอดขายของช่วงเวลาที่เลือกจากสาขาปัจจุบัน</div>
                    </article>

                    <article class="report-metric">
                        <div class="report-metric-top">
                            <div class="report-metric-label">รายเดือน</div>
                            <span class="report-metric-icon"><i class="fa-solid fa-calendar"></i></span>
                        </div>
                        <div class="report-metric-value">{{ number_format($monthlySales) }} บ.</div>
                        <div class="report-metric-note">ยอดสะสมตั้งแต่ต้นเดือนจากบิลที่ชำระแล้ว</div>
                    </article>
                    
                    <article class="report-metric" style="background: linear-gradient(145deg, rgba(255, 230, 100, 0.78), rgba(255, 170, 0, 0.72));">
                        <div class="report-metric-top">
                            <div class="report-metric-label" style="color: #6d4c00;">กำไรสุทธิ (เดือนนี้)</div>
                            <span class="report-metric-icon" style="background: #e69d00;"><i class="fa-solid fa-piggy-bank"></i></span>
                        </div>
                        <div class="report-metric-value" style="color: #4a3400;">{{ number_format($stats['net_profit'] ?? 0) }} บ.</div>
                        <div class="report-metric-note" style="color: #8c6200;">หักค่ามือจากยอดขายรายเดือนแล้ว</div>
                    </article>

                    <article class="report-metric" style="background: linear-gradient(145deg, rgba(160, 100, 255, 0.78), rgba(100, 50, 200, 0.72));">
                        <div class="report-metric-top">
                            <div class="report-metric-label" style="color: #3b1b68;">สัดส่วน บริการ/แพ็กเกจ</div>
                            <span class="report-metric-icon" style="background: #5b28a2;"><i class="fa-solid fa-chart-pie"></i></span>
                        </div>
                        <div class="report-metric-value" style="color: #250b4a; font-size: 1.5rem;">
                            @php
                                $totalComb = ($stats['today_total_combined_sales'] ?? 0) > 0 ? $stats['today_total_combined_sales'] : 1;
                                $srvPct = round((($stats['today_service_sales'] ?? 0) / $totalComb) * 100);
                                $pkgPct = 100 - $srvPct;
                            @endphp
                            {{ $srvPct }}% / {{ $pkgPct }}%
                        </div>
                        <div class="report-metric-note" style="color: #4b2385;">วันนี้: บ. {{ number_format($stats['today_service_sales'] ?? 0) }} / พ. {{ number_format($stats['today_package_sales'] ?? 0) }}</div>
                    </article>
                </div>

                <div class="report-meta-row">
                    <span class="report-meta-pill"><i class="fa-regular fa-clock"></i> อัปเดต {{ $stats['last_sync'] ?? '-' }}</span>
                    <span class="report-meta-pill"><i class="fa-solid fa-chart-line"></i> ช่วงที่เลือก {{ $selectedRangeLabel }}</span>
                </div>
            </section>
        </div>

        <div class="col-12 col-xl-8">
            <section class="chart-card">
                <div class="section-head">
                    <div>
                        <h2 class="section-title">แนวโน้มยอดขายรายสัปดาห์</h2>
                        <div class="section-note">ส่วนลึกสำหรับดูจังหวะของยอดขายหลังจากเช็กตัวเลขหลักด้านบนแล้ว</div>
                    </div>
                    <span class="soft-tag"><i class="fa-solid fa-chart-area"></i> 7 วันล่าสุด</span>
                </div>

                <div class="chart-stage">
                    <canvas id="salesChart"></canvas>
                </div>
            </section>
        </div>

        <div class="col-12 col-xl-4">
            <section class="ranking-card">
                <div class="section-head">
                    <div>
                        <h2 class="section-title">หมอนวดเด่นวันนี้</h2>
                        <div class="section-note">เรียงตามยอดขายบริการที่ทำได้ในวันนี้</div>
                    </div>
                </div>

                @if(count($topMasseuses) > 0)
                <div class="masseuse-list">
                    @foreach($topMasseuses as $index => $masseuse)
                    <div class="masseuse-item">
                        <span class="rank-badge">{{ $index + 1 }}</span>
                        <img src="{{ $masseuse['avatar'] }}" alt="{{ $masseuse['name'] }}" class="masseuse-avatar">
                        <div class="masseuse-copy">
                            <div class="masseuse-name">{{ $masseuse['name'] }}</div>
                            <div class="masseuse-meta">{{ number_format($masseuse['queue_count']) }} คิวในวันนี้</div>
                        </div>
                        <div class="masseuse-amount">{{ number_format($masseuse['amount']) }} ฿</div>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="empty-state">
                    ยังไม่มีข้อมูลหมอนวดเด่นสำหรับวันนี้
                </div>
                @endif
            </section>
        </div>

        <div class="col-12 col-xl-5">
            <section class="service-card">
                <div class="section-head">
                    <div>
                        <h2 class="section-title">บริการยอดฮิต</h2>
                        <div class="section-note">สัดส่วนของบริการที่ถูกซื้อบ่อยในเดือนนี้</div>
                    </div>
                </div>

                @if(count($topServices) > 0)
                <div class="service-list">
                    @foreach($topServices as $service)
                    <div class="service-item">
                        <div class="service-row">
                            <div>
                                <div class="service-name">{{ $service['name'] }}</div>
                                <div class="service-meta">ราคาเฉลี่ย {{ number_format((int) ($service['price'] ?? 0)) }} ฿</div>
                            </div>
                            <div class="service-meta">{{ number_format((int) ($service['count'] ?? 0)) }} ครั้ง</div>
                        </div>
                        <div class="service-progress">
                            <div class="service-progress-bar" style="width: {{ (int) ($service['percent'] ?? 0) }}%"></div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="empty-state">
                    ยังไม่มีข้อมูลบริการยอดฮิตในเดือนนี้
                </div>
                @endif
            </section>
        </div>

        @endif

        <div class="col-12 {{ $isShopOwnerDashboard ? '' : 'col-xl-7' }}">
            <section class="service-card">
                <div class="section-head">
                    <div>
                        <h2 class="section-title">ภาพรวมค่ามือและยอดขาย</h2>
                        <div class="section-note">ดูสมดุลระหว่างยอดที่เข้าร้านกับต้นทุนค่ามือหมอนวด</div>
                    </div>
                </div>

                <div class="row g-3">
                    {{-- === กลุ่มวันนี้ === --}}
                    <div class="col-12">
                        <div class="service-name" style="font-size:0.85rem; color:var(--dash-muted); margin-bottom:0.25rem;"><i class="fa-solid fa-sun" style="margin-right:0.35rem;"></i>สรุปวันนี้</div>
                    </div>
                    <div class="col-12 col-md-3">
                        <div class="service-item h-100" style="border-color: rgba(31, 115, 224, 0.2);">
                            <div class="service-row mb-2">
                                <div>
                                    <div class="service-name">รวมนวด+แพคเกจ</div>
                                    <div class="service-meta">ยอดใช้งานรวมทั้งหมดวันนี้</div>
                                </div>
                                <span class="soft-tag"><i class="fa-solid fa-layer-group"></i> วันนี้</span>
                            </div>
                            <div class="spotlight-value mb-0" style="font-size: clamp(1.5rem, 1.15rem + 1vw, 2.3rem); color: #1b5fae;">{{ number_format($stats['today_total_combined_sales'] ?? 0) }} ฿</div>
                        </div>
                    </div>
                    <div class="col-12 col-md-3">
                        <div class="service-item h-100">
                            <div class="service-row mb-2">
                                <div>
                                    <div class="service-name">ยอดใช้บริการวันนี้</div>
                                    <div class="service-meta">ยอดขายบริการ (service) วันนี้</div>
                                </div>
                                <span class="soft-tag"><i class="fa-solid fa-spa"></i> วันนี้</span>
                            </div>
                            <div class="spotlight-value mb-0" style="font-size: clamp(1.5rem, 1.15rem + 1vw, 2.3rem);">{{ number_format($todayServiceSales) }} ฿</div>
                        </div>
                    </div>
                    <div class="col-12 col-md-3">
                        <div class="service-item h-100">
                            <div class="service-row mb-2">
                                <div>
                                    <div class="service-name">ยอดขายแพคเกจวันนี้</div>
                                    <div class="service-meta">ยอดขายแพคเกจวันนี้</div>
                                </div>
                                <span class="soft-tag"><i class="fa-solid fa-box-open"></i> วันนี้</span>
                            </div>
                            <div class="spotlight-value mb-0" style="font-size: clamp(1.5rem, 1.15rem + 1vw, 2.3rem);">{{ number_format($todayPackageSales) }} ฿</div>
                        </div>
                    </div>
                    <div class="col-12 col-md-3">
                        <div class="service-item h-100">
                            <div class="service-row mb-2">
                                <div>
                                    <div class="service-name">ค่ามือวันนี้</div>
                                    <div class="service-meta">คอมมิชชั่นที่จ่ายให้หมอนวด</div>
                                </div>
                                <span class="soft-tag"><i class="fa-solid fa-hand-holding-dollar"></i> วันนี้</span>
                            </div>
                            <div class="spotlight-value mb-0" style="font-size: clamp(1.5rem, 1.15rem + 1vw, 2.3rem);">{{ number_format($dailyMasseuseFee) }} ฿</div>
                        </div>
                    </div>

                    {{-- === กลุ่มเดือนนี้ === --}}
                    <div class="col-12" style="margin-top:0.6rem;">
                        <div class="service-name mt-4" style="font-size:0.85rem; color:var(--dash-muted); margin-bottom:0.25rem;"><i class="fa-solid fa-calendar-days" style="margin-right:0.35rem;"></i>สรุปเดือนนี้</div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="service-item h-100">
                            <div class="service-row mb-2">
                                <div>
                                    <div class="service-name">ยอดขายรวมเดือนนี้</div>
                                    <div class="service-meta">สะสมตั้งแต่ต้นเดือน</div>
                                </div>
                                <span class="soft-tag"><i class="fa-solid fa-chart-line"></i> เดือนนี้</span>
                            </div>
                            <div class="spotlight-value mb-0" style="font-size: clamp(1.5rem, 1.15rem + 1vw, 2.3rem);">{{ number_format($monthlySales) }} ฿</div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="service-item h-100">
                            <div class="service-row mb-2">
                                <div>
                                    <div class="service-name">ค่ามือเดือนนี้</div>
                                    <div class="service-meta">สะสมตั้งแต่ต้นเดือน</div>
                                </div>
                                <span class="soft-tag"><i class="fa-solid fa-sack-dollar"></i> เดือนนี้</span>
                            </div>
                            <div class="spotlight-value mb-0" style="font-size: clamp(1.5rem, 1.15rem + 1vw, 2.3rem);">{{ number_format($monthlyMasseuseFee) }} ฿</div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="service-item h-100" style="border-color: {{ $netProfit >= 0 ? 'rgba(20,184,154,0.25)' : 'rgba(220,83,102,0.25)' }};">
                            <div class="service-row mb-2">
                                <div>
                                    <div class="service-name">กำไรสุทธิ</div>
                                    <div class="service-meta">ยอดขาย - ค่ามือหมอ</div>
                                </div>
                                <span class="soft-tag" style="background:{{ $netProfit >= 0 ? 'rgba(20,184,154,0.12)' : 'rgba(220,83,102,0.12)' }}; color:{{ $netProfit >= 0 ? '#118671' : '#be4255' }};">
                                    <i class="fa-solid {{ $netProfit >= 0 ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down' }}"></i> เดือนนี้
                                </span>
                            </div>
                            <div class="spotlight-value mb-0" style="font-size: clamp(1.5rem, 1.15rem + 1vw, 2.3rem); color:{{ $netProfit >= 0 ? '#118671' : '#be4255' }};">{{ number_format($netProfit) }} ฿</div>
                        </div>
                    </div>
                </div>
                </div>
            </section>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <section class="service-card mb-4">
                <div class="section-head">
                    <div>
                        <h2 class="section-title"><i class="fa-solid fa-hand-holding-dollar text-primary me-2"></i> สรุปรายได้หมอนวด (วันนี้)</h2>
                        <div class="section-note">แสดงค่าคอมมิชชั่น ทิป ยอดเงินประกัน และส่วนต่างที่ร้านต้องจ่าย (Top-up)</div>
                    </div>
                </div>
                
                @php $masseuses = $stats['masseuses'] ?? []; @endphp
                @if(count($masseuses) > 0)
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light text-muted" style="font-size: 0.85rem;">
                            <tr>
                                <th class="ps-4">หมอนวด</th>
                                <th class="text-end">คิว (วันนี้)</th>
                                <th class="text-end">คอมมิชชั่น</th>
                                <th class="text-end">เงินทิป (100%)</th>
                                <th class="text-end">ยอดประกัน</th>
                                <th class="text-end text-danger">ร้านจ่ายเพิ่ม (Top-up)</th>
                                <th class="text-end text-success pe-4">รายได้รวมวันนี้</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($masseuses as $m)
                            <tr>
                                <td class="ps-4 fw-bold">
                                    {{ $m['name'] }}
                                </td>
                                <td class="text-end">{{ number_format($m['today_queue_count']) }}</td>
                                <td class="text-end">{{ number_format($m['today_commission']) }} ฿</td>
                                <td class="text-end">{{ number_format($m['today_tip']) }} ฿</td>
                                <td class="text-end">{{ number_format($m['today_base_salary']) }} ฿</td>
                                <td class="text-end text-danger fw-bold">{{ number_format($m['today_top_up']) }} ฿</td>
                                <td class="text-end text-success fw-bold pe-4 fs-5">{{ number_format($m['today_total_wage']) }} ฿</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="empty-state">
                    ยังไม่มีข้อมูลรายได้หมอนวดสำหรับวันนี้
                </div>
                @endif
            </section>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    (function () {
        const canvas = document.getElementById('salesChart');
        if (!canvas) return;

        const ctx = canvas.getContext('2d');
        if (!ctx) return;

        const salesChartLabels = @json($salesChart['labels'] ?? []);
        const salesChartData = @json($salesChart['data'] ?? []);
        const labels = salesChartLabels.length ? salesChartLabels : ['-', '-', '-', '-', '-', '-', '-'];
        const data = salesChartData.length ? salesChartData : [0, 0, 0, 0, 0, 0, 0];
        const gradient = ctx.createLinearGradient(0, 0, 0, 320);
        gradient.addColorStop(0, 'rgba(31, 115, 224, 0.30)');
        gradient.addColorStop(0.65, 'rgba(31, 115, 224, 0.10)');
        gradient.addColorStop(1, 'rgba(20, 184, 154, 0.04)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'ยอดขาย (บาท)',
                    data: data,
                    borderColor: '#1f73e0',
                    backgroundColor: gradient,
                    fill: true,
                    tension: 0.42,
                    pointRadius: 4,
                    pointHoverRadius: 5,
                    pointBorderWidth: 2,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: '#1f73e0',
                    borderWidth: 3,
                }]
            },
            options: {
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        displayColors: false,
                        backgroundColor: 'rgba(18, 59, 99, 0.94)',
                        titleColor: '#ffffff',
                        bodyColor: '#dcecff',
                        padding: 12,
                        cornerRadius: 12,
                        callbacks: {
                            label: function (context) {
                                const value = Number(context.parsed.y || 0);
                                return 'ยอดขาย ' + value.toLocaleString('th-TH') + ' บาท';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            color: '#64819d',
                            font: {
                                family: 'Prompt',
                                size: 12,
                                weight: '600'
                            }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(30, 90, 150, 0.10)',
                            drawBorder: false
                        },
                        ticks: {
                            color: '#64819d',
                            padding: 10,
                            font: {
                                family: 'Prompt',
                                size: 12
                            },
                            callback: function (value) {
                                return Number(value).toLocaleString('th-TH');
                            }
                        }
                    }
                }
            }
        });
    })();
</script>
@endpush
