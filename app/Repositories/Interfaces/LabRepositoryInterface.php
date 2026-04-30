<?php

namespace App\Repositories\Interfaces;

interface LabRepositoryInterface
{
    public function getStats($today, $yesterday, $kdPoli);

    public function getVisitsTrend($selectedYear);

    public function getInsuranceBreakdown($selectedYear);

    public function getLabExaminations($today);

    public function getYearlyComparison($years);
}
