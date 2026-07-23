<?php

namespace App\Http\Controllers;

use App\Services\PosService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PosController extends Controller
{
    private PosService $posService;

    public function __construct(PosService $posService)
    {
        $this->posService = $posService;
    }

    public function index(Request $request): View
    {
        $pageData = $this->posService->getPageData($request->user(), $request->query());

        return view('pos.index', $pageData);
    }

    public function checkout(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'branch_id' => 'nullable|integer',
            'customer_id' => 'nullable|integer|exists:customers,id',
            'staff_id' => 'nullable|integer|exists:masseuses,id',
            'seller_id' => 'nullable|integer',
            'discount_amount' => 'nullable|numeric|min:0',
            'tip_amount' => 'nullable|numeric|min:0',
            'points_redeem' => 'nullable|integer|min:0',
            'payment_method' => 'required|string|in:cash,transfer,card,credit_card,package_redeem,wallet',
            'use_package' => 'nullable|boolean',
            'items' => 'required|array|min:1',
            'items.*.type' => 'required|string|in:service,product,package',
            'items.*.source_id' => 'required|integer',
            'items.*.qty' => 'required|integer|min:1',
            'booking_context' => 'nullable|array',
            'booking_context.booking_id' => 'nullable|integer',
            'booking_context.queue_date' => 'nullable|date_format:Y-m-d',
            'booking_context.start_time' => 'nullable|date_format:H:i',
            'booking_context.end_time' => 'nullable|date_format:H:i',
            'booking_context.customer_id' => 'nullable|integer',
            'booking_context.staff_id' => 'nullable|integer',
            'booking_context.service_id' => 'nullable|integer',
            'booking_context.service_ids' => 'nullable|array|max:3',
            'booking_context.service_ids.*' => 'integer|exists:services,id',
            'booking_context.bed_id' => 'nullable|integer',
            'booking_context.is_paid' => 'nullable|boolean',
            'booking_context.re_checkout' => 'nullable|boolean',
        ]);

        $result = $this->posService->checkout($request->user(), $payload);

        return response()->json($result);
    }
}
