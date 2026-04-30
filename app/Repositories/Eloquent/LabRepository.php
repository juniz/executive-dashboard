<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Interfaces\LabRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class LabRepository implements LabRepositoryInterface
{
    public function getStats($today, $yesterday, $kdPoli)
    {
        $fetcher = function () use ($today, $yesterday, $kdPoli) {
            $totalVisitsToday = DB::table('periksa_lab')
                ->where('tgl_periksa', $today)
                ->count();   
            
            $totalQueueCount = DB::table('reg_periksa as rp')
                ->where('rp.tgl_registrasi', $today)
                ->where('rp.stts', 'Belum')
                ->where('rp.kd_poli', $kdPoli)
                ->count();

            $activeDoctorsCount = DB::table('periksa_lab')
                ->where('tgl_periksa', $today)
                ->distinct('kd_dokter')
                ->count('kd_dokter');

            $totalVisitsYesterday = DB::table('periksa_lab')
                ->where('tgl_periksa', $yesterday)
                ->count();

            $totalQueueYesterday = DB::table('reg_periksa as rp')
                ->where('rp.tgl_registrasi', $yesterday)
                ->where('rp.stts', 'Belum')
                ->where('rp.kd_poli', $kdPoli)
                ->count();

            $avgWaitTimeToday = DB::table('reg_periksa as rp')
                ->join('periksa_lab as pl', 'rp.no_rawat', '=', 'pl.no_rawat')
                ->where('pl.tgl_periksa', $today)
                ->avg(DB::raw('TIMESTAMPDIFF(MINUTE, CONCAT(rp.tgl_registrasi, " ", rp.jam_reg), CONCAT(pl.tgl_periksa, " ", pl.jam))'));

            $avgWaitTimeYesterday = DB::table('reg_periksa as rp')
                ->join('periksa_lab as pl', 'rp.no_rawat', '=', 'pl.no_rawat')
                ->where('pl.tgl_periksa', $yesterday)
                ->avg(DB::raw('TIMESTAMPDIFF(MINUTE, CONCAT(rp.tgl_registrasi, " ", rp.jam_reg), CONCAT(pl.tgl_periksa, " ", pl.jam))'));

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

        $cacheKey = "lab_stats_{$today}_{$kdPoli}";
        return Cache::remember($cacheKey, 3600, $fetcher);
    }

    public function getVisitsTrend($selectedYear)
    {
        $fetcher = function () use ($selectedYear) {
            $prevYear = $selectedYear - 1;

            $thisYearData = DB::table('periksa_lab')
                ->whereYear('tgl_periksa', $selectedYear)
                ->select(DB::raw('MONTH(tgl_periksa) as month'), DB::raw('count(*) as count'))
                ->groupBy('month')
                ->pluck('count', 'month');

            $prevYearData = DB::table('periksa_lab')
                ->whereYear('tgl_periksa', $prevYear)
                ->select(DB::raw('MONTH(tgl_periksa) as month'), DB::raw('count(*) as count'))
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

        $cacheKey = "lab_trend_{$selectedYear}";
        return Cache::remember($cacheKey, 86400, $fetcher);
    }

    public function getInsuranceBreakdown($selectedYear)
    {
        $fetcher = function () use ($selectedYear) {
            $insuranceBreakdown = DB::table('periksa_lab as pl')
                ->join('reg_periksa as rp', 'pl.no_rawat', '=', 'rp.no_rawat')
                ->join('penjab as pj', 'rp.kd_pj', '=', 'pj.kd_pj')
                ->select('pj.png_jawab as label', DB::raw('count(*) as value'))
                ->whereYear('pl.tgl_periksa', $selectedYear)
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

        $cacheKey = "lab_insurance_{$selectedYear}";
        return Cache::remember($cacheKey, 86400, $fetcher);
    }

    public function getLabExaminations($today)
    {
        $fetcher = function () use ($today) {
            return DB::table('periksa_lab as pl')
                ->join('jns_perawatan_lab as jp', 'pl.kd_jenis_prw', '=', 'jp.kd_jenis_prw')
                ->join('reg_periksa as rp', 'pl.no_rawat', '=', 'rp.no_rawat')
                ->select(
                    'jp.kd_jenis_prw as id',
                    'pl.kategori as name', 
                    'jp.nm_perawatan as doctor', 
                    DB::raw('count(*) as totalVisitsToday'),
                    DB::raw('0 as queueCount'),
                    DB::raw('ROUND(AVG(TIMESTAMPDIFF(MINUTE, CONCAT(rp.tgl_registrasi, " ", rp.jam_reg), CONCAT(pl.tgl_periksa, " ", pl.jam))), 0) as avgWaitTime')
                )
                ->where('pl.tgl_periksa', $today)
                ->groupBy('jp.kd_jenis_prw', 'pl.kategori', 'jp.nm_perawatan')
                ->orderByDesc('totalVisitsToday')
                ->limit(30)
                ->get()
                ->map(function($item) {
                    $item->status = 'active'; 
                    $item->avgWaitTime = $item->avgWaitTime ?: 0;
                    $item->capacityPercent = 0; 
                    $item->vsLastWeek = rand(-5, 5); 
                    $item->weeklyTrend = [0, 0, 0, 0, 0, 0, $item->totalVisitsToday];
                    return $item;
                })->toArray();
        };

        if ($today == date('Y-m-d')) {
            return $fetcher();
        }

        $cacheKey = "lab_examinations_{$today}";
        return Cache::remember($cacheKey, 3600, $fetcher);
    }

    public function getYearlyComparison($years)
    {
        $currentYear = date('Y');
        $hasCurrentYear = in_array($currentYear, $years);

        $fetcher = function () use ($years) {
            $data = DB::table('periksa_lab')
                ->whereIn(DB::raw('YEAR(tgl_periksa)'), $years)
                ->select(DB::raw('YEAR(tgl_periksa) as year'), DB::raw('count(*) as count'))
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

        $cacheKey = "lab_yearly_" . implode('_', $years);
        return Cache::remember($cacheKey, 86400, $fetcher);
    }
}
