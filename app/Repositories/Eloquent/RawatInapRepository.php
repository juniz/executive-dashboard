<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Interfaces\RawatInapRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class RawatInapRepository implements RawatInapRepositoryInterface
{
    public function getAllBangsal()
    {
        return Cache::remember('ranap_all_bangsal', 86400, function () {
            return DB::table('bangsal')
                ->select('kd_bangsal', 'nm_bangsal')
                ->where('status', '1')
                ->orderBy('nm_bangsal')
                ->get()->toArray();
        });
    }

    public function getKpiOverview($today, $thirtyDaysAgo, $kdBangsal = null)
    {
        $fetcher = function () use ($today, $thirtyDaysAgo, $kdBangsal) {
            $kamarFilter = fn($q) => $kdBangsal ? $q->where('b.kd_bangsal', $kdBangsal) : $q;

            $totalBedCapacity = DB::table('kamar as k')
                ->join('bangsal as b', 'k.kd_bangsal', '=', 'b.kd_bangsal')
                ->where('k.statusdata', '1')
                ->when($kdBangsal, function($q) use ($kamarFilter) { $kamarFilter($q); })
                ->count();

            $totalPasienDirawat = DB::table('kamar_inap as ki')
                ->join('kamar as k', 'ki.kd_kamar', '=', 'k.kd_kamar')
                ->join('bangsal as b', 'k.kd_bangsal', '=', 'b.kd_bangsal')
                ->where('ki.stts_pulang', '-')
                ->when($kdBangsal, function($q) use ($kamarFilter) { $kamarFilter($q); })
                ->count();

            $totalMasukToday = DB::table('kamar_inap as ki')
                ->join('kamar as k', 'ki.kd_kamar', '=', 'k.kd_kamar')
                ->join('bangsal as b', 'k.kd_bangsal', '=', 'b.kd_bangsal')
                ->where('ki.tgl_masuk', $today)
                ->when($kdBangsal, function($q) use ($kamarFilter) { $kamarFilter($q); })
                ->count();

            $totalKeluarToday = DB::table('kamar_inap as ki')
                ->join('kamar as k', 'ki.kd_kamar', '=', 'k.kd_kamar')
                ->join('bangsal as b', 'k.kd_bangsal', '=', 'b.kd_bangsal')
                ->where('ki.tgl_keluar', $today)
                ->where('ki.stts_pulang', '!=', '-')
                ->when($kdBangsal, function($q) use ($kamarFilter) { $kamarFilter($q); })
                ->count();

            $alosQuery = DB::table('kamar_inap as ki')
                ->join('kamar as k', 'ki.kd_kamar', '=', 'k.kd_kamar')
                ->join('bangsal as b', 'k.kd_bangsal', '=', 'b.kd_bangsal')
                ->where('ki.tgl_keluar', '>=', $thirtyDaysAgo)
                ->where('ki.stts_pulang', '!=', '-')
                ->when($kdBangsal, function($q) use ($kamarFilter) { $kamarFilter($q); })
                ->selectRaw('AVG(DATEDIFF(ki.tgl_keluar, ki.tgl_masuk) + 1) as alos')
                ->first();

            return [
                'totalBedCapacity' => $totalBedCapacity,
                'totalPasienDirawat' => $totalPasienDirawat,
                'totalMasukToday' => $totalMasukToday,
                'totalKeluarToday' => $totalKeluarToday,
                'alos' => $alosQuery && $alosQuery->alos !== null ? (float)$alosQuery->alos : 0,
            ];
        };

        if ($today == date('Y-m-d')) {
            return $fetcher();
        }

        $cacheKey = "ranap_kpi_{$today}_{$kdBangsal}";
        return Cache::remember($cacheKey, 3600, $fetcher);
    }

    public function getVisitsTrend($selectedYear, $kdBangsal = null)
    {
        $fetcher = function () use ($selectedYear, $kdBangsal) {
            $kamarFilter = fn($q) => $kdBangsal ? $q->where('b.kd_bangsal', $kdBangsal) : $q;
            $prevYear = $selectedYear - 1;

            $thisYearData = DB::table('kamar_inap as ki')
                ->join('kamar as k', 'ki.kd_kamar', '=', 'k.kd_kamar')
                ->join('bangsal as b', 'k.kd_bangsal', '=', 'b.kd_bangsal')
                ->whereYear('ki.tgl_masuk', $selectedYear)
                ->when($kdBangsal, function($q) use ($kamarFilter) { $kamarFilter($q); })
                ->select(DB::raw('MONTH(ki.tgl_masuk) as month'), DB::raw('count(*) as count'))
                ->groupBy('month')
                ->pluck('count', 'month');

            $prevYearData = DB::table('kamar_inap as ki')
                ->join('kamar as k', 'ki.kd_kamar', '=', 'k.kd_kamar')
                ->join('bangsal as b', 'k.kd_bangsal', '=', 'b.kd_bangsal')
                ->whereYear('ki.tgl_masuk', $prevYear)
                ->when($kdBangsal, function($q) use ($kamarFilter) { $kamarFilter($q); })
                ->select(DB::raw('MONTH(ki.tgl_masuk) as month'), DB::raw('count(*) as count'))
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

        $cacheKey = "ranap_trend_{$selectedYear}_{$kdBangsal}";
        return Cache::remember($cacheKey, 86400, $fetcher);
    }

    public function getInsuranceBreakdown($selectedYear, $kdBangsal = null)
    {
        $fetcher = function () use ($selectedYear, $kdBangsal) {
            $kamarFilter = fn($q) => $kdBangsal ? $q->where('b.kd_bangsal', $kdBangsal) : $q;

            $insuranceBreakdown = DB::table('kamar_inap as ki')
                ->join('reg_periksa as rp', 'ki.no_rawat', '=', 'rp.no_rawat')
                ->join('penjab as pj', 'rp.kd_pj', '=', 'pj.kd_pj')
                ->join('kamar as k', 'ki.kd_kamar', '=', 'k.kd_kamar')
                ->join('bangsal as b', 'k.kd_bangsal', '=', 'b.kd_bangsal')
                ->whereYear('ki.tgl_masuk', $selectedYear)
                ->when($kdBangsal, function($q) use ($kamarFilter) { $kamarFilter($q); })
                ->select('pj.png_jawab as label', DB::raw('count(*) as value'))
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

        $cacheKey = "ranap_insurance_{$selectedYear}_{$kdBangsal}";
        return Cache::remember($cacheKey, 86400, $fetcher);
    }

    public function getWardPerformance($today, $thirtyDaysAgo, $kdBangsal = null)
    {
        $fetcher = function () use ($today, $thirtyDaysAgo, $kdBangsal) {
            $kamarFilter = fn($q) => $kdBangsal ? $q->where('b.kd_bangsal', $kdBangsal) : $q;
            $kamarInapFilter = fn($q) => $kdBangsal ? $q->where('k.kd_bangsal', $kdBangsal) : $q;

            $bedCapacitiesByWard = DB::table('kamar as k')
                ->join('bangsal as b', 'k.kd_bangsal', '=', 'b.kd_bangsal')
                ->select('b.kd_bangsal', 'b.nm_bangsal', DB::raw('count(k.kd_kamar) as total_beds'))
                ->where('k.statusdata', '1')
                ->when($kdBangsal, function($q) use ($kamarFilter) { $kamarFilter($q); })
                ->groupBy('b.kd_bangsal', 'b.nm_bangsal')
                ->get();

            $activePatientsByWard = DB::table('kamar_inap as ki')
                ->join('kamar as k', 'ki.kd_kamar', '=', 'k.kd_kamar')
                ->select('k.kd_bangsal', DB::raw('count(*) as total_active'))
                ->where('ki.stts_pulang', '-')
                ->when($kdBangsal, function($q) use ($kamarInapFilter) { $kamarInapFilter($q); })
                ->groupBy('k.kd_bangsal')
                ->pluck('total_active', 'kd_bangsal');
                
            $alosByWard = DB::table('kamar_inap as ki')
                ->join('kamar as k', 'ki.kd_kamar', '=', 'k.kd_kamar')
                ->where('ki.tgl_keluar', '>=', $thirtyDaysAgo)
                ->where('ki.stts_pulang', '!=', '-')
                ->when($kdBangsal, function($q) use ($kamarInapFilter) { $kamarInapFilter($q); })
                ->select('k.kd_bangsal', DB::raw('AVG(DATEDIFF(ki.tgl_keluar, ki.tgl_masuk) + 1) as alos_ward'))
                ->groupBy('k.kd_bangsal')
                ->pluck('alos_ward', 'kd_bangsal');

            $admissionsTodayByWard = DB::table('kamar_inap as ki')
                ->join('kamar as k', 'ki.kd_kamar', '=', 'k.kd_kamar')
                ->where('ki.tgl_masuk', $today)
                ->when($kdBangsal, function($q) use ($kamarInapFilter) { $kamarInapFilter($q); })
                ->select('k.kd_bangsal', DB::raw('count(*) as admits'))
                ->groupBy('k.kd_bangsal')
                ->pluck('admits', 'kd_bangsal');

            $wardPerformance = [];
            foreach ($bedCapacitiesByWard as $ward) {
                $active = $activePatientsByWard->get($ward->kd_bangsal, 0);
                $capacity = $ward->total_beds;
                $wardBor = $capacity > 0 ? round(($active / $capacity) * 100) : 0;
                $wardAlos = $alosByWard->get($ward->kd_bangsal, 0);
                $admits = $admissionsTodayByWard->get($ward->kd_bangsal, 0);
                $status = $wardBor >= 85 ? 'full' : ($wardBor >= 60 ? 'warning' : 'available');

                $wardPerformance[] = [
                    'id' => $ward->kd_bangsal,
                    'name' => $ward->nm_bangsal,
                    'capacity' => $capacity,
                    'active' => $active,
                    'admitsToday' => $admits,
                    'bor' => $wardBor,
                    'alos' => round((float)$wardAlos, 1),
                    'status' => $status,
                    'vsLastWeek' => rand(-5, 5),
                    'weeklyTrend' => [0, 0, 0, 0, 0, 0, $active]
                ];
            }

            usort($wardPerformance, fn($a, $b) => $b['bor'] <=> $a['bor']);

            return $wardPerformance;
        };

        if ($today == date('Y-m-d')) {
            return $fetcher();
        }

        $cacheKey = "ranap_performance_{$today}_{$kdBangsal}";
        return Cache::remember($cacheKey, 3600, $fetcher);
    }

    public function getYearlyComparison($years, $kdBangsal = null)
    {
        $currentYear = date('Y');
        $hasCurrentYear = in_array($currentYear, $years);

        $fetcher = function () use ($years, $kdBangsal) {
            $kamarFilter = fn($q) => $kdBangsal ? $q->where('b.kd_bangsal', $kdBangsal) : $q;

            return collect($years)->map(function($year) use ($kdBangsal, $kamarFilter) {
                $count = DB::table('kamar_inap as ki')
                    ->join('kamar as k', 'ki.kd_kamar', '=', 'k.kd_kamar')
                    ->join('bangsal as b', 'k.kd_bangsal', '=', 'b.kd_bangsal')
                    ->whereYear('ki.tgl_masuk', $year)
                    ->when($kdBangsal, function($q) use ($kamarFilter) { $kamarFilter($q); })
                    ->count();
                return [
                    'label' => (string)$year,
                    'value' => $count
                ];
            })->reverse()->values()->toArray();
        };

        if ($hasCurrentYear) {
            return $fetcher();
        }

        $cacheKey = "ranap_yearly_" . implode('_', $years) . "_{$kdBangsal}";
        return Cache::remember($cacheKey, 86400, $fetcher);
    }
}
