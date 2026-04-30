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
            manager.loadFromDatabase(database);
            if (database.filters?.kd_poli !== undefined) {
                manager.selectedClinic = database.filters.kd_poli || '';
            }
        }
    });

    function handleClinicChange(event: Event) {
        const val = (event.target as HTMLSelectElement).value.trim();
        const url = new URL(window.location.href);
        url.searchParams.set('kd_poli', val);
        router.get(url.pathname + url.search, {}, { 
            preserveState: true, 
            preserveScroll: true,
            only: ['database']
        });
    }

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

    function capacityColor(pct: number) {
        return pct >= 85 ? '#ef4444' : pct >= 60 ? '#f59e0b' : '#22c55e';
    }
    function waitColor(min: number) {
        return min >= 60 ? '#ef4444' : min >= 30 ? '#f59e0b' : '#22c55e';
    }
    function deltaClass(v: number) {
        return v >= 0 ? 'text-emerald-600 bg-emerald-50' : 'text-rose-600 bg-rose-50';
    }
    function deltaIcon(v: number) { return v >= 0 ? '▲' : '▼'; }

    const statusLabels: Record<string, string> = {
        active: 'Aktif', break: 'Istirahat', closed: 'Tutup',
    };
    const statusDot: Record<string, string> = {
        active: 'bg-emerald-500', break: 'bg-amber-500', closed: 'bg-slate-400',
    };

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

<AppHead title="Tren Poliklinik - Rawat Jalan" />

