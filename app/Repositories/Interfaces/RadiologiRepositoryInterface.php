<?php

namespace App\Repositories\Interfaces;

interface RadiologiRepositoryInterface
{
    public function getStats($today, $yesterday);

    public function getVisitsTrend($selectedYear);

    public function getInsuranceBreakdown($selectedYear);

    public function getRadiologyExams($today);

    public function getYearlyComparison($years);
}
