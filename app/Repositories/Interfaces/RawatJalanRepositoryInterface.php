<?php

namespace App\Repositories\Interfaces;

interface RawatJalanRepositoryInterface
{
    public function getAllClinics();

    public function getStats($date, $kdPoli = null);

    public function getWaitTime($date, $kdPoli = null);

    public function getTotalKuota($dayName, $kdPoli = null);

    public function getVisitsTrend($selectedYear, $kdPoli = null);

    public function getInsuranceBreakdown($selectedYear, $kdPoli = null);

    public function getClinicPerformance($date, $dayName, $kdPoli = null);
    public function getYearlyComparison($years, $kdPoli = null);
}