<div class="min-h-screen bg-slate-100 flex flex-col font-sans">

    <DashboardHeader 
        title="RAWAT JALAN" 
        subtitle="Monitor Rawat Jalan"
        iconPath="M12 2v20M2 12h20"
    >
        {#snippet filters()}
            <!-- Clinic Filter Dropdown -->
            <div class="relative group">
                <select 
                    value={manager.selectedClinic}
                    onchange={handleClinicChange}
                    class="bg-white/10 border border-white/20 text-white text-[11px] font-black uppercase tracking-widest rounded-lg px-4 py-2 appearance-none cursor-pointer hover:bg-white/20 transition-all outline-none"
                >
                    <option value="" class="bg-primary-deep text-white">Semua Poliklinik</option>
                    {#each database.allClinics || [] as clinic (clinic.kd_poli)}
                        <option value={clinic.kd_poli} class="bg-primary-deep text-white">{clinic.nm_poli}</option>
                    {/each}
                </select>
                <div class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none opacity-50 text-white">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7" />
                    </svg>
                </div>
            </div>
        {/snippet}
    </DashboardHeader>


    <main class="flex-1 max-w-[1600px] w-full mx-auto px-4 sm:px-8 py-4 sm:py-8 flex flex-col gap-6 sm:gap-8">

        <!-- 1. KPI Overview -->
        <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">

            {#each [
                { label: 'Total Kunjungan',     value: manager.totalVisitsToday,        unit: '',    delta:  manager.visitsDelta, sub: 'Hari ini' },
                { label: 'Antrean Aktif',        value: manager.totalQueueCount,         unit: '',    delta:   manager.queueDelta, sub: 'Semua poli' },
                { label: 'Klinik Beroperasi',    value: `${manager.activeClinicsCount}/${manager.totalClinicsCount}`, unit: '', delta: null, sub: 'Unit aktif' },
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
            <div class="bg-white rounded-xl p-5 shadow-sm border border-slate-100 flex flex-col gap-3 animate-fade-in" style="animation-delay: 300ms">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Rata-rata Tunggu</p>
                {#if manager.isLoading}
                    <Skeleton class="h-10 w-1/2 mt-1" />
                {:else}
                    <p class="text-4xl font-black tabular-nums leading-none" style="color: {waitColor(manager.averageWaitTimeOverall)}">
                        {manager.averageWaitTimeOverall}<span class="text-lg font-bold ml-1">mnt</span>
                    </p>
                {/if}
                <div class="flex items-center justify-between mt-auto pt-3 border-t border-slate-50">
                    <p class="text-[10px] text-slate-400 font-medium">Per pasien</p>
                    <span class="text-[10px] font-black px-2 py-0.5 rounded {deltaClass(manager.waitTimeDelta)}">{deltaIcon(manager.waitTimeDelta)} {Math.abs(manager.waitTimeDelta)}%</span>
                </div>
            </div>

            <!-- Capacity utilization -->
            <div class="bg-white rounded-xl p-5 shadow-sm border border-slate-100 flex flex-col gap-3 animate-fade-in" style="animation-delay: 400ms">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Utilisasi Kapasitas</p>
                <p class="text-4xl font-black tabular-nums leading-none" style="color: {capacityColor(manager.overallEfficiency)}">
                    {manager.overallEfficiency}<span class="text-lg font-bold ml-0.5">%</span>
                </p>
                <div class="mt-auto pt-3 border-t border-slate-50">
                    <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                        <div class="h-1.5 rounded-full transition-all"
                             style="width: {Math.min(manager.overallEfficiency, 100)}%; background-color: {capacityColor(manager.overallEfficiency)}">
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- 2. Charts -->
        <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">

            <!-- Trend Chart -->
            <div class="xl:col-span-7 bg-white rounded-xl shadow-sm border border-slate-100 p-6 animate-fade-in" style="animation-delay: 500ms">
                <div class="flex flex-col sm:flex-row justify-between items-start mb-5 gap-3">
                    <div>
                        <h2 class="text-base font-black text-slate-900">Tren Kunjungan</h2>
                        <p class="text-xs text-slate-400 font-medium mt-0.5 text-ellipsis">Ringkasan kunjungan bulanan tahun {manager.selectedYear} {manager.selectedClinic ? '- ' + database.allClinics.find(c => c.kd_poli === manager.selectedClinic)?.nm_poli : ''}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <select 
                            value={manager.selectedYear} 
                            onchange={handleYearChange}
                            class="text-[11px] font-black uppercase tracking-wider bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-primary/20 transition-all cursor-pointer"
                        >
                            {#each manager.availableYears as year (year)}
                                <option value={year}>Tahun {year}</option>
                            {#each [] as _}{/each}
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
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest leading-tight">Titik Tertinggi</span>
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

                <!-- Donut: Insurance -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-6 flex-1">
                    <h2 class="text-base font-black text-slate-900 mb-1">Populasi Asuransi</h2>
                    <p class="text-xs text-slate-400 font-medium mb-6">Jaminan kesehatan pasien tahun {manager.selectedYear}</p>
                    
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

        <!-- 3. Clinic Comparison Table -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center">
                <div>
                    <h2 class="text-base font-black text-slate-900 tracking-tight">Performa Per Poliklinik</h2>
                    <p class="text-xs text-slate-400 font-medium mt-0.5">
                        Tren layanan hari ini ·
                        <span class="text-emerald-600 font-bold">hijau</span> baik ·
                        <span class="text-amber-500 font-bold">kuning</span> perhatian ·
                        <span class="text-rose-500 font-bold">merah</span> kritis
                    </p>
                </div>
                <span class="text-[10px] font-black px-3 py-1.5 bg-slate-50 border border-slate-100 rounded-lg text-slate-500 uppercase tracking-wider">
                    {manager.clinics.length} Poliklinik
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 text-[10px] font-black text-slate-400 uppercase tracking-widest">
                            <th class="px-6 py-3 text-left">Poliklinik</th>
                            <th class="px-4 py-3 text-left">Dokter</th>
                            <th class="px-4 py-3 text-center">Status</th>
                            <th class="px-4 py-3 text-right">Kunjungan</th>
                            <th class="px-4 py-3 text-right">Antrean</th>
                            <th class="px-4 py-3 text-right">Tunggu</th>
                            <th class="px-4 py-3 text-center">Tren Layanan</th>
                            <th class="px-4 py-3 text-right">vs Minggu Lalu</th>
                            <th class="px-6 py-3 text-left">Kapasitas</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        {#each manager.clinics as clinic (clinic.id)}
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="px-6 py-4 font-black text-slate-800">{clinic.name}</td>
                                <td class="px-4 py-4 text-xs text-slate-500 font-medium">{clinic.doctor}</td>
                                <td class="px-4 py-4 text-center">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-black uppercase
                                        {clinic.status === 'active' ? 'bg-emerald-50 text-emerald-700' : clinic.status === 'break' ? 'bg-amber-50 text-amber-700' : 'bg-slate-100 text-slate-500'}">
                                        <span class="w-1.5 h-1.5 rounded-full {statusDot[clinic.status]}"></span>
                                        {statusLabels[clinic.status]}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-right">
                                    <p class="font-black text-slate-900 tabular-nums">{clinic.totalVisitsToday}</p>
                                    <p class="text-[10px] text-slate-400">pasien</p>
                                </td>
                                <td class="px-4 py-4 text-right font-black text-slate-900 tabular-nums">{clinic.queueCount}</td>
                                <td class="px-4 py-4 text-right font-black tabular-nums" style="color: {waitColor(clinic.avgWaitTime)}">
                                    {clinic.avgWaitTime} mnt
                                </td>
                                <td class="px-4 py-4">
                                    <div class="flex justify-center">
                                        <Sparkline
                                            data={clinic.weeklyTrend}
                                            color={clinic.vsLastWeek >= 0 ? '#0093dd' : '#ef4444'}
                                        />
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-right">
                                    <span class="text-xs font-black px-2 py-0.5 rounded {deltaClass(clinic.vsLastWeek)}">
                                        {deltaIcon(clinic.vsLastWeek)} {Math.abs(clinic.vsLastWeek)}%
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2 min-w-[100px]">
                                        <div class="flex-1 bg-slate-100 rounded-full h-2 overflow-hidden">
                                            <div class="h-2 rounded-full"
                                                 style="width: {Math.min(clinic.capacityPercent, 100)}%; background-color: {capacityColor(clinic.capacityPercent)}">
                                            </div>
                                        </div>
                                        <span class="text-xs font-black tabular-nums w-8 text-right" style="color: {capacityColor(clinic.capacityPercent)}">{clinic.capacityPercent}%</span>
                                    </div>
                                </td>
                            </tr>
                        {/each}
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
