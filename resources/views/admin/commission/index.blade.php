@extends('layouts.main')

@section('title', 'ตั้งค่าคอมมิชชัน - PlayFlow')
@section('page_title', 'ตั้งค่าคอมมิชชัน')

@push('head')
<style>
    .commission-page .card {
        border-radius: 1.05rem;
    }

    .commission-page .hero-card {
        border: 1px solid rgba(31, 115, 224, 0.16) !important;
        background: linear-gradient(165deg, #eef9ff 0%, #f6fdff 100%);
        box-shadow: 0 16px 28px rgba(18, 85, 150, 0.1) !important;
    }

    .commission-page .section-title {
        font-weight: 700;
        color: #1e5f9d;
        margin-bottom: 0.2rem;
    }

    .commission-page .section-subtitle {
        font-size: 0.82rem;
        color: #6b8ab0;
    }

    .commission-page .gradient-btn {
        background: linear-gradient(135deg, #2d8ff0, #14b89a) !important;
        border-color: #2d8ff0 !important;
        color: #ffffff !important;
        box-shadow: 0 10px 18px rgba(21, 101, 181, 0.24);
        transition: all 0.2s;
    }

    .commission-page .gradient-btn:hover {
        filter: brightness(0.96);
        transform: translateY(-1px);
    }

    .commission-page .table-card {
        border: 1px solid rgba(31, 115, 224, 0.14) !important;
        box-shadow: 0 14px 30px rgba(17, 81, 146, 0.08) !important;
    }

    .commission-page .table thead th {
        color: #1e5f9d;
        background: linear-gradient(180deg, #eef6ff 0%, #e8f3ff 100%);
        border-bottom-color: rgba(31, 115, 224, 0.15);
        font-size: 0.82rem;
        white-space: nowrap;
    }

    .commission-page .badge-type {
        border-radius: 999px;
        padding: 0.26rem 0.7rem;
        font-size: 0.74rem;
        font-weight: 700;
    }

    .commission-page .badge-service {
        color: #0c907d;
        background: rgba(20, 184, 154, 0.14);
    }

    .commission-page .badge-product {
        color: #0f65b8;
        background: rgba(45, 143, 240, 0.12);
    }

    .commission-page .badge-package {
        color: #6d59d8;
        background: rgba(129, 96, 255, 0.12);
    }

    .commission-page .badge-fixed {
        color: #d35400;
        background: rgba(230, 126, 34, 0.12);
    }

    .commission-page .badge-percent {
        color: #1e824c;
        background: rgba(30, 130, 76, 0.12);
    }

    .commission-page .icon-chip {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 1.9rem;
        height: 1.9rem;
        border-radius: 999px;
        font-size: 0.95rem;
        box-shadow: 0 2px 6px rgba(16, 76, 136, 0.18);
    }

    .commission-page .icon-chip i {
        font-size: 0.9rem;
    }

    .commission-page .icon-chip--blue {
        color: #0f67bf;
        background: linear-gradient(145deg, rgba(55, 153, 246, 0.28), rgba(72, 173, 248, 0.16));
    }

    .commission-page .icon-chip--mint {
        color: #0c907d;
        background: linear-gradient(145deg, rgba(20, 184, 154, 0.26), rgba(111, 222, 203, 0.16));
    }

    .commission-page .pf-modal .modal-content {
        border: 1px solid rgba(31, 115, 224, 0.16);
        border-radius: 1.1rem;
        box-shadow: 0 24px 48px rgba(14, 60, 120, 0.18);
    }

    .commission-page .pf-modal .modal-header {
        background: linear-gradient(135deg, #2d8ff0, #14b89a);
        color: #ffffff;
        border-radius: 1.1rem 1.1rem 0 0;
        border-bottom: none;
    }

    .commission-page .pf-modal .modal-header .btn-close {
        filter: brightness(0) invert(1);
    }

    .commission-page .pf-modal .modal-body {
        background: linear-gradient(180deg, #f7fbff 0%, #ffffff 100%);
    }

    .commission-page .pf-modal .modal-footer {
        border-top: 1px solid rgba(31, 115, 224, 0.1);
    }

    .commission-page .empty-state {
        padding: 3rem 1rem;
        text-align: center;
    }

    .commission-page .empty-state i {
        font-size: 3rem;
        color: #c5d8ed;
        margin-bottom: 1rem;
    }

    .btn-action-delete,
    .btn-action-edit {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        border: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
        font-size: 1rem;
    }

    .btn-action-delete {
        background: rgba(240, 68, 56, 0.1);
        color: #f04438;
    }
    
    .btn-action-edit {
        background: rgba(31, 115, 224, 0.1);
        color: #1f73e0;
    }

    .btn-action-delete:hover {
        background: #f04438;
        color: white;
        transform: translateY(-2px);
    }
    
    .btn-action-edit:hover {
        background: #1f73e0;
        color: white;
        transform: translateY(-2px);
    }

    .commission-page .empty-state h6 {
        color: #6b8ab0;
        font-weight: 600;
    }

    .commission-page .empty-state p {
        color: #9aadbe;
        font-size: 0.85rem;
    }

    /* ─── Mobile Card Layout ─── */
    .commission-page .mobile-config-card {
        border: 1px solid rgba(31, 115, 224, 0.12);
        border-radius: 0.85rem;
        background: #fff;
        padding: 0.85rem;
        box-shadow: 0 4px 10px rgba(14, 72, 133, 0.06);
        transition: transform 0.15s;
    }

    .commission-page .mobile-config-card:active {
        transform: scale(0.99);
    }

    .commission-page .mobile-config-card .card-title {
        font-weight: 700;
        color: #1e3a5f;
        font-size: 0.95rem;
    }

    .commission-page .mobile-config-card .card-meta {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
        margin-top: 0.4rem;
    }

    .commission-page .mobile-config-card .meta-item {
        font-size: 0.78rem;
        color: #5c728a;
    }

    .commission-page .mobile-config-card .meta-item strong {
        color: #1e5f9d;
    }

    @media (max-width: 767.98px) {
        .commission-page .card-body {
            padding: 0.9rem;
        }

        .commission-page .btn {
            font-size: 0.9rem;
        }
    }
</style>
@endpush

@section('content')
<div class="row g-3 commission-page">
    @if(session('success'))
    <div class="col-12">
        <div class="alert alert-success border-0 shadow-sm rounded-4 mb-0">
            <i class="fa-solid fa-circle-check me-1"></i> {{ session('success') }}
        </div>
    </div>
    @endif

    @if($errors->any())
    <div class="col-12">
        <div class="alert alert-danger border-0 shadow-sm rounded-4 mb-0">
            <div class="fw-bold mb-1">บันทึกข้อมูลไม่สำเร็จ</div>
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif

    {{-- ═══ Hero Card ═══ --}}
    <div class="col-12">
        <div class="card border-0 shadow-sm hero-card">
            <div class="card-body p-3 p-lg-4">
                <div class="row g-3 align-items-center">
                    <div class="col">
                        <div class="section-title">
                            <span class="icon-chip icon-chip--mint me-2"><i class="fa-solid fa-percent"></i></span>
                            จัดการค่าคอมมิชชัน
                        </div>
                        <div class="section-subtitle">ตั้งค่ารายการสินค้าและบริการที่ต้องการคิดค่าตอบแทนให้พนักงาน</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12">
        <div class="d-flex justify-content-end">
            <button type="button" class="btn gradient-btn rounded-3 px-3" onclick="pfOpenCommissionModal()">
                <i class="fa-solid fa-plus me-1"></i>เพิ่มรายการใหม่
            </button>
        </div>
    </div>

    {{-- ═══ Table Card (Desktop) ═══ --}}
    <div class="col-12">
        <div class="card border-0 shadow-sm table-card">
            <div class="card-header bg-white border-0 pt-3 pb-2 d-flex align-items-center justify-content-between">
                <h6 class="fw-bold mb-0">
                    <span class="icon-chip icon-chip--blue me-2"><i class="fa-solid fa-list-check"></i></span>
                    รายการที่คอมมิชชัน
                </h6>
                <span class="badge-type badge-service">{{ count($configs) }} รายการ</span>
            </div>
            <div class="card-body p-2 p-lg-3">

                @if(count($configs) === 0)
                    {{-- Empty State --}}
                    <div class="empty-state">
                        <i class="fa-solid fa-folder-open d-block"></i>
                        <h6>ยังไม่มีรายการค่าคอมมิชชัน</h6>
                        <p>กดปุ่ม "เพิ่มรายการใหม่" เพื่อเริ่มตั้งค่า<br>เฉพาะรายการที่คุณเพิ่มเท่านั้นจะคิดค่าคอม</p>
                    </div>
                @else

                    {{-- Desktop Table (hidden on mobile) --}}
                    <div class="table-responsive d-none d-md-block">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>ประเภท</th>
                                    <th>ชื่อรายการ</th>
                                    <th>รูปแบบ</th>
                                    <th>ค่าตอบแทน</th>
                                    <th>ต้นทุนหักออก</th>
                                    <th class="text-end">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($configs as $config)
                                <tr>
                                    <td>
                                        @php
                                            $typeBadge = match($config->item_type) {
                                                'service' => ['badge-service', 'บริการ'],
                                                'product' => ['badge-product', 'สินค้า'],
                                                'package' => ['badge-package', 'แพ็กเกจ'],
                                                default => ['badge-service', $config->item_type],
                                            };
                                        @endphp
                                        <span class="badge-type {{ $typeBadge[0] }}">{{ $typeBadge[1] }}</span>
                                    </td>
                                    <td class="fw-bold text-dark">{{ $config->item_name ?? '-' }}</td>
                                    <td>
                                        <span class="badge-type {{ $config->type === 'fixed' ? 'badge-fixed' : 'badge-percent' }}">
                                            {{ $config->type === 'fixed' ? 'เงินก้อน' : 'เปอร์เซ็นต์' }}
                                        </span>
                                    </td>
                                    <td class="fw-bold">
                                        {{ number_format($config->value, 2) }}{{ $config->type === 'percent' ? '%' : ' ฿' }}
                                    </td>
                                    <td>
                                        @if($config->deduct_cost > 0)
                                            {{ number_format($config->deduct_cost, 2) }} ฿
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <div class="d-inline-flex gap-2">
                                            <button type="button" class="btn-action-edit" title="แก้ไข"
                                                    onclick="pfOpenEditCommissionModal('{{ $config->id }}', '{{ $config->item_type }}', '{{ $config->item_id }}', '{{ $config->type }}', '{{ $config->value }}', '{{ $config->deduct_cost }}')">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </button>
                                            <form action="{{ route('admin.commission.destroy', $config->id) }}" method="POST" onsubmit="return confirm('ยืนยันการลบรายการนี้?')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="btn-action-delete" title="ลบ">
                                                    <i class="fa-solid fa-trash-can"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Mobile Card Layout (hidden on desktop) --}}
                    <div class="d-flex flex-column gap-2 d-md-none">
                        @foreach($configs as $config)
                        <div class="mobile-config-card">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    @php
                                        $typeBadge = match($config->item_type) {
                                            'service' => ['badge-service', 'บริการ'],
                                            'product' => ['badge-product', 'สินค้า'],
                                            'package' => ['badge-package', 'แพ็กเกจ'],
                                            default => ['badge-service', $config->item_type],
                                        };
                                    @endphp
                                    <span class="badge-type {{ $typeBadge[0] }} mb-1 d-inline-block">{{ $typeBadge[1] }}</span>
                                    <div class="card-title">{{ $config->item_name ?? '-' }}</div>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn-action-edit" title="แก้ไข"
                                            onclick="pfOpenEditCommissionModal('{{ $config->id }}', '{{ $config->item_type }}', '{{ $config->item_id }}', '{{ $config->type }}', '{{ $config->value }}', '{{ $config->deduct_cost }}')">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                    <form action="{{ route('admin.commission.destroy', $config->id) }}" method="POST" onsubmit="return confirm('ยืนยันการลบรายการนี้?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn-action-delete" title="ลบ">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <div class="card-meta">
                                <div class="meta-item">
                                    <span class="badge-type {{ $config->type === 'fixed' ? 'badge-fixed' : 'badge-percent' }}">
                                        {{ $config->type === 'fixed' ? 'เงินก้อน' : '%' }}
                                    </span>
                                </div>
                                <div class="meta-item">
                                    ค่าตอบแทน: <strong>{{ number_format($config->value, 2) }}{{ $config->type === 'percent' ? '%' : ' ฿' }}</strong>
                                </div>
                                @if($config->deduct_cost > 0)
                                <div class="meta-item">
                                    หักต้นทุน: <strong>{{ number_format($config->deduct_cost, 2) }} ฿</strong>
                                </div>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>

                @endif

            </div>
        </div>
    </div>
</div>

{{-- ═══ Modal: เพิ่มค่าคอมมิชชัน ═══ --}}
<div class="modal fade pf-modal commission-page" id="addCommissionModal" tabindex="-1" aria-labelledby="addCommissionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="addCommissionModalLabel">
                    <i class="fa-solid fa-plus-circle me-2"></i>เพิ่มรายการค่าคอมมิชชัน
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('admin.commission.store') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-bold">1. เลือกประเภทไอเทม <span class="text-danger">*</span></label>
                            <select name="item_type" id="item_type" class="form-select" required onchange="filterItems()">
                                <option value="">-- เลือกประเภท --</option>
                                <option value="service">บริการ (Service)</option>
                                <option value="product">สินค้า (Product)</option>
                                <option value="package">แพ็กเกจ (Package)</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">2. เลือกชื่อรายการ <span class="text-danger">*</span></label>
                            <select name="item_id" id="item_id" class="form-select" required>
                                <option value="">-- เลือกรายการ --</option>
                            </select>
                        </div>
                        <div class="col-12"><hr class="my-1"></div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">รูปแบบการจ่าย</label>
                            <select name="type" class="form-select">
                                <option value="fixed">เงินก้อน (฿)</option>
                                <option value="percent">เปอร์เซ็นต์ (%)</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">มูลค่าตอบแทน <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" name="value" class="form-control" placeholder="0.00" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">ต้นทุนหักออก (฿)</label>
                            <input type="number" step="0.01" min="0" name="deduct_cost" class="form-control" placeholder="0.00">
                            <div class="form-text">
                                <i class="fa-solid fa-circle-info me-1 text-primary"></i>
                                กรณีคิดเป็น % จะหักยอดนี้ออกจากราคาขายก่อนคำนวณ
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn gradient-btn rounded-pill px-4">
                        <i class="fa-solid fa-check me-1"></i>ยืนยันการเพิ่ม
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
{{-- ═══ Modal: แก้ไขค่าคอมมิชชัน ═══ --}}
<div class="modal fade pf-modal commission-page" id="editCommissionModal" tabindex="-1" aria-labelledby="editCommissionModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="editCommissionModalLabel">
                    <i class="fa-solid fa-pen-to-square me-2"></i>แก้ไขรายการค่าคอมมิชชัน
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editCommissionForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-bold">ประเภทไอเทม</label>
                            <input type="text" id="edit_item_type_display" class="form-control" readonly>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">ชื่อรายการ</label>
                            <input type="text" id="edit_item_id_display" class="form-control" readonly>
                        </div>
                        <div class="col-12"><hr class="my-1"></div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">รูปแบบการจ่าย</label>
                            <select name="type" id="edit_type" class="form-select">
                                <option value="fixed">เงินก้อน (฿)</option>
                                <option value="percent">เปอร์เซ็นต์ (%)</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">มูลค่าตอบแทน <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" name="value" id="edit_value" class="form-control" placeholder="0.00" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">ต้นทุนหักออก (฿)</label>
                            <input type="number" step="0.01" min="0" name="deduct_cost" id="edit_deduct_cost" class="form-control" placeholder="0.00">
                            <div class="form-text">
                                <i class="fa-solid fa-circle-info me-1 text-primary"></i>
                                กรณีคิดเป็น % จะหักยอดนี้ออกจากราคาขายก่อนคำนวณ
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn gradient-btn rounded-pill px-4">
                        <i class="fa-solid fa-check me-1"></i>บันทึกข้อมูล
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const commissionData = {
        service: @json($availableServices),
        product: @json($availableProducts),
        package: @json($availablePackages)
    };

    function filterItems() {
        const type = document.getElementById('item_type').value;
        const select = document.getElementById('item_id');
        select.innerHTML = '<option value="">-- เลือกรายการ --</option>';
        if (type && commissionData[type]) {
            commissionData[type].forEach(item => {
                select.innerHTML += `<option value="${item.id}">${item.name}</option>`;
            });
        }
    }

    function pfOpenCommissionModal() {
        var modalEl = document.getElementById('addCommissionModal');
        if (!modalEl) return;

        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            var bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);
            bsModal.show();
        } else {
            modalEl.classList.add('show');
            modalEl.style.display = 'block';
            modalEl.removeAttribute('aria-hidden');
            modalEl.setAttribute('aria-modal', 'true');
            document.body.classList.add('modal-open');

            var backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop fade show pf-fallback';
            document.body.appendChild(backdrop);
        }
    }
    function pfOpenEditCommissionModal(id, itemType, itemId, type, value, deductCost) {
        var modalEl = document.getElementById('editCommissionModal');
        if (!modalEl) return;
        
        document.getElementById('editCommissionForm').action = '/admin/commission/' + id;
        
        let typeName = '';
        if (itemType === 'service') typeName = 'บริการ (Service)';
        else if (itemType === 'product') typeName = 'สินค้า (Product)';
        else if (itemType === 'package') typeName = 'แพ็กเกจ (Package)';
        document.getElementById('edit_item_type_display').value = typeName;
        
        let itemName = '';
        if (commissionData[itemType]) {
            const item = commissionData[itemType].find(i => i.id == itemId);
            if (item) itemName = item.name;
        }
        document.getElementById('edit_item_id_display').value = itemName || ('ID: ' + itemId);
        
        document.getElementById('edit_type').value = type;
        document.getElementById('edit_value').value = value;
        document.getElementById('edit_deduct_cost').value = deductCost;

        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            var bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);
            bsModal.show();
        } else {
            modalEl.classList.add('show');
            modalEl.style.display = 'block';
            modalEl.removeAttribute('aria-hidden');
            modalEl.setAttribute('aria-modal', 'true');
            document.body.classList.add('modal-open');

            var backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop fade show pf-fallback';
            document.body.appendChild(backdrop);
        }
    }
</script>
@endpush