<script lang="ts">
    import { router, Link } from '@inertiajs/svelte';
    import AppHead from '../components/AppHead.svelte';
    import DashboardHeader from '../components/DashboardHeader.svelte';
    import Sparkline from '../components/Sparkline.svelte';
    import Skeleton from '../components/Skeleton.svelte';
    import TrendChart from '../components/TrendChart.svelte';
    import DonutChart from '../components/DonutChart.svelte';
    import BarChart from '../components/BarChart.svelte';
    import { ClinicManager } from '../models/ClinicManager.svelte';

    interface Props {
        database: any;
    }

    let { database }: Props = $props();
    
    const manager = new ClinicManager();
    
    $effect(() => {
        const start = router.on('start', () => { manager.isLoading = true; });
        const finish = router.on('finish', () => { manager.isLoading = false; });
        return () => {
            start();
            finish();
        };
    });

    $effect(() => {
        if (database) {
            manager.loadFromDatabase({
                ...database,
                clinicPerformance: database.doctorPerformance
            });
        }
    });

    function handleYearChange(e: Event) {
        const year = (e.target as HTMLSelectElement).value;
        const url = new URL(window.location.href);
        url.searchParams.set('tahun', year);
        router.get(url.pathname + url.search, {}, { 
            preserveState: true, 
            preserveScroll: true,
            only: ['database']
        });
    }

    function waitColor(min: number) {
        return min >= 60 ? '#ef4444' : min >= 30 ? '#f59e0b' : '#22c55e';
    }
    
    function deltaClass(v: number) {
        return v >= 0 ? 'text-emerald-600 bg-emerald-50' : 'text-rose-600 bg-rose-50';
    }
    function deltaIcon(v: number) { return v >= 0 ? '▲' : '▼'; }

    // ... (retaining chart geometry and point logic)
    const peakIdx = $derived(
        manager.trendPoints.reduce((mi, p, i, arr) => (p.value || 0) > (arr[mi]?.value || 0) ? i : mi, 0)
    );

    const trendSummary = $derived.by(() => {
        const pts = manager.trendPoints;
        const total = pts.reduce((acc, p) => acc + (p.value || 0), 0);
        const prevTotal = pts.reduce((acc, p) => acc + (p.prevValue || 0), 0);
        const avg = pts.length > 0 ? Math.round(total / pts.length) : 0;
        const peak = pts[peakIdx]?.value || 0;
        const growth = prevTotal > 0 ? ((total - prevTotal) / prevTotal) * 100 : 0;
        return { total, avg, peak, growth };
    });
</script>

<AppHead title="Command Center - Laboratorium" />

