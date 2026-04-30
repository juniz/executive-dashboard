<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Interfaces\RekamMedisRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class RekamMedisRepository implements RekamMedisRepositoryInterface
{
    public function getTotalBeds()
    {
        return Cache::remember('rm_total_beds', 86400, function () {
            return DB::table('kamar')
                ->where('statusdata', '1')
                ->count();
        });
    }

    public function getMonthlyStats($selectedYear, $totalBeds)
    {
        $fetcher = function () use ($selectedYear, $totalBeds) {
            $monthlyStats = [];
            $now = Carbon::now();
            
            for ($m = 1; $m <= 12; $m++) {
                $startDate = Carbon::create($selectedYear, $m, 1)->startOfMonth();
                $endDate = Carbon::create($selectedYear, $m, 1)->endOfMonth();
                $daysInMonth = $startDate->daysInMonth;
                
                if ($startDate->isAfter($now) && $selectedYear == $now->year) {
                    $monthlyStats[] = [
                        'month' => $startDate->isoFormat('MMM'),
                        'bor' => 0, 'alos' => 0, 'bto' => 0, 'toi' => 0, 'ndr' => 0, 'gdr' => 0,
                        'hp' => 0, 'ld' => 0, 'keluar' => 0
                    ];
                    continue;
                }

                $hp = DB::table('kamar_inap')
                    ->where('tgl_masuk', '<=', $endDate->format('Y-m-d'))
                    ->where(function($q) use ($startDate) {
                        $q->where('tgl_keluar', '>=', $startDate->format('Y-m-d'))
                          ->orWhere('stts_pulang', '-');
                    })
                    ->selectRaw("SUM(GREATEST(0, DATEDIFF(
                        LEAST(IF(stts_pulang='-', CURRENT_DATE, tgl_keluar), ?),
                        GREATEST(tgl_masuk, ?)
                    ) + 1)) as total_hp", [$endDate->format('Y-m-d'), $startDate->format('Y-m-d')])
                    ->first()->total_hp ?? 0;

                $keluarTotal = DB::table('kamar_inap')
                    ->whereBetween('tgl_keluar', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                    ->where('stts_pulang', '!=', '-')
                    ->count();

                $matiTotal = DB::table('kamar_inap')
                    ->whereBetween('tgl_keluar', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                    ->where(function($q) {
                        $q->where('stts_pulang', 'like', 'Meninggal%')
                          ->orWhere('stts_pulang', 'Atas Permintaan');
                    })
                    ->count();

                $matiOver48 = DB::table('kamar_inap')
                    ->whereBetween('tgl_keluar', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                    ->where('stts_pulang', 'like', 'Meninggal%')
                    ->whereRaw('DATEDIFF(tgl_keluar, tgl_masuk) >= 2')
                    ->count();

                $ldTotal = DB::table('kamar_inap')
                    ->whereBetween('tgl_keluar', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                    ->where('stts_pulang', '!=', '-')
                    ->selectRaw('SUM(DATEDIFF(tgl_keluar, tgl_masuk) + 1) as total_ld')
                    ->first()->total_ld ?? 0;

                $bor = ($totalBeds * $daysInMonth) > 0 ? ($hp / ($totalBeds * $daysInMonth)) * 100 : 0;
                $alos = $keluarTotal > 0 ? $ldTotal / $keluarTotal : 0;
                $bto = $totalBeds > 0 ? $keluarTotal / $totalBeds : 0;
                $toi = $keluarTotal > 0 ? (($totalBeds * $daysInMonth) - $hp) / $keluarTotal : 0;
                $ndr = $keluarTotal > 0 ? ($matiOver48 / $keluarTotal) * 1000 : 0;
                $gdr = $keluarTotal > 0 ? ($matiTotal / $keluarTotal) * 1000 : 0;

                $monthlyStats[] = [
                    'month' => $startDate->isoFormat('MMM'),
                    'bor' => round($bor, 2),
                    'alos' => round($alos, 2),
                    'bto' => round($bto, 2),
                    'toi' => round($toi, 2),
                    'ndr' => round($ndr, 2),
                    'gdr' => round($gdr, 2),
                    'hp' => $hp,
                    'ld' => $ldTotal,
                    'keluar' => $keluarTotal
                ];
            }

            return $monthlyStats;
        };

        if ($selectedYear == date('Y')) {
            return $fetcher();
        }

        $cacheKey = "rm_monthly_stats_{$selectedYear}_{$totalBeds}";
        return Cache::remember($cacheKey, 86400, $fetcher);
    }

    public function getWardPerformance($startDate, $endDate)
    {
        $fetcher = function () use ($startDate, $endDate) {
            $wards = DB::table('bangsal')
                ->where('status', '1')
                ->select('kd_bangsal', 'nm_bangsal')
                ->get();

            $wardPerformance = [];
            $daysInMonth = Carbon::parse($startDate)->daysInMonth;

            foreach ($wards as $ward) {
                $wardBeds = DB::table('kamar')
                    ->where('kd_bangsal', $ward->kd_bangsal)
                    ->where('statusdata', '1')
                    ->count();

                if ($wardBeds == 0) continue;

                $wardHp = DB::table('kamar_inap as ki')
                    ->join('kamar as k', 'ki.kd_kamar', '=', 'k.kd_kamar')
                    ->where('k.kd_bangsal', $ward->kd_bangsal)
                    ->where('ki.tgl_masuk', '<=', $endDate)
                    ->where(function($q) use ($startDate) {
                        $q->where('ki.tgl_keluar', '>=', $startDate)
                          ->orWhere('ki.stts_pulang', '-');
                    })
                    ->selectRaw("SUM(DATEDIFF(
                        LEAST(IF(ki.stts_pulang='-', CURRENT_DATE, ki.tgl_keluar), ?),
                        GREATEST(ki.tgl_masuk, ?)
                    ) + 1) as total_hp", [$endDate, $startDate])
                    ->first()->total_hp ?? 0;

                $wardKeluar = DB::table('kamar_inap as ki')
                    ->join('kamar as k', 'ki.kd_kamar', '=', 'k.kd_kamar')
                    ->where('k.kd_bangsal', $ward->kd_bangsal)
                    ->whereBetween('ki.tgl_keluar', [$startDate, $endDate])
                    ->where('ki.stts_pulang', '!=', '-')
                    ->count();

                $wardLd = DB::table('kamar_inap as ki')
                    ->join('kamar as k', 'ki.kd_kamar', '=', 'k.kd_kamar')
                    ->where('k.kd_bangsal', $ward->kd_bangsal)
                    ->whereBetween('ki.tgl_keluar', [$startDate, $endDate])
                    ->where('ki.stts_pulang', '!=', '-')
                    ->selectRaw('SUM(DATEDIFF(ki.tgl_keluar, ki.tgl_masuk) + 1) as total_ld')
                    ->first()->total_ld ?? 0;

                $wardBor = ($wardBeds * $daysInMonth) > 0 ? ($wardHp / ($wardBeds * $daysInMonth)) * 100 : 0;
                $wardAlos = $wardKeluar > 0 ? $wardLd / $wardKeluar : 0;

                $wardPerformance[] = [
                    'name' => $ward->nm_bangsal,
                    'beds' => $wardBeds,
                    'hp' => $wardHp,
                    'keluar' => $wardKeluar,
                    'bor' => round($wardBor, 2),
                    'alos' => round($wardAlos, 2),
                    'bto' => $wardBeds > 0 ? round($wardKeluar / $wardBeds, 2) : 0,
                    'toi' => $wardKeluar > 0 ? round((($wardBeds * $daysInMonth) - $wardHp) / $wardKeluar, 2) : 0,
                ];
            }

            return $wardPerformance;
        };

        if (str_contains($startDate, date('Y'))) {
            return $fetcher();
        }

        $cacheKey = "rm_ward_performance_{$startDate}_{$endDate}";
        return Cache::remember($cacheKey, 86400, $fetcher);
    }
}
