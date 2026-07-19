@extends('layouts.main')

@section('title', 'ใบเสร็จ - PlayFlow')
@section('page_title', 'ใบเสร็จ')

@section('content')
<div class="row g-3">
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-3 p-lg-4">
                <form method="GET" action="{{ route('receipts') }}" class="row g-2 align-items-end">
                    <div class="col-12 col-md-3">
                        <label class="form-label small fw-bold">สาขา</label>
                        <input type="text" class="form-control rounded-3" value="{{ $activeBranchName }}" disabled>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small fw-bold">วันที่เริ่ม</label>
                        <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-control rounded-3">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small fw-bold">วันที่สิ้นสุด</label>
                        <input type="date" name="date_to" value="{{ $dateTo }}" class="form-control rounded-3">
                    </div>
                    <div class="col-12 col-md-3 d-grid">
                        <button type="submit" class="btn btn-primary rounded-3">
                            <i class="bi bi-funnel me-1"></i> กรองข้อมูล
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body">
                <div class="text-muted small">จำนวนบิล</div>
                <div class="fw-bold fs-4">{{ number_format($summary['order_count']) }}</div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body">
                <div class="text-muted small">ยอดชำระสุทธิ</div>
                <div class="fw-bold fs-4">{{ number_format($summary['sales_total'], 2) }} ฿</div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body">
                <div class="text-muted small">บิลเฉลี่ย</div>
                <div class="fw-bold fs-4">{{ number_format($summary['average_bill'], 2) }} ฿</div>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="px-3">เลขที่บิล</th>
                                <th>วันที่เวลา</th>
                                <th>ลูกค้า</th>
                                <th>ชำระเงิน</th>
                                <th>สถานะ</th>
                                <th class="text-end">ยอดสุทธิ</th>
                                <th class="text-center">รายละเอียด</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($orders as $order)
                            <tr>
                                <td class="px-3 fw-semibold">{{ $order['order_no'] }}</td>
                                <td>{{ $order['created_at'] }}</td>
                                <td>{{ $order['customer_name'] }}</td>
                                <td>{{ $order['payment_method_label'] }}</td>
                                <td>
                                    <span class="badge {{ $order['status'] === 'paid' ? 'text-bg-success' : 'text-bg-secondary' }}">
                                        {{ $order['status_label'] }}
                                    </span>
                                </td>
                                <td class="text-end fw-semibold">{{ number_format($order['grand_total'], 2) }} ฿</td>
                                <td class="text-center">
                                    <button type="button"
                                            class="btn btn-sm btn-outline-primary rounded-pill px-3 view-receipt-btn"
                                            data-order-id="{{ $order['id'] }}">
                                        ดูบิล
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-5">ไม่พบข้อมูลใบเสร็จในช่วงวันที่ที่เลือก</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="receiptDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold">
                    <i class="bi bi-receipt-cutoff me-2 text-primary"></i>รายละเอียดใบเสร็จ
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="receipt-detail-body">
                <div class="text-center text-muted py-4">กำลังโหลดข้อมูล...</div>
            </div>
            <div class="modal-footer border-0 justify-content-between">
                <div>
                    @if(in_array(auth()->user()->role, ['shop_owner', 'branch_manager']))
                        <button type="button" class="btn btn-outline-danger rounded-pill px-4" id="void-receipt-btn" style="display: none;">
                            <i class="bi bi-x-circle me-1"></i> ยกเลิกบิล
                        </button>
                    @endif
                </div>
                <div>
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">ปิด</button>
                    <button type="button" class="btn btn-primary rounded-pill px-4" id="print-receipt-btn">
                        <i class="bi bi-printer me-1"></i> พิมพ์
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const receiptModalEl = document.getElementById('receiptDetailModal');
    const receiptBodyEl = document.getElementById('receipt-detail-body');
    const printReceiptBtn = document.getElementById('print-receipt-btn');
    const receiptDetailBaseUrl = "{{ url('/receipts') }}";
    const activeBranchId = @json($activeBranchId);
    let currentReceipt = null;

    function formatBaht(amount) {
        return Number(amount || 0).toLocaleString('th-TH', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }) + ' ฿';
    }

    function renderReceiptDetail(receipt) {
        const rows = (receipt.items || []).map(item => `
            <tr>
                <td>${item.item_name}</td>
                <td>${item.item_type_label}</td>
                <td>${item.staff_name || '-'}</td>
                <td class="text-end">${Number(item.qty).toLocaleString('th-TH')}</td>
                <td class="text-end">${formatBaht(item.unit_price)}</td>
                <td class="text-end fw-semibold">${formatBaht(item.line_total)}</td>
            </tr>
        `).join('');

        receiptBodyEl.innerHTML = `
            <div id="receipt-print-area">
                <div class="d-flex justify-content-between flex-wrap gap-2 mb-3">
                    <div>
                        <div class="fw-bold fs-5">${receipt.order_no}</div>
                        <div class="text-muted small">วันที่ ${receipt.created_at}</div>
                    </div>
                    <div class="text-end">
                        <div><span class="text-muted">ลูกค้า:</span> <span class="fw-semibold">${receipt.customer_name}</span></div>
                        <div><span class="text-muted">เบอร์:</span> ${receipt.customer_phone}</div>
                        <div><span class="text-muted">ชำระ:</span> ${receipt.payment_method_label}</div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th>รายการ</th>
                                <th>ประเภท</th>
                                <th>หมอนวด</th>
                                <th class="text-end">จำนวน</th>
                                <th class="text-end">ราคา/หน่วย</th>
                                <th class="text-end">รวม</th>
                            </tr>
                        </thead>
                        <tbody>${rows}</tbody>
                    </table>
                </div>
                <div class="border-top pt-3 mt-2">
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">รวมเงิน</span>
                        <span>${formatBaht(receipt.total_amount)}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">ส่วนลด</span>
                        <span>${formatBaht(receipt.discount_amount)}</span>
                    </div>
                    ${parseFloat(receipt.tip_amount) > 0 ? `
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">ทิปพนักงาน (Tip)</span>
                        <span>${formatBaht(receipt.tip_amount)}</span>
                    </div>
                    ` : ''}
                    <div class="d-flex justify-content-between fw-bold fs-5 text-primary mt-1">
                        <span>ยอดสุทธิ</span>
                        <span>${formatBaht(receipt.grand_total)}</span>
                    </div>
                </div>
            </div>
        `;

        const voidReceiptBtn = document.getElementById('void-receipt-btn');
        if (voidReceiptBtn) {
            if (receipt.status === 'paid') {
                voidReceiptBtn.style.display = 'inline-block';
                voidReceiptBtn.disabled = false;
            } else {
                voidReceiptBtn.style.display = 'none';
            }
        }
    }

    async function loadReceiptDetail(orderId) {
        receiptBodyEl.innerHTML = '<div class="text-center text-muted py-4">กำลังโหลดข้อมูล...</div>';
        currentReceipt = null;

        const params = new URLSearchParams();
        if (activeBranchId) params.set('branch_id', String(activeBranchId));

        try {
            const response = await fetch(`${receiptDetailBaseUrl}/${orderId}?${params.toString()}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok) {
                const message = payload.message || 'โหลดรายละเอียดใบเสร็จไม่สำเร็จ';
                throw new Error(message);
            }

            currentReceipt = payload;
            renderReceiptDetail(payload);
        } catch (error) {
            receiptBodyEl.innerHTML = `<div class="text-danger text-center py-4">${error.message}</div>`;
            if (window.PFPopup && typeof window.PFPopup.error === 'function') {
                window.PFPopup.error(error.message);
            }
        }
    }

    document.querySelectorAll('.view-receipt-btn').forEach(button => {
        button.addEventListener('click', async () => {
            const orderId = button.dataset.orderId;
            if (!orderId) return;

            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                const modal = bootstrap.Modal.getOrCreateInstance(receiptModalEl);
                modal.show();
            } else {
                receiptModalEl.classList.add('show');
                receiptModalEl.style.display = 'block';
                document.body.classList.add('modal-open');
            }

            await loadReceiptDetail(orderId);
        });
    });

    const voidReceiptBtn = document.getElementById('void-receipt-btn');
    if (voidReceiptBtn) {
        voidReceiptBtn.addEventListener('click', async () => {
            if (!currentReceipt || currentReceipt.status !== 'paid') return;

            if (!confirm(`ยืนยันการยกเลิกบิลเลขที่ ${currentReceipt.order_no} หรือไม่?\n\n* การยกเลิกบิลจะคืนโควต้าแพ็กเกจ เปลี่ยนสถานะคิวเป็นยกเลิก และลบยอดขาย/คอมมิชชันที่เกี่ยวข้องทันที`)) {
                return;
            }

            try {
                voidReceiptBtn.disabled = true;
                const params = new URLSearchParams();
                if (activeBranchId) params.set('branch_id', String(activeBranchId));

                const response = await fetch(`${receiptDetailBaseUrl}/${currentReceipt.id}/void?${params.toString()}`, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                });

                const payload = await response.json().catch(() => ({}));
                if (!response.ok) {
                    throw new Error(payload.message || 'ยกเลิกบิลไม่สำเร็จ');
                }

                if (window.PFPopup && typeof window.PFPopup.success === 'function') {
                    window.PFPopup.success('ยกเลิกบิลเรียบร้อยแล้ว');
                } else {
                    alert('ยกเลิกบิลเรียบร้อยแล้ว');
                }

                setTimeout(() => window.location.reload(), 1000);
            } catch (error) {
                if (window.PFPopup && typeof window.PFPopup.error === 'function') {
                    window.PFPopup.error(error.message);
                } else {
                    alert(error.message);
                }
                voidReceiptBtn.disabled = false;
            }
        });
    }

    if (printReceiptBtn) {
        printReceiptBtn.addEventListener('click', () => {
            if (!currentReceipt) return;
            const printable = document.getElementById('receipt-print-area');
            if (!printable) return;

            const win = window.open('', '_blank');
            if (!win) return;
            win.document.write(`
                <html>
                <head><title>${currentReceipt.order_no}</title></head>
                <body style="font-family: Prompt, sans-serif; padding: 20px;">
                    ${printable.innerHTML}
                </body>
                </html>
            `);
            win.document.close();
            win.focus();
            win.print();
        });
    }
</script>
@endpush
