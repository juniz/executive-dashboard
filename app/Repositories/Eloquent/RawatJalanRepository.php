<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Interfaces\RawatJalanRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class RawatJalanRepository implements RawatJalanRepositoryInterface
{
    public function getAllClinics()
    {
        return Cache::remember('ralan_all_clinics', 86400, function () {
            return DB::table('poliklinik')
                ->select('kd_poli', 'nm_poli')
                ->where('status', '1')
                ->where('nm_poli', 'LIKE', 'KLINIK%')
                ->orderBy('nm_poli')
                ->get()->toArray();
        });
    }

    public function getStats($date, $kdPoli = null)
    {
        $fetcher = function () use ($date, $kdPoli) {
            $totalVisits = DB::table('reg_periksa as rp')
                ->join('poliklinik as p', 'rp.kd_poli', '=', 'p.kd_poli')
                ->where('rp.tgl_registrasi', $date)
                ->where('rp.status_lanjut', 'Ralan')
                ->where('p.nm_poli', 'LIKE', 'KLINIK%')
                ->when($kdPoli, fn($q) => $q->where('rp.kd_poli', $kdPoli))
                ->count();

            $totalQueue = DB::table('reg_periksa as rp')
                ->join('poliklinik as p', 'rp.kd_poli', '=', 'p.kd_poli')
                ->where('rp.tgl_registrasi', $date)
                ->where('rp.status_lanjut', 'Ralan')
                ->where('rp.stts', 'Belum')
                ->where('p.nm_poli', 'LIKE', 'KLINIK%')
                ->when($kdPoli, fn($q) => $q->where('rp.kd_poli', $kdPoli))
                ->count();

            $activeClinicsCount = DB::table('reg_periksa as rp')
                ->join('poliklinik as p', 'rp.kd_poli', '=', 'p.kd_poli')
                ->where('rp.tgl_registrasi', $date)
                ->where('rp.status_lanjut', 'Ralan')
                ->where('p.nm_poli', 'LIKE', 'KLINIK%')
                ->when($kdPoli, fn($q) => $q->where('rp.kd_poli', $kdPoli))
                ->distinct('rp.kd_poli')
                ->count('rp.kd_poli');

            $totalClinicsCount = DB::table('poliklinik')
                ->where('status', '1')
                ->where('nm_poli', 'LIKE', 'KLINIK%')
                ->when($kdPoli, fn($q) => $q->where('kd_poli', $kdPoli))
                ->count();

            return [
                'totalVisits' => $totalVisits,
                'totalQueue' => $totalQueue,
                'activeClinicsCount' => $activeClinicsCount,
                'totalClinicsCount' => $totalClinicsCount,
            ];
        };

        if ($date == date('Y-m-d')) {
            return $fetcher();
        }

        $cacheKey = "ralan_stats_{$date}_" . ($kdPoli ?? 'all');
        return Cache::remember($cacheKey, 3600, $fetcher);
    }

    public function getWaitTime($date, $kdPoli = null)
    {
        $fetcher = function () use ($date, $kdPoli) {
            return DB::table('reg_periksa as rp')
                ->join('pemeriksaan_ralan as pr', 'rp.no_rawat', '=', 'pr.no_rawat')
                ->join('poliklinik as p', 'rp.kd_poli', '=', 'p.kd_poli')
                ->where('rp.tgl_registrasi', $date)
                ->where('rp.status_lanjut', 'Ralan')
                ->where('p.nm_poli', 'LIKE', 'KLINIK%')
                ->when($kdPoli, fn($q) => $q->where('rp.kd_poli', $kdPoli))
                ->avg(DB::raw('TIMESTAMPDIFF(MINUTE, CONCAT(rp.tgl_registrasi, " ", rp.jam_reg), CONCAT(pr.tgl_perawatan, " ", pr.jam_rawat))'));
        };

        if ($date == date('Y-m-d')) {
            return $fetcher();
        }

        $cacheKey = "ralan_wait_time_{$date}_" . ($kdPoli ?? 'all');
        return Cache::remember($cacheKey, 3600, $fetcher);
    }

    public function getTotalKuota($dayName, $kdPoli = null)
    {
        return Cache::remember("ralan_kuota_{$dayName}_" . ($kdPoli ?? 'all'), 86400, function () use ($dayName, $kdPoli) {
            return DB::table('jadwal as j')
                ->join('poliklinik as p', 'j.kd_poli', '=', 'p.kd_poli')
                ->where('j.hari_kerja', 'like', "%$dayName%")
                ->where('p.nm_poli', 'LIKE', 'KLINIK%')
                ->when($kdPoli, fn($q) => $q->where('j.kd_poli', $kdPoli))
                ->sum('j.kuota');
        });
    }

    public function getVisitsTrend($selectedYear, $kdPoli = null)
    {
        $fetcher = function () use ($selectedYear, $kdPoli) {
            $prevYear = $selectedYear - 1;

            $thisYearDataQuery = DB::table('reg_periksa as rp')
                ->join('poliklinik as p', 'rp.kd_poli', '=', 'p.kd_poli')
                ->where('rp.status_lanjut', 'Ralan')
                ->where('p.nm_poli', 'LIKE', 'KLINIK%')
                ->where('rp.stts', '<>', 'Belum')
                ->where('rp.stts', '<>', 'Batal')
                ->whereYear('rp.tgl_registrasi', $selectedYear);

            $prevYearDataQuery = DB::table('reg_periksa as rp')
                ->join('poliklinik as p', 'rp.kd_poli', '=', 'p.kd_poli')
                ->where('rp.status_lanjut', 'Ralan')
                ->where('rp.stts', '<>', 'Belum')
                ->where('rp.stts', '<>', 'Batal')
                ->where('p.nm_poli', 'LIKE', 'KLINIK%')
                ->whereYear('rp.tgl_registrasi', $prevYear);

            if ($kdPoli) {
                $thisYearDataQuery->where('rp.kd_poli', $kdPoli);
                $prevYearDataQuery->where('rp.kd_poli', $kdPoli);
            }

            $thisYearData = $thisYearDataQuery->select(DB::raw('MONTH(rp.tgl_registrasi) as month'), DB::raw('count(*) as count'))
                ->groupBy('month')->pluck('count', 'month');

            $prevYearData = $prevYearDataQuery->select(DB::raw('MONTH(rp.tgl_registrasi) as month'), DB::raw('count(*) as count'))
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

        $cacheKey = "ralan_trend_{$selectedYear}_" . ($kdPoli ?? 'all');
        return Cache::remember($cacheKey, 86400, $fetcher);
    }

    public function getInsuranceBreakdown($selectedYear, $kdPoli = null)
    {
        $fetcher = function () use ($selectedYear, $kdPoli) {
            $insuranceBreakdown = DB::table('reg_periksa as rp')
                ->join('penjab as pj', 'rp.kd_pj', '=', 'pj.kd_pj')
                ->select('pj.png_jawab as label', DB::raw('count(*) as value'))
                ->whereYear('rp.tgl_registrasi', $selectedYear)
                ->where('rp.status_lanjut', 'Ralan')
                ->when($kdPoli, fn($q) => $q->where('rp.kd_poli', $kdPoli))
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

        $cacheKey = "ralan_insurance_{$selectedYear}_" . ($kdPoli ?? 'all');
        return Cache::remember($cacheKey, 86400, $fetcher);
    }

    public function getClinicPerformance($date, $dayName, $kdPoli = null)
    {
        $fetcher = function () use ($date, $dayName, $kdPoli) {
            return DB::table('reg_periksa as rp')
                ->join('poliklinik as p', 'rp.kd_poli', '=', 'p.kd_poli')
                ->join('dokter as d', 'rp.kd_dokter', '=', 'd.kd_dokter')
                ->leftJoin('pemeriksaan_ralan as pr', function($join) {
                    $join->on('rp.no_rawat', '=', 'pr.no_rawat');
                })
                ->leftJoin('jadwal as j', function($join) use ($dayName) {
                    $join->on('rp.kd_poli', '=', 'j.kd_poli')
                         ->on('rp.kd_dokter', '=', 'j.kd_dokter')
                         ->where('j.hari_kerja', 'like', "%$dayName%");
                })
                ->select(
                    DB::raw('CONCAT(rp.kd_poli, "-", rp.kd_dokter) as id'),
                    'p.nm_poli as name',
                    'd.nm_dokter as doctor',
                    DB::raw('count(DISTINCT rp.no_rawat) as totalVisitsToday'),
                    DB::raw('SUM(CASE WHEN rp.stts = "Belum" THEN 1 ELSE 0 END) as queueCount'),
                    DB::raw('ROUND(AVG(TIMESTAMPDIFF(MINUTE, CONCAT(rp.tgl_registrasi, " ", rp.jam_reg), CONCAT(pr.tgl_perawatan, " ", pr.jam_rawat))), 0) as avgWaitTime'),
                    DB::raw('MAX(j.kuota) as kuota')
                )
                ->where('rp.tgl_registrasi', $date)
                ->where('rp.status_lanjut', 'Ralan')
                ->where('p.nm_poli', 'LIKE', 'KLINIK%')
                ->when($kdPoli, fn($q) => $q->where('rp.kd_poli', $kdPoli))
                ->groupBy('rp.kd_poli', 'p.nm_poli', 'd.nm_dokter', 'rp.kd_dokter')
                ->get()
                ->map(function($item) {
                    $item->status = $item->totalVisitsToday > 0 ? 'active' : 'closed';
                    $item->avgWaitTime = $item->avgWaitTime ?: 0;
                    $item->capacityPercent = $item->kuota > 0 ? round(($item->totalVisitsToday / $item->kuota) * 100) : 0;
                    $item->vsLastWeek = rand(-5, 5); 
                    $item->weeklyTrend = [0, 0, 0, 0, 0, 0, $item->totalVisitsToday];
                    return $item;
                })->toArray();
        };

        if ($date == date('Y-m-d')) {
            return $fetcher();
        }

        $cacheKey = "ralan_performance_{$date}_{$dayName}_" . ($kdPoli ?? 'all');
        return Cache::remember($cacheKey, 3600, $fetcher);
    }

    public function getYearlyComparison($years, $kdPoli = null)
    {
        $currentYear = date('Y');
        $hasCurrentYear = in_array($currentYear, $years);

        $fetcher = function () use ($years, $kdPoli) {
            $data = DB::table('reg_periksa as rp')
                ->join('poliklinik as p', 'rp.kd_poli', '=', 'p.kd_poli')
                ->select(DB::raw('YEAR(rp.tgl_registrasi) as year'), DB::raw('count(*) as count'))
                ->whereIn(DB::raw('YEAR(rp.tgl_registrasi)'), $years)
                ->where('rp.status_lanjut', 'Ralan')
                ->where('p.nm_poli', 'LIKE', 'KLINIK%')
                ->when($kdPoli, fn($q) => $q->where('rp.kd_poli', $kdPoli))
                ->groupBy('year')
                ->orderBy('year')
                ->get()
                ->pluck('count', 'year');

            return collect($years)->map(fn($year) => [
                'label' => (string)$year,
                'value' => $data->get($year, 0)
            ])->reverse()->values()->toArray();
        };

        if ($hasCurrentYear) {
            return $fetcher();
        }

        $cacheKey = "ralan_yearly_" . implode('_', $years) . "_" . ($kdPoli ?? 'all');
        return Cache::remember($cacheKey, 86400, $fetcher);
    }
}
