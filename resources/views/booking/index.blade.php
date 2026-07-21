@extends('layouts.main')

@section('title', 'ตารางคิวนวด - PlayFlow')
@section('page_title', 'ตารางคิวนวด')

@section('content')
@php
    $branchOpenTime = $branchOpenTime ?? '10:00';
    $branchCloseTime = $branchCloseTime ?? '20:00';
    $startMinutes = ((int) substr($branchOpenTime, 0, 2) * 60) + (int) substr($branchOpenTime, 3, 2);
    $endMinutes = ((int) substr($branchCloseTime, 0, 2) * 60) + (int) substr($branchCloseTime, 3, 2);
    $slotTimes = [];
    for ($minutes = $startMinutes; $minutes < $endMinutes; $minutes += 60) {
        $slotTimes[] = sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }
    if (empty($slotTimes)) {
        $slotTimes[] = $branchOpenTime;
    }
    $slotCount = count($slotTimes);
@endphp

<div class="booking-page booking-mobile-safe">
    <div class="card shadow-sm border-0 booking-shell">
        <div class="card-body p-4 booking-card-body">
            <div class="queue-toolbar d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4 gap-3">
                <div class="d-flex flex-wrap gap-2 align-items-center w-100" style="max-width: 100%;">
                    <input type="date" id="queue-date" class="form-control rounded-pill px-3 shadow-none border-secondary-subtle flex-grow-1" value="{{ $selectedDate }}" style="width: auto; max-width: 160px;">
                    <button class="btn btn-primary rounded-pill px-4 flex-shrink-0" onclick="openModal()"><i class="bi bi-plus-lg me-2"></i> เพิ่มคิว</button>
                    <span class="badge text-bg-light border rounded-pill px-3 py-2 fw-semibold">
                        <i class="bi bi-clock me-1"></i> {{ $branchOpenTime }} - {{ $branchCloseTime }}
                    </span>
                </div>
                <span class="badge text-bg-light border rounded-3 px-3 py-2 fw-semibold text-wrap text-start lh-base d-block w-100 w-md-auto">
                    <i class="bi bi-info-circle me-1"></i> กดแทบคิวเพื่อแก้บริการ/เวลา/หมอ และชำระเงิน
                </span>
            </div>

            <div class="queue-board-toolbar d-flex align-items-center justify-content-between gap-2 mb-3">
                <span class="queue-zoom-hint small text-muted">
                    <i class="bi bi-arrows-angle-expand me-1"></i>ซูมได้เฉพาะตาราง
                </span>
                <div class="btn-group btn-group-sm queue-zoom-controls" role="group" aria-label="Queue board zoom controls">
                    <button type="button" class="btn btn-outline-secondary" id="queue-zoom-out" aria-label="Zoom out">
                        <i class="bi bi-dash-lg"></i>
                    </button>
                    <button type="button" class="btn btn-outline-secondary fw-semibold" id="queue-zoom-reset">100%</button>
                    <button type="button" class="btn btn-outline-secondary" id="queue-zoom-in" aria-label="Zoom in">
                        <i class="bi bi-plus-lg"></i>
                    </button>
                </div>
            </div>

            <div class="table-responsive rounded-4 border queue-board-wrap">
                <div id="queue-board" class="queue-board bg-white" style="--slot-count: {{ $slotCount }};">
                    <div class="queue-grid-row queue-head-row">
                        <div class="queue-cell queue-staff-head">หมอนวด</div>
                        @foreach($slotTimes as $slotTime)
                        <div class="queue-cell queue-time-head">{{ $slotTime }}</div>
                        @endforeach
                    </div>

                    @foreach($staff as $s)
                    @php
                        $staffNameParts = preg_split('/\s+/', trim($s['name']), 2);
                        $staffFirstName = $staffNameParts[0] ?? $s['name'];
                        $staffLastName = $staffNameParts[1] ?? '';
                    @endphp
                    <div class="queue-grid-row queue-data-row" data-staff-id="{{ $s['id'] }}">
                        <div class="queue-cell queue-staff-cell" title="{{ $s['name'] }}">
                            <div class="queue-staff-name fw-bold">
                                <span>{{ $staffFirstName }}</span>
                                @if($staffLastName !== '')
                                <span>{{ $staffLastName }}</span>
                                @endif
                            </div>
                        </div>
                        @foreach($slotTimes as $slotTime)
                        <div class="queue-cell queue-slot-cell"
                            data-time="{{ $slotTime }}"
                            onclick="openModal({staffId:'{{ $s['id'] }}', time:'{{ $slotTime }}'})"></div>
                        @endforeach
                        <div class="booking-row-layer" id="layer-{{ $s['id'] }}" data-staff-id="{{ $s['id'] }}"></div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

@include('booking.partials.modal')
@endsection

