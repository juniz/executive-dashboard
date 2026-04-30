<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Interfaces\HemodialisaRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class HemodialisaRepository implements HemodialisaRepositoryInterface
{
    public function getStats($today, $yesterday, $kdPoli)
    {
        $fetcher = function () use ($today, $yesterday, $kdPoli) {
            $totalVisitsToday = DB::table('reg_periksa')
                ->where('tgl_registrasi', $today)
                ->where('kd_poli', $kdPoli)
                ->count();
            
            $totalVisitsYesterday = DB::table('reg_periksa')
                ->where('tgl_registrasi', $yesterday)
                ->where('kd_poli', $kdPoli)
                ->count();

            $finishedToday = DB::table('reg_periksa')
                ->where('tgl_registrasi', $today)
                ->where('kd_poli', $kdPoli)
                ->where('stts', 'Sudah')
                ->count();

            $waitingToday = DB::table('reg_periksa')
                ->where('tgl_registrasi', $today)
                ->where('kd_poli', $kdPoli)
                ->where('stts', 'Belum')
                ->count();

            $avgDuration = DB::table('hemodialisa as h')
                ->join('reg_periksa as rp', 'h.no_rawat', '=', 'rp.no_rawat')
                ->where('rp.tgl_registrasi', $today)
                ->where('rp.kd_poli', $kdPoli)
                ->avg(DB::raw('CAST(h.lama AS UNSIGNED)'));

            return [
                'totalVisitsToday' => $totalVisitsToday,
                'totalVisitsYesterday' => $totalVisitsYesterday,
                'finishedToday' => $finishedToday,
                'waitingToday' => $waitingToday,
                'avgDuration' => $avgDuration ?: 0,
            ];
        };

        if ($today == date('Y-m-d')) {
            return $fetcher();
        }

        $cacheKey = "hemo_stats_{$today}_{$kdPoli}";
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
                ->groupBy('month')->pluck('count', 'month');

            $prevYearData = DB::table('reg_periksa')
                ->where('kd_poli', $kdPoli)
                ->whereYear('tgl_registrasi', $prevYear)
                ->select(DB::raw('MONTH(tgl_registrasi) as month'), DB::raw('count(*) as count'))
                ->groupBy('month')->pluck('count', 'month');

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

        $cacheKey = "hemo_trend_{$selectedYear}_{$kdPoli}";
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

        $cacheKey = "hemo_insurance_{$selectedYear}_{$kdPoli}";
        return Cache::remember($cacheKey, 86400, $fetcher);
    }

    public function getPatientPerformance($today, $kdPoli)
    {
        $fetcher = function () use ($today, $kdPoli) {
            return DB::table('reg_periksa as rp')
                ->join('pasien as p', 'rp.no_rkm_medis', '=', 'p.no_rkm_medis')
                ->leftJoin('hemodialisa as h', 'rp.no_rawat', '=', 'h.no_rawat')
                ->select(
                    'rp.no_rawat as id',
                    'p.nm_pasien as patient',
                    'rp.stts as status',
                    'h.lama as duration',
                    'h.akses as access',
                    'h.dialist as dialyzer',
                    'h.qb',
                    'h.qd'
                )
                ->where('rp.tgl_registrasi', $today)
                ->where('rp.kd_poli', $kdPoli)
                ->get()
                ->map(function($item) {
                    $item->statusLabel = $item->status === 'Sudah' ? 'Finished' : ($item->status === 'Belum' ? 'Waiting' : $item->status);
                    $item->statusColor = $item->status === 'Sudah' ? 'active' : ($item->status === 'Belum' ? 'closed' : 'break');
                    return $item;
                })->toArray();
        };

        if ($today == date('Y-m-d')) {
            return $fetcher();
        }

        $cacheKey = "hemo_performance_{$today}_{$kdPoli}";
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

        $cacheKey = "hemo_yearly_" . implode('_', $years) . "_{$kdPoli}";
        return Cache::remember($cacheKey, 86400, $fetcher);
    }
}
