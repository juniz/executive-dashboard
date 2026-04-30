<?php

namespace App\Repositories\Interfaces;

interface RekamMedisRepositoryInterface
{
    public function getTotalBeds();

    public function getMonthlyStats($selectedYear, $totalBeds);

    public function getWardPerformance($startDate, $endDate);
}
