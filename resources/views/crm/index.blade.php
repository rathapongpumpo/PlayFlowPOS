@extends('layouts.main')

@section('title', 'CRM Analytics - PlayFlow POS')
@section('page_title', 'วิเคราะห์ข้อมูลลูกค้า (CRM)')
@section('page_subtitle', 'สุขุมวิท | Manager')

@section('content')
<div class="row g-4">
    <!-- Summary Cards -->
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-4 d-flex align-items-center">
                <div class="bg-primary-subtle text-primary rounded-circle d-flex justify-content-center align-items-center me-3" style="width: 56px; height: 56px;">
                    <i class="bi bi-people-fill fs-3"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-1 fw-bold small">ลูกค้าทั้งหมด</h6>
                    <h3 class="fw-bold mb-0 text-dark">{{ number_format($totalCustomers) }}</h3>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-4 d-flex align-items-center">
                <div class="bg-success-subtle text-success rounded-circle d-flex justify-content-center align-items-center me-3" style="width: 56px; height: 56px;">
                    <i class="bi bi-person-check-fill fs-3"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-1 fw-bold small">ลูกค้าประจำ (3 เดือนล่าสุด)</h6>
                    <h3 class="fw-bold mb-0 text-dark">{{ number_format($activeCustomers) }}</h3>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-4 d-flex align-items-center">
                <div class="bg-warning-subtle text-warning rounded-circle d-flex justify-content-center align-items-center me-3" style="width: 56px; height: 56px;">
                    <i class="bi bi-star-fill fs-3"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-1 fw-bold small">แต้มสะสมที่แจกไป</h6>
                    <h3 class="fw-bold mb-0 text-dark">{{ number_format($totalPointsIssued) }}</h3>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-4 d-flex align-items-center">
                <div class="bg-danger-subtle text-danger rounded-circle d-flex justify-content-center align-items-center me-3" style="width: 56px; height: 56px;">
                    <i class="bi bi-gift-fill fs-3"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-1 fw-bold small">แต้มสะสมที่ใช้แล้ว</h6>
                    <h3 class="fw-bold mb-0 text-dark">{{ number_format($totalPointsRedeemed) }}</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts and Tables Row -->
    <div class="col-12 col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white border-0 pt-4 pb-0 px-4">
                <h5 class="fw-bold"><i class="bi bi-trophy text-warning me-2"></i>ลูกค้าที่มียอดใช้จ่ายสูงสุด (Top Spenders)</h5>
            </div>
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table class="table table-borderless table-hover align-middle mb-0">
                        <thead class="text-muted small border-bottom border-light">
                            <tr>
                                <th class="fw-bold ps-0">อันดับ</th>
                                <th class="fw-bold">ชื่อลูกค้า</th>
                                <th class="fw-bold">ยอดใช้จ่ายรวม</th>
                                <th class="fw-bold">เข้าใช้บริการ</th>
                                <th class="fw-bold text-end pe-0">แต้มคงเหลือ</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($topSpenders as $index => $customer)
                            <tr>
                                <td class="ps-0">
                                    <div class="bg-light rounded-circle d-flex align-items-center justify-content-center fw-bold text-muted" style="width: 32px; height: 32px;">
                                        {{ $index + 1 }}
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center me-2 fw-bold" style="width: 40px; height: 40px;">
                                            {{ mb_substr($customer->name, 0, 1) }}
                                        </div>
                                        <div>
                                            <div class="fw-bold">{{ $customer->name }}</div>
                                            <div class="small text-muted">{{ $customer->phone }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="fw-bold text-success">{{ number_format($customer->total_spending, 2) }} ฿</td>
                                <td>{{ number_format($customer->visit_count) }} ครั้ง</td>
                                <td class="text-end pe-0 fw-bold text-warning">{{ number_format($customer->total_points) }} pts</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">ยังไม่มีข้อมูล</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-header bg-white border-0 pt-4 pb-0 px-4">
                <h5 class="fw-bold"><i class="bi bi-pie-chart text-info me-2"></i>สัดส่วนระดับสมาชิก</h5>
            </div>
            <div class="card-body p-4 d-flex flex-column justify-content-center">
                <ul class="list-group list-group-flush border-0">
                    @forelse($tierDistribution as $tier)
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0 border-0 mb-2 py-1">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-circle-fill text-primary me-2 small"></i>
                            <span class="fw-semibold">{{ $tier->name ?? 'ไม่มีระดับ' }}</span>
                        </div>
                        <span class="badge bg-light text-dark rounded-pill fw-bold px-3 py-2 border">{{ number_format($tier->count) }} คน</span>
                    </li>
                    @empty
                    <li class="list-group-item text-center text-muted border-0 py-4">ยังไม่มีข้อมูล</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

    <!-- Recent Feedback -->
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-0 pt-4 pb-0 px-4">
                <h5 class="fw-bold"><i class="bi bi-chat-right-quote text-primary me-2"></i>ความคิดเห็นล่าสุดจากลูกค้า</h5>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    @forelse($recentFeedback as $feedback)
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="border rounded-4 p-3 h-100 position-relative">
                            <i class="bi bi-quote fs-1 text-light position-absolute top-0 end-0 me-2 mt-1"></i>
                            <div class="d-flex align-items-center mb-2">
                                <div class="bg-light text-dark rounded-circle d-flex align-items-center justify-content-center me-2 fw-bold" style="width: 32px; height: 32px; font-size: 12px;">
                                    {{ mb_substr($feedback->customer_name, 0, 1) }}
                                </div>
                                <div>
                                    <div class="fw-bold small">{{ $feedback->customer_name }}</div>
                                    <div class="text-muted" style="font-size: 11px;">{{ \Carbon\Carbon::parse($feedback->created_at)->diffForHumans() }}</div>
                                </div>
                            </div>
                            <div class="mb-2">
                                @for($i = 1; $i <= 5; $i++)
                                    @if($i <= $feedback->rating)
                                    <i class="bi bi-star-fill text-warning small"></i>
                                    @else
                                    <i class="bi bi-star text-muted small opacity-25"></i>
                                    @endif
                                @endfor
                            </div>
                            <p class="mb-0 text-dark small" style="font-style: italic;">"{{ $feedback->comment ?? 'ให้คะแนนประเมินแต่ไม่มีความคิดเห็น' }}"</p>
                        </div>
                    </div>
                    @empty
                    <div class="col-12 text-center text-muted py-4">
                        ยังไม่มีความคิดเห็นจากลูกค้า
                    </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
