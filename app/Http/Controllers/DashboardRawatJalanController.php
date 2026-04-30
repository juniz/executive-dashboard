<?php

namespace App\Http\Controllers;

use App\Repositories\Interfaces\RawatJalanRepositoryInterface;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class DashboardRawatJalanController extends Controller
{
    protected $repository;

    public function __construct(RawatJalanRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function index(Request $request)
    {
        Carbon::setLocale('id');
        $today = Carbon::today()->format('Y-m-d');
        $yesterday = Carbon::yesterday()->format('Y-m-d');
        
        $kdPoli = $request->query('kd_poli') ? trim($request->query('kd_poli')) : null;
        $dayName = Carbon::today()->isoFormat('dddd'); 

        // Year filter logic
        $selectedYear = $request->tahun ?: date('Y');
        $years = range(date('Y'), 2022);

        // Fetch all clinics
        $allClinics = $this->repository->getAllClinics();

        // 1. Overall Stats
        $todayStats = $this->repository->getStats($today, $kdPoli);
        $yesterdayStats = $this->repository->getStats($yesterday, $kdPoli);

        $totalVisitsToday = $todayStats['totalVisits'];
        $totalQueueCount = $todayStats['totalQueue'];
        $activeClinicsCount = $todayStats['activeClinicsCount'];
        $totalClinicsCount = $todayStats['totalClinicsCount'];

        $totalVisitsYesterday = $yesterdayStats['totalVisits'];
        $totalQueueYesterday = $yesterdayStats['totalQueue'];

        $avgWaitTimeOverall = $this->repository->getWaitTime($today, $kdPoli);
        $avgWaitTimeYesterday = $this->repository->getWaitTime($yesterday, $kdPoli);

        $visitsDelta = $totalVisitsYesterday > 0 ? (($totalVisitsToday - $totalVisitsYesterday) / $totalVisitsYesterday) * 100 : 0;
        $queueDelta = $totalQueueYesterday > 0 ? (($totalQueueCount - $totalQueueYesterday) / $totalQueueYesterday) * 100 : 0;
        $waitTimeDelta = $avgWaitTimeYesterday > 0 ? (($avgWaitTimeOverall - $avgWaitTimeYesterday) / $avgWaitTimeYesterday) * 100 : 0;

        $totalKuota = $this->repository->getTotalKuota($dayName, $kdPoli);
        $overallEfficiency = $totalKuota > 0 ? ($totalVisitsToday / $totalKuota) * 100 : 0;
            
        // 2. Trend Kunjungan
        $trend = collect($this->repository->getVisitsTrend($selectedYear, $kdPoli));

        // 3. Insurance Breakdown
        $insuranceBreakdown = collect($this->repository->getInsuranceBreakdown($selectedYear, $kdPoli));

        // 4. Clinic Performance
        $clinicPerformance = collect($this->repository->getClinicPerformance($today, $dayName, $kdPoli));

        // 5. Yearly Comparison
        $yearlyComparison = collect($this->repository->getYearlyComparison($years, $kdPoli));

        return Inertia::render('RawatJalanDashboard', [
            'database' => [
                'totalVisitsToday' => $totalVisitsToday,
                'totalVisitsYearly' => $insuranceBreakdown->sum('value'),
                'totalQueueCount' => $totalQueueCount,
                'activeClinicsCount' => $activeClinicsCount,
                'totalClinicsCount' => $totalClinicsCount,
                'avgWaitTimeOverall' => round($avgWaitTimeOverall ?: 0),
                'overallEfficiency' => round($overallEfficiency),
                'visitsDelta' => round($visitsDelta, 1),
                'queueDelta' => round($queueDelta, 1),
                'waitTimeDelta' => round($waitTimeDelta, 1),
                'trend' => $trend,
                'insuranceBreakdown' => $insuranceBreakdown,
                'clinicPerformance' => $clinicPerformance,
                'yearlyComparison' => $yearlyComparison,
                'allClinics' => $allClinics,
                'availableYears' => $years,
                'filters' => [
                    'kd_poli' => $kdPoli,
                    'tahun' => (int)$selectedYear
                ]
            ]
        ]);
    }
}
