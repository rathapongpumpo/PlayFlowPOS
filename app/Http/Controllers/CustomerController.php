<?php

namespace App\Http\Controllers;

use App\Services\CustomerService;
use App\Services\WalletService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    private CustomerService $customerService;
    private WalletService $walletService;

    public function __construct(CustomerService $customerService, WalletService $walletService)
    {
        $this->customerService = $customerService;
        $this->walletService = $walletService;
    }

    public function index(Request $request): View
    {
        $selectedCustomerId = $request->has('customer_id') ? (int) $request->query('customer_id') : null;
        $search = (string) $request->query('search', '');
        $pageData = $this->customerService->getPageData($request->user(), $selectedCustomerId, $search);

        return view('customers.index', $pageData);
    }

    public function store(Request $request): RedirectResponse
    {
        $payload = $this->validateCustomerPayload($request);
        $customer = $this->customerService->createCustomer($request->user(), $payload);

        return redirect()
            ->route('customers', ['customer_id' => $customer['id']])
            ->with('success', 'เพิ่มลูกค้าใหม่เรียบร้อยแล้ว');
    }

    public function update(Request $request, int $customerId): RedirectResponse
    {
        $payload = $this->validateCustomerPayload($request, $customerId);
        $this->customerService->updateCustomer($request->user(), $customerId, $payload);

        return redirect()
            ->route('customers', ['customer_id' => $customerId])
            ->with('success', 'อัปเดตข้อมูลลูกค้าเรียบร้อยแล้ว');
    }

    public function destroy(Request $request, int $customerId): RedirectResponse
    {
        $this->customerService->deleteCustomer($request->user(), $customerId);

        return redirect()
            ->route('customers')
            ->with('success', 'ลบข้อมูลลูกค้าเรียบร้อยแล้ว');
    }

    public function topup(Request $request, int $customerId): RedirectResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'bonus' => 'nullable|numeric|min:0',
            'note' => 'nullable|string|max:255',
        ]);

        $amount = (float)$request->input('amount', 0);
        $bonus = (float)$request->input('bonus', 0);

        if ($amount <= 0 && $bonus <= 0) {
            return redirect()->back()->withErrors(['amount' => 'จำนวนเงินหรือโบนัสต้องมากกว่า 0']);
        }

        $user = $request->user();
        $this->walletService->topUp(
            (int)$user->branch_id,
            $customerId,
            $amount,
            $request->input('note', 'เติมเงินผ่านระบบหลังบ้าน'),
            $bonus
        );

        return redirect()
            ->route('customers', ['customer_id' => $customerId])
            ->with('success', 'เติมเงินเข้ากระเป๋าสำเร็จแล้ว');
    }

    public function quickCreate(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'phone' => [
                'required',
                'string',
                'max:32',
                'regex:/^[0-9\\-\\+\\s\\(\\)]+$/',
                $this->buildUniquePhoneRule($request),
            ],
            'line_id' => ['nullable', 'string', 'max:120'],
        ]);

        $customer = $this->customerService->createCustomer($request->user(), $payload);

        return response()->json([
            'message' => 'บันทึกข้อมูลลูกค้าเรียบร้อยแล้ว',
            'customer' => [
                'id' => (int) $customer['id'],
                'name' => (string) $customer['name'],
                'phone' => (string) $customer['phone'],
                'line_id' => (string) ($customer['line_id'] ?? ''),
            ],
        ]);
    }

    public function history(Request $request, int $customerId): JsonResponse
    {
        $customer = $this->customerService->getCustomerById($request->user(), $customerId);
        if ($customer === null) {
            return response()->json([
                'message' => 'ไม่พบข้อมูลลูกค้า',
            ], 404);
        }

        return response()->json([
            'customer' => $customer,
            'history' => $this->customerService->getHistoryByCustomerId($request->user(), $customerId),
        ]);
    }

    private function validateCustomerPayload(Request $request, ?int $customerId = null): array
    {
        $tierRules = ['nullable'];
        if (Schema::hasTable('membership_tiers')) {
            $tierRules = ['nullable', 'integer', 'exists:membership_tiers,id'];
        }

        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['required', 'string', 'max:32', 'regex:/^[0-9\\-\\+\\s\\(\\)]+$/', $this->buildUniquePhoneRule($request, $customerId)],
            'line_id' => ['nullable', 'string', 'max:120'],
            'tier_id' => $tierRules,
            'preferred_pressure_level' => ['nullable', 'string', Rule::in(['light', 'medium', 'firm'])],
            'health_notes' => ['nullable', 'string', 'max:4000'],
            'contraindications' => ['nullable', 'string', 'max:4000'],
        ]);
    }

    private function buildUniquePhoneRule(Request $request, ?int $customerId = null)
    {
        $rule = Rule::unique('customers', 'phone');

        if ($customerId !== null) {
            $rule = $rule->ignore($customerId);
        }

        if (Schema::hasColumn('customers', 'branch_id')) {
            $branchId = isset($request->user()->branch_id) ? (int) $request->user()->branch_id : 0;
            if ($branchId > 0) {
                $rule = $rule->where(static function ($query) use ($branchId): void {
                    $query->where('branch_id', $branchId);
                });
            }
        }

        return $rule;
    }
}
