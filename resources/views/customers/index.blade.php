@extends('layouts.main')

@section('title', 'ลูกค้า - PlayFlow')
@section('page_title', 'ลูกค้า')

@push('head')
<style>
    .customers-main-card {
        overflow: hidden;
        border: 1px solid rgba(31, 115, 224, 0.15) !important;
        box-shadow: 0 18px 35px rgba(20, 73, 133, 0.08) !important;
        background: linear-gradient(180deg, #f8fcff 0%, #f1f7fb 100%);
    }

    .customers-card-header {
        padding-top: 0.95rem !important;
        padding-bottom: 0.95rem !important;
        background: linear-gradient(125deg, rgba(38, 143, 235, 0.12), rgba(20, 184, 154, 0.1)) !important;
        border-bottom: 1px solid rgba(31, 115, 224, 0.12) !important;
    }

    .customers-toolbar {
        min-height: 52px;
        align-items: center !important;
    }

    .customers-toolbar h6 {
        display: inline-flex;
        align-items: center;
        margin-bottom: 0;
        color: #1f4f84;
        font-size: 1.2rem;
    }

    .customers-toolbar .btn {
        box-shadow: 0 8px 16px rgba(16, 92, 170, 0.2);
    }

    .customers-hero {
        border: 1px solid rgba(31, 115, 224, 0.12);
        border-radius: 1rem;
        padding: 0.95rem;
        background: linear-gradient(160deg, rgba(255, 255, 255, 0.95), rgba(233, 245, 252, 0.9));
    }

    .customers-search-label {
        color: #1f4f84;
        font-size: 0.84rem;
        letter-spacing: 0.02em;
    }

    .customers-search-group .input-group-text {
        background-color: #ffffff !important;
        border-right: 0;
    }

    .customers-search-group .form-control {
        border-left: 0;
        font-size: 0.95rem;
    }

    .customers-stat-card {
        border: 1px solid rgba(31, 115, 224, 0.14);
        border-radius: 0.85rem;
        padding: 0.72rem 0.78rem;
        background: #ffffff;
        box-shadow: 0 5px 14px rgba(19, 84, 148, 0.06);
        height: 100%;
    }

    .customers-stat-label {
        color: #537292;
        font-size: 0.76rem;
        margin-bottom: 0.1rem;
    }

    .customers-stat-value {
        font-size: 1.7rem;
        line-height: 1;
        color: #184f89;
        font-weight: 700;
    }

    .customers-table-wrap {
        border: 1px solid rgba(31, 115, 224, 0.14);
        border-radius: 0.95rem;
        overflow-x: auto;
        overflow-y: hidden;
        -webkit-overflow-scrolling: touch;
        background: #ffffff;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.85);
    }

    .customers-table thead.table-light th {
        background: linear-gradient(180deg, #eef5ff 0%, #e8f2ff 100%);
        color: #1f5e9d;
        font-size: 0.88rem;
        border-bottom-color: rgba(31, 115, 224, 0.18);
    }

    .customers-table tbody td {
        border-color: rgba(31, 115, 224, 0.1);
    }

    .customers-table tbody tr:hover td {
        background: rgba(31, 115, 224, 0.05);
    }

    .customers-table {
        table-layout: auto;
        min-width: 980px;
    }

    .customers-table .membership-col {
        width: 12%;
        min-width: 120px;
    }

    .customers-table .action-col {
        min-width: 150px;
    }

    .customers-table .membership-cell {
        font-size: 0.86rem;
        line-height: 1.25;
    }

    .customers-table .membership-badge {
        font-size: 0.74rem;
    }

    .customers-table .membership-next {
        font-size: 0.78rem;
    }

    .customers-action-group {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .btn-action-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 38px;
        height: 38px;
        padding: 0;
        border-radius: 10px;
        border: none;
        background: #f8fafc;
        color: #64748b;
        font-size: 1rem;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: inset 0 0 0 1px rgba(0,0,0,0.05);
    }

    .btn-action-icon:hover {
        transform: translateY(-2px);
    }

    .btn-edit-icon:hover {
        background: linear-gradient(135deg, rgba(31,115,224,0.1) 0%, rgba(31,115,224,0.2) 100%);
        color: #1f73e0;
        box-shadow: inset 0 0 0 1px rgba(31,115,224,0.2), 0 4px 12px rgba(31,115,224,0.15);
    }

    .btn-history-icon:hover {
        background: linear-gradient(135deg, rgba(20,184,154,0.1) 0%, rgba(20,184,154,0.2) 100%);
        color: #14b89a;
        box-shadow: inset 0 0 0 1px rgba(20,184,154,0.2), 0 4px 12px rgba(20,184,154,0.15);
    }

    .btn-topup-icon:hover {
        background: linear-gradient(135deg, rgba(245,158,11,0.1) 0%, rgba(245,158,11,0.2) 100%);
        color: #d97706;
        box-shadow: inset 0 0 0 1px rgba(245,158,11,0.2), 0 4px 12px rgba(245,158,11,0.15);
    }

    .btn-delete-icon:hover {
        background: linear-gradient(135deg, rgba(239,68,68,0.1) 0%, rgba(239,68,68,0.2) 100%);
        color: #dc2626;
        box-shadow: inset 0 0 0 1px rgba(239,68,68,0.2), 0 4px 12px rgba(239,68,68,0.15);
    }

    @media (max-width: 767.98px) {
        .customers-toolbar {
            flex-direction: row;
            flex-wrap: nowrap !important;
            justify-content: space-between !important;
            align-items: center !important;
            text-align: left;
            gap: 0.5rem !important;
            min-height: 44px;
        }

        .customers-card-header {
            padding-top: 0.75rem !important;
            padding-bottom: 0.75rem !important;
        }

        .customers-toolbar h6 {
            font-size: 1.02rem;
        }

        .customers-toolbar .btn {
            min-width: 0;
            white-space: nowrap;
            padding-left: 0.85rem !important;
            padding-right: 0.85rem !important;
            font-size: 0.88rem;
        }

        .customers-hero {
            padding: 0.72rem;
        }

        .customers-search-label {
            font-size: 0.8rem;
        }

        .customers-search-group .form-control {
            font-size: 0.9rem;
        }

        .customers-stat-card {
            padding: 0.56rem 0.6rem;
            border-radius: 0.75rem;
        }

        .customers-stat-label {
            font-size: 0.72rem;
        }

        .customers-stat-value {
            font-size: 1.42rem;
        }

        .customers-table th,
        .customers-table td {
            vertical-align: middle;
            padding-left: 0.5rem;
            padding-right: 0.5rem;
            font-size: 0.86rem;
            white-space: nowrap;
        }

        .customers-table tbody td:first-child {
            min-width: 160px;
            white-space: normal;
            line-height: 1.25;
        }

        .customers-table .membership-col {
            min-width: 140px;
        }

        .customers-table .action-col {
            min-width: 150px;
        }

        .customers-table .membership-badge {
            display: inline-block;
            font-size: 0.7rem;
        }

        .customers-table .membership-next {
            display: none;
        }

        .customers-action-group {
            justify-content: flex-end !important;
            flex-wrap: nowrap;
            width: auto;
        }

        .customers-action-group form {
            margin: 0;
        }

        .customers-action-group .btn {
            font-size: 0.8rem;
            padding: 0.22rem 0.7rem !important;
        }
    }
</style>
@endpush

@section('content')
<div class="row g-3">
    @if(session('success'))
    <div class="col-12">
        <div class="alert alert-success border-0 shadow-sm rounded-4 mb-0">
            <i class="bi bi-check-circle-fill me-1"></i> {{ session('success') }}
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

    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4 customers-main-card">
            <div class="card-header bg-white border-0 pt-3 pb-0 customers-card-header">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 customers-toolbar">
                    <h6 class="fw-bold mb-0"><i class="bi bi-people-fill me-2 text-primary"></i>ฐานข้อมูลลูกค้า</h6>
                    <button type="button" class="btn btn-primary rounded-pill px-4" id="open-add-customer-btn">
                        <i class="bi bi-person-plus-fill me-1"></i> เพิ่มลูกค้าใหม่
                    </button>
                </div>
            </div>
            <div class="card-body p-3 p-lg-4">
                <div class="customers-hero mb-3 mb-lg-4">
                <div class="row g-2">
                    <div class="col-12 col-lg-5">
                        <form method="GET" action="{{ route('customers') }}">
                            <label class="form-label fw-bold customers-search-label">ค้นหาลูกค้า</label>
                            <div class="input-group customers-search-group">
                                <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                                <input type="text"
                                       class="form-control"
                                       name="search"
                                       value="{{ $search }}"
                                       placeholder="ชื่อ, เบอร์โทร, ไลน์ไอดี">
                            </div>
                        </form>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="customers-stat-card">
                            <div class="customers-stat-label">ลูกค้าทั้งหมด</div>
                            <div class="customers-stat-value">{{ number_format($summary['total_customers'] ?? 0) }}</div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-4">
                        <div class="customers-stat-card">
                            <div class="customers-stat-label">ลูกค้าเคลื่อนไหว 30 วัน</div>
                            <div class="customers-stat-value">{{ number_format($summary['active_customers_30d'] ?? 0) }}</div>
                        </div>
                    </div>
                </div>
                </div>

                <div class="table-responsive customers-table-wrap">
                    <table class="table table-hover align-middle mb-0 customers-table">
                        <thead class="table-light">
                            <tr>
                                <th class="px-3">ลูกค้า</th>
                                <th>เบอร์โทร</th>
                                <th>ไลน์ไอดี</th>
                                <th class="membership-col">สมาชิก</th>
                                <th>จำนวนครั้งใช้บริการ</th>
                                <th>ยอดคงเหลือ/แต้ม</th>
                                <th>เข้าล่าสุด</th>
                                <th class="text-end action-col">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($customers as $customer)
                            @php
                                $customerPayload = [
                                    'id' => $customer['id'],
                                    'name' => $customer['name'],
                                    'phone' => $customer['phone'],
                                    'line_id' => $customer['line_id'],
                                    'tier_id' => $customer['tier_id'],
                                    'preferred_pressure_level' => $customer['preferred_pressure_level'],
                                    'health_notes' => $customer['health_notes'],
                                    'contraindications' => $customer['contraindications'],
                                ];
                            @endphp
                            <tr>
                                <td class="px-3 fw-semibold">{{ $customer['name'] }}</td>
                                <td>{{ $customer['phone'] !== '' ? $customer['phone'] : '-' }}</td>
                                <td>{{ $customer['line_id'] !== '' ? $customer['line_id'] : '-' }}</td>
                                <td class="membership-cell">
                                    @if($customer['tier_id'] !== null)
                                    <span class="badge text-bg-primary membership-badge">{{ $customer['tier_name'] }} ({{ number_format($customer['tier_discount_percent'], 2) }}%)</span>
                                    @else
                                    <span class="badge text-bg-secondary membership-badge">ไม่มีระดับสมาชิก</span>
                                    @endif
                                    @if(!empty($customer['next_tier_name']))
                                    <div class="text-muted mt-1 membership-next d-none d-md-block">
                                        อีก {{ number_format((float) ($customer['amount_to_next_tier'] ?? 0), 2) }} บาท
                                        ถึง {{ $customer['next_tier_name'] }}
                                    </div>
                                    @elseif(!empty($customer['is_top_tier']))
                                    <div class="text-success mt-1 membership-next d-none d-md-block">ถึงระดับสูงสุดแล้ว</div>
                                    @endif
                                </td>
                                <td>{{ number_format($customer['visit_count']) }} ครั้ง</td>
                                <td>
                                    <div class="text-primary fw-bold mb-1">
                                        <i class="bi bi-wallet2"></i> ฿{{ number_format((float)($customer['wallet_balance'] ?? 0), 2) }}
                                    </div>
                                    <div class="text-warning fw-bold small">
                                        <i class="bi bi-star-fill"></i> {{ number_format((int)($customer['total_points'] ?? 0)) }} pts
                                    </div>
                                </td>
                                <td>{{ $customer['last_visit_at'] ?? '-' }}</td>
                                <td class="text-end">
                                    <div class="d-inline-flex gap-2 customers-action-group">
                                        <button type="button"
                                                class="btn btn-action-icon btn-edit-icon edit-customer-btn"
                                                data-update-url="{{ route('customers.update', ['customerId' => $customer['id']]) }}"
                                                data-customer='@json($customerPayload)'
                                                title="แก้ไขข้อมูล">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <button type="button"
                                                class="btn btn-action-icon btn-history-icon history-customer-btn"
                                                data-customer-id="{{ $customer['id'] }}"
                                                data-customer-name="{{ $customer['name'] }}"
                                                title="ดูประวัติ">
                                            <i class="fa-solid fa-clock-rotate-left"></i>
                                        </button>
                                        <button type="button"
                                                class="btn btn-action-icon btn-topup-icon topup-customer-btn"
                                                data-customer-id="{{ $customer['id'] }}"
                                                data-customer-name="{{ $customer['name'] }}"
                                                title="เติมเงิน">
                                            <i class="fa-solid fa-wallet"></i>
                                        </button>
                                        <form method="POST"
                                              action="{{ route('customers.destroy', ['customerId' => $customer['id']]) }}"
                                              onsubmit="return confirm(@js('ยืนยันการลบลูกค้า ' . $customer['name'] . ' ?'))"
                                              class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-action-icon btn-delete-icon" title="ลบลูกค้า">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">ไม่พบข้อมูลลูกค้า</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addCustomerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold"><i class="bi bi-person-plus-fill me-2 text-primary"></i>เพิ่มลูกค้าใหม่</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('customers.store') }}">
                @csrf
                <input type="hidden" name="_form" value="add_customer">
                <div class="modal-body pt-1">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">ชื่อลูกค้า <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control rounded-3" value="{{ old('name') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">เบอร์โทรศัพท์ <span class="text-danger">*</span></label>
                            <input type="text" name="phone" class="form-control rounded-3" value="{{ old('phone') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">ไลน์ไอดี</label>
                            <input type="text" name="line_id" class="form-control rounded-3" value="{{ old('line_id') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">ระดับสมาชิก</label>
                            <select name="tier_id" class="form-select rounded-3">
                                <option value="">ไม่มีระดับสมาชิก</option>
                                @foreach(($membershipTiers ?? []) as $tier)
                                <option value="{{ $tier['id'] }}" {{ (string) old('tier_id') === (string) $tier['id'] ? 'selected' : '' }}>
                                    {{ $tier['name'] }} ({{ number_format($tier['discount_percent'], 2) }}%)
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">น้ำหนักมือที่ชอบ</label>
                            <select name="preferred_pressure_level" class="form-select rounded-3">
                                <option value="">ไม่ระบุ</option>
                                @foreach($pressureLevels as $option)
                                <option value="{{ $option['value'] }}" {{ old('preferred_pressure_level') === $option['value'] ? 'selected' : '' }}>
                                    {{ $option['label'] }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">ประวัติสุขภาพ</label>
                            <textarea name="health_notes" class="form-control rounded-3" rows="3">{{ old('health_notes') }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">จุดที่ต้องระวัง / ห้ามนวด</label>
                            <textarea name="contraindications" class="form-control rounded-3" rows="3">{{ old('contraindications') }}</textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">
                        <i class="bi bi-save me-1"></i> บันทึกลูกค้า
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="editCustomerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2 text-primary"></i>แก้ไขข้อมูลลูกค้า</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="edit-customer-form" action="{{ route('customers.update', ['customerId' => 0]) }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="_form" value="edit_customer">
                <input type="hidden" name="edit_customer_id" id="edit-customer-id" value="{{ old('edit_customer_id') }}">
                <div class="modal-body pt-1">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">ชื่อลูกค้า <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="edit-customer-name" class="form-control rounded-3" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">เบอร์โทรศัพท์ <span class="text-danger">*</span></label>
                            <input type="text" name="phone" id="edit-customer-phone" class="form-control rounded-3" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">ไลน์ไอดี</label>
                            <input type="text" name="line_id" id="edit-customer-line-id" class="form-control rounded-3">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">ระดับสมาชิก</label>
                            <select name="tier_id" id="edit-customer-tier-id" class="form-select rounded-3">
                                <option value="">ไม่มีระดับสมาชิก</option>
                                @foreach(($membershipTiers ?? []) as $tier)
                                <option value="{{ $tier['id'] }}">{{ $tier['name'] }} ({{ number_format($tier['discount_percent'], 2) }}%)</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">น้ำหนักมือที่ชอบ</label>
                            <select name="preferred_pressure_level" id="edit-customer-pressure" class="form-select rounded-3">
                                <option value="">ไม่ระบุ</option>
                                @foreach($pressureLevels as $option)
                                <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">ประวัติสุขภาพ</label>
                            <textarea name="health_notes" id="edit-customer-health-notes" class="form-control rounded-3" rows="3"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">จุดที่ต้องระวัง / ห้ามนวด</label>
                            <textarea name="contraindications" id="edit-customer-contraindications" class="form-control rounded-3" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">
                        <i class="bi bi-check2-circle me-1"></i> บันทึกการแก้ไข
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="historyCustomerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold"><i class="bi bi-clock-history me-2 text-primary"></i><span id="history-modal-title">ประวัติการใช้บริการ</span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-1" id="history-modal-body">
                <div class="text-center text-muted py-4">กำลังโหลดข้อมูล...</div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">ปิด</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="topupCustomerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-success"><i class="bi bi-cash-coin me-2"></i>เติมเงินเข้ากระเป๋า: <span id="topup-customer-name"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="topup-customer-form" action="">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold small">จำนวนเงินที่ต้องการเติม (บาท) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="bi bi-currency-bitcoin"></i></span>
                            <input type="number" name="amount" class="form-control form-control-lg fw-bold text-success" required min="0" step="0.01" value="0">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">โบนัสแถมฟรี (บาท)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="bi bi-gift text-primary"></i></span>
                            <input type="number" name="bonus" class="form-control form-control-lg fw-bold text-primary" min="0" step="0.01" value="0">
                        </div>
                        <div class="form-text">ตัวอย่าง: ลูกค้าจ่าย 5,000 ใส่ช่องบน 5000 ใส่ช่องนี้ 1000 (ลูกค้าจะได้ยอด 6,000)</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">หมายเหตุ (Optional)</label>
                        <textarea name="note" class="form-control" rows="2" placeholder="เช่น โปรโมชั่นปีใหม่"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-success rounded-pill px-4 fw-bold">
                        <i class="bi bi-check-circle me-1"></i> ยืนยันการเติมเงิน
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const addCustomerModalEl = document.getElementById('addCustomerModal');
    const editCustomerModalEl = document.getElementById('editCustomerModal');
    const historyCustomerModalEl = document.getElementById('historyCustomerModal');
    const historyModalTitleEl = document.getElementById('history-modal-title');
    const historyModalBodyEl = document.getElementById('history-modal-body');
    const editCustomerFormEl = document.getElementById('edit-customer-form');
    const openAddCustomerBtn = document.getElementById('open-add-customer-btn');
    const editUpdateUrlTemplate = @json(route('customers.update', ['customerId' => '__ID__']));
    const historyUrlTemplate = @json(route('customers.history', ['customerId' => '__ID__']));
    const topupUrlTemplate = @json(route('customers.topup', ['customerId' => '__ID__']));
    const deleteUrlTemplate = @json(route('customers.destroy', ['customerId' => '__ID__']));
    const csrfToken = @json(csrf_token());
    const oldForm = @json(old('_form'));
    const oldEditCustomerId = @json(old('edit_customer_id'));

    function resolveTemplateUrl(template, customerId) {
        return String(template || '').replace('__ID__', String(customerId || ''));
    }

    function showModal(modalEl) {
        if (!modalEl) return;
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
            return;
        }

        modalEl.style.display = 'block';
        modalEl.classList.add('show');
        modalEl.removeAttribute('aria-hidden');
        document.body.classList.add('modal-open');
    }

    function setEditFormCustomer(customer, updateUrl) {
        if (!editCustomerFormEl || !customer) return;

        editCustomerFormEl.action = updateUrl;
        const customerIdInput = document.getElementById('edit-customer-id');
        const nameInput = document.getElementById('edit-customer-name');
        const phoneInput = document.getElementById('edit-customer-phone');
        const lineIdInput = document.getElementById('edit-customer-line-id');
        const tierSelect = document.getElementById('edit-customer-tier-id');
        const pressureSelect = document.getElementById('edit-customer-pressure');
        const healthNotesInput = document.getElementById('edit-customer-health-notes');
        const contraindicationsInput = document.getElementById('edit-customer-contraindications');

        if (customerIdInput) customerIdInput.value = String(customer.id || '');
        if (nameInput) nameInput.value = String(customer.name || '');
        if (phoneInput) phoneInput.value = String(customer.phone || '');
        if (lineIdInput) lineIdInput.value = String(customer.line_id || '');
        if (tierSelect) tierSelect.value = customer.tier_id ? String(customer.tier_id) : '';
        if (pressureSelect) pressureSelect.value = customer.preferred_pressure_level || '';
        if (healthNotesInput) healthNotesInput.value = String(customer.health_notes || '');
        if (contraindicationsInput) contraindicationsInput.value = String(customer.contraindications || '');
    }

    function renderHistoryTable(customer, history) {
        const rows = (history || []).map((item) => `
            <tr>
                <td class="px-3 fw-semibold">${item.order_no || '-'}</td>
                <td>${item.created_at || '-'}</td>
                <td>
                    <div class="small">${item.item_summary || '-'}</div>
                    <div class="text-muted small">${Number(item.item_count || 0).toLocaleString('th-TH')} รายการ</div>
                </td>
                <td>${item.payment_method_label || '-'}</td>
                <td><span class="badge ${item.status === 'paid' ? 'text-bg-success' : 'text-bg-secondary'}">${item.status_label || '-'}</span></td>
                <td class="text-end fw-semibold">${Number(item.grand_total || 0).toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ฿</td>
            </tr>
        `).join('');

        const summary = `
            <div class="d-flex flex-wrap gap-3 border rounded-3 p-3 mb-3 bg-light">
                <div><span class="text-muted">ลูกค้า:</span> <span class="fw-semibold">${customer.name || '-'}</span></div>
                <div><span class="text-muted">เบอร์:</span> <span class="fw-semibold">${customer.phone || '-'}</span></div>
                <div><span class="text-muted">ใช้บริการทั้งหมด:</span> <span class="fw-semibold">${Number(customer.visit_count || 0).toLocaleString('th-TH')} ครั้ง</span></div>
                <div><span class="text-muted">ยอดสะสม:</span> <span class="fw-semibold">${Number(customer.total_spent || 0).toLocaleString('th-TH', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} ฿</span></div>
            </div>
        `;

        if (!rows) {
            return `${summary}<div class="text-center text-muted py-4">ยังไม่มีประวัติการใช้บริการของลูกค้ารายนี้</div>`;
        }

        return `
            ${summary}
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="px-3">เลขที่บิล</th>
                            <th>วันเวลา</th>
                            <th>รายการ</th>
                            <th>ชำระ</th>
                            <th>สถานะ</th>
                            <th class="text-end">ยอดสุทธิ</th>
                        </tr>
                    </thead>
                    <tbody>${rows}</tbody>
                </table>
            </div>
        `;
    }

    async function openHistoryModal(customerId, customerName) {
        if (!historyModalBodyEl || !historyModalTitleEl) return;

        historyModalTitleEl.textContent = `ประวัติการใช้บริการ: ${customerName || '-'}`;
        historyModalBodyEl.innerHTML = '<div class="text-center text-muted py-4">กำลังโหลดข้อมูล...</div>';
        showModal(historyCustomerModalEl);

        try {
            const response = await fetch(resolveTemplateUrl(historyUrlTemplate, customerId), {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok) {
                throw new Error(payload.message || 'โหลดประวัติลูกค้าไม่สำเร็จ');
            }

            historyModalBodyEl.innerHTML = renderHistoryTable(payload.customer || {}, payload.history || []);
        } catch (error) {
            historyModalBodyEl.innerHTML = `<div class="text-center text-danger py-4">${error.message}</div>`;
        }
    }

    if (openAddCustomerBtn) {
        openAddCustomerBtn.addEventListener('click', () => showModal(addCustomerModalEl));
    }

    document.querySelectorAll('.edit-customer-btn').forEach((button) => {
        button.addEventListener('click', () => {
            const updateUrl = button.dataset.updateUrl || '';
            const rawCustomer = button.dataset.customer || '{}';
            const customer = JSON.parse(rawCustomer);
            setEditFormCustomer(customer, updateUrl);
            showModal(editCustomerModalEl);
        });
    });

    document.querySelectorAll('.history-customer-btn').forEach((button) => {
        button.addEventListener('click', () => {
            openHistoryModal(
                Number(button.dataset.customerId || 0),
                String(button.dataset.customerName || '')
            );
        });
    });

    const topupCustomerModalEl = document.getElementById('topupCustomerModal');
    const topupCustomerFormEl = document.getElementById('topup-customer-form');
    const topupCustomerNameEl = document.getElementById('topup-customer-name');

    document.querySelectorAll('.topup-customer-btn').forEach((button) => {
        button.addEventListener('click', () => {
            const customerId = button.dataset.customerId;
            const customerName = button.dataset.customerName;
            
            topupCustomerNameEl.textContent = customerName;
            topupCustomerFormEl.action = resolveTemplateUrl(topupUrlTemplate, customerId);
            topupCustomerFormEl.reset();
            
            showModal(topupCustomerModalEl);
        });
    });


    if (oldForm === 'add_customer') {
        showModal(addCustomerModalEl);
    }

    if (oldForm === 'edit_customer' && oldEditCustomerId) {
        setEditFormCustomer({
            id: oldEditCustomerId,
            name: @json(old('name')),
            phone: @json(old('phone')),
            line_id: @json(old('line_id')),
            tier_id: @json(old('tier_id')),
            preferred_pressure_level: @json(old('preferred_pressure_level')),
            health_notes: @json(old('health_notes')),
            contraindications: @json(old('contraindications')),
        }, resolveTemplateUrl(editUpdateUrlTemplate, oldEditCustomerId));
        showModal(editCustomerModalEl);
    }
</script>
@endpush
