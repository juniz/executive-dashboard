<?php

namespace App\Http\Controllers;

use App\Repositories\Interfaces\HemodialisaRepositoryInterface;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class DashboardHemodialisaController extends Controller
{
    protected $repository;

    public function __construct(HemodialisaRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function index(Request $request)
    {
        Carbon::setLocale('id');
        $today = Carbon::today()->format('Y-m-d');
        $yesterday = Carbon::yesterday()->format('Y-m-d');
        
        $kdPoli = 'HDL';
        $selectedYear = $request->tahun ?: date('Y');
        $years = range(date('Y'), 2022);

        // 1. Overall Stats
        $stats = $this->repository->getStats($today, $yesterday, $kdPoli);
        
        $totalVisitsToday = $stats['totalVisitsToday'];
        $totalVisitsYesterday = $stats['totalVisitsYesterday'];
        $finishedToday = $stats['finishedToday'];
        $waitingToday = $stats['waitingToday'];
        $avgDuration = $stats['avgDuration'];

        $visitsDelta = $totalVisitsYesterday > 0 ? (($totalVisitsToday - $totalVisitsYesterday) / $totalVisitsYesterday) * 100 : 0;

        // 2. Trend Kunjungan
        $trend = collect($this->repository->getVisitsTrend($selectedYear, $kdPoli));

        // 3. Insurance Breakdown
        $insuranceBreakdown = collect($this->repository->getInsuranceBreakdown($selectedYear, $kdPoli));

        // 4. Patient Performance
        $patientPerformance = collect($this->repository->getPatientPerformance($today, $kdPoli));

        // 5. Yearly Comparison
        $yearlyComparison = collect($this->repository->getYearlyComparison($years, $kdPoli));

        return Inertia::render('HemodialisaDashboard', [
            'database' => [
                'totalVisitsToday' => $totalVisitsToday,
                'totalVisitsYearly' => $insuranceBreakdown->sum('value'),
                'finishedToday' => $finishedToday,
                'waitingToday' => $waitingToday,
                'avgDuration' => round($avgDuration ?: 0),
                'visitsDelta' => round($visitsDelta, 1),
                'trend' => $trend,
                'insuranceBreakdown' => $insuranceBreakdown,
                'patientPerformance' => $patientPerformance,
                'yearlyComparison' => $yearlyComparison,
                'availableYears' => $years,
                'filters' => [
                    'tahun' => (int)$selectedYear
                ]
            ]
        ]);
    }
}
