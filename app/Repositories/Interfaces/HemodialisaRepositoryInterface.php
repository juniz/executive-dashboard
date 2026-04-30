<?php

namespace App\Repositories\Interfaces;

interface HemodialisaRepositoryInterface
{
    public function getStats($today, $yesterday, $kdPoli);

    public function getVisitsTrend($selectedYear, $kdPoli);

    public function getInsuranceBreakdown($selectedYear, $kdPoli);

    public function getPatientPerformance($today, $kdPoli);

    public function getYearlyComparison($years, $kdPoli);
}
