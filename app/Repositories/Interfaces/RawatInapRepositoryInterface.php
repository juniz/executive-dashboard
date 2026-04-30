<?php

namespace App\Repositories\Interfaces;

interface RawatInapRepositoryInterface
{
    public function getAllBangsal();

    public function getKpiOverview($today, $thirtyDaysAgo, $kdBangsal = null);

    public function getVisitsTrend($selectedYear, $kdBangsal = null);

    public function getInsuranceBreakdown($selectedYear, $kdBangsal = null);

    public function getWardPerformance($today, $thirtyDaysAgo, $kdBangsal = null);

    public function getYearlyComparison($years, $kdBangsal = null);
}
