@extends('layouts.main')

@section('title', 'ระบบเปิด-ปิดลิ้นชัก (ร้าน) - PlayFlow POS')

@push('head')
<style>
    .ops-page {
        --ops-primary: #8e2de2;
        --ops-secondary: #4a00e0;
        --ops-success: #00b09b;
        --ops-danger: #ff416c;
        --ops-text: #2d3748;
        --ops-muted: #718096;
        --ops-bg: #f7fafc;
        --ops-card-bg: rgba(255, 255, 255, 0.95);
    }
    
    .ops-header {
        background: linear-gradient(135deg, var(--ops-primary) 0%, var(--ops-secondary) 100%);
        border-radius: 1.5rem;
        padding: 2.5rem 2rem;
        color: white;
        box-shadow: 0 15px 35px rgba(74, 0, 224, 0.2);
        position: relative;
        overflow: hidden;
        margin-bottom: 2rem;
    }
    
    .ops-header::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 60%);
        transform: rotate(30deg);
        pointer-events: none;
    }
    
    .ops-title {
        font-weight: 800;
        font-size: 2rem;
        letter-spacing: -0.5px;
        margin-bottom: 0.5rem;
        position: relative;
        z-index: 1;
    }
    
    .ops-subtitle {
        font-size: 1rem;
        opacity: 0.9;
        position: relative;
        z-index: 1;
    }

    .ops-card {
        background: var(--ops-card-bg);
        border-radius: 1.5rem;
        border: 1px solid rgba(0,0,0,0.05);
        box-shadow: 0 10px 40px rgba(0,0,0,0.04);
        padding: 2.5rem;
        backdrop-filter: blur(20px);
        transition: transform 0.3s ease;
    }

    .ops-card:hover {
        transform: translateY(-5px);
    }
    
    .status-badge {
        display: inline-block;
        padding: 0.6rem 1.5rem;
        border-radius: 50px;
        font-weight: 700;
        font-size: 0.9rem;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    
    .status-badge.open {
        background: linear-gradient(135deg, var(--ops-success) 0%, #96c93d 100%);
        color: white;
    }
    
    .status-badge.closed {
        background: linear-gradient(135deg, var(--ops-danger) 0%, #ff4b2b 100%);
        color: white;
    }
    
    .form-control-custom {
        border: 2px solid #e2e8f0;
        border-radius: 1rem;
        padding: 1rem 1.5rem;
        font-size: 1.2rem;
        font-weight: 700;
        color: var(--ops-text);
        background: #f8fafc;
        transition: all 0.3s;
    }
    
    .form-control-custom:focus {
        background: white;
        border-color: var(--ops-primary);
        box-shadow: 0 0 0 4px rgba(142, 45, 226, 0.1);
    }
    
    .btn-custom {
        border-radius: 1rem;
        padding: 1rem 2rem;
        font-weight: 700;
        font-size: 1.1rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        transition: all 0.3s ease;
        border: none;
    }
    
    .btn-open {
        background: linear-gradient(135deg, var(--ops-success) 0%, #96c93d 100%);
        color: white;
        box-shadow: 0 10px 25px rgba(0, 176, 155, 0.3);
    }
    
    .btn-open:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 35px rgba(0, 176, 155, 0.4);
    }
    
    .btn-close-store {
        background: linear-gradient(135deg, var(--ops-danger) 0%, #ff4b2b 100%);
        color: white;
        box-shadow: 0 10px 25px rgba(255, 65, 108, 0.3);
    }
    
    .btn-close-store:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 35px rgba(255, 65, 108, 0.4);
    }
    
    .summary-box {
        background: #f8fafc;
        border-radius: 1.25rem;
        padding: 1.5rem;
        border: 1px solid #e2e8f0;
        margin-bottom: 1.5rem;
    }
    
    .summary-label {
        font-size: 0.9rem;
        color: var(--ops-muted);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 600;
        margin-bottom: 0.5rem;
    }
    
    .summary-value {
        font-size: 1.75rem;
        font-weight: 800;
        color: var(--ops-text);
    }
</style>
@endpush

@section('content')
<div class="row justify-content-center ops-page">
    <div class="col-lg-8">
        
        <div class="ops-header">
            <h1 class="ops-title"><i class="bi bi-shop me-3"></i> ระบบเปิด-ปิดลิ้นชักประจำวัน</h1>
            <div class="ops-subtitle">
                <i class="bi bi-calendar-check me-2"></i> วันที่: {{ \Carbon\Carbon::parse($today)->format('d/m/Y') }}
            </div>
        </div>

        @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-4 d-flex align-items-center mb-4" role="alert">
            <i class="bi bi-check-circle-fill fs-4 me-3"></i>
            <div class="fw-medium">{{ session('success') }}</div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
        @endif
        
        @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-4 d-flex align-items-center mb-4" role="alert">
            <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i>
            <div class="fw-medium">{{ session('error') }}</div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
        @endif

        @if(!$drawer)
            <!-- ยังไม่ได้เปิดร้าน -->
            <div class="ops-card text-center text-md-start">
                <div class="row align-items-center">
                    <div class="col-md-6 mb-4 mb-md-0 d-flex justify-content-center align-items-center">
                        <div style="background: linear-gradient(135deg, rgba(142, 45, 226, 0.1) 0%, rgba(74, 0, 224, 0.1) 100%); width: 250px; height: 250px; border-radius: 50%; display: flex; justify-content: center; align-items: center;">
                            <i class="bi bi-safe2-fill" style="font-size: 8rem; color: var(--ops-primary);"></i>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-4">
                            <span class="status-badge closed mb-3"><i class="bi bi-lock-fill me-1"></i> ร้านยังปิดอยู่</span>
                            <h3 class="fw-bold text-dark mb-2">เริ่มต้นวันใหม่</h3>
                            <p class="text-muted">กรุณาใส่จำนวนเงินทอนตั้งต้นที่มีในลิ้นชักก่อนเริ่มการขาย</p>
                        </div>
                        
                        <form action="{{ route('operations.open') }}" method="POST">
                            @csrf
                            <div class="mb-3 text-start">
                                <label class="form-label fw-bold text-muted small text-uppercase">เงินทอนตั้งต้น (บาท)</label>
                                <input type="number" name="opening_amount" class="form-control form-control-custom text-success" required min="0" step="0.01" value="0" placeholder="เช่น 2000">
                            </div>
                            <div class="mb-4 text-start">
                                <label class="form-label fw-bold text-muted small text-uppercase">หมายเหตุ (Optional)</label>
                                <input type="text" name="note" class="form-control form-control-custom" style="font-size: 1rem; font-weight: normal; padding: 0.75rem 1.25rem;" placeholder="หมายเหตุ...">
                            </div>
                            <button type="submit" class="btn btn-custom btn-open w-100" onclick="return confirm('ยืนยันยอดเงินทอนตั้งต้นตามนี้?');">
                                <i class="bi bi-unlock-fill me-2"></i> ยืนยันการเปิดร้าน
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
        @elseif($drawer && !$drawer->closed_at)
            <!-- ร้านเปิดแล้ว แต่ยังไม่ได้ปิด -->
            <div class="ops-card">
                <div class="text-center mb-5">
                    <span class="status-badge open mb-3"><i class="bi bi-unlock-fill me-1"></i> ร้านเปิดแล้ว</span>
                    <h3 class="fw-bold text-dark">สรุปยอดขายสำหรับวันนี้</h3>
                    <p class="text-muted">ตรวจสอบยอดเงินในลิ้นชัก และทำการปิดกะเมื่อหมดวัน</p>
                </div>
                
                <div class="row g-4 mb-4">
                    <div class="col-sm-4">
                        <div class="summary-box text-center h-100">
                            <div class="summary-label">เงินตั้งต้น (ยกมา)</div>
                            <div class="summary-value text-primary">{{ number_format($drawer->opening_amount, 2) }}</div>
                            <div class="text-muted small mt-1">บาท</div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="summary-box text-center h-100">
                            <div class="summary-label">ยอดขาย (เงินสด)</div>
                            <div class="summary-value text-success">{{ number_format($salesToday, 2) }}</div>
                            <div class="text-muted small mt-1">บาท</div>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="summary-box text-center h-100 position-relative" style="background: rgba(142, 45, 226, 0.05); border-color: rgba(142, 45, 226, 0.2);">
                            <div class="summary-label text-primary">ยอดเงินที่ควรมีในลิ้นชัก</div>
                            <div class="summary-value" style="color: var(--ops-primary);">{{ number_format($expectedAmount, 2) }}</div>
                            <div class="text-muted small mt-1">บาท</div>
                        </div>
                    </div>
                </div>
                
                <hr class="my-4 border-light">
                
                <form action="{{ route('operations.close') }}" method="POST">
                    @csrf
                    <input type="hidden" name="expected_amount" value="{{ $expectedAmount }}">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold text-muted small text-uppercase">นับยอดเงินจริง (บาท)</label>
                            <input type="number" name="closing_amount" id="closing_amount" class="form-control form-control-custom text-danger" required min="0" step="0.01" value="{{ $expectedAmount }}">
                            <div id="diff-text" class="form-text mt-2 fw-bold text-secondary">ยอดต่าง: 0.00 บาท</div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label fw-bold text-muted small text-uppercase">หมายเหตุการปิดร้าน</label>
                            <input type="text" name="note" class="form-control form-control-custom" style="font-size: 1rem; font-weight: normal; padding: 0.75rem 1.25rem;" placeholder="เช่น เงินขาด, เงินเกิน, หรือนำเงินไปจ่ายค่าใช้จ่ายจิปาถะ...">
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-custom btn-close-store w-100" onclick="return confirm('ยืนยันการปิดลิ้นชัก? ยอดที่นับได้จะถูกบันทึกเข้าระบบทันที');">
                        <i class="bi bi-box-arrow-down me-2"></i> ปิดร้านและสรุปยอดเงิน
                    </button>
                </form>
            </div>
            
        @else
            <!-- ร้านปิดแล้ว -->
            <div class="ops-card text-center">
                <img src="https://illustrations.popsy.co/amber/success.svg" alt="Store Closed" class="img-fluid mb-4 rounded-4" style="max-height: 200px;">
                <h3 class="fw-bold text-dark mb-2">ร้านได้ถูกปิดลงแล้วสำหรับวันนี้</h3>
                <p class="text-muted mb-4">ข้อมูลการขายและยอดเงินในลิ้นชักได้ถูกบันทึกเรียบร้อยแล้ว<br>สามารถดูรายละเอียดได้ที่รายงาน</p>
                
                <div class="row justify-content-center">
                    <div class="col-md-8">
                        <div class="summary-box">
                            <div class="row">
                                <div class="col-6 text-end pe-4 border-end">
                                    <div class="summary-label">ยอดที่ควรมี</div>
                                    <div class="fw-bold text-dark fs-4">{{ number_format($drawer->expected_amount, 2) }}</div>
                                </div>
                                <div class="col-6 text-start ps-4">
                                    <div class="summary-label">ยอดนับจริง</div>
                                    <div class="fw-bold text-primary fs-4">{{ number_format($drawer->closing_amount, 2) }}</div>
                                </div>
                            </div>
                            <hr>
                            <div class="summary-label">ยอดต่าง (Difference)</div>
                            @if($drawer->difference > 0)
                                <div class="fw-bold fs-3 text-success">+{{ number_format($drawer->difference, 2) }}</div>
                            @elseif($drawer->difference < 0)
                                <div class="fw-bold fs-3 text-danger">{{ number_format($drawer->difference, 2) }}</div>
                            @else
                                <div class="fw-bold fs-3 text-secondary">0.00</div>
                            @endif
                            
                            @if($drawer->note)
                            <div class="mt-3 p-3 bg-white rounded text-start text-muted small border">
                                <i class="bi bi-info-circle me-1"></i> <strong>หมายเหตุ:</strong> {{ $drawer->note }}
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
                
                <a href="{{ route('dashboard') }}" class="btn btn-outline-primary rounded-pill px-4 mt-3 fw-bold">
                    <i class="bi bi-arrow-left me-2"></i> กลับสู่หน้าหลัก
                </a>
            </div>
        @endif
        
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const closingInput = document.getElementById('closing_amount');
        const diffText = document.getElementById('diff-text');
        const expectedAmount = {{ $expectedAmount ?? 0 }};
        
        if (closingInput && diffText) {
            closingInput.addEventListener('input', function() {
                const closingAmount = parseFloat(this.value) || 0;
                const diff = closingAmount - expectedAmount;
                
                let textClass = 'text-secondary';
                let prefix = '';
                
                if (diff > 0) {
                    textClass = 'text-success';
                    prefix = '+';
                } else if (diff < 0) {
                    textClass = 'text-danger';
                }
                
                diffText.className = `form-text mt-2 fw-bold ${textClass}`;
                diffText.textContent = `ยอดต่าง: ${prefix}${diff.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})} บาท`;
            });
        }
    });
</script>
@endpush
@endsection