<div class="min-h-screen bg-slate-100 flex flex-col font-sans">
    
    <DashboardHeader 
        title="Laboratorium" 
        subtitle="Laboratorium"
        iconPath="M4.5 12h3.75M19.5 12h-3.75m-6.75-6.75v3.75m0 9v-3.75m0-6l3-3m-3 3l-3-3m3 9l3 3m-3-3l-3 3"
    />

    <main class="flex-1 max-w-[1600px] w-full mx-auto px-4 sm:px-8 py-4 sm:py-8 flex flex-col gap-6 sm:gap-8">

        <!-- 1. KPI Overview -->
        <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            {#each [
                { label: 'Permintaan Lab Hari Ini', value: manager.totalVisitsToday,       delta: manager.visitsDelta, sub: 'Hari ini' },
                { label: 'Belum Keluar Hasil',value: manager.totalQueueCount,        delta: manager.queueDelta, sub: 'Masih antre' },
                { label: 'Petugas / Dokter Jaga',          value: database.activeDoctorsCount,   delta: null, sub: 'Aktif hari ini' },
            ] as kpi, ki (ki)}
                <div class="bg-white rounded-xl p-5 shadow-sm border border-slate-100 flex flex-col gap-3 animate-fade-in" style="animation-delay: {ki * 100}ms">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{kpi.label}</p>
                    {#if manager.isLoading}
                        <Skeleton class="h-10 w-2/3 mt-1" />
                    {:else}
                        <p class="text-4xl font-black text-slate-900 tabular-nums leading-none">{kpi.value}</p>
                    {/if}
                    <div class="flex items-center justify-between mt-auto pt-3 border-t border-slate-50">
                        <p class="text-[10px] text-slate-400 font-medium">{kpi.sub}</p>
                        {#if kpi.delta !== null}
                            <span class="text-[10px] font-black px-2 py-0.5 rounded {deltaClass(kpi.delta)}">
                                {deltaIcon(kpi.delta)} {Math.abs(kpi.delta)}%
                            </span>
                        {:else}
                            <span class="text-[10px] font-black px-2 py-0.5 rounded bg-emerald-50 text-emerald-600">Normal</span>
                        {/if}
                    </div>
                </div>
            {/each}

            <!-- Wait time -->
            <div class="bg-white rounded-xl p-5 shadow-sm border border-slate-100 flex flex-col gap-3">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Rata-rata Waktu Tunggu</p>
                {#if manager.isLoading}
                    <Skeleton class="h-10 w-1/2 mt-1" />
                {:else}
                    <p class="text-4xl font-black tabular-nums leading-none" style="color: {waitColor(manager.averageWaitTimeOverall)}">
                        {manager.averageWaitTimeOverall}<span class="text-lg font-bold ml-1">mnt</span>
                    </p>
                {/if}
                <div class="flex items-center justify-between mt-auto pt-3 border-t border-slate-50">
                    <p class="text-[10px] text-slate-400 font-medium">Dari kedatangan ke IGD</p>
                    <span class="text-[10px] font-black px-2 py-0.5 rounded {deltaClass(manager.waitTimeDelta)}">{deltaIcon(manager.waitTimeDelta)} {Math.abs(manager.waitTimeDelta)}%</span>
                </div>
            </div>
        </section>

        <!-- 2. Charts -->
        <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">

            <!-- Trend Chart -->
            <div class="xl:col-span-7 bg-white rounded-xl shadow-sm border border-slate-100 p-6 animate-fade-in" style="animation-delay: 300ms">
                <div class="flex flex-col sm:flex-row justify-between items-start mb-5 gap-3">
                    <div>
                        <h2 class="text-base font-black text-slate-900">Tren Kunjungan Laboratorium</h2>
                        <p class="text-xs text-slate-400 font-medium mt-0.5 text-ellipsis">Ringkasan kunjungan bulanan tahun {manager.selectedYear}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <select 
                            value={manager.selectedYear} 
                            onchange={handleYearChange}
                            class="text-[11px] font-black uppercase tracking-wider bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-primary/20 transition-all cursor-pointer"
                        >
                            {#each manager.availableYears as year (year)}
                                <option value={year}>Tahun {year}</option>
                            {/each}
                        </select>
                        <div class="hidden sm:flex items-center gap-4 ml-2 text-[9px] font-black uppercase text-slate-400">
                            <span class="flex items-center gap-1.5">
                                <span class="w-3 h-3 rounded bg-primary inline-block"></span>{manager.selectedYear}
                            </span>
                            <span class="flex items-center gap-1.5">
                                <span class="w-3 h-3 rounded-full border border-slate-300 border-dashed inline-block"></span>{manager.selectedYear - 1}
                            </span>
                        </div>
                    </div>
                </div>

                <TrendChart 
                    data={manager.trendPoints} 
                    isLoading={manager.isLoading} 
                />

                <div class="mt-8 pt-6 border-t border-slate-50 grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="flex flex-col">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest leading-tight">Total Kunjungan</span>
                        <div class="flex items-baseline gap-1.5 mt-1">
                            <span class="text-2xl font-black text-slate-900 leading-none">{trendSummary.total}</span>
                            <span class="text-[10px] font-bold text-slate-400">Tahun {manager.selectedYear}</span>
                        </div>
                    </div>
                    <div class="flex flex-col border-l border-slate-100 pl-4">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest leading-tight">Rata-rata/Bulan</span>
                        <div class="flex items-baseline gap-1.5 mt-1">
                            <span class="text-2xl font-black text-slate-900 leading-none">{trendSummary.avg}</span>
                            <span class="text-[10px] font-bold text-slate-400">Pasien</span>
                        </div>
                    </div>
                    <div class="flex flex-col border-l border-slate-100 pl-4">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest leading-tight">Puncak Layan</span>
                        <div class="flex items-baseline gap-1.5 mt-1">
                            <span class="text-2xl font-black text-emerald-600 leading-none">{trendSummary.peak}</span>
                            <span class="text-[10px] font-bold text-emerald-600/70">{manager.trendPoints[peakIdx]?.label || '-'}</span>
                        </div>
                    </div>
                    <div class="flex flex-col border-l border-slate-100 pl-4">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest leading-tight">Vs Tahun Lalu</span>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="text-2xl font-black {trendSummary.growth >= 0 ? 'text-emerald-600' : 'text-rose-600'} leading-none">
                                {trendSummary.growth >= 0 ? '+' : ''}{Math.round(trendSummary.growth)}%
                            </span>
                            <span class="text-[10px] font-bold text-slate-400">Pertumbuhan</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right column: Donut + Bar stacked -->
            <div class="xl:col-span-5 flex flex-col gap-6">

                <!-- Donut: Asuransi -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-6 flex-1">
                    <h2 class="text-base font-black text-slate-900 mb-1">Populasi Asuransi di Laboratorium</h2>
                    <p class="text-xs text-slate-400 font-medium mb-6">Jaminan kesehatan pasien Lab tahun {manager.selectedYear}</p>
                    
                    <DonutChart 
                        segments={manager.insuranceSegments} 
                        totalValue={manager.totalVisitsYearly} 
                    />
                </div>

                <!-- Grouped bar -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-5">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4 gap-2">
                        <h2 class="text-sm font-black text-slate-900 uppercase tracking-tight">Perbandingan Tahunan</h2>
                        <div class="flex items-center gap-3">
                            <span class="text-[9px] font-black text-slate-400 bg-slate-50 px-2 py-1 rounded ml-1">HISTORI TAHUNAN</span>
                        </div>
                    </div>
                    <BarChart 
                        labels={manager.yearlyComparison.labels} 
                        values={manager.yearlyComparison.values} 
                        highlightLabel={manager.selectedYear.toString()} 
                    />
                </div>
            </div>
        </div>

        <!-- 3. Pemeriksaan Lab Table -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center">
                <div>
                    <h2 class="text-base font-black text-slate-900 tracking-tight">Pemeriksaan Laboratorium</h2>
                    <p class="text-xs text-slate-400 font-medium mt-0.5">
                        Agregat sampel lab hari ini dan tren 7 hari penyelesaian.
                    </p>
                </div>
                <span class="text-[10px] font-black px-3 py-1.5 bg-slate-50 border border-slate-100 rounded-lg text-slate-500 uppercase tracking-wider">
                    {manager.clinics.length} Tes Tersedia
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 text-[10px] font-black text-slate-400 uppercase tracking-widest">
                            <th class="px-6 py-3 text-left">Pemeriksaan</th>
                            <th class="px-4 py-3 text-center">Kategori</th>
                            <th class="px-4 py-3 text-right">Permintaan Hari Ini</th>
                            <th class="px-4 py-3 text-right">Belum Diperiksa</th>
                            <th class="px-4 py-3 text-right">Rata-rata Waktu (mnt)</th>
                            <th class="px-4 py-3 text-center">Tren Layanan</th>
                            <th class="px-6 py-3 text-right">vs Minggu Lalu</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        {#each manager.clinics as labExam (labExam.id)}
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="px-6 py-4 font-black text-slate-800">{labExam.doctor}</td>
                                <td class="px-4 py-4 text-center">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-black uppercase bg-primary/10 text-primary-deep">
                                        {labExam.name}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-right">
                                    <p class="font-black text-slate-900 tabular-nums">{labExam.totalVisitsToday}</p>
                                    <p class="text-[10px] text-slate-400">sampel</p>
                                </td>
                                <td class="px-4 py-4 text-right font-black text-slate-900 tabular-nums">{labExam.queueCount}</td>
                                <td class="px-4 py-4 text-right font-black tabular-nums" style="color: {waitColor(labExam.avgWaitTime)}">
                                    {labExam.avgWaitTime}
                                </td>
                                <td class="px-4 py-4">
                                    <div class="flex justify-center">
                                        <Sparkline
                                            data={labExam.weeklyTrend}
                                            color={labExam.vsLastWeek >= 0 ? '#0093dd' : '#ef4444'}
                                        />
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <span class="text-xs font-black px-2 py-0.5 rounded {deltaClass(labExam.vsLastWeek)}">
                                        {deltaIcon(labExam.vsLastWeek)} {Math.abs(labExam.vsLastWeek)}%
                                    </span>
                                </td>
                            </tr>
                        {/each}

                        {#if manager.clinics.length === 0}
                            <tr>
                                <td colspan="7" class="px-6 py-8 text-center text-slate-400 font-medium">Belum ada permintaan lab hari ini.</td>
                            </tr>
                        {/if}
                    </tbody>
                </table>
            </div>
        </div>

    </main>
</div>

<style>
    @keyframes draw {
        from { stroke-dashoffset: 5000; }
        to { stroke-dashoffset: 0; }
    }
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    @keyframes scaleIn {
        from { transform: scaleY(0); transform-origin: bottom; }
        to { transform: scaleY(1); transform-origin: bottom; }
    }
    .animate-fade-in {
        animation: fadeIn 0.6s ease-out forwards;
    }
</style>
