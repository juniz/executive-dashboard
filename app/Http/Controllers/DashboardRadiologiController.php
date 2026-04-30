<?php

namespace App\Http\Controllers;

use App\Repositories\Interfaces\RadiologiRepositoryInterface;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class DashboardRadiologiController extends Controller
{
    protected $repository;

    public function __construct(RadiologiRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function index(Request $request)
    {
        Carbon::setLocale('id');
        $today = Carbon::today()->format('Y-m-d');
        $yesterday = Carbon::yesterday()->format('Y-m-d');
        
        // Year filter logic
        $selectedYear = $request->tahun ?: date('Y');
        $years = range(date('Y'), 2022); 
        
        // 1. Overall Stats
        $stats = $this->repository->getStats($today, $yesterday);
        
        $totalVisitsToday = $stats['totalVisitsToday'];
        $activeRadiologistsCount = $stats['activeRadiologistsCount'];
        $totalVisitsYesterday = $stats['totalVisitsYesterday'];
        $avgWaitTimeOverall = $stats['avgWaitTimeToday'];
        $avgWaitTimeYesterday = $stats['avgWaitTimeYesterday'];

        $visitsDelta = $totalVisitsYesterday > 0 ? (($totalVisitsToday - $totalVisitsYesterday) / $totalVisitsYesterday) * 100 : 0;
        $waitTimeDelta = $avgWaitTimeYesterday > 0 ? (($avgWaitTimeOverall - $avgWaitTimeYesterday) / $avgWaitTimeYesterday) * 100 : 0;

        $overallEfficiency = 0;
            
        // 2. Trend Kunjungan
        $trend = collect($this->repository->getVisitsTrend($selectedYear));

        // 3. Insurance Breakdown
        $insuranceBreakdown = collect($this->repository->getInsuranceBreakdown($selectedYear));

        // 4. Radiology Examinations
        $radiologyExams = collect($this->repository->getRadiologyExams($today));

        // 5. Yearly Comparison
        $yearlyComparison = collect($this->repository->getYearlyComparison($years));

        return Inertia::render('RadiologyDashboard', [
            'database' => [
                'totalVisitsToday' => $totalVisitsToday,
                'totalVisitsYearly' => $insuranceBreakdown->sum('value'),
                'totalQueueCount' => 0,
                'activeDoctorsCount' => $activeRadiologistsCount,
                'avgWaitTimeOverall' => round($avgWaitTimeOverall ?: 0),
                'overallEfficiency' => $overallEfficiency,
                'visitsDelta' => round($visitsDelta, 1),
                'queueDelta' => 0,
                'waitTimeDelta' => round($waitTimeDelta, 1),
                'trend' => $trend,
                'insuranceBreakdown' => $insuranceBreakdown,
                'doctorPerformance' => $radiologyExams,
                'yearlyComparison' => $yearlyComparison,
                'availableYears' => $years,
                'filters' => [
                    'tahun' => (int)$selectedYear
                ]
            ]
        ]);
    }
}
