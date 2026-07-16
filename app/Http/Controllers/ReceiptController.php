<?php

namespace App\Http\Controllers;

use App\Services\ReceiptService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReceiptController extends Controller
{
    private ReceiptService $receiptService;

    public function __construct(ReceiptService $receiptService)
    {
        $this->receiptService = $receiptService;
    }

    public function index(Request $request): View
    {
        $requestedBranchId = $request->has('branch_id') ? (int) $request->query('branch_id') : null;
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        $pageData = $this->receiptService->getPageData(
            $request->user(),
            $requestedBranchId,
            is_string($dateFrom) ? $dateFrom : null,
            is_string($dateTo) ? $dateTo : null
        );

        return view('receipts.index', $pageData);
    }

    public function show(Request $request, int $orderId): JsonResponse
    {
        $requestedBranchId = $request->has('branch_id') ? (int) $request->query('branch_id') : null;
        $receipt = $this->receiptService->getReceiptDetail($request->user(), $orderId, $requestedBranchId);

        return response()->json($receipt);
    }

    public function voidOrder(Request $request, int $orderId): JsonResponse
    {
        $requestedBranchId = $request->has('branch_id') ? (int) $request->query('branch_id') : null;
        $result = $this->receiptService->voidOrder($request->user(), $orderId, $requestedBranchId);

        return response()->json($result);
    }
}