@push('head')
<style>
    .booking-page,
    .booking-shell,
    .booking-card-body {
        min-width: 0;
    }
    .queue-board {
        --board-zoom: 1;
        --staff-col-base: 124px;
        --slot-base: 132px;
        --head-row-base-height: 62px;
        --data-row-base-height: 94px;
        --staff-font-base: 0.92rem;
        --time-font-base: 0.86rem;
        --card-top-base: 8px;
        --card-radius-base: 12px;
        --card-padding-y-base: 0.42rem;
        --card-padding-x-base: 0.58rem;
        --card-min-width-base: 84px;
        --card-customer-font-base: 0.84rem;
        --card-service-font-base: 0.72rem;
        --card-meta-font-base: 0.68rem;
        --paid-font-base: 0.62rem;
        --paid-pad-y-base: 0.06rem;
        --paid-pad-x-base: 0.42rem;
        --staff-col-width: calc(var(--staff-col-base) * var(--board-zoom));
        --slot-width: calc(var(--slot-base) * var(--board-zoom));
        min-width: calc(var(--staff-col-width) + (var(--slot-count) * var(--slot-width)));
    }
    .queue-board-wrap {
        max-width: 100%;
        overscroll-behavior-x: contain;
        overscroll-behavior-y: auto;
        -webkit-overflow-scrolling: touch;
        touch-action: pan-x pan-y;
        padding-bottom: 0.5rem;
    }
    .queue-grid-row {
        display: grid;
        grid-template-columns: var(--staff-col-width) repeat(var(--slot-count), minmax(var(--slot-width), 1fr));
        position: relative;
    }
    .queue-cell {
        border-top: 1px solid #e5edf5;
    }
    .queue-head-row .queue-cell {
        height: calc(var(--head-row-base-height) * var(--board-zoom));
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f4f8fc;
        border-top: 0;
        border-bottom: 1px solid #d9e6f2;
        font-weight: 700;
        color: #27496d;
    }
    .queue-staff-head,
    .queue-staff-cell {
        position: sticky;
        left: 0;
        z-index: 30;
        border-right: 1px solid #dce7f2;
        background: #f8fbff;
    }
    .queue-staff-head {
        z-index: 40;
        justify-content: flex-start;
        padding-left: 1rem;
    }
    .queue-time-head {
        border-left: 1px solid #e5edf5;
        font-size: calc(var(--time-font-base) * var(--board-zoom));
    }
    .queue-data-row .queue-cell {
        min-height: calc(var(--data-row-base-height) * var(--board-zoom));
    }
    .queue-staff-cell {
        padding: 0.9rem 0.85rem;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: flex-start;
    }
    .queue-staff-name {
        display: flex;
        flex-direction: column;
        gap: 0.12rem;
        line-height: 1.12;
        word-break: break-word;
        font-size: calc(var(--staff-font-base) * var(--board-zoom));
    }
    .queue-slot-cell {
        border-left: 1px solid #edf2f7;
        cursor: crosshair;
        background: linear-gradient(180deg, #ffffff 0%, #fcfeff 100%);
        transition: background-color 0.15s;
    }
    .queue-slot-cell:hover {
        background: #edf7ff;
    }
    .booking-row-layer {
        position: absolute;
        left: var(--staff-col-width);
        right: 0;
        top: 0;
        bottom: 0;
        pointer-events: auto;
        z-index: 10;
    }
    .booking-card {
        position: absolute;
        top: calc(var(--card-top-base) * var(--board-zoom));
        height: calc(100% - (var(--card-top-base) * var(--board-zoom) * 2));
        border-radius: calc(var(--card-radius-base) * var(--board-zoom));
        border: 1px solid;
        padding: calc(var(--card-padding-y-base) * var(--board-zoom)) calc(var(--card-padding-x-base) * var(--board-zoom));
        cursor: pointer;
        pointer-events: auto;
        z-index: 50;
        overflow: hidden;
        transition: transform 0.2s, box-shadow 0.2s;
        min-width: calc(var(--card-min-width-base) * var(--board-zoom));
    }
    .booking-card:hover {
        transform: translateY(-1px);
        box-shadow: 0 10px 20px rgba(15, 66, 120, 0.16);
    }
    .booking-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 0.3rem;
        font-size: calc(var(--card-meta-font-base) * var(--board-zoom));
        margin-bottom: 0.18rem;
    }
    .booking-time {
        font-weight: 700;
        opacity: 0.9;
    }
    .booking-paid,
    .booking-unpaid {
        border-radius: 999px;
        padding: calc(var(--paid-pad-y-base) * var(--board-zoom)) calc(var(--paid-pad-x-base) * var(--board-zoom));
        font-size: calc(var(--paid-font-base) * var(--board-zoom));
        font-weight: 700;
        white-space: nowrap;
    }
    .booking-paid {
        background: rgba(20, 184, 154, 0.18);
        color: #0f8b73;
    }
    .booking-unpaid {
        background: rgba(31, 115, 224, 0.15);
        color: #1a5ea8;
    }
    .booking-customer {
        font-weight: 700;
        font-size: calc(var(--card-customer-font-base) * var(--board-zoom));
        line-height: 1.1;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .booking-service {
        opacity: 0.86;
        font-size: calc(var(--card-service-font-base) * var(--board-zoom));
        line-height: 1.18;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .queue-board-toolbar {
        padding: 0.78rem 0.95rem;
        border-radius: 1rem;
        border: 1px solid rgba(180, 149, 88, 0.28);
        background: linear-gradient(135deg, rgba(248, 244, 235, 0.96), rgba(234, 224, 204, 0.9));
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.72), 0 10px 22px rgba(43, 58, 79, 0.08);
    }
    .queue-zoom-hint {
        color: #7d6740 !important;
        font-size: 0.78rem !important;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }
    .queue-zoom-controls {
        padding: 0.2rem;
        border-radius: 999px;
        background: linear-gradient(135deg, #192838, #0f1c2a);
        border: 1px solid rgba(191, 161, 101, 0.34);
        box-shadow: 0 12px 24px rgba(15, 26, 40, 0.22), inset 0 1px 0 rgba(255, 255, 255, 0.08);
        gap: 0.25rem;
    }
    .queue-zoom-controls .btn {
        min-width: 2.4rem;
        border: 0;
        border-radius: 999px !important;
        background: transparent;
        color: #f3e8cf;
        font-weight: 700;
        box-shadow: none !important;
        transition: transform 0.18s ease, background-color 0.18s ease, color 0.18s ease, opacity 0.18s ease;
    }
    .queue-zoom-controls #queue-zoom-reset {
        min-width: 4.4rem;
        background: linear-gradient(135deg, #d8bd82, #b88d4d);
        color: #1e2430;
        letter-spacing: 0.04em;
        text-shadow: 0 1px 0 rgba(255, 255, 255, 0.32);
    }
    .queue-zoom-controls .btn:hover,
    .queue-zoom-controls .btn:focus-visible {
        background: rgba(255, 255, 255, 0.12);
        color: #fff4d8;
        transform: translateY(-1px);
    }
    .queue-zoom-controls #queue-zoom-reset:hover,
    .queue-zoom-controls #queue-zoom-reset:focus-visible {
        background: linear-gradient(135deg, #e4c98f, #c79b56);
        color: #171c25;
    }
    .queue-zoom-controls .btn:disabled {
        opacity: 0.4;
        transform: none;
    }
    .state-waiting {
        background: #e6f5ff;
        border-color: #9fd5f5;
        color: #245f98;
    }
    .state-in_service {
        background: #e3f9f2;
        border-color: #98e3d1;
        color: #117a67;
    }
    .state-completed {
        background: #eef4f8;
        border-color: #cbdae4;
        color: #4e7186;
    }
    .state-cancelled {
        background: #fff1f1;
        border-color: #f4b7b7;
        color: #c34a4a;
    }
    .selected-service-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        border-radius: 999px;
        padding: 0.22rem 0.52rem;
        background-color: #ffffff;
        border: 1px solid #b6d5ef;
        color: #275f98;
        font-size: 0.78rem;
        font-weight: 600;
    }
    .selected-service-chip button {
        border: 0;
        background: transparent;
        color: inherit;
        padding: 0;
        line-height: 1;
    }
    @media (max-width: 991.98px) {
        .booking-mobile-safe {
            padding-bottom: var(--pf-mobile-nav-offset, calc(84px + env(safe-area-inset-bottom)));
        }
        .booking-page {
            margin-left: -0.9rem;
            margin-right: -0.9rem;
        }
        .booking-shell {
            border-radius: 1rem;
        }
        .booking-card-body {
            padding: 0.9rem !important;
        }
        .queue-toolbar {
            margin-bottom: 0.9rem !important;
        }
        .queue-toolbar .badge {
            padding: 0.55rem 0.7rem !important;
            font-size: 0.74rem;
        }
        .queue-board {
            --staff-col-base: 78px;
            --slot-base: 116px;
            --head-row-base-height: 48px;
            --data-row-base-height: 78px;
            --staff-font-base: 0.74rem;
            --time-font-base: 0.75rem;
            --card-top-base: 6px;
            --card-radius-base: 8px;
            --card-padding-y-base: 0.3rem;
            --card-padding-x-base: 0.4rem;
            --card-min-width-base: 70px;
            --card-customer-font-base: 0.78rem;
            --card-service-font-base: 0.66rem;
        }
        .queue-staff-cell {
            padding: 0.55rem 0.4rem;
        }
        .queue-staff-name {
            gap: 0.08rem;
        }
        .queue-staff-head {
            padding-left: 0.4rem;
            font-size: calc(0.72rem * var(--board-zoom));
        }
        .booking-card {
            box-shadow: 0 4px 12px rgba(15, 66, 120, 0.08);
        }
        .queue-board-toolbar {
            margin-bottom: 0.75rem !important;
            padding: 0.68rem 0.8rem;
        }
        .queue-zoom-hint {
            font-size: 0.68rem !important;
        }
        .queue-zoom-controls .btn {
            padding-left: 0.6rem;
            padding-right: 0.6rem;
        }
        .queue-zoom-controls #queue-zoom-reset {
            min-width: 3.9rem;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    const BRANCH_OPEN_TIME = @json($branchOpenTime);
    const BRANCH_CLOSE_TIME = @json($branchCloseTime);
    const START_MINUTES = {{ $startMinutes }};
    const END_MINUTES = {{ $endMinutes }};
    const USER_ROLE = @json(auth()->user() ? auth()->user()->role : 'staff');
    const CAN_EDIT_PAID = USER_ROLE === 'shop_owner' || USER_ROLE === 'branch_manager' || USER_ROLE === 'admin';
    const activeBranchId = @json($activeBranchId);
    const staffData = @json($staff);
    const customerData = @json($customers);
    const serviceData = @json($serviceItems);
    const bedData = @json($beds);
    const initialBookings = @json($bookings);

    const bookingModalEl = document.getElementById('bookingModal');
    const queueBoardEl = document.getElementById('queue-board');
    const queueBoardWrapEl = document.querySelector('.queue-board-wrap');
    const queueDateEl = document.getElementById('queue-date');
    const bookingFormEl = document.getElementById('booking-form');
    const customerPhoneEl = document.getElementById('customer-phone');
    const customerPhoneHintEl = document.getElementById('customer-phone-hint');
    const customerSelectEl = document.getElementById('customer-select');
    const staffSelectEl = document.getElementById('staff-select');
    const bedSelectEl = document.getElementById('bed-select');
    const startTimeEl = document.getElementById('start-time');
    const endTimeEl = document.getElementById('end-time');
    const statusSelectEl = document.getElementById('status-select');
    const serviceSelectEl = document.getElementById('service-select');
    const selectedServicesEl = document.getElementById('selected-services');
    const serviceSelectionHintEl = document.getElementById('service-selection-hint');
    const staffAvailabilityHintEl = document.getElementById('staff-availability-hint');
    const bedAvailabilityHintEl = document.getElementById('bed-availability-hint');
    const bookingTotalEl = document.getElementById('booking-total');
    const bookingTitleEl = document.getElementById('booking-modal-title');
    const bookingSubtitleEl = document.getElementById('booking-modal-subtitle');
    const saveBookingBtn = document.getElementById('save-booking-btn');
    const deleteBookingBtn = document.getElementById('delete-booking-btn');
    const addServiceBtn = document.getElementById('add-service-btn');
    const payBookingBtn = document.getElementById('pay-booking-btn');
    const queueZoomOutBtn = document.getElementById('queue-zoom-out');
    const queueZoomResetBtn = document.getElementById('queue-zoom-reset');
    const queueZoomInBtn = document.getElementById('queue-zoom-in');

    const bookingApi = {
        data: "{{ route('booking.data') }}",
        store: "{{ route('booking.store') }}",
        updateBase: "{{ url('/booking') }}",
        pos: "{{ route('pos') }}",
        csrfToken: "{{ csrf_token() }}",
    };
    const BOARD_ZOOM_MIN = 0.85;
    const BOARD_ZOOM_MAX = 1.9;
    const BOARD_ZOOM_STEP = 0.12;
    const BOARD_ZOOM_STORAGE_KEY = 'playflow-booking-board-zoom';
    const MAX_BOOKING_SERVICES = 3;

    function normalizeId(value) {
        return value === null || value === undefined ? '' : String(value);
    }

    const customerMap = new Map(customerData.map(c => [normalizeId(c.id), c]));
    const serviceMap = new Map(serviceData.map(s => [normalizeId(s.id), s]));
    const bedMap = new Map(bedData.map(b => [normalizeId(b.id), b]));

    const defaultCustomerId = customerData.length ? normalizeId(customerData[0].id) : '';
    const defaultServiceId = serviceData.length ? normalizeId(serviceData[0].id) : '';
    const defaultStaffId = staffData.length ? normalizeId(staffData[0].id) : '';
    const defaultDate = queueDateEl ? queueDateEl.value : "{{ $selectedDate }}";
    const DEFAULT_START_TIME = BRANCH_OPEN_TIME;
    const DEFAULT_END_TIME = toHHMM(Math.min(END_MINUTES, START_MINUTES + 60));

    let bookings = (Array.isArray(initialBookings) ? initialBookings : []).map(normalizeBooking);
    let bookingModal = null;
    let editingBookingId = null;
    let selectedServiceIds = [];
    let boardZoom = 1;
    let pinchZoomState = null;
    let pendingZoomRestore = null;
    let pendingZoomFrame = null;

    function normalizeTimeValue(rawValue, fallback = DEFAULT_START_TIME) {
        const value = String(rawValue || '').trim();
        if (value === '') {
            return fallback;
        }

        const twelveHourMatch = value.match(/^(\d{1,2}):(\d{2})(?:\s*([AP]M))$/i);
        if (twelveHourMatch) {
            let hours = Number(twelveHourMatch[1]);
            const minutes = Number(twelveHourMatch[2]);
            const meridiem = String(twelveHourMatch[3] || '').toUpperCase();
            if (Number.isFinite(hours) && Number.isFinite(minutes) && minutes >= 0 && minutes < 60) {
                if (meridiem === 'PM' && hours < 12) hours += 12;
                if (meridiem === 'AM' && hours === 12) hours = 0;
                return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`;
            }
        }

        const match = value.match(/^(\d{1,2}):(\d{2})/);
        if (!match) {
            return fallback;
        }

        const hours = Number(match[1]);
        const minutes = Number(match[2]);
        if (!Number.isFinite(hours) || !Number.isFinite(minutes) || hours < 0 || hours > 23 || minutes < 0 || minutes > 59) {
            return fallback;
        }

        return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`;
    }

    function notifyError(message) {
        if (window.PFPopup && typeof window.PFPopup.error === 'function') {
            window.PFPopup.error(message);
            return;
        }
        console.error(message);
    }

    function clampBoardZoom(nextZoom) {
        return Math.max(BOARD_ZOOM_MIN, Math.min(BOARD_ZOOM_MAX, nextZoom));
    }

    function updateBoardZoomControls() {
        if (queueZoomResetBtn) {
            queueZoomResetBtn.textContent = `${Math.round(boardZoom * 100)}%`;
        }
        if (queueZoomOutBtn) queueZoomOutBtn.disabled = boardZoom <= BOARD_ZOOM_MIN + 0.001;
        if (queueZoomInBtn) queueZoomInBtn.disabled = boardZoom >= BOARD_ZOOM_MAX - 0.001;
    }

    function scheduleZoomRender() {
        if (pendingZoomFrame !== null) return;
        pendingZoomFrame = requestAnimationFrame(() => {
            pendingZoomFrame = null;
            renderBookings();

            if (!queueBoardWrapEl || !queueBoardEl || !pendingZoomRestore) return;

            const restore = pendingZoomRestore;
            pendingZoomRestore = null;
            const wrapRect = queueBoardWrapEl.getBoundingClientRect();
            const anchorX = typeof restore.clientX === 'number'
                ? (restore.clientX - wrapRect.left)
                : (wrapRect.width / 2);
            const targetContentX = restore.contentRatio * queueBoardEl.scrollWidth;
            queueBoardWrapEl.scrollLeft = Math.max(0, targetContentX - anchorX);
        });
    }

    function applyBoardZoom(nextZoom, options = {}) {
        if (!queueBoardEl || !queueBoardWrapEl) return;

        const zoom = clampBoardZoom(nextZoom);
        if (Math.abs(zoom - boardZoom) < 0.001) return;

        const wrapRect = queueBoardWrapEl.getBoundingClientRect();
        const anchorX = typeof options.clientX === 'number'
            ? (options.clientX - wrapRect.left)
            : (wrapRect.width / 2);
        const contentRatio = (queueBoardWrapEl.scrollLeft + anchorX) / Math.max(queueBoardEl.scrollWidth, 1);

        boardZoom = zoom;
        queueBoardEl.style.setProperty('--board-zoom', String(boardZoom));
        pendingZoomRestore = {
            clientX: options.clientX,
            contentRatio,
        };
        updateBoardZoomControls();
        scheduleZoomRender();

        try {
            window.localStorage.setItem(BOARD_ZOOM_STORAGE_KEY, String(boardZoom));
        } catch (error) {
            console.warn('Unable to persist board zoom', error);
        }
    }

    function restoreBoardZoom() {
        if (!queueBoardEl) return;

        let savedZoom = 1;
        try {
            const rawValue = window.localStorage.getItem(BOARD_ZOOM_STORAGE_KEY);
            savedZoom = rawValue ? Number(rawValue) : 1;
        } catch (error) {
            savedZoom = 1;
        }

        boardZoom = clampBoardZoom(Number.isFinite(savedZoom) ? savedZoom : 1);
        queueBoardEl.style.setProperty('--board-zoom', String(boardZoom));
        updateBoardZoomControls();
    }

    function getTouchDistance(touchA, touchB) {
        const deltaX = touchA.clientX - touchB.clientX;
        const deltaY = touchA.clientY - touchB.clientY;
        return Math.hypot(deltaX, deltaY);
    }

    function getTouchCenter(touchA, touchB) {
        return {
            x: (touchA.clientX + touchB.clientX) / 2,
            y: (touchA.clientY + touchB.clientY) / 2,
        };
    }

    function normalizeBooking(input = {}) {
        const inputServiceIds = Array.isArray(input.serviceIds)
            ? input.serviceIds
            : (Array.isArray(input.service_ids) ? input.service_ids : []);
        const normalizedServiceIds = inputServiceIds.length
            ? inputServiceIds.map(normalizeId).filter(Boolean)
            : ((input.serviceId || input.service_id) ? [normalizeId(input.serviceId || input.service_id)] : []);

        return {
            id: normalizeId(input.id),
            queueDate: input.queueDate || input.queue_date || defaultDate,
            customerId: normalizeId(input.customerId || input.customer_id),
            customerName: input.customerName || input.customer_name || '',
            serviceIds: normalizedServiceIds,
            serviceNames: input.serviceNames || input.service_names || [],
            serviceSummary: input.serviceSummary || input.service_summary || '',
            staffId: normalizeId(input.staffId || input.masseuse_id),
            staffName: input.staffName || input.staff_name || '',
            bedId: normalizeId(input.bedId || input.bed_id),
            bedName: input.bedName || input.bed_name || '',
            start: normalizeTimeValue(input.start || input.startTime || input.start_time || DEFAULT_START_TIME, DEFAULT_START_TIME),
            end: normalizeTimeValue(input.end || input.endTime || input.end_time || DEFAULT_END_TIME, DEFAULT_END_TIME),
            status: input.status || 'waiting',
            paid: Boolean(input.paid || input.isPaid),
            cancelReason: input.cancelReason || input.cancel_reason || null,
        };
    }

    function toMinutes(hhmm) {
        const [h, m] = normalizeTimeValue(hhmm, '00:00').split(':').map(Number);
        return (h * 60) + m;
    }

    function toHHMM(totalMinutes) {
        const h = Math.floor(totalMinutes / 60);
        const m = totalMinutes % 60;
        return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`;
    }

    function isWithinOperatingHours(startTime, endTime) {
        const start = toMinutes(startTime);
        const end = toMinutes(endTime);
        return start >= START_MINUTES && end <= END_MINUTES && end > start;
    }

    function clampMinutes(totalMinutes) {
        const min = START_MINUTES;
        const max = END_MINUTES;
        return Math.max(min, Math.min(max, totalMinutes));
    }

    function addMinutes(time, offset) {
        return toHHMM(clampMinutes(toMinutes(time) + offset));
    }

    function getServiceDuration(serviceId) {
        const service = serviceMap.get(normalizeId(serviceId));
        return Number((service && service.duration) || 60);
    }

    function getServicePrice(serviceId) {
        const service = serviceMap.get(normalizeId(serviceId));
        return Number((service && service.price) || 0);
    }

    function getTotalDuration(serviceIds) {
        const total = serviceIds.reduce((sum, id) => sum + getServiceDuration(id), 0);
        return total > 0 ? total : 60;
    }

    function getTotalPrice(serviceIds) {
        return serviceIds.reduce((sum, id) => sum + getServicePrice(id), 0);
    }

    function getCustomerName(customerId, fallbackName = 'Walk-in') {
        const customer = customerMap.get(normalizeId(customerId));
        return (customer && customer.name) || fallbackName;
    }

    function getBedName(bedId) {
        const bed = bedMap.get(normalizeId(bedId));
        return bed ? bed.name : '';
    }

    function getServiceSummary(serviceIds) {
        const names = serviceIds
            .map(id => {
                const service = serviceMap.get(normalizeId(id));
                return service ? service.name : '';
            })
            .filter(Boolean);
        if (!names.length) return '-';
        return names.length > 1 ? `${names[0]} +${names.length - 1}` : names[0];
    }

    function updateAddServiceState() {
        if (addServiceBtn) {
            const reachedMax = selectedServiceIds.length >= MAX_BOOKING_SERVICES;
            addServiceBtn.disabled = reachedMax;
            addServiceBtn.innerHTML = reachedMax
                ? `<i class="bi bi-check2 me-1"></i> ครบ ${MAX_BOOKING_SERVICES}`
                : '<i class="bi bi-plus-lg"></i> เพิ่ม';
        }

        if (serviceSelectionHintEl) {
            serviceSelectionHintEl.textContent = selectedServiceIds.length >= MAX_BOOKING_SERVICES
                ? `เลือกบริการครบ ${MAX_BOOKING_SERVICES} รายการแล้ว`
                : `เพิ่มบริการได้สูงสุด ${MAX_BOOKING_SERVICES} รายการ (${selectedServiceIds.length}/${MAX_BOOKING_SERVICES})`;
        }
    }

    function setAvailabilityHint(element, message, state = 'muted') {
        if (!element) return;
        element.classList.remove('text-muted', 'text-success', 'text-danger', 'text-warning');
        if (state === 'success') {
            element.classList.add('text-success');
        } else if (state === 'danger') {
            element.classList.add('text-danger');
        } else if (state === 'warning') {
            element.classList.add('text-warning');
        } else {
            element.classList.add('text-muted');
        }
        element.textContent = message;
    }

    function getBookingConflicts(startTime, endTime, staffId, bedId) {
        const start = normalizeTimeValue(startTime, '');
        const end = normalizeTimeValue(endTime, '');
        if (!start || !end || toMinutes(end) <= toMinutes(start)) {
            return { staffConflict: null, bedConflict: null };
        }

        const activeDate = queueDateEl && queueDateEl.value ? queueDateEl.value : defaultDate;
        const currentBookingId = normalizeId(editingBookingId);

        const overlappingBookings = bookings.filter((booking) => {
            if ((booking.queueDate || defaultDate) !== activeDate) return false;
            if (normalizeId(booking.id) === currentBookingId) return false;
            if ((booking.status || '') === 'cancelled') return false;

            const bookingStart = normalizeTimeValue(booking.start, '');
            const bookingEnd = normalizeTimeValue(booking.end, '');
            if (!bookingStart || !bookingEnd) return false;

            return toMinutes(bookingStart) < toMinutes(end) && toMinutes(bookingEnd) > toMinutes(start);
        });

        const staffConflict = staffId
            ? overlappingBookings.find((booking) => normalizeId(booking.staffId) === normalizeId(staffId)) || null
            : null;
        const bedConflict = bedId
            ? overlappingBookings.find((booking) => normalizeId(booking.bedId) === normalizeId(bedId)) || null
            : null;

        return { staffConflict, bedConflict };
    }

    function updateAvailabilityIndicators() {
        const start = normalizeTimeValue(startTimeEl ? startTimeEl.value : '', '');
        const end = normalizeTimeValue(endTimeEl ? endTimeEl.value : '', '');
        const staffId = normalizeId(staffSelectEl ? staffSelectEl.value : '');
        const bedId = normalizeId(bedSelectEl ? bedSelectEl.value : '');
        const isPaidBooking = Boolean(payBookingBtn && payBookingBtn.disabled);
        let hasConflict = false;

        if (!start || !end || toMinutes(end) <= toMinutes(start)) {
            setAvailabilityHint(staffAvailabilityHintEl, 'กรุณาเลือกเวลาเริ่มและเวลาสิ้นสุดให้ถูกต้อง', 'warning');
            setAvailabilityHint(bedAvailabilityHintEl, bedId ? 'กรุณาเลือกเวลาเริ่มและเวลาสิ้นสุดให้ถูกต้อง' : 'ยังไม่ได้เลือกเตียง');
            if (saveBookingBtn) {
                saveBookingBtn.disabled = true;
            }
            return;
        }

        if (!isWithinOperatingHours(start, end)) {
            setAvailabilityHint(staffAvailabilityHintEl, `คิวต้องอยู่ในช่วงเวลาเปิดร้าน ${BRANCH_OPEN_TIME}-${BRANCH_CLOSE_TIME}`, 'warning');
            setAvailabilityHint(bedAvailabilityHintEl, `เลือกเวลาในช่วง ${BRANCH_OPEN_TIME}-${BRANCH_CLOSE_TIME}`, 'warning');
            if (saveBookingBtn) {
                saveBookingBtn.disabled = true;
            }
            return;
        }

        const { staffConflict, bedConflict } = getBookingConflicts(start, end, staffId, bedId);

        if (staffId) {
            setAvailabilityHint(
                staffAvailabilityHintEl,
                staffConflict
                    ? `หมอนวดติดคิว ${staffConflict.start}-${staffConflict.end}`
                    : 'หมอนวดว่างในช่วงเวลานี้',
                staffConflict ? 'danger' : 'success'
            );
        } else {
            setAvailabilityHint(staffAvailabilityHintEl, 'กรุณาเลือกหมอนวด', 'warning');
        }

        if (bedId) {
            setAvailabilityHint(
                bedAvailabilityHintEl,
                bedConflict
                    ? `เตียงติดคิว ${bedConflict.start}-${bedConflict.end}`
                    : 'เตียงว่างในช่วงเวลานี้',
                bedConflict ? 'danger' : 'success'
            );
        } else {
            setAvailabilityHint(bedAvailabilityHintEl, 'ยังไม่ได้เลือกเตียง', 'muted');
        }

        hasConflict = Boolean(staffConflict || bedConflict || !staffId);
        if (saveBookingBtn) {
            saveBookingBtn.disabled = Boolean((isPaidBooking && !CAN_EDIT_PAID) || hasConflict);
        }
    }

    function renderSelectedServices() {
        if (!selectedServiceIds.length) {
            updateAddServiceState();
            selectedServicesEl.innerHTML = '<span class="small text-muted">ยังไม่มีบริการที่เลือก</span>';
            bookingTotalEl.textContent = '0 บ.';
            return;
        }

        selectedServicesEl.innerHTML = selectedServiceIds.map(serviceId => {
            const service = serviceMap.get(normalizeId(serviceId));
            if (!service) return '';
            return `
                <span class="selected-service-chip">
                    ${service.name}
                    <button type="button" onclick="removeService('${serviceId}')" aria-label="ลบบริการ">
                        <i class="fa-solid fa-circle-xmark"></i>
                    </button>
                </span>
            `;
        }).join('');

        bookingTotalEl.textContent = `${getTotalPrice(selectedServiceIds).toLocaleString()} บ.`;
        updateAddServiceState();
    }

    function removeService(serviceId) {
        selectedServiceIds = selectedServiceIds.filter(id => normalizeId(id) !== normalizeId(serviceId));
        renderSelectedServices();
        recalculateEndTime();
    }
    window.removeService = removeService;

    function ensureEndAfterStart() {
        const normalizedStart = normalizeTimeValue(startTimeEl.value, DEFAULT_START_TIME);
        const normalizedEnd = normalizeTimeValue(endTimeEl.value, DEFAULT_END_TIME);
        startTimeEl.value = normalizedStart;
        endTimeEl.value = normalizedEnd;
        const start = toMinutes(normalizedStart);
        const end = toMinutes(normalizedEnd);
        if (end <= start) {
            endTimeEl.value = addMinutes(normalizedStart, getTotalDuration(selectedServiceIds));
        }
        updateAvailabilityIndicators();
    }

    function recalculateEndTime() {
        if (!startTimeEl || !endTimeEl) return;
        const normalizedStart = normalizeTimeValue(startTimeEl.value, DEFAULT_START_TIME);
        endTimeEl.value = addMinutes(normalizedStart, getTotalDuration(selectedServiceIds));
        updateAvailabilityIndicators();
    }

    function setModalMode(isEditing) {
        bookingTitleEl.innerHTML = isEditing
            ? '<i class="bi bi-pencil-square me-2 text-primary"></i>แก้ไขคิวจอง'
            : '<i class="bi bi-journal-plus me-2 text-primary"></i>เพิ่มรายการจองใหม่';

        bookingSubtitleEl.textContent = isEditing
            ? 'แก้บริการ เวลา หมอนวด หรือชำระเงินจากคิวนี้ได้เลย'
            : 'กำหนดรายละเอียดคิวก่อนบันทึก';

        deleteBookingBtn.classList.toggle('d-none', !isEditing);
    }

    function fillModal(booking, isEditing = false) {
        customerSelectEl.value = booking.customerId || defaultCustomerId;
        staffSelectEl.value = normalizeId(booking.staffId || defaultStaffId);
        if (bedSelectEl) bedSelectEl.value = normalizeId(booking.bedId || '');
        startTimeEl.value = normalizeTimeValue(booking.start || DEFAULT_START_TIME, DEFAULT_START_TIME);
        endTimeEl.value = normalizeTimeValue(booking.end || addMinutes(startTimeEl.value, getTotalDuration(booking.serviceIds)), addMinutes(startTimeEl.value, getTotalDuration(booking.serviceIds)));
        statusSelectEl.value = booking.status || 'waiting';
        selectedServiceIds = [...(booking.serviceIds || [])].map(normalizeId).slice(0, MAX_BOOKING_SERVICES);
        renderSelectedServices();
        setModalMode(isEditing);

        const isPaid = Boolean(booking.paid);

        // ปุ่มชำระเงิน: ถ้าชำระแล้ว + เป็น manager/owner → ให้กดเพื่อชำระใหม่ได้
        if (isPaid && CAN_EDIT_PAID) {
            payBookingBtn.innerHTML = '<i class="bi bi-arrow-repeat me-1"></i> ชำระใหม่';
            payBookingBtn.disabled = false;
            payBookingBtn.className = 'btn btn-warning rounded-pill px-3 shadow-sm';
        } else if (isPaid) {
            payBookingBtn.innerHTML = '<i class="bi bi-check2-circle me-1"></i> ชำระแล้ว';
            payBookingBtn.disabled = true;
            payBookingBtn.className = 'btn btn-success rounded-pill px-3 opacity-75';
        } else {
            payBookingBtn.innerHTML = '<i class="bi bi-wallet2 me-1"></i> ชำระเงิน';
            payBookingBtn.disabled = false;
            payBookingBtn.className = 'btn btn-outline-success rounded-pill px-3';
        }

        // ปุ่มบันทึก: ถ้าชำระแล้ว + ไม่ใช่ manager/owner → disabled + สีจาง
        saveBookingBtn.disabled = isPaid && !CAN_EDIT_PAID;
        if (isPaid && !CAN_EDIT_PAID) {
            saveBookingBtn.className = 'btn btn-secondary rounded-pill px-4 opacity-50';
        } else {
            saveBookingBtn.className = 'btn btn-primary rounded-pill px-4 shadow-sm';
        }

        // ปุ่มลบ: แสดงเฉพาะ editing + (ยังไม่ชำระ หรือ เป็น manager/owner)
        deleteBookingBtn.classList.toggle('d-none', !isEditing || (isPaid && !CAN_EDIT_PAID));

        updateAvailabilityIndicators();
    }

    function renderBookings() {
        document.querySelectorAll('.booking-row-layer').forEach(l => l.innerHTML = '');
        const totalMinutes = END_MINUTES - START_MINUTES;

        bookings.forEach(b => {
            const layer = document.getElementById(`layer-${normalizeId(b.staffId)}`);
            if (!layer) return;

            const layerWidth = layer.clientWidth;
            const layerHeight = layer.clientHeight;
            if (!layerWidth || !layerHeight) return;

            let startOffset = toMinutes(b.start) - START_MINUTES;
            let endOffset = toMinutes(b.end) - START_MINUTES;

            startOffset = Math.max(0, Math.min(totalMinutes - 15, startOffset));
            endOffset = Math.max(startOffset + 15, Math.min(totalMinutes, endOffset));

            const left = (startOffset / totalMinutes) * layerWidth;
            const width = Math.max(((endOffset - startOffset) / totalMinutes) * layerWidth - 4, 86);

            const card = document.createElement('div');
            card.className = `booking-card state-${b.status}`;
            card.dataset.bookingId = b.id;
            card.style.left = `${left + 2}px`;
            card.style.width = `${Math.min(width, layerWidth - left - 2)}px`;

            card.innerHTML = `
                <div class="booking-top">
                    <span class="booking-time">${b.start} - ${b.end}</span>
                    <span class="${b.paid ? 'booking-paid' : 'booking-unpaid'}">${b.paid ? 'ชำระแล้ว' : 'รอชำระ'}</span>
                </div>
                <div class="booking-customer">${getCustomerName(b.customerId, b.customerName)}</div>
                <div class="booking-service">${getServiceSummary(b.serviceIds)}${getBedName(b.bedId) ? ' · ' + getBedName(b.bedId) : ''}</div>
            `;
            card.addEventListener('click', (ev) => {
                ev.stopPropagation();
                openModal({ bookingId: b.id });
            });

            layer.appendChild(card);
        });
    }

    function getModalInstance() {
        if (!bookingModalEl) return null;

        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            if (!bookingModal) {
                bookingModal = bootstrap.Modal.getOrCreateInstance(bookingModalEl);
            }
            return bookingModal;
        }

        return {
            show: function () {
                bookingModalEl.style.display = 'block';
                bookingModalEl.classList.add('show');
                bookingModalEl.removeAttribute('aria-hidden');
                document.body.classList.add('modal-open');

                let backdrop = document.querySelector('.modal-backdrop.pf-fallback');
                if (!backdrop) {
                    backdrop = document.createElement('div');
                    backdrop.className = 'modal-backdrop fade show pf-fallback';
                    backdrop.addEventListener('click', () => {
                        const modal = getModalInstance();
                        if (modal && typeof modal.hide === 'function') modal.hide();
                    });
                    document.body.appendChild(backdrop);
                }
                bookingModalEl.dispatchEvent(new Event('shown.bs.modal'));
            },
            hide: function () {
                bookingModalEl.classList.remove('show');
                bookingModalEl.style.display = 'none';
                bookingModalEl.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('modal-open');
                const backdrop = document.querySelector('.modal-backdrop.pf-fallback');
                if (backdrop) backdrop.remove();
                bookingModalEl.dispatchEvent(new Event('hidden.bs.modal'));
            }
        };
    }

    function hasModalControls() {
        return customerSelectEl
            && staffSelectEl
            && bedSelectEl
            && startTimeEl
            && endTimeEl
            && statusSelectEl
            && selectedServicesEl
            && bookingTotalEl
            && bookingTitleEl
            && bookingSubtitleEl
            && payBookingBtn
            && deleteBookingBtn;
    }

    function collectBookingPayload() {
        if (!hasModalControls()) return null;
        const start = normalizeTimeValue(startTimeEl.value, '');
        const end = normalizeTimeValue(endTimeEl.value, '');
        const queueDate = (queueDateEl && queueDateEl.value) ? queueDateEl.value : defaultDate;

        if (!customerSelectEl.value || !staffSelectEl.value || !start || !end) {
            notifyError('กรุณากรอกข้อมูลให้ครบก่อนบันทึก');
            return null;
        }

        if (!selectedServiceIds.length) {
            notifyError('กรุณาเลือกอย่างน้อย 1 บริการ');
            return null;
        }

        if (toMinutes(end) <= toMinutes(start)) {
            notifyError('เวลาสิ้นสุดต้องมากกว่าเวลาเริ่ม');
            return null;
        }

        if (!isWithinOperatingHours(start, end)) {
            notifyError(`เวลาจองต้องอยู่ในช่วง ${BRANCH_OPEN_TIME}-${BRANCH_CLOSE_TIME}`);
            return null;
        }

        const { staffConflict, bedConflict } = getBookingConflicts(start, end, staffSelectEl.value, bedSelectEl.value);
        if (staffConflict) {
            notifyError(`หมอนวดคนนี้ติดคิวช่วง ${staffConflict.start}-${staffConflict.end}`);
            return null;
        }

        if (bedConflict) {
            notifyError(`เตียงนี้ติดคิวช่วง ${bedConflict.start}-${bedConflict.end}`);
            return null;
        }

        return {
            branch_id: activeBranchId || null,
            queue_date: queueDate,
            customer_id: Number(customerSelectEl.value),
            service_id: Number(selectedServiceIds[0]),
            service_ids: [...selectedServiceIds].map(id => Number(id)),
            masseuse_id: staffSelectEl.value ? Number(staffSelectEl.value) : null,
            bed_id: bedSelectEl.value ? Number(bedSelectEl.value) : null,
            start_time: start,
            end_time: end,
            status: statusSelectEl.value,
            cancel_reason: null,
        };
    }

    function upsertBooking(booking) {
        const normalized = normalizeBooking(booking);
        const idx = bookings.findIndex(item => normalizeId(item.id) === normalizeId(normalized.id));
        if (idx >= 0) {
            bookings[idx] = normalized;
        } else {
            bookings.push(normalized);
            editingBookingId = normalized.id;
        }
    }

    function extractErrorMessage(errorPayload = {}) {
        if (errorPayload && typeof errorPayload.message === 'string' && errorPayload.message.trim() !== '') {
            if (!errorPayload.errors) {
                return errorPayload.message;
            }
        }

        if (errorPayload && errorPayload.errors && typeof errorPayload.errors === 'object') {
            const firstKey = Object.keys(errorPayload.errors)[0];
            if (firstKey && Array.isArray(errorPayload.errors[firstKey]) && errorPayload.errors[firstKey].length > 0) {
                return errorPayload.errors[firstKey][0];
            }
        }

        return 'เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง';
    }

    async function requestJson(url, options = {}) {
        const response = await fetch(url, {
            method: options.method || 'GET',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': bookingApi.csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                ...(options.headers || {}),
            },
            body: options.body || null,
        });

        const payload = await response.json().catch(() => ({}));

        if (!response.ok) {
            throw new Error(extractErrorMessage(payload));
        }

        return payload;
    }

    async function saveBookingAndClose() {
        const payload = collectBookingPayload();
        if (!payload) return;

        try {
            saveBookingBtn.disabled = true;
            const isEditing = Boolean(editingBookingId);
            const url = isEditing
                ? `${bookingApi.updateBase}/${editingBookingId}`
                : bookingApi.store;
            const method = isEditing ? 'PUT' : 'POST';
            const response = await requestJson(url, {
                method,
                body: JSON.stringify(payload),
            });
            upsertBooking(response.booking);
            renderBookings();
            const modal = getModalInstance();
            if (modal && typeof modal.hide === 'function') {
                modal.hide();
            }
        } catch (error) {
            notifyError(error.message);
        } finally {
            saveBookingBtn.disabled = false;
        }
    }

    async function markAsPaid() {
        const payload = collectBookingPayload();
        if (!payload) return;

        try {
            if (payBookingBtn) payBookingBtn.disabled = true;
            
            const isEditing = Boolean(editingBookingId);
            const url = isEditing
                ? `${bookingApi.updateBase}/${editingBookingId}`
                : bookingApi.store;
            const method = isEditing ? 'PUT' : 'POST';
            
            const response = await requestJson(url, {
                method,
                body: JSON.stringify(payload),
            });
            
            const savedBooking = response.booking;
            upsertBooking(savedBooking);
            
            const isReCheckout = Boolean(savedBooking.paid && CAN_EDIT_PAID);
            
            const params = new URLSearchParams({
                from_booking: '1',
                booking_id: String(savedBooking.id),
                queue_date: savedBooking.queueDate || payload.queue_date
            });
            
            if (activeBranchId) {
                params.set('branch_id', String(activeBranchId));
            }

            if (isReCheckout) {
                params.set('re_checkout', '1');
                params.set('is_paid', '1');
            }

            window.location.href = `${bookingApi.pos}?${params.toString()}`;
        } catch (error) {
            notifyError(error.message);
            if (payBookingBtn) payBookingBtn.disabled = false;
        }
    }

    async function deleteBooking() {
        if (!editingBookingId) return;

        try {
            deleteBookingBtn.disabled = true;
            const deleteUrl = activeBranchId
                ? `${bookingApi.updateBase}/${editingBookingId}?branch_id=${encodeURIComponent(String(activeBranchId))}`
                : `${bookingApi.updateBase}/${editingBookingId}`;
            await requestJson(deleteUrl, {
                method: 'DELETE',
            });
            bookings = bookings.filter(item => normalizeId(item.id) !== normalizeId(editingBookingId));
            renderBookings();
            const modal = getModalInstance();
            if (modal && typeof modal.hide === 'function') {
                modal.hide();
            }
        } catch (error) {
            notifyError(error.message);
        } finally {
            deleteBookingBtn.disabled = false;
        }
    }

    async function loadBookingsByDate(dateValue) {
        const params = new URLSearchParams({
            date: dateValue || defaultDate,
        });
        if (activeBranchId) {
            params.set('branch_id', String(activeBranchId));
        }

        try {
            const response = await requestJson(`${bookingApi.data}?${params.toString()}`);
            bookings = (response.bookings || []).map(normalizeBooking);
            renderBookings();
        } catch (error) {
            notifyError(error.message);
        }
    }

    function scheduleRenderBookings() {
        requestAnimationFrame(renderBookings);
    }

    function openModal(data = {}) {
        const modalInstance = getModalInstance();
        if (!modalInstance) {
            notifyError('ไม่สามารถเปิดหน้าต่างคิวได้ กรุณารีเฟรชหน้าอีกครั้ง');
            return;
        }
        if (!hasModalControls()) {
            notifyError('ไม่พบฟอร์มจัดการคิวในหน้านี้');
            return;
        }

        if (data.bookingId) {
            const current = bookings.find(item => item.id === data.bookingId);
            if (!current) return;
            editingBookingId = current.id;
            fillModal(current, true);
            modalInstance.show();
            return;
        }

        editingBookingId = null;
        const startTime = normalizeTimeValue(data.time || DEFAULT_START_TIME, DEFAULT_START_TIME);
        const initialServices = [];
        const duration = getTotalDuration(initialServices);

        fillModal({
            customerId: defaultCustomerId,
            serviceIds: initialServices,
            staffId: normalizeId(data.staffId || defaultStaffId),
            bedId: '',
            start: startTime,
            end: addMinutes(startTime, duration),
            status: 'waiting',
            paid: false
        }, false);

        modalInstance.show();
    }
    window.openModal = openModal;

    if (queueBoardEl) {
        queueBoardEl.addEventListener('click', (e) => {
            const card = e.target.closest('.booking-card');
            if (card && card.dataset.bookingId) {
                openModal({ bookingId: card.dataset.bookingId });
                return;
            }

            const slot = e.target.closest('.queue-slot-cell');
            if (slot) {
                const row = slot.closest('.queue-data-row');
                openModal({
                    staffId: (row && row.dataset.staffId) || defaultStaffId,
                    time: slot.dataset.time || DEFAULT_START_TIME
                });
                return;
            }

            const layer = e.target.closest('.booking-row-layer');
            if (layer && e.target === layer) {
                const rect = layer.getBoundingClientRect();
                const relativeX = Math.max(0, Math.min(rect.width, e.clientX - rect.left));
                const totalMinutes = END_MINUTES - START_MINUTES;
                const offsetMinutes = Math.floor((relativeX / Math.max(rect.width, 1)) * totalMinutes / 60) * 60;
                const time = toHHMM(START_MINUTES + offsetMinutes);
                openModal({
                    staffId: layer.dataset.staffId || defaultStaffId,
                    time
                });
            }
        });
    }

    if (addServiceBtn) {
        addServiceBtn.addEventListener('click', () => {
            const serviceId = normalizeId(serviceSelectEl.value);
            if (!serviceId) return;

            if (selectedServiceIds.includes(serviceId)) {
                notifyError('บริการนี้ถูกเลือกแล้ว');
                updateAddServiceState();
                return;
            }

            if (selectedServiceIds.length >= MAX_BOOKING_SERVICES) {
                notifyError(`เพิ่มบริการได้สูงสุด ${MAX_BOOKING_SERVICES} รายการ`);
                updateAddServiceState();
                return;
            }

            selectedServiceIds = [...selectedServiceIds, serviceId];
            renderSelectedServices();
            recalculateEndTime();
        });
    }

    if (startTimeEl) startTimeEl.addEventListener('change', ensureEndAfterStart);
    if (endTimeEl) endTimeEl.addEventListener('change', ensureEndAfterStart);
    if (staffSelectEl) staffSelectEl.addEventListener('change', updateAvailabilityIndicators);
    if (bedSelectEl) bedSelectEl.addEventListener('change', updateAvailabilityIndicators);
    if (saveBookingBtn) saveBookingBtn.addEventListener('click', saveBookingAndClose);
    if (payBookingBtn) payBookingBtn.addEventListener('click', markAsPaid);
    if (deleteBookingBtn) deleteBookingBtn.addEventListener('click', deleteBooking);

    if (bookingModalEl) {
        bookingModalEl.addEventListener('hidden.bs.modal', () => {
            if (bookingFormEl) bookingFormEl.reset();
            selectedServiceIds = [];
            editingBookingId = null;
            if (saveBookingBtn) saveBookingBtn.disabled = false;
            if (payBookingBtn) payBookingBtn.disabled = false;
            if (deleteBookingBtn) deleteBookingBtn.disabled = false;
            renderSelectedServices();
            updateAvailabilityIndicators();
            updateAddServiceState();
        });
    }

    if (customerPhoneEl) {
        customerPhoneEl.addEventListener('input', () => {
            const rawDigits = customerPhoneEl.value.replace(/\D/g, '');
            if (rawDigits.length < 3) {
                if (customerPhoneHintEl) {
                    customerPhoneHintEl.textContent = 'พิมพ์อย่างน้อย 3 ตัวเลขเพื่อเลือกข้อมูลลูกค้าเดิมอัตโนมัติ';
                }
                return;
            }

            const found = customerData.find(customer => {
                const customerPhone = String(customer.phone || '').replace(/\D/g, '');
                return customerPhone.includes(rawDigits);
            });

            if (!found) {
                if (customerPhoneHintEl) {
                    customerPhoneHintEl.textContent = 'ไม่พบลูกค้าจากเบอร์นี้';
                }
                return;
            }

            customerSelectEl.value = normalizeId(found.id);
            if (customerPhoneHintEl) {
                customerPhoneHintEl.textContent = `พบลูกค้า: ${found.name}`;
            }
        });
    }

    if (queueDateEl) {
        queueDateEl.addEventListener('change', () => {
            const params = new URLSearchParams(window.location.search);
            params.set('date', queueDateEl.value);
            if (activeBranchId) {
                params.set('branch_id', String(activeBranchId));
            }
            window.location.href = `${bookingApi.updateBase}?${params.toString()}`;
        });
    }

    if (queueZoomOutBtn) {
        queueZoomOutBtn.addEventListener('click', () => {
            applyBoardZoom(boardZoom - BOARD_ZOOM_STEP);
        });
    }

    if (queueZoomResetBtn) {
        queueZoomResetBtn.addEventListener('click', () => {
            applyBoardZoom(1);
        });
    }

    if (queueZoomInBtn) {
        queueZoomInBtn.addEventListener('click', () => {
            applyBoardZoom(boardZoom + BOARD_ZOOM_STEP);
        });
    }

    if (queueBoardWrapEl) {
        queueBoardWrapEl.addEventListener('touchstart', (event) => {
            if (event.touches.length !== 2) return;

            const [touchA, touchB] = event.touches;
            pinchZoomState = {
                startDistance: getTouchDistance(touchA, touchB),
                startZoom: boardZoom,
            };
            event.preventDefault();
        }, { passive: false });

        queueBoardWrapEl.addEventListener('touchmove', (event) => {
            if (event.touches.length !== 2 || !pinchZoomState) return;

            const [touchA, touchB] = event.touches;
            const nextDistance = getTouchDistance(touchA, touchB);
            if (!nextDistance || !pinchZoomState.startDistance) return;

            const center = getTouchCenter(touchA, touchB);
            const nextZoom = pinchZoomState.startZoom * (nextDistance / pinchZoomState.startDistance);
            applyBoardZoom(nextZoom, { clientX: center.x, clientY: center.y });
            event.preventDefault();
        }, { passive: false });

        const clearPinchZoomState = () => {
            pinchZoomState = null;
        };

        queueBoardWrapEl.addEventListener('touchend', clearPinchZoomState);
        queueBoardWrapEl.addEventListener('touchcancel', clearPinchZoomState);
    }

    window.addEventListener('resize', scheduleRenderBookings);

    document.addEventListener('DOMContentLoaded', () => {
        restoreBoardZoom();
        scheduleRenderBookings();
        updateAddServiceState();
    });
</script>
@endpush
