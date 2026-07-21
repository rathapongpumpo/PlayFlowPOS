@push('head')
<style>
    .shift-section, .custom-modal {
        --sp-primary: #1f73e0;
        --sp-primary-light: rgba(31, 115, 224, 0.1);
        --sp-success: #14b89a;
        --sp-success-light: rgba(20, 184, 154, 0.1);
        --sp-danger: #f04438;
        --sp-danger-light: rgba(240, 68, 56, 0.1);
        --sp-text: #2c3e50;
        --sp-muted: #8392a5;
        --sp-border: rgba(0, 0, 0, 0.05);
        --sp-card-bg: rgba(255, 255, 255, 0.95);
    }
    
    .shift-header-wrapper {
        background: linear-gradient(135deg, rgba(255,255,255,0.9) 0%, rgba(245,248,255,0.8) 100%);
        border-radius: 1.25rem;
        padding: 1.5rem 2rem;
        box-shadow: 0 8px 30px rgba(31, 115, 224, 0.04);
        border: 1px solid rgba(255,255,255,0.8);
        backdrop-filter: blur(10px);
        margin-bottom: 1.5rem;
    }

    .shift-title {
        font-weight: 700;
        color: var(--sp-text);
        letter-spacing: -0.5px;
        margin-bottom: 0.25rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    
    .shift-title-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 42px;
        height: 42px;
        background: linear-gradient(135deg, var(--sp-primary) 0%, #468ef5 100%);
        color: white;
        border-radius: 12px;
        font-size: 1.1rem;
        box-shadow: 0 4px 15px rgba(31, 115, 224, 0.3);
    }

    .shift-subtitle {
        color: var(--sp-muted);
        font-size: 0.95rem;
        margin-left: 54px;
    }

    .btn-add-shift {
        background: linear-gradient(135deg, var(--sp-primary) 0%, #468ef5 100%);
        color: white;
        border: none;
        padding: 0.6rem 1.25rem;
        border-radius: 50px;
        font-weight: 500;
        box-shadow: 0 4px 15px rgba(31, 115, 224, 0.25);
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .btn-add-shift:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(31, 115, 224, 0.35);
        color: white;
    }

    .shift-card {
        background: var(--sp-card-bg);
        border-radius: 1.25rem;
        border: 1px solid var(--sp-border);
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.03);
        overflow: hidden;
    }

    .shift-table {
        margin-bottom: 0;
    }

    .shift-table th {
        background: rgba(248, 250, 252, 0.8);
        color: var(--sp-muted);
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.8rem;
        letter-spacing: 0.5px;
        padding: 1rem 1.25rem;
        border-bottom: 2px solid var(--sp-border);
        white-space: nowrap;
    }

    .shift-table td {
        padding: 1.25rem;
        vertical-align: middle;
        border-bottom: 1px solid var(--sp-border);
        color: var(--sp-text);
        font-weight: 500;
        white-space: nowrap;
    }

    .shift-table tbody tr:hover {
        background: rgba(248, 250, 252, 0.5);
    }

    .shift-date-badge {
        display: inline-block;
        padding: 0.35rem 0.75rem;
        background: var(--sp-primary-light);
        color: var(--sp-primary);
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.9rem;
    }

    .time-slot {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        padding: 0.4rem 0.85rem;
        border-radius: 8px;
        font-family: monospace;
        font-size: 0.95rem;
        color: #475569;
    }

    .time-slot i {
        color: var(--sp-muted);
        font-size: 0.85rem;
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
        background: var(--sp-danger-light);
        color: var(--sp-danger);
    }
    
    .btn-action-edit {
        background: var(--sp-primary-light);
        color: var(--sp-primary);
    }

    .btn-action-delete:hover {
        background: var(--sp-danger);
        color: white;
        transform: translateY(-2px);
    }
    
    .btn-action-edit:hover {
        background: var(--sp-primary);
        color: white;
        transform: translateY(-2px);
    }

    .empty-state {
        padding: 4rem 2rem;
        text-align: center;
    }

    .empty-state-icon {
        width: 80px;
        height: 80px;
        background: var(--sp-primary-light);
        color: var(--sp-primary);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.5rem;
        margin: 0 auto 1.5rem;
    }

    /* Modal Styling */
    .custom-modal .modal-content {
        border: none;
        border-radius: 1.5rem;
        box-shadow: 0 20px 60px rgba(0,0,0,0.1);
        overflow: hidden;
    }
    
    .custom-modal .modal-header {
        background: linear-gradient(135deg, var(--sp-primary) 0%, #468ef5 100%);
        color: white;
        padding: 1.5rem;
        border: none;
    }
    
    .custom-modal .modal-title {
        font-weight: 600;
        letter-spacing: 0.5px;
    }
    
    .custom-modal .btn-close {
        filter: brightness(0) invert(1);
        opacity: 0.8;
    }
    
    .custom-modal .modal-body {
        padding: 2rem;
    }
    
    .custom-modal .form-label {
        font-weight: 600;
        color: var(--sp-text);
        margin-bottom: 0.5rem;
    }
    
    .custom-modal .form-control,
    .custom-modal .form-select {
        border-radius: 10px;
        padding: 0.75rem 1rem;
        border: 1px solid #e2e8f0;
        box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);
        background: #f8fafc;
        transition: all 0.2s;
    }
    
    .custom-modal .form-control:focus,
    .custom-modal .form-select:focus {
        background: white;
        border-color: var(--sp-primary);
        box-shadow: 0 0 0 3px var(--sp-primary-light);
    }
    
    .custom-modal .modal-footer {
        padding: 1.25rem 2rem;
        background: #f8fafc;
        border-top: 1px solid #e2e8f0;
    }
</style>
@endpush

<div class="row g-3 shift-section mt-2">
    <!-- Header Section -->
    <div class="col-12">
        <div class="shift-header-wrapper d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <div>
                <h3 class="shift-title">
                    <span class="shift-title-icon"><i class="fa-solid fa-calendar-check"></i></span>
                    จัดการตารางงาน
                </h3>
                <div class="shift-subtitle">
                    กำหนดเวลาเข้างานประจำเดือน 
                    <strong class="text-primary">{{ $month }}/{{ $year }}</strong>
                    <div class="d-inline-block ms-2">
                        <form method="GET" action="{{ url()->current() }}" class="d-inline">
                            @foreach(request()->except(['month', 'year']) as $key => $value)
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                            @endforeach
                            <select name="month" class="form-select form-select-sm d-inline-block w-auto" onchange="this.form.submit()">
                                @for($i = 1; $i <= 12; $i++)
                                    <option value="{{ sprintf('%02d', $i) }}" {{ $month == sprintf('%02d', $i) ? 'selected' : '' }}>
                                        {{ date('F', mktime(0, 0, 0, $i, 10)) }}
                                    </option>
                                @endfor
                            </select>
                            <select name="year" class="form-select form-select-sm d-inline-block w-auto" onchange="this.form.submit()">
                                @for($i = date('Y') - 1; $i <= date('Y') + 1; $i++)
                                    <option value="{{ $i }}" {{ $year == $i ? 'selected' : '' }}>
                                        {{ $i }}
                                    </option>
                                @endfor
                            </select>
                        </form>
                    </div>
                </div>
            </div>
            <button class="btn-add-shift" data-bs-toggle="modal" data-bs-target="#addShiftModal">
                <i class="fa-solid fa-plus"></i> เพิ่มกะการทำงาน
            </button>
        </div>
    </div>

    @if(session('success'))
    <div class="col-12">
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-4 d-flex align-items-center mb-0" role="alert" style="background: var(--sp-success-light); color: var(--sp-success);">
            <i class="fa-solid fa-circle-check fs-5 me-3"></i>
            <div class="fw-medium">{{ session('success') }}</div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
    @endif

    <!-- Table Section -->
    <div class="col-12">
        <div class="shift-card">
            <div class="table-responsive">
                <table class="table shift-table table-borderless">
                <thead>
                    <tr>
                        <th class="ps-4" style="width: 25%;">วันที่เข้างาน</th>
                        <th style="width: 25%;">เวลาเริ่ม - เวลาจบ</th>
                        <th class="text-center" style="width: 25%;">ค่ามือที่ได้ / การันตี</th>
                        <th class="text-center" style="width: 15%;">เงินชดเชย</th>
                        <th class="text-end pe-4" style="width: 10%;">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($shifts as $shift)
                        <tr>
                            <td class="ps-4">
                                <span class="shift-date-badge">
                                    <i class="fa-regular fa-calendar me-1"></i> {{ \Carbon\Carbon::parse($shift->shift_date)->format('d/m/Y') }}
                                </span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="time-slot"><i class="fa-regular fa-clock"></i> {{ \Carbon\Carbon::parse($shift->start_time)->format('H:i') }}</span>
                                    <span class="text-muted"><i class="fa-solid fa-arrow-right"></i></span>
                                    <span class="time-slot"><i class="fa-regular fa-clock"></i> {{ \Carbon\Carbon::parse($shift->end_time)->format('H:i') }}</span>
                                </div>
                            </td>
                            <td class="text-center">
                                <div class="fw-bold text-success">{{ number_format($shift->earned_commission, 2) }} ฿</div>
                                <div class="text-muted small">/ {{ number_format($shift->guarantee_amount ?? 0, 2) }} ฿</div>
                            </td>
                            <td class="text-center">
                                @if($shift->guarantee_topup > 0)
                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 py-2 px-3 fw-bold rounded-pill">
                                        +{{ number_format($shift->guarantee_topup, 2) }} ฿
                                    </span>
                                @else
                                    <span class="text-muted small">-</span>
                                @endif
                            </td>
                            <td class="text-end pe-4">
                                <div class="d-inline-flex gap-2">
                                    <button type="button" 
                                            class="btn-action-edit edit-shift-btn" 
                                            title="แก้ไขตารางงาน"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editShiftModal"
                                            data-id="{{ $shift->id }}"
                                            data-start="{{ \Carbon\Carbon::parse($shift->start_time)->format('H:i') }}"
                                            data-end="{{ \Carbon\Carbon::parse($shift->end_time)->format('H:i') }}"
                                            data-guarantee="{{ $shift->guarantee_amount ?? 0 }}">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                    <form action="{{ route('masseuse.shifts.destroy', $shift->id) }}" method="POST" class="d-inline" onsubmit="return confirm('ยืนยันการลบตารางงานนี้?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-action-delete" title="ลบตารางงาน">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-0">
                                <div class="empty-state">
                                    <div class="empty-state-icon">
                                        <i class="fa-solid fa-calendar-xmark"></i>
                                    </div>
                                    <h5 class="fw-bold text-dark mb-2">ไม่มีตารางงานในเดือนนี้</h5>
                                    <p class="text-muted mb-4">พนักงานยังไม่มีกะการทำงานในเดือนที่เลือก</p>
                                    <button class="btn-add-shift shadow-none" data-bs-toggle="modal" data-bs-target="#addShiftModal">
                                        <i class="fa-solid fa-plus"></i> เพิ่มกะแรกของคุณ
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Shift Modal -->
<div class="modal fade custom-modal" id="addShiftModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-calendar-plus me-2"></i> เพิ่มกะการทำงาน</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('masseuse.shifts.store') }}" method="POST">
                @csrf
                <input type="hidden" name="masseuse_id" value="{{ $formRecord['id'] }}">
                <div class="modal-body">
                    <div class="mb-4">
                        <label class="form-label">
                            <i class="fa-regular fa-calendar text-primary me-1"></i> วันที่เข้างาน
                        </label>
                        <input type="date" name="shift_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">
                                <i class="fa-regular fa-clock text-primary me-1"></i> เวลาเริ่ม
                            </label>
                            <input type="time" name="start_time" class="form-control" value="10:00" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">
                                <i class="fa-solid fa-clock text-primary me-1"></i> เวลาจบ
                            </label>
                            <input type="time" name="end_time" class="form-control" value="22:00" required>
                        </div>
                    </div>
                    
                    <div class="mb-2">
                        <label class="form-label">
                            <i class="fa-solid fa-hand-holding-dollar text-primary me-1"></i> ยอดการันตี/วัน (บาท)
                        </label>
                        <input type="number" step="0.01" name="guarantee_amount" class="form-control" value="0.00" min="0" placeholder="0.00">
                        <div class="form-text text-muted small">หากใส่เป็น 0 หมายถึงไม่มีการบวกยอดส่วนต่างให้</div>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-light px-4 py-2 rounded-pill fw-medium" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary px-4 py-2 rounded-pill fw-medium shadow-sm" style="background: linear-gradient(135deg, var(--sp-primary) 0%, #468ef5 100%); border: none;">
                        <i class="fa-solid fa-floppy-disk me-1"></i> บันทึกข้อมูล
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Shift Modal -->
<div class="modal fade custom-modal" id="editShiftModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fa-solid fa-pen-to-square me-2"></i> แก้ไขกะการทำงาน</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editShiftForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="row">
                        <div class="col-6 mb-3">
                            <label class="form-label">
                                <i class="fa-regular fa-clock text-primary me-1"></i> เวลาเริ่ม
                            </label>
                            <input type="time" name="start_time" id="edit_start_time" class="form-control" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label">
                                <i class="fa-solid fa-clock text-primary me-1"></i> เวลาจบ
                            </label>
                            <input type="time" name="end_time" id="edit_end_time" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="mb-2">
                        <label class="form-label">
                            <i class="fa-solid fa-hand-holding-dollar text-primary me-1"></i> ยอดการันตี/วัน (บาท)
                        </label>
                        <input type="number" step="0.01" name="guarantee_amount" id="edit_guarantee_amount" class="form-control" min="0" placeholder="0.00">
                        <div class="form-text text-muted small">หากใส่เป็น 0 หมายถึงไม่มีการบวกยอดส่วนต่างให้</div>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-light px-4 py-2 rounded-pill fw-medium" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary px-4 py-2 rounded-pill fw-medium shadow-sm" style="background: linear-gradient(135deg, var(--sp-primary) 0%, #468ef5 100%); border: none;">
                        <i class="fa-solid fa-floppy-disk me-1"></i> บันทึกข้อมูล
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const editBtns = document.querySelectorAll('.edit-shift-btn');
    const editForm = document.getElementById('editShiftForm');
    
    if(editBtns && editForm) {
        editBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.dataset.id;
                const start = this.dataset.start;
                const end = this.dataset.end;
                const guarantee = this.dataset.guarantee;
                
                editForm.action = `/masseuse/shifts/${id}`;
                document.getElementById('edit_start_time').value = start;
                document.getElementById('edit_end_time').value = end;
                document.getElementById('edit_guarantee_amount').value = guarantee;
            });
        });
    }
});
</script>
@endpush
