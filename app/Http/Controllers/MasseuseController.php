<?php

namespace App\Http\Controllers;

use App\Services\MasseuseService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MasseuseController extends Controller
{
    private MasseuseService $masseuseService;

    public function __construct(MasseuseService $masseuseService)
    {
        $this->masseuseService = $masseuseService;
    }

    public function index(Request $request): View
    {
        if ((string) ($request->user()->role ?? '') === 'masseuse') {
            return view('masseuse.self', $this->masseuseService->getSelfDashboardData(
                $request->user(),
                (string) $request->query('date', now()->toDateString())
            ));
        }

        $requestedBranchId = $request->has('branch_id') ? (int) $request->query('branch_id') : null;
        $selectedDate = (string) $request->query('date', now()->toDateString());

        return view('masseuse.index', $this->masseuseService->getPageData(
            $request->user(),
            $requestedBranchId,
            $selectedDate
        ));
    }

    public function selfDashboard(Request $request): View
    {
        return view('masseuse.self', $this->masseuseService->getSelfDashboardData(
            $request->user(),
            (string) $request->query('date', now()->toDateString())
        ));
    }

    public function create(Request $request): View
    {
        $requestedBranchId = $request->has('branch_id') ? (int) $request->query('branch_id') : null;
        $selectedDate = (string) $request->query('date', now()->toDateString());

        return view('masseuse.create', array_merge(
            $this->masseuseService->getPageData($request->user(), $requestedBranchId, $selectedDate),
            ['formRecord' => $this->masseuseService->getEmptyFormRecord()]
        ));
    }

    public function edit(Request $request, int $staffId): View
    {
        $requestedBranchId = $request->has('branch_id') ? (int) $request->query('branch_id') : null;
        $selectedDate = (string) $request->query('date', now()->toDateString());
        $pageData = $this->masseuseService->getPageData($request->user(), $requestedBranchId, $selectedDate);
        $formRecord = collect($pageData['staffRecords'] ?? [])->firstWhere('id', $staffId);

        abort_if(!($pageData['moduleReady'] ?? false), 404);
        abort_if(!is_array($formRecord), 404);

        $month = $request->query('month', now()->format('m'));
        $year = $request->query('year', now()->format('Y'));
        $activeBranchId = $pageData['activeBranchId'] ?? 0;

        $shifts = \Illuminate\Support\Facades\DB::table('staff_shifts')
            ->where('masseuse_id', $staffId)
            ->where('branch_id', $activeBranchId)
            ->whereMonth('shift_date', $month)
            ->whereYear('shift_date', $year)
            ->orderBy('shift_date', 'asc')
            ->get();

        foreach ($shifts as $shift) {
            $shift->earned_commission = \Illuminate\Support\Facades\DB::table('commissions')
                ->where('masseuse_id', $staffId)
                ->where('branch_id', $activeBranchId)
                ->whereDate('calculated_at', $shift->shift_date)
                ->sum('amount');
                
            $guarantee = (float)($shift->guarantee_amount ?? 0);
            $shift->guarantee_topup = max(0, $guarantee - $shift->earned_commission);
        }

        return view('masseuse.edit', array_merge($pageData, [
            'formRecord' => $formRecord,
            'shifts' => $shifts,
            'month' => $month,
            'year' => $year,
        ]));
    }

    public function updateAttendance(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'branch_id' => 'nullable|integer',
            'date' => 'required|date_format:Y-m-d',
            'staff_id' => 'required|integer',
            'is_working' => 'required|boolean',
        ]);

        $requestedBranchId = isset($validated['branch_id']) ? (int) $validated['branch_id'] : null;

        $this->masseuseService->updateStaffAttendance(
            $request->user(),
            $requestedBranchId,
            (string) $validated['date'],
            (int) $validated['staff_id'],
            (bool) $validated['is_working']
        );

        return $this->redirectToIndex($requestedBranchId, (string) $validated['date']);
    }

    public function store(Request $request): RedirectResponse
    {
        $payload = $request->validate([
            'branch_id' => 'nullable|integer',
            'date' => 'required|date_format:Y-m-d',
            'nickname' => 'required|string|max:255',
            'full_name' => 'nullable|string|max:255',
            'skills_description' => 'nullable|string|max:5000',
            'status' => 'required|in:available,busy,on_break,off_duty,day_off',
            'profile_image' => 'nullable|image|max:2048',
        ]);

        $requestedBranchId = isset($payload['branch_id']) ? (int) $payload['branch_id'] : null;

        $this->masseuseService->createMasseuse(
            $request->user(),
            $requestedBranchId,
            $payload,
            $request->file('profile_image')
        );

        return $this->redirectToIndex($requestedBranchId, (string) $payload['date'])
            ->with('success', 'เพิ่มข้อมูลหมอนวดเรียบร้อยแล้ว');
    }

    public function update(Request $request, int $staffId): RedirectResponse
    {
        $payload = $request->validate([
            'branch_id' => 'nullable|integer',
            'date' => 'required|date_format:Y-m-d',
            'nickname' => 'required|string|max:255',
            'full_name' => 'nullable|string|max:255',
            'skills_description' => 'nullable|string|max:5000',
            'status' => 'required|in:available,busy,on_break,off_duty,day_off',
            'profile_image' => 'nullable|image|max:2048',
            'remove_profile_image' => 'nullable|boolean',
        ]);

        $requestedBranchId = isset($payload['branch_id']) ? (int) $payload['branch_id'] : null;

        $this->masseuseService->updateMasseuse(
            $request->user(),
            $requestedBranchId,
            $staffId,
            $payload,
            $request->file('profile_image')
        );

        return $this->redirectToIndex($requestedBranchId, (string) $payload['date'])
            ->with('success', 'อัปเดตข้อมูลหมอนวดเรียบร้อยแล้ว');
    }

    public function destroy(Request $request, int $staffId): RedirectResponse
    {
        $payload = $request->validate([
            'branch_id' => 'nullable|integer',
            'date' => 'required|date_format:Y-m-d',
        ]);

        $requestedBranchId = isset($payload['branch_id']) ? (int) $payload['branch_id'] : null;

        $this->masseuseService->deleteMasseuse(
            $request->user(),
            $requestedBranchId,
            $staffId
        );

        return $this->redirectToIndex($requestedBranchId, (string) $payload['date'])
            ->with('success', 'ลบข้อมูลหมอนวดเรียบร้อยแล้ว');
    }

    private function redirectToIndex(?int $requestedBranchId, string $date): RedirectResponse
    {
        return redirect()->route('masseuse', array_filter([
            'branch_id' => $requestedBranchId,
            'date' => $date,
        ], static function ($value): bool {
            return $value !== null && $value !== '';
        }));
    }
}
