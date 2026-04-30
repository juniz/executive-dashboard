<?php

namespace App\Http\Controllers;

use App\Repositories\Interfaces\LabRepositoryInterface;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class DashboardLabController extends Controller
{
    protected $repository;

    public function __construct(LabRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function index(Request $request)
    {
        Carbon::setLocale('id');
        $today = Carbon::today()->format('Y-m-d');
        $yesterday = Carbon::yesterday()->format('Y-m-d');
        
        $kdPoli = 'LAB';

        // Year filter logic
        $selectedYear = $request->tahun ?: date('Y');
        $years = range(date('Y'), 2022);
        
        // 1. Overall Stats
        $stats = $this->repository->getStats($today, $yesterday, $kdPoli);
        
        $totalVisitsToday = $stats['totalVisitsToday'];
        $totalQueueCount = $stats['totalQueueCount'];
        $activeDoctorsCount = $stats['activeDoctorsCount'];
        $totalVisitsYesterday = $stats['totalVisitsYesterday'];
        $totalQueueYesterday = $stats['totalQueueYesterday'];
        $avgWaitTimeOverall = $stats['avgWaitTimeToday'];
        $avgWaitTimeYesterday = $stats['avgWaitTimeYesterday'];

        $visitsDelta = $totalVisitsYesterday > 0 ? (($totalVisitsToday - $totalVisitsYesterday) / $totalVisitsYesterday) * 100 : 0;
        $queueDelta = $totalQueueYesterday > 0 ? (($totalQueueCount - $totalQueueYesterday) / $totalQueueYesterday) * 100 : 0;
        $waitTimeDelta = $avgWaitTimeYesterday > 0 ? (($avgWaitTimeOverall - $avgWaitTimeYesterday) / $avgWaitTimeYesterday) * 100 : 0;

        $overallEfficiency = 0;
            
        // 2. Trend Kunjungan
        $trend = collect($this->repository->getVisitsTrend($selectedYear));

        // 3. Insurance Breakdown
        $insuranceBreakdown = collect($this->repository->getInsuranceBreakdown($selectedYear));

        // 4. Lab Exams
        $labExaminations = collect($this->repository->getLabExaminations($today));

        // 5. Yearly Comparison
        $yearlyComparison = collect($this->repository->getYearlyComparison($years));

        return Inertia::render('LabDashboard', [
            'database' => [
                'totalVisitsToday' => $totalVisitsToday,
                'totalVisitsYearly' => $insuranceBreakdown->sum('value'),
                'totalQueueCount' => $totalQueueCount,
                'activeDoctorsCount' => $activeDoctorsCount,
                'avgWaitTimeOverall' => round($avgWaitTimeOverall ?: 0),
                'overallEfficiency' => $overallEfficiency,
                'visitsDelta' => round($visitsDelta, 1),
                'queueDelta' => round($queueDelta, 1),
                'waitTimeDelta' => round($waitTimeDelta, 1),
                'trend' => $trend, 
                'insuranceBreakdown' => $insuranceBreakdown,
                'doctorPerformance' => $labExaminations,
                'yearlyComparison' => $yearlyComparison,
                'availableYears' => $years,
                'filters' => [
                    'kd_poli' => $kdPoli,
                    'tahun' => (int)$selectedYear
                ]
            ]
        ]);
    }
}
