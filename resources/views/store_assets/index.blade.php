@extends('layouts.main')

@section('title', 'จัดการอุปกรณ์/ของใช้ภายในร้าน - PlayFlow POS')

@push('head')
<style>
    .assets-page {
        --asset-primary: #0072ff;
        --asset-secondary: #00c6ff;
        --asset-text: #2d3748;
        --asset-muted: #718096;
        --asset-bg: #f8fafc;
        --asset-card-bg: rgba(255, 255, 255, 0.95);
        --asset-border: rgba(0,0,0,0.06);
    }
    
    .assets-header {
        background: linear-gradient(135deg, var(--asset-primary) 0%, var(--asset-secondary) 100%);
        border-radius: 1.5rem;
        padding: 2.5rem 2rem;
        color: white;
        box-shadow: 0 15px 35px rgba(0, 114, 255, 0.2);
        margin-bottom: 2rem;
        position: relative;
        overflow: hidden;
    }
    
    .assets-title {
        font-weight: 800;
        font-size: 2rem;
        letter-spacing: -0.5px;
        margin-bottom: 0.5rem;
    }
    
    .assets-card {
        background: var(--asset-card-bg);
        border-radius: 1.5rem;
        border: 1px solid var(--asset-border);
        box-shadow: 0 10px 40px rgba(0,0,0,0.03);
        padding: 2rem;
        height: 100%;
    }

    .asset-item {
        padding: 1.25rem;
        border-radius: 1rem;
        border: 1px solid var(--asset-border);
        margin-bottom: 1rem;
        background: white;
        transition: all 0.2s;
    }

    .asset-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        border-color: var(--asset-secondary);
    }
    
    .asset-name {
        font-weight: 700;
        color: var(--asset-text);
        font-size: 1.1rem;
    }
    
    .asset-stock {
        font-size: 1.5rem;
        font-weight: 800;
        color: var(--asset-primary);
    }
    
    .asset-unit {
        color: var(--asset-muted);
        font-size: 0.85rem;
        font-weight: 600;
        text-transform: uppercase;
    }
    
    .btn-action {
        border-radius: 0.75rem;
        padding: 0.5rem 1rem;
        font-weight: 600;
        font-size: 0.85rem;
    }
    
    .transaction-list {
        max-height: 500px;
        overflow-y: auto;
    }
    
    .transaction-item {
        padding: 1rem 0;
        border-bottom: 1px solid var(--asset-border);
    }
    
    .transaction-item:last-child {
        border-bottom: none;
    }
    
    .badge-in { background: rgba(16, 185, 129, 0.15); color: #10b981; }
    .badge-out { background: rgba(245, 158, 11, 0.15); color: #f59e0b; }
    .badge-loss { background: rgba(239, 68, 68, 0.15); color: #ef4444; }
</style>
@endpush

@section('content')
<div class="row g-4 assets-page">
    
    <div class="col-12">
        <div class="assets-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <h1 class="assets-title"><i class="bi bi-box-seam me-3"></i> อุปกรณ์/ของใช้ภายในร้าน</h1>
                <div class="opacity-75">
                    <i class="bi bi-info-circle me-2"></i> จัดการสต็อกของใช้ เช่น ผ้าเช็ดตัว, น้ำมันนวด, สบู่ ที่ไม่ได้นำมาขาย
                </div>
            </div>
            <button class="btn btn-light rounded-pill px-4 py-2 fw-bold text-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#addAssetModal">
                <i class="bi bi-plus-lg me-2"></i> เพิ่มของใช้ใหม่
            </button>
        </div>
    </div>

    @if(session('success'))
    <div class="col-12">
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-4 d-flex align-items-center mb-0">
            <i class="bi bi-check-circle-fill fs-4 me-3"></i>
            <div class="fw-medium">{{ session('success') }}</div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
    </div>
    @endif
    
    @if(session('error'))
    <div class="col-12">
        <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-4 d-flex align-items-center mb-0">
            <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i>
            <div class="fw-medium">{{ session('error') }}</div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
    </div>
    @endif
    
    <!-- รายการของใช้ -->
    <div class="col-lg-8">
        <div class="assets-card">
            <h4 class="fw-bold mb-4">รายการของใช้ในสต็อก</h4>
            
            <div class="row g-3">
                @forelse($assets as $asset)
                <div class="col-md-6">
                    <div class="asset-item d-flex justify-content-between align-items-center">
                        <div>
                            <div class="asset-name">{{ $asset->name }}</div>
                            <div class="d-flex align-items-baseline gap-1 mt-1">
                                <span class="asset-stock">{{ number_format($asset->qty, 2) }}</span>
                                <span class="asset-unit">{{ $asset->unit }}</span>
                            </div>
                        </div>
                        <button class="btn btn-light btn-action text-primary border" onclick="openAdjustModal({{ $asset->id }}, '{{ $asset->name }}', '{{ $asset->unit }}')">
                            ปรับปรุง
                        </button>
                    </div>
                </div>
                @empty
                <div class="col-12 text-center py-5 text-muted">
                    <i class="bi bi-box fs-1 mb-3 d-block"></i>
                    <p>ยังไม่มีรายการของใช้</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>
    
    <!-- ประวัติการเคลื่อนไหว -->
    <div class="col-lg-4">
        <div class="assets-card">
            <h5 class="fw-bold mb-4">ประวัติการเคลื่อนไหว</h5>
            <div class="transaction-list pe-2">
                @forelse($transactions as $tx)
                <div class="transaction-item">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="fw-bold">{{ $tx->asset_name }}</span>
                        @if($tx->transaction_type == 'in')
                            <span class="badge badge-in rounded-pill px-2 py-1">นำเข้า</span>
                        @elseif($tx->transaction_type == 'out')
                            <span class="badge badge-out rounded-pill px-2 py-1">เบิกใช้</span>
                        @else
                            <span class="badge badge-loss rounded-pill px-2 py-1">สูญหาย/ชำรุด</span>
                        @endif
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-2">
                        <div class="small text-muted">
                            <i class="bi bi-clock me-1"></i> {{ \Carbon\Carbon::parse($tx->created_at)->format('d/m/y H:i') }}
                        </div>
                        <div class="fw-bold" style="color: {{ $tx->transaction_type == 'in' ? '#10b981' : '#ef4444' }};">
                            {{ $tx->transaction_type == 'in' ? '+' : '-' }}{{ number_format($tx->quantity, 2) }}
                        </div>
                    </div>
                    @if($tx->note)
                    <div class="small text-muted mt-1 fst-italic">หมายเหตุ: {{ $tx->note }}</div>
                    @endif
                </div>
                @empty
                <div class="text-center py-4 text-muted small">
                    ไม่มีประวัติการเคลื่อนไหว
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<!-- Add Asset Modal -->
<div class="modal fade custom-modal" id="addAssetModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 1.5rem; border: none;">
            <div class="modal-header" style="background: linear-gradient(135deg, var(--asset-primary), var(--asset-secondary)); color: white; border-bottom: none; padding: 1.5rem;">
                <h5 class="modal-title fw-bold">เพิ่มอุปกรณ์/ของใช้ใหม่</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('store-assets.store') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">ชื่อของใช้</label>
                        <input type="text" name="name" class="form-control" required placeholder="เช่น ผ้าเช็ดตัวไซส์ L">
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label fw-bold">จำนวนตั้งต้น</label>
                            <input type="number" name="stock_quantity" class="form-control" required min="0" step="0.01" value="0">
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label fw-bold">หน่วยนับ</label>
                            <input type="text" name="unit" class="form-control" required placeholder="เช่น ผืน, ขวด, กรัม">
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid #f1f5f9; padding: 1.25rem;">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4" style="background: var(--asset-primary); border: none;">บันทึก</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Adjust Stock Modal -->
<div class="modal fade custom-modal" id="adjustStockModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 1.5rem; border: none;">
            <div class="modal-header" style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 1.5rem;">
                <h5 class="modal-title fw-bold text-dark">ปรับปรุงสต็อก: <span id="adjust-asset-name" class="text-primary"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="adjustForm" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-4">
                        <label class="form-label fw-bold">ประเภทการปรับปรุง</label>
                        <select name="transaction_type" class="form-select" required>
                            <option value="in">นำเข้าสต็อก (+)</option>
                            <option value="out">เบิกใช้งาน (-)</option>
                            <option value="loss">สูญหาย / ชำรุด (-)</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-bold">จำนวน <span id="adjust-unit" class="badge bg-light text-dark ms-2 border"></span></label>
                        <input type="number" name="quantity" class="form-control form-control-lg text-center" required min="0.01" step="0.01">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">หมายเหตุ</label>
                        <input type="text" name="note" class="form-control" placeholder="ระบุสาเหตุการเบิก/นำเข้า">
                    </div>
                </div>
                <div class="modal-footer" style="border-top: 1px solid #f1f5f9; padding: 1.25rem;">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">ยืนยัน</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function openAdjustModal(id, name, unit) {
        document.getElementById('adjust-asset-name').textContent = name;
        document.getElementById('adjust-unit').textContent = unit;
        document.getElementById('adjustForm').action = `/store-assets/${id}/adjust`;
        var myModal = new bootstrap.Modal(document.getElementById('adjustStockModal'));
        myModal.show();
    }
</script>
@endpush
@endsection
