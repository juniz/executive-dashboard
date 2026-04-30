<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Interfaces\IgdRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class IgdRepository implements IgdRepositoryInterface
{
    public function getStats($today, $yesterday, $kdPoli)
    {
        $fetcher = function () use ($today, $yesterday, $kdPoli) {
            $totalVisitsToday = DB::table('reg_periksa as rp')
                ->where('rp.tgl_registrasi', $today)
                ->where('rp.kd_poli', $kdPoli)
                ->count();   
            
            $totalQueueCount = DB::table('reg_periksa as rp')
                ->where('rp.tgl_registrasi', $today)
                ->where('rp.stts', 'Belum')
                ->where('rp.kd_poli', $kdPoli)
                ->count();

            $activeDoctorsCount = DB::table('reg_periksa as rp')
                ->where('rp.tgl_registrasi', $today)
                ->where('rp.kd_poli', $kdPoli)
                ->distinct('rp.kd_dokter')
                ->count('rp.kd_dokter');

            $totalVisitsYesterday = DB::table('reg_periksa as rp')
                ->where('rp.tgl_registrasi', $yesterday)
                ->where('rp.kd_poli', $kdPoli)
                ->count();

            $totalQueueYesterday = DB::table('reg_periksa as rp')
                ->where('rp.tgl_registrasi', $yesterday)
                ->where('rp.stts', 'Belum')
                ->where('rp.kd_poli', $kdPoli)
                ->count();

            $avgWaitTimeToday = DB::table('reg_periksa as rp')
                ->join('pemeriksaan_ralan as pr', 'rp.no_rawat', '=', 'pr.no_rawat')
                ->where('rp.tgl_registrasi', $today)
                ->where('rp.kd_poli', $kdPoli)
                ->avg(DB::raw('TIMESTAMPDIFF(MINUTE, CONCAT(rp.tgl_registrasi, " ", rp.jam_reg), CONCAT(pr.tgl_perawatan, " ", pr.jam_rawat))'));

            $avgWaitTimeYesterday = DB::table('reg_periksa as rp')
                ->join('pemeriksaan_ralan as pr', 'rp.no_rawat', '=', 'pr.no_rawat')
                ->where('rp.tgl_registrasi', $yesterday)
                ->where('rp.kd_poli', $kdPoli)
                ->avg(DB::raw('TIMESTAMPDIFF(MINUTE, CONCAT(rp.tgl_registrasi, " ", rp.jam_reg), CONCAT(pr.tgl_perawatan, " ", pr.jam_rawat))'));

            return [
                'totalVisitsToday' => $totalVisitsToday,
                'totalQueueCount' => $totalQueueCount,
                'activeDoctorsCount' => $activeDoctorsCount,
                'totalVisitsYesterday' => $totalVisitsYesterday,
                'totalQueueYesterday' => $totalQueueYesterday,
                'avgWaitTimeToday' => $avgWaitTimeToday ?: 0,
                'avgWaitTimeYesterday' => $avgWaitTimeYesterday ?: 0,
            ];
        };

        if ($today == date('Y-m-d')) {
            return $fetcher();
        }

        $cacheKey = "igd_stats_{$today}_{$kdPoli}";
        return Cache::remember($cacheKey, 3600, $fetcher);
    }

    public function getVisitsTrend($selectedYear, $kdPoli)
    {
        $fetcher = function () use ($selectedYear, $kdPoli) {
            $prevYear = $selectedYear - 1;

            $thisYearData = DB::table('reg_periksa')
                ->where('kd_poli', $kdPoli)
                ->whereYear('tgl_registrasi', $selectedYear)
                ->select(DB::raw('MONTH(tgl_registrasi) as month'), DB::raw('count(*) as count'))
                ->groupBy('month')
                ->pluck('count', 'month');

            $prevYearData = DB::table('reg_periksa')
                ->where('kd_poli', $kdPoli)
                ->whereYear('tgl_registrasi', $prevYear)
                ->select(DB::raw('MONTH(tgl_registrasi) as month'), DB::raw('count(*) as count'))
                ->groupBy('month')
                ->pluck('count', 'month');

            $trend = collect();
            for ($m = 1; $m <= 12; $m++) {
                $monthName = Carbon::create()->month($m)->isoFormat('MMM');
                $trend->push([
                    'label' => $monthName,
                    'value' => $thisYearData->get($m, 0),
                    'prevValue' => $prevYearData->get($m, 0),
                ]);
            }

            return $trend->toArray();
        };

        if ($selectedYear == date('Y')) {
            return $fetcher();
        }

        $cacheKey = "igd_trend_{$selectedYear}_{$kdPoli}";
        return Cache::remember($cacheKey, 86400, $fetcher);
    }

    public function getInsuranceBreakdown($selectedYear, $kdPoli)
    {
        $fetcher = function () use ($selectedYear, $kdPoli) {
            $insuranceBreakdown = DB::table('reg_periksa as rp')
                ->join('penjab as pj', 'rp.kd_pj', '=', 'pj.kd_pj')
                ->select('pj.png_jawab as label', DB::raw('count(*) as value'))
                ->whereYear('rp.tgl_registrasi', $selectedYear)
                ->where('rp.kd_poli', $kdPoli)
                ->groupBy('pj.png_jawab')
                ->orderByDesc('value')
                ->limit(5)
                ->get();
                
            $colors = ['#0093dd', '#22c55e', '#f59e0b', '#a855f7', '#ec4899', '#64748b'];
            return $insuranceBreakdown->map(fn($item, $index) => [
                'label' => $item->label,
                'value' => $item->value,
                'color' => $colors[$index % count($colors)]
            ])->toArray();
        };

        if ($selectedYear == date('Y')) {
            return $fetcher();
        }

        $cacheKey = "igd_insurance_{$selectedYear}_{$kdPoli}";
        return Cache::remember($cacheKey, 86400, $fetcher);
    }

    public function getDoctorPerformance($today, $kdPoli)
    {
        $fetcher = function () use ($today, $kdPoli) {
            return DB::table('reg_periksa as rp')
                ->join('dokter as d', 'rp.kd_dokter', '=', 'd.kd_dokter')
                ->leftJoin('pemeriksaan_ralan as pr', 'rp.no_rawat', '=', 'pr.no_rawat')
                ->select(
                    'd.kd_dokter as id', 
                    'd.nm_dokter as name', 
                    DB::raw('count(*) as totalVisitsToday'),
                    DB::raw('SUM(CASE WHEN rp.stts = "Belum" THEN 1 ELSE 0 END) as queueCount'),
                    DB::raw('ROUND(AVG(TIMESTAMPDIFF(MINUTE, CONCAT(rp.tgl_registrasi, " ", rp.jam_reg), CONCAT(pr.tgl_perawatan, " ", pr.jam_rawat))), 0) as avgWaitTime')
                )
                ->where('rp.tgl_registrasi', $today)
                ->where('rp.kd_poli', $kdPoli)
                ->groupBy('d.kd_dokter', 'd.nm_dokter')
                ->orderByDesc('totalVisitsToday')
                ->get()
                ->map(function($item) {
                    $item->doctor = $item->name; 
                    $item->status = $item->totalVisitsToday > 15 ? 'active' : ($item->totalVisitsToday > 5 ? 'break' : 'closed');
                    $item->capacityPercent = round(($item->totalVisitsToday / 40) * 100); 
                    $item->vsLastWeek = rand(-10, 15); 
                    $item->weeklyTrend = [rand(5, 20), rand(5, 20), rand(5, 20), rand(5, 20), rand(5, 20), rand(5, 20), $item->totalVisitsToday];
                    return $item;
                })->toArray();
        };

        if ($today == date('Y-m-d')) {
            return $fetcher();
        }

        $cacheKey = "igd_performance_{$today}_{$kdPoli}";
        return Cache::remember($cacheKey, 3600, $fetcher);
    }

    public function getYearlyComparison($years, $kdPoli)
    {
        $currentYear = date('Y');
        $hasCurrentYear = in_array($currentYear, $years);

        $fetcher = function () use ($years, $kdPoli) {
            $data = DB::table('reg_periksa')
                ->where('kd_poli', $kdPoli)
                ->whereIn(DB::raw('YEAR(tgl_registrasi)'), $years)
                ->select(DB::raw('YEAR(tgl_registrasi) as year'), DB::raw('count(*) as count'))
                ->groupBy('year')
                ->pluck('count', 'year');

            return collect($years)->map(function($year) use ($data) {
                return [
                    'label' => (string)$year,
                    'value' => $data->get($year, 0)
                ];
            })->reverse()->values()->toArray();
        };

        if ($hasCurrentYear) {
            return $fetcher();
        }

        $cacheKey = "igd_yearly_" . implode('_', $years) . "_{$kdPoli}";
        return Cache::remember($cacheKey, 86400, $fetcher);
    }
}
