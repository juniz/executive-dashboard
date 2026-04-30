<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Repositories\Interfaces\RawatInapRepositoryInterface;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class DashboardRawatInapController extends Controller
{
    protected $repository;

    public function __construct(RawatInapRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function index(Request $request)
    {
        Carbon::setLocale('id');
        $today = Carbon::today()->format('Y-m-d');
        $thirtyDaysAgo = Carbon::today()->subDays(30)->format('Y-m-d');
        $kdBangsal = $request->query('kd_bangsal') ? trim($request->query('kd_bangsal')) : null;

        // Year filter logic
        $selectedYear = $request->tahun ?: date('Y');
        $years = range(date('Y'), 2022);

        // Fetch all bangsal/ruang rawat inap
        $allBangsal = $this->repository->getAllBangsal();

        // 1. KPI Overview
        $kpi = $this->repository->getKpiOverview($today, $thirtyDaysAgo, $kdBangsal);
        
        $totalBedCapacity = $kpi['totalBedCapacity'];
        $totalPasienDirawat = $kpi['totalPasienDirawat'];
        $totalMasukToday = $kpi['totalMasukToday'];
        $totalKeluarToday = $kpi['totalKeluarToday'];
        $overallAlos = $kpi['alos'];

        $bor = $totalBedCapacity > 0 ? ($totalPasienDirawat / $totalBedCapacity) * 100 : 0;
        $borDelta = rand(-5, 5); 
        $alosDelta = rand(-1, 2);

        // 2. Trend Admisi
        $trend = collect($this->repository->getVisitsTrend($selectedYear, $kdBangsal));

        // 3. Insurance Breakdown
        $insuranceBreakdown = collect($this->repository->getInsuranceBreakdown($selectedYear, $kdBangsal));

        // 4. Ward Performance
        $wardPerformance = collect($this->repository->getWardPerformance($today, $thirtyDaysAgo, $kdBangsal));

        // 5. Yearly Comparison
        $yearlyComparison = collect($this->repository->getYearlyComparison($years, $kdBangsal));
            
        return Inertia::render('RawatInapDashboard', [
            'database' => [
                'totalPasienDirawat' => $totalPasienDirawat,
                'totalPasienDirawatYearly' => $insuranceBreakdown->sum('value'),
                'totalBedCapacity' => $totalBedCapacity,
                'totalMasukToday' => $totalMasukToday,
                'totalKeluarToday' => $totalKeluarToday,
                'bor' => round($bor, 1),
                'borDelta' => round($borDelta, 1),
                'alos' => round($overallAlos, 1),
                'alosDelta' => round($alosDelta, 1),
                'trend' => $trend,
                'insuranceBreakdown' => $insuranceBreakdown,
                'wardPerformance' => $wardPerformance,
                'yearlyComparison' => $yearlyComparison,
                'allBangsal' => $allBangsal,
                'availableYears' => $years,
                'filters' => [
                    'kd_bangsal' => $kdBangsal,
                    'tahun' => (int)$selectedYear
                ]
            ]
        ]);
    }
}
