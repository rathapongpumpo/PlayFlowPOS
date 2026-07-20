<?php

namespace App\Http\Controllers;

use App\Services\PackageService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PackageController extends Controller
{
    private PackageService $packageService;

    public function __construct(PackageService $packageService)
    {
        $this->packageService = $packageService;
    }

    public function index(Request $request): View
    {
        $search = (string) $request->query('search', '');
        $branchId = $request->has('branch_id') && $request->query('branch_id') !== ''
            ? (int) $request->query('branch_id')
            : null;
        $pageData = $this->packageService->getPageData($request->user(), $search, $branchId);

        return view('packages.index', $pageData);
    }

    public function store(Request $request): RedirectResponse
    {
        $payload = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'in:session,wallet_credit'],
            'price' => ['required', 'numeric', 'min:0'],
            'total_qty' => ['nullable', 'integer', 'min:1'],
            'credit_amount' => ['nullable', 'numeric', 'min:0'],
            'valid_days' => ['nullable', 'integer', 'min:1'],
        ]);

        $this->packageService->createPackage($request->user(), $payload);

        return redirect()
            ->route('packages')
            ->with('success', 'เพิ่มแพ็กเกจเรียบร้อยแล้ว');
    }

    public function update(Request $request, int $packageId): RedirectResponse
    {
        $payload = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'in:session,wallet_credit'],
            'price' => ['required', 'numeric', 'min:0'],
            'total_qty' => ['nullable', 'integer', 'min:1'],
            'credit_amount' => ['nullable', 'numeric', 'min:0'],
            'valid_days' => ['nullable', 'integer', 'min:1'],
        ]);

        $this->packageService->updatePackage($request->user(), $packageId, $payload);

        return redirect()
            ->route('packages')
            ->with('success', 'อัปเดตแพ็กเกจเรียบร้อยแล้ว');
    }

    public function destroy(Request $request, int $packageId): RedirectResponse
    {
        $this->packageService->destroyPackage($request->user(), $packageId);

        return redirect()
            ->route('packages')
            ->with('success', 'ลบแพ็กเกจเรียบร้อยแล้ว');
    }
}
