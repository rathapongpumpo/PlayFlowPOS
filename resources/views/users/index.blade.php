@extends('layouts.main')

@section('title', 'จัดการผู้ใช้งาน - PlayFlowPOS')
@section('page_title', 'จัดการผู้ใช้งาน')
@section('page_subtitle', 'User Accounts')

@push('head')
<style>
    .users-page .hero-card,
    .users-page .table-card {
        border: 1px solid rgba(31, 115, 224, 0.14) !important;
        box-shadow: 0 16px 30px rgba(17, 81, 146, 0.09) !important;
    }

    .users-page .soft-box,
    .users-page .section-card {
        border: 1px solid rgba(31, 115, 224, 0.13);
        border-radius: 1rem;
        background: #fff;
        box-shadow: 0 8px 18px rgba(14, 72, 133, 0.07);
    }

    .users-page .soft-box {
        padding: 1rem;
        height: 100%;
    }

    .users-page .section-card {
        padding: 0.95rem 1rem;
        background: linear-gradient(180deg, #fbfdff, #f4faff);
    }

    .users-page .gradient-btn {
        background: linear-gradient(135deg, #2d8ff0, #14b89a) !important;
        border-color: #2d8ff0 !important;
        color: #fff !important;
    }

    .users-page .badge-soft {
        border-radius: 999px;
        padding: 0.26rem 0.6rem;
        font-size: 0.75rem;
        font-weight: 700;
        color: #0f65b8;
        background: rgba(45, 143, 240, 0.12);
    }

    .users-page .role-pill {
        border-radius: 999px;
        padding: 0.24rem 0.58rem;
        font-size: 0.72rem;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
    }

    .users-page .role-pill.is-super { color: #d35400; background: rgba(230, 126, 34, 0.12); }
    .users-page .role-pill.is-owner { color: #8f5d00; background: rgba(236, 179, 44, 0.18); }
    .users-page .role-pill.is-manager { color: #6d59d8; background: rgba(129, 96, 255, 0.12); }
    .users-page .role-pill.is-cashier { color: #0c907d; background: rgba(20, 184, 154, 0.14); }
    .users-page .role-pill.is-masseuse { color: #2c79b8; background: rgba(31, 115, 224, 0.1); }

    .users-page .user-summary,
    .users-page .source-preview {
        display: flex;
        align-items: center;
        gap: 0.8rem;
        min-width: 0;
    }

    .users-page .user-avatar,
    .users-page .source-avatar {
        width: 48px;
        height: 48px;
        border-radius: 999px;
        object-fit: cover;
        border: 2px solid rgba(31, 115, 224, 0.14);
        background: #fff;
        flex-shrink: 0;
    }

    .users-page .user-name,
    .users-page .source-title {
        font-weight: 700;
        color: #1f456c;
        line-height: 1.15;
    }

    .users-page .user-meta,
    .users-page .source-meta {
        color: #6b7f93;
        font-size: 0.78rem;
        line-height: 1.25;
    }

    .users-page .pf-modal .modal-content {
        border: 1px solid rgba(31, 115, 224, 0.16);
        border-radius: 1.1rem;
        box-shadow: 0 24px 48px rgba(14, 60, 120, 0.18);
    }

    .users-page .pf-modal .modal-header {
        background: linear-gradient(135deg, #2d8ff0, #14b89a);
        color: #fff;
        border-radius: 1.1rem 1.1rem 0 0;
        border-bottom: none;
    }

    .users-page .pf-modal .modal-header .btn-close {
        filter: brightness(0) invert(1);
    }

    .users-page .standalone-card {
        border-color: rgba(236, 179, 44, 0.32);
        background: linear-gradient(180deg, rgba(255,249,229,.95), rgba(255,244,212,.95));
    }

    .users-page .table thead th {
        color: #1e5f9d;
        background: linear-gradient(180deg, #eef6ff 0%, #e8f3ff 100%);
        border-bottom-color: rgba(31, 115, 224, 0.15);
        font-size: 0.82rem;
        white-space: nowrap;
    }

    @media (max-width: 767.98px) {
        .users-page .card-body {
            padding: 0.95rem;
        }
    }
</style>
@endpush

@section('content')
@php
    $roleClasses = [
        'super_admin' => 'is-super',
        'shop_owner' => 'is-owner',
        'branch_manager' => 'is-manager',
        'cashier' => 'is-cashier',
        'masseuse' => 'is-masseuse',
    ];
    $roleLabels = [
        'super_admin' => 'Super Admin',
        'shop_owner' => 'เจ้าของร้าน',
        'branch_manager' => 'ผู้จัดการสาขา',
        'cashier' => 'แคชเชียร์',
        'masseuse' => 'หมอนวด',
    ];
    $standaloneRoleValues = $standaloneRoles ?? ['super_admin', 'shop_owner'];
@endphp

<div class="row g-3 users-page">
    @if(session('success'))
        <div class="col-12">
            <div class="alert alert-success border-0 shadow-sm rounded-4 mb-0">{{ session('success') }}</div>
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

    @if(!($moduleReady ?? false))
        <div class="col-12">
            <div class="alert alert-warning border-0 shadow-sm rounded-4 mb-0">
                <div class="fw-bold mb-1">ยังไม่พบตาราง users</div>
                <div>โปรดตรวจสอบฐานข้อมูลจริงของระบบก่อนใช้งานโมดูลนี้</div>
            </div>
        </div>
    @elseif(($canManageAllBranches ?? false) && !($shopSelected ?? true))
        <div class="col-12">
            <div class="alert alert-info border-0 shadow-sm rounded-4 mb-0 d-flex flex-column gap-2">
                <div class="fw-bold">กรุณาเลือกร้านจากพอร์ทัลก่อน</div>
                <div>เมนูผู้ใช้งานระบบจะอ้างอิงตามร้านที่คุณเลือกอยู่ในพอร์ทัลร้าน</div>
                <div><a href="{{ route('system.shops.index') }}" class="btn gradient-btn rounded-pill px-4">ไปพอร์ทัลร้าน</a></div>
            </div>
        </div>
    @elseif($requiresBranchSetup ?? false)
        <div class="col-12">
            <div class="alert alert-info border-0 shadow-sm rounded-4 mb-0 d-flex flex-column gap-2">
                <div class="fw-bold">ร้านนี้ยังไม่มีสาขา</div>
                <div>กรุณาสร้างสาขาแรกก่อน แล้วค่อยกลับมาเพิ่มผู้ใช้งานของร้านนี้</div>
                <div><a href="{{ route('branches.index') }}" class="btn gradient-btn rounded-pill px-4">ไปสร้างสาขาแรก</a></div>
            </div>
        </div>
    @else
        <div class="col-12">
            <div class="card hero-card border-0">
                <div class="card-body p-3 p-lg-4">
                    <div class="row g-3 align-items-end">
                        <div class="col-12 col-lg-4">
                            <div class="soft-box d-flex flex-column gap-2 justify-content-center">
                                <div class="fw-bold text-primary mb-0">จัดการบัญชีผู้ใช้</div>
                                <button type="button" class="btn gradient-btn rounded-3 w-100" onclick="pfOpenModal('addUserModal')">
                                    <i class="bi bi-person-plus-fill me-1"></i>เพิ่มผู้ใช้งานใหม่
                                </button>
                            </div>
                        </div>
                        <div class="col-12 col-lg-8">
                            <div class="soft-box">
                                <form method="GET" action="{{ route('users.index') }}" class="row g-2 align-items-end">
                                    <div class="col-12 col-md-5">
                                        <label class="form-label small fw-bold">ค้นหาผู้ใช้</label>
                                        <input type="text" name="search" class="form-control" value="{{ $search }}" placeholder="ชื่อพนักงาน, ชื่อเล่น, Username">
                                    </div>
                                    <div class="col-6 col-md-4">
                                        <label class="form-label small fw-bold">สาขา</label>
                                        <select name="branch_id" class="form-select">
                                            <option value="">ทุกสาขา</option>
                                            @foreach($branches as $br)
                                                <option value="{{ $br['id'] }}" {{ ($branchFilter ?? null) == $br['id'] ? 'selected' : '' }}>{{ $br['name'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-6 col-md-3 d-grid">
                                        <button type="submit" class="btn gradient-btn rounded-3"><i class="bi bi-filter me-1"></i>กรอง</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card table-card border-0">
                <div class="card-header bg-white border-0 pt-3 pb-2 d-flex align-items-center justify-content-between">
                    <h6 class="fw-bold mb-0"><i class="bi bi-shield-lock-fill me-2 text-primary"></i>รายการผู้ใช้งาน</h6>
                    <span class="badge-soft">{{ count($users) }} บัญชี</span>
                </div>
                <div class="card-body p-2 p-lg-3">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th style="min-width:50px;">#</th>
                                    <th style="min-width:260px;">ผู้ใช้ / Username</th>
                                    <th style="min-width:140px;">บทบาท</th>
                                    <th style="min-width:130px;">สาขา</th>
                                    <th style="min-width:120px;">เข้าสู่ระบบล่าสุด</th>
                                    <th style="min-width:80px;">สถานะ</th>
                                    <th class="text-end" style="min-width:190px;">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($users as $idx => $user)
                                    <tr>
                                        <td class="text-muted small">{{ $idx + 1 }}</td>
                                        <td>
                                            <form id="user-form-{{ $user['id'] }}" method="POST" action="{{ route('users.update', ['userId' => $user['id']]) }}">
                                                @csrf
                                                @method('PUT')
                                            </form>
                                            <div class="user-summary">
                                                @if($user['staff_avatar'])
                                                    <img src="{{ $user['staff_avatar'] }}" alt="{{ $user['display_name'] }}" class="user-avatar">
                                                @else
                                                    <div class="user-avatar d-flex align-items-center justify-content-center text-primary">
                                                        <i class="bi bi-person-fill"></i>
                                                    </div>
                                                @endif
                                                <div class="min-w-0">
                                                    <div class="user-name">{{ $user['display_name'] }}</div>
                                                    <div class="user-meta">{{ $user['username'] }}</div>
                                                    <div class="user-meta">{{ $user['display_meta'] }}@if($user['display_submeta']) • {{ $user['display_submeta'] }}@endif</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="mb-2">
                                                <span class="role-pill {{ $roleClasses[$user['role']] ?? 'is-cashier' }}">{{ $roleLabels[$user['role']] ?? $user['role'] }}</span>
                                            </div>
                                            <select form="user-form-{{ $user['id'] }}" name="role" class="form-select">
                                                @php
                                                    $hasRole = false;
                                                    foreach($roles as $r) {
                                                        if($r['value'] === $user['role']) $hasRole = true;
                                                    }
                                                @endphp
                                                @if(!$hasRole)
                                                    <option value="{{ $user['role'] }}" selected>{{ $roleLabels[$user['role']] ?? $user['role'] }} (ปัจจุบัน)</option>
                                                @endif
                                                @foreach($roles as $role)
                                                    <option value="{{ $role['value'] }}" {{ $user['role'] === $role['value'] ? 'selected' : '' }}>{{ $role['label'] }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <select form="user-form-{{ $user['id'] }}" name="branch_id" class="form-select">
                                                <option value="">ไม่ระบุ</option>
                                                @foreach($branches as $br)
                                                    <option value="{{ $br['id'] }}" {{ $user['branch_id'] == $br['id'] ? 'selected' : '' }}>{{ $br['name'] }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <span class="small text-muted">
                                                @if($user['last_login'])
                                                    {{ \Carbon\Carbon::parse($user['last_login'])->diffForHumans() }}
                                                @else
                                                    ยังไม่เคย
                                                @endif
                                            </span>
                                        </td>
                                        <td>
                                            @if($supportsActiveToggle)
                                                <div class="form-check form-switch">
                                                    <input form="user-form-{{ $user['id'] }}" class="form-check-input" type="checkbox" name="is_active" value="1" {{ $user['is_active'] ? 'checked' : '' }}>
                                                </div>
                                            @else
                                                <span class="badge-soft">ใช้งาน</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <div class="d-flex gap-1 justify-content-end flex-nowrap">
                                                <button form="user-form-{{ $user['id'] }}" type="submit" class="btn btn-outline-primary btn-sm rounded-pill px-2">
                                                    <i class="bi bi-save2 me-1"></i>บันทึก
                                                </button>
                                                <button type="button" class="btn btn-outline-warning btn-sm rounded-pill px-2" onclick="pfOpenResetModal({{ $user['id'] }}, '{{ addslashes($user['display_name']) }}')">
                                                    <i class="bi bi-shield-lock-fill"></i>
                                                </button>
                                                @if($user['id'] !== (int) (auth()->user()->id ?? 0))
                                                    <form method="POST" action="{{ route('users.destroy', ['userId' => $user['id']]) }}" onsubmit="return confirm('ยืนยันลบผู้ใช้ {{ addslashes($user['display_name']) }} ?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-2">
                                                            <i class="bi bi-trash3"></i>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">ยังไม่มีผู้ใช้งาน</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade pf-modal" id="addUserModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold"><i class="bi bi-person-plus-fill me-2"></i>เพิ่มผู้ใช้งานใหม่</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST" action="{{ route('users.store') }}" id="addUserForm">
                        @csrf
                        <div class="modal-body p-4">
                            <div class="row g-3">
                                <div class="col-12 col-md-6">
                                    <label class="form-label small fw-bold">บทบาท <span class="text-danger">*</span></label>
                                    <select name="role" id="addUserRole" class="form-select" required>
                                        @foreach($roles as $role)
                                            <option value="{{ $role['value'] }}" {{ old('role', 'cashier') === $role['value'] ? 'selected' : '' }}>{{ $role['label'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label small fw-bold">สาขา</label>
                                    <select name="branch_id" id="addUserBranch" class="form-select">
                                        <option value="">ไม่ระบุ</option>
                                        @foreach($branches as $br)
                                            <option value="{{ $br['id'] }}" {{ old('branch_id') == $br['id'] ? 'selected' : '' }}>{{ $br['name'] }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-12">
                                    <div id="standaloneHintCard" class="section-card standalone-card d-none">
                                        <div class="fw-bold text-dark mb-1">บัญชีประเภทนี้ไม่ต้องผูกพนักงาน</div>
                                        <div class="small text-muted mb-0">ใช้สำหรับเจ้าของร้านหรือผู้ดูแลระบบ จึงสร้าง user ได้เลยโดยไม่ต้องเลือกพนักงาน</div>
                                    </div>
                                </div>

                                <div class="col-12" id="sourceSelectionBlock">
                                    <label class="form-label small fw-bold">เลือกพนักงานหรือหมอนวด <span class="text-danger">*</span></label>
                                    <select name="staff_id" id="account_source" class="form-select">
                                        <option value="">เลือกจากรายชื่อในระบบ</option>
                                        @foreach($staffOptions as $staff)
                                            <option
                                                value="{{ $staff['id'] }}"
                                                data-name="{{ $staff['name'] }}"
                                                data-position="{{ $staff['position'] }}"
                                                data-branch-id="{{ $staff['branch_id'] }}"
                                                data-branch-name="{{ $staff['branch_name'] }}"
                                                data-avatar="{{ $staff['avatar'] }}"
                                                data-type-label="{{ $staff['type_label'] }}"
                                                {{ old('staff_id') == $staff['id'] ? 'selected' : '' }}
                                            >
                                                {{ $staff['name'] }} - {{ $staff['branch_name'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-12" id="sourcePreviewBlock">
                                    <div class="section-card">
                                        <div class="source-preview">
                                            <img id="selectedSourceAvatar" src="" alt="source preview" class="source-avatar">
                                            <div>
                                                <div id="selectedSourceName" class="source-title">ยังไม่ได้เลือกบุคลากร</div>
                                                <div id="selectedSourceMeta" class="source-meta">เมื่อเลือกพนักงานหรือหมอนวด ระบบจะผูกบัญชีนี้เข้ากับคนนั้นโดยตรง</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div id="sourceRequiredWarning" class="alert alert-warning border-0 rounded-4 mb-0 d-none">
                                        ต้องเลือกพนักงานหรือหมอนวดก่อน จึงจะสร้างบัญชีประเภทนี้ได้
                                    </div>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label small fw-bold">Username <span class="text-danger">*</span></label>
                                    <input type="text" name="username" class="form-control" value="{{ old('username') }}" placeholder="เช่น cashier01" required>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label small fw-bold">รหัสผ่าน <span class="text-danger">*</span></label>
                                    <input type="password" name="password" class="form-control" placeholder="อย่างน้อย 4 ตัวอักษร" required>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">ยกเลิก</button>
                            <button type="submit" id="addUserSubmitButton" class="btn gradient-btn rounded-pill px-4">
                                <i class="bi bi-plus-lg me-1"></i>เพิ่มผู้ใช้
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade pf-modal" id="resetPasswordModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold"><i class="bi bi-shield-lock-fill me-2"></i>รีเซ็ตรหัสผ่าน</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form id="resetPasswordForm" method="POST" action="">
                        @csrf
                        <div class="modal-body p-4">
                            <div class="alert alert-info border-0 rounded-3 mb-3">
                                กำลังรีเซ็ตรหัสผ่านให้ <strong id="resetUserName"></strong>
                            </div>
                            <label class="form-label small fw-bold">รหัสผ่านใหม่ <span class="text-danger">*</span></label>
                            <input type="password" name="new_password" class="form-control" placeholder="อย่างน้อย 4 ตัวอักษร" required>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">ยกเลิก</button>
                            <button type="submit" class="btn btn-warning text-white rounded-pill px-4">
                                <i class="bi bi-shield-lock-fill me-1"></i>รีเซ็ตรหัสผ่าน
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    function pfOpenModal(modalId) {
        var modalEl = document.getElementById(modalId);
        if (!modalEl) return;
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        }
    }

    function pfOpenResetModal(userId, userName) {
        var form = document.getElementById('resetPasswordForm');
        var nameEl = document.getElementById('resetUserName');
        if (form) {
            form.action = '/users/' + userId + '/reset-password';
        }
        if (nameEl) {
            nameEl.textContent = userName;
        }
        pfOpenModal('resetPasswordModal');
    }

    (function () {
        var standaloneRoles = @json($standaloneRoleValues);
        var roleSelect = document.getElementById('addUserRole');
        var sourceSelect = document.getElementById('account_source');
        var branchSelect = document.getElementById('addUserBranch');
        var sourceBlock = document.getElementById('sourceSelectionBlock');
        var previewBlock = document.getElementById('sourcePreviewBlock');
        var hintCard = document.getElementById('standaloneHintCard');
        var warningBox = document.getElementById('sourceRequiredWarning');
        var submitButton = document.getElementById('addUserSubmitButton');
        var sourceName = document.getElementById('selectedSourceName');
        var sourceMeta = document.getElementById('selectedSourceMeta');
        var sourceAvatar = document.getElementById('selectedSourceAvatar');
        if (!roleSelect) return;

        function updateSourcePreview() {
            if (!sourceSelect) return;
            var option = sourceSelect.options[sourceSelect.selectedIndex];

            if (!option || !option.value) {
                if (sourceName) sourceName.textContent = 'ยังไม่ได้เลือกบุคลากร';
                if (sourceMeta) sourceMeta.textContent = 'เมื่อเลือกพนักงานหรือหมอนวด ระบบจะผูกบัญชีนี้เข้ากับคนนั้นโดยตรง';
                if (sourceAvatar) sourceAvatar.removeAttribute('src');
                return;
            }

            var personName = option.getAttribute('data-name') || '-';
            var position = option.getAttribute('data-position') || '-';
            var branchName = option.getAttribute('data-branch-name') || '-';
            var branchId = option.getAttribute('data-branch-id') || '';
            var avatar = option.getAttribute('data-avatar') || '';
            var typeLabel = option.getAttribute('data-type-label') || '';

            if (sourceName) sourceName.textContent = personName;
            if (sourceMeta) sourceMeta.textContent = typeLabel + ' • ' + position + ' • ' + branchName;
            if (sourceAvatar && avatar) sourceAvatar.src = avatar;
            if (branchSelect && branchId && !branchSelect.value) {
                branchSelect.value = branchId;
            }
        }

        function syncRoleState() {
            var role = roleSelect.value || '';
            var isStandalone = standaloneRoles.indexOf(role) !== -1;
            var hasSource = sourceSelect && sourceSelect.value;

            if (sourceBlock) sourceBlock.classList.toggle('d-none', isStandalone);
            if (previewBlock) previewBlock.classList.toggle('d-none', isStandalone);
            if (hintCard) hintCard.classList.toggle('d-none', !isStandalone);

            if (branchSelect) {
                branchSelect.disabled = isStandalone;
                if (isStandalone) {
                    branchSelect.value = '';
                }
            }

            if (sourceSelect) {
                sourceSelect.required = !isStandalone;
                if (isStandalone) {
                    sourceSelect.value = '';
                }
            }

            updateSourcePreview();

            var shouldWarn = !isStandalone && !hasSource;
            if (warningBox) warningBox.classList.toggle('d-none', !shouldWarn);
            if (submitButton) submitButton.disabled = shouldWarn;
        }

        if (sourceSelect) {
            sourceSelect.addEventListener('change', function () {
                updateSourcePreview();
                syncRoleState();
            });
        }

        roleSelect.addEventListener('change', syncRoleState);
        updateSourcePreview();
        syncRoleState();
    })();
</script>
@endpush
