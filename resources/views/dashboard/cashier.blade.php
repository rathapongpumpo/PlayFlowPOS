@extends('layouts.main')

@section('title', 'Cashier Dashboard | PlayFlow Spa POS')
@section('page_title', 'แคชเชียร์')
@section('page_subtitle', $stats['branch_name'] ?? 'Dashboard')

@push('head')
<style>
    .cashier-dashboard {
        --cashier-blue: #2d8ff0;
        --cashier-teal: #14b89a;
        --cashier-ink: #25405c;
        --cashier-soft: #eef8fb;
    }

    .cashier-dashboard .cashier-hero,
    .cashier-dashboard .cashier-card {
        border: 1px solid rgba(45, 143, 240, 0.12);
        border-radius: 1.6rem;
        background: rgba(255, 255, 255, 0.96);
        box-shadow: 0 22px 40px rgba(37, 64, 92, 0.12);
    }

    .cashier-dashboard .cashier-hero {
        padding: 1.35rem;
        background:
            radial-gradient(circle at top right, rgba(20, 184, 154, 0.16), transparent 32%),
            linear-gradient(135deg, rgba(45, 143, 240, 0.12), rgba(20, 184, 154, 0.1)),
            rgba(255,255,255,0.96);
    }

    .cashier-dashboard .cashier-label {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.42rem 0.85rem;
        border-radius: 999px;
        background: rgba(255,255,255,0.8);
        color: var(--cashier-blue);
        font-weight: 700;
    }

    .cashier-dashboard .cashier-summary {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.9rem;
        margin-top: 1rem;
    }

    .cashier-dashboard .summary-tile {
        padding: 1rem 1.05rem;
        border-radius: 1.2rem;
        background: linear-gradient(180deg, rgba(255,255,255,0.98), rgba(238,248,251,0.98));
        border: 1px solid rgba(45, 143, 240, 0.12);
    }

    .cashier-dashboard .summary-caption {
        font-size: 0.84rem;
        font-weight: 700;
        color: #6b84a0;
        margin-bottom: 0.3rem;
    }

    .cashier-dashboard .summary-value {
        font-size: clamp(1.45rem, 3vw, 2rem);
        font-weight: 800;
        color: var(--cashier-ink);
        line-height: 1.1;
    }

    .cashier-dashboard .summary-subvalue {
        margin-top: 0.3rem;
        color: #5e7894;
        font-weight: 600;
        font-size: 0.88rem;
    }

    .cashier-dashboard .cashier-card {
        padding: 1.15rem;
    }

    .cashier-dashboard .section-title {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        margin-bottom: 0.9rem;
    }

    .cashier-dashboard .section-title h3 {
        margin: 0;
        font-size: 1.08rem;
        font-weight: 800;
        color: var(--cashier-ink);
    }

    .cashier-dashboard .section-title span {
        font-size: 0.85rem;
        font-weight: 700;
        color: #6f88a1;
    }

    .cashier-dashboard .masseuse-grid {
        display: grid;
        gap: 0.9rem;
    }

    .cashier-dashboard .masseuse-row {
        border-radius: 1.2rem;
        border: 1px solid rgba(45, 143, 240, 0.12);
        background: linear-gradient(180deg, rgba(255,255,255,1), rgba(243,250,253,0.98));
        padding: 1rem;
    }

    .cashier-dashboard .masseuse-name {
        font-size: 1rem;
        font-weight: 800;
        color: var(--cashier-ink);
        margin-bottom: 0.75rem;
    }

    .cashier-dashboard .masseuse-metrics {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.75rem;
    }

    .cashier-dashboard .metric-block {
        border-radius: 1rem;
        padding: 0.85rem;
        background: rgba(255,255,255,0.88);
        border: 1px solid rgba(45, 143, 240, 0.1);
    }

    .cashier-dashboard .metric-title {
        font-size: 0.8rem;
        font-weight: 800;
        color: #7390aa;
        margin-bottom: 0.35rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .cashier-dashboard .metric-value {
        font-size: 1.25rem;
        font-weight: 800;
        color: var(--cashier-ink);
        line-height: 1.1;
    }

    .cashier-dashboard .metric-meta {
        margin-top: 0.25rem;
        color: #63819b;
        font-size: 0.83rem;
        font-weight: 600;
    }

    .cashier-dashboard .quick-actions {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.8rem;
    }

    .cashier-dashboard .quick-link {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.55rem;
        min-height: 3.3rem;
        border-radius: 1rem;
        text-decoration: none;
        font-weight: 800;
        color: #fff;
        background: linear-gradient(135deg, var(--cashier-blue), var(--cashier-teal));
        box-shadow: 0 14px 24px rgba(45, 143, 240, 0.2);
    }

    .cashier-dashboard .empty-state {
        text-align: center;
        padding: 1.35rem 1rem;
        border-radius: 1rem;
        background: rgba(238, 248, 251, 0.9);
        color: #5c7894;
        font-weight: 700;
    }

    @media (max-width: 575.98px) {
        .cashier-dashboard .cashier-summary,
        .cashier-dashboard .masseuse-metrics,
        .cashier-dashboard .quick-actions {
            grid-template-columns: 1fr;
        }
    }
</style>
@endpush

@section('content')
<div class="cashier-dashboard row g-3">
    <div class="col-12">
        <section class="cashier-hero">
            <div class="cashier-label">
                <i class="bi bi-cash-coin"></i>
                <span>Cashier Dashboard</span>
            </div>
            <div class="cashier-summary">
                <div class="summary-tile">
                    <div class="summary-caption">ยอดขายวันนี้</div>
                    <div class="summary-value">{{ number_format((int) ($stats['today_sales'] ?? 0)) }} ฿</div>
                    <div class="summary-subvalue">{{ number_format((int) ($stats['today_orders'] ?? 0)) }} บิล</div>
                </div>
                <div class="summary-tile">
                    <div class="summary-caption">ยอดขายเมื่อวาน</div>
                    <div class="summary-value">{{ number_format((int) ($stats['yesterday_sales'] ?? 0)) }} ฿</div>
                    <div class="summary-subvalue">{{ number_format((int) ($stats['yesterday_orders'] ?? 0)) }} บิล</div>
                </div>
            </div>
        </section>
    </div>

    <div class="col-12 col-xl-8">
        <section class="cashier-card">
            <div class="section-title">
                <h3>แดชบอร์ดหมอนวด</h3>
                <span>วันนี้ / เมื่อวาน</span>
            </div>

            @if(!empty($stats['masseuses']))
            <div class="masseuse-grid">
                @foreach($stats['masseuses'] as $masseuse)
                <article class="masseuse-row">
                    <div class="masseuse-name">{{ $masseuse['name'] }}</div>
                    <div class="masseuse-metrics">
                        <div class="metric-block">
                            <div class="metric-title">วันนี้</div>
                            <div class="metric-value">{{ number_format((int) $masseuse['today_income']) }} ฿</div>
                            <div class="metric-meta">ค่ามือ {{ number_format((int) ($masseuse['today_commission'] + $masseuse['today_top_up'])) }} ฿ | {{ number_format((int) $masseuse['today_queue_count']) }} คิว</div>
                        </div>
                        <div class="metric-block">
                            <div class="metric-title">เมื่อวาน</div>
                            <div class="metric-value">{{ number_format((int) $masseuse['yesterday_income']) }} ฿</div>
                            <div class="metric-meta">ค่ามือ {{ number_format((int) ($masseuse['yesterday_commission'] + $masseuse['yesterday_top_up'])) }} ฿ | {{ number_format((int) $masseuse['yesterday_queue_count']) }} คิว</div>
                        </div>
                    </div>
                </article>
                @endforeach
            </div>
            @else
            <div class="empty-state">ยังไม่มีข้อมูลหมอนวดของสาขานี้</div>
            @endif
        </section>
    </div>

    <div class="col-12 col-xl-4">
        <section class="cashier-card">
            <div class="section-title">
                <h3>ทางลัด</h3>
                <span>เครื่องมือที่ใช้บ่อย</span>
            </div>
            <div class="quick-actions">
                <a href="{{ route('pos') }}" class="quick-link">
                    <i class="bi bi-cart4"></i>
                    <span>หน้าขาย</span>
                </a>
                <a href="{{ route('booking') }}" class="quick-link">
                    <i class="bi bi-calendar-check"></i>
                    <span>คิว</span>
                </a>
                <a href="{{ route('masseuse') }}" class="quick-link">
                    <i class="bi bi-people"></i>
                    <span>หมอนวด</span>
                </a>
            </div>
        </section>
    </div>
</div>
@endsection
