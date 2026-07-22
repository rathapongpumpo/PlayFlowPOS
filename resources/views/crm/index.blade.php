@extends('layouts.main')

@section('title', 'CRM Analytics - PlayFlow POS')
@section('page_title', 'วิเคราะห์ข้อมูลลูกค้า (CRM)')
@section('page_subtitle', 'สุขุมวิท | Manager')

@section('content')
<div class="row g-4">
    @if(session('success'))
    <div class="col-12">
        <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
    @endif

    <!-- Point Settings Banner -->
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4 bg-primary-subtle text-primary-emphasis">
            <div class="card-body p-4">
                <div class="row align-items-center g-3">
                    <div class="col-12 col-md-8">
                        <div class="d-flex align-items-center">
                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3 flex-shrink-0" style="width: 48px; height: 48px;">
                                <i class="bi bi-gear-wide-connected fs-4"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-1 text-primary"><i class="bi bi-star-fill text-warning me-1"></i>กติกาการแจกและใช้แต้มสะสมประจำสาขา</h6>
                                <p class="mb-0 small text-muted">
                                    กติกาปัจจุบัน: ยอดซื้อบริการทุกๆ <strong class="text-dark">{{ number_format($pointSettings->earn_rate_thb ?? 100) }} บาท</strong> ➔ ได้รับ <strong class="text-primary">1 แต้ม</strong> 
                                    (สะสมแต้มเมื่อซื้อขั้นต่ำ {{ number_format($pointSettings->min_spend_thb ?? 0) }} บาท | 1 แต้ม = {{ number_format($pointSettings->redeem_rate_thb ?? 1, 2) }} บาทส่วนลด)
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4 text-md-end">
                        <button class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#pointSettingsModal">
                            <i class="bi bi-sliders me-1"></i> ตั้งค่าอัตราสะสมแต้ม
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
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
                                <td class="fw-bold text-success">{{ number_format((float)($customer->total_spending ?? 0), 2) }} ฿</td>
                                <td>{{ number_format((int)($customer->visit_count ?? 0)) }} ครั้ง</td>
                                <td class="text-end pe-0 fw-bold text-warning">{{ number_format((int)($customer->total_points ?? 0)) }} pts</td>
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

<!-- Point Settings Modal -->
<div class="modal fade" id="pointSettingsModal" tabindex="-1" aria-labelledby="pointSettingsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 1.25rem; overflow: hidden;">
            <div class="modal-header border-0 bg-primary text-white p-3 p-md-4" style="background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%);">
                <h5 class="modal-title fw-bold d-flex align-items-center m-0" id="pointSettingsModalLabel">
                    <i class="bi bi-star-fill text-warning me-2"></i> 
                    ตั้งค่าและแนะนำระบบสะสมแต้ม
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('crm.point-settings.update') }}" method="POST">
                @csrf
                <div class="modal-body p-3 p-md-4 bg-light">

                    <div class="card border-0 shadow-sm rounded-4 mb-3">
                        <div class="card-body p-3">
                            <label for="earn_rate_thb" class="form-label fw-bold text-dark">อัตราการสะสมแต้ม</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-secondary">ทุกๆ</span>
                                <input type="number" step="1" min="1" name="earn_rate_thb" id="earn_rate_thb" class="form-control fw-bold text-primary text-center bg-white" value="{{ (int)($pointSettings->earn_rate_thb ?? 100) }}" required>
                                <span class="input-group-text bg-light text-secondary">บาท ➔ 1 แต้ม</span>
                            </div>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm rounded-4 mb-3">
                        <div class="card-body p-3">
                            <label for="redeem_rate_thb" class="form-label fw-bold text-dark">มูลค่าการแลกแต้ม</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-secondary">1 แต้ม =</span>
                                <input type="number" step="0.25" min="0" name="redeem_rate_thb" id="redeem_rate_thb" class="form-control fw-bold text-success text-center bg-white" value="{{ $pointSettings->redeem_rate_thb ?? 1.00 }}" required>
                                <span class="input-group-text bg-light text-secondary">บาท (ส่วนลด)</span>
                            </div>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-body p-3">
                            <label for="min_spend_thb" class="form-label fw-bold text-dark">ยอดขั้นต่ำที่จะเริ่มได้แต้ม</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-secondary"><i class="bi bi-cart3"></i></span>
                                <input type="number" step="10" min="0" name="min_spend_thb" id="min_spend_thb" class="form-control fw-bold text-center bg-white" value="{{ (int)($pointSettings->min_spend_thb ?? 0) }}" placeholder="0 = ไม่มีขั้นต่ำ">
                            </div>
                            <div class="form-text mt-2 small text-muted"><i class="bi bi-lightbulb text-warning me-1"></i>ใส่ 0 หากต้องการแจกแต้มทุกยอดบิล</div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer border-0 p-3 p-md-4 pt-1 bg-light justify-content-end">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-bold shadow-sm" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-5 fw-bold shadow-sm" style="background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%); border: none;">
                        <i class="bi bi-save me-2"></i> บันทึก
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
