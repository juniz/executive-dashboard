<?php

namespace App\Http\Controllers;

use App\Repositories\Interfaces\RekamMedisRepositoryInterface;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class DashboardRekamMedisController extends Controller
{
    protected $repository;

    public function __construct(RekamMedisRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function index(Request $request)
    {
        Carbon::setLocale('id');
        $selectedYear = $request->tahun ?: date('Y');
        $years = range(date('Y'), 2022);

        // 1. Fetch Basic Totals
        $totalBeds = $this->repository->getTotalBeds();

        // 2. Monthly Indicators Aggregation
        $monthlyStats = $this->repository->getMonthlyStats($selectedYear, $totalBeds);

        // 3. Current Month Summary
        $currentMonthStats = $monthlyStats[date('n') - 1] ?? end($monthlyStats);

        // 4. Ward Performance (Current Month)
        $currentStartDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $currentEndDate = Carbon::now()->endOfMonth()->format('Y-m-d');
        $wardPerformance = $this->repository->getWardPerformance($currentStartDate, $currentEndDate);

        return Inertia::render('RekamMedisDashboard', [
            'database' => [
                'summary' => $currentMonthStats,
                'monthlyTrends' => $monthlyStats,
                'wardPerformance' => $wardPerformance,
                'availableYears' => $years,
                'filters' => [
                    'tahun' => (int)$selectedYear
                ]
            ]
        ]);
    }
}
