<?php

namespace App\Repositories\Interfaces;

interface IgdRepositoryInterface
{
    public function getStats($today, $yesterday, $kdPoli);

    public function getVisitsTrend($selectedYear, $kdPoli);

    public function getInsuranceBreakdown($selectedYear, $kdPoli);

    public function getDoctorPerformance($today, $kdPoli);

    public function getYearlyComparison($years, $kdPoli);
}
