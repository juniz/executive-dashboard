<script lang="ts">
    import { router, Link } from '@inertiajs/svelte';
    import AppHead from '../components/AppHead.svelte';
    import DashboardHeader from '../components/DashboardHeader.svelte';
    import Sparkline from '../components/Sparkline.svelte';
    import Skeleton from '../components/Skeleton.svelte';
    import TrendChart from '../components/TrendChart.svelte';
    import DonutChart from '../components/DonutChart.svelte';
    import BarChart from '../components/BarChart.svelte';
    import { WardManager } from '../models/WardManager.svelte';

    interface Props {
        database: any;
    }

    let { database }: Props = $props();
    const manager = new WardManager();
    
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
            if (database.filters?.kd_bangsal !== undefined) {
                manager.selectedWard = database.filters.kd_bangsal || '';
            }
        }
    });

    function handleWardChange(event: Event) {
        const val = (event.target as HTMLSelectElement).value.trim();
        const url = new URL(window.location.href);
        url.searchParams.set('kd_bangsal', val);
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
    
    function alosColor(days: number) {
        return days >= 14 ? '#ef4444' : days >= 7 ? '#f59e0b' : '#22c55e';
    }

    function deltaClass(v: number) {
        return v >= 0 ? 'text-emerald-600 bg-emerald-50' : 'text-rose-600 bg-rose-50';
    }
    function deltaIcon(v: number) { return v >= 0 ? '▲' : '▼'; }

    const statusLabels: Record<string, string> = {
        available: 'Tersedia', warning: 'Waspada', full: 'Penuh',
    };
    const statusDot: Record<string, string> = {
        available: 'bg-emerald-500', warning: 'bg-amber-500', full: 'bg-rose-500',
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

<AppHead title="Tren Admisi - Rawat Inap" />

<div class="min-h-screen bg-slate-100 flex flex-col font-sans">

    <DashboardHeader 
        title="RAWAT INAP" 
        subtitle="Monitor Rawat Inap"
        iconPath="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
    >
        {#snippet filters()}
            <!-- Ward Filter Dropdown -->
            <div class="relative group">
                <select 
                    value={manager.selectedWard}
                    onchange={handleWardChange}
                    class="bg-white/10 border border-white/20 text-white text-[11px] font-black uppercase tracking-widest rounded-lg px-4 py-2 appearance-none cursor-pointer hover:bg-white/20 transition-all outline-none"
                >
                    <option value="" class="bg-primary-deep text-white">Semua Bangsal</option>
                    {#each database.allBangsal || [] as ward (ward.kd_bangsal)}
                        <option value={ward.kd_bangsal} class="bg-primary-deep text-white">{ward.nm_bangsal}</option>
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
                { label: 'Pasien Dirawat', value: manager.totalPasienDirawat, unit: '',   delta: null, sub: `Kapasitas total ${manager.totalBedCapacity}` },
                { label: 'Admisi Hari Ini',value: manager.totalMasukToday,    unit: '',   delta: null, sub: 'Pasien Masuk' },
                { label: 'Keluar Hari Ini',value: manager.totalKeluarToday,   unit: '',   delta: null, sub: 'Pasien Pulang' },
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
                            <span class="text-[10px] font-black px-2 py-0.5 rounded bg-slate-50 text-slate-500">-</span>
                        {/if}
                    </div>
                </div>
            {/each}

            <!-- ALOS -->
            <div class="bg-white rounded-xl p-5 shadow-sm border border-slate-100 flex flex-col gap-3 animate-fade-in" style="animation-delay: 300ms">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Rata-rata Rawat (ALOS)</p>
                {#if manager.isLoading}
                    <Skeleton class="h-10 w-1/2 mt-1" />
                {:else}
                    <p class="text-4xl font-black tabular-nums leading-none" style="color: {alosColor(manager.alos)}">
                        {manager.alos}<span class="text-lg font-bold ml-1 text-slate-500">hari</span>
                    </p>
                {/if}
                <div class="flex items-center justify-between mt-auto pt-3 border-t border-slate-50">
                    <p class="text-[10px] text-slate-400 font-medium">Histori 30 Hari</p>
                    <span class="text-[10px] font-black px-2 py-0.5 rounded {deltaClass(-manager.alosDelta)}">{deltaIcon(-manager.alosDelta)} {Math.abs(manager.alosDelta)}%</span>
                </div>
            </div>

            <!-- Capacity (BOR) -->
            <div class="bg-white rounded-xl p-5 shadow-sm border border-slate-100 flex flex-col gap-3 animate-fade-in" style="animation-delay: 400ms">
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Occupancy (BOR)</p>
                {#if manager.isLoading}
                    <Skeleton class="h-10 w-1/2 mt-1" />
                {:else}
                    <p class="text-4xl font-black tabular-nums leading-none" style="color: {capacityColor(manager.bor)}">
                        {manager.bor}<span class="text-lg font-bold ml-0.5">%</span>
                    </p>
                {/if}
                <div class="mt-auto pt-3 border-t border-slate-50 flex items-center justify-between">
                    <div class="w-full mr-3 bg-slate-100 rounded-full h-1.5 overflow-hidden">
                        <div class="h-1.5 rounded-full transition-all"
                             style="width: {Math.min(manager.bor, 100)}%; background-color: {capacityColor(manager.bor)}">
                        </div>
                    </div>
                    <span class="text-[10px] font-black px-2 py-0.5 rounded {deltaClass(manager.borDelta)} flex-shrink-0">{deltaIcon(manager.borDelta)} {Math.abs(manager.borDelta)}%</span>
                </div>
            </div>
        </section>

        <!-- 2. Charts -->
        <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">

            <!-- Trend Chart -->
            <div class="xl:col-span-7 bg-white rounded-xl shadow-sm border border-slate-100 p-6 animate-fade-in" style="animation-delay: 500ms">
                <div class="flex flex-col sm:flex-row justify-between items-start mb-5 gap-3">
                    <div>
                        <h2 class="text-base font-black text-slate-900">Tren Admisi (Pasien Masuk)</h2>
                        <p class="text-xs text-slate-400 font-medium mt-0.5 text-ellipsis">Ringkasan bulanan tahun {manager.selectedYear} {manager.selectedWard ? '- ' + database.allBangsal.find(w => w.kd_bangsal === manager.selectedWard)?.nm_bangsal : ''}</p>
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
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest leading-tight">Total Admisi</span>
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
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest leading-tight">Puncak Admisi</span>
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
                    <h2 class="text-base font-black text-slate-900 mb-1">Populasi Penjamin</h2>
                    <p class="text-xs text-slate-400 font-medium mb-6">Penjamin kesehatan pasien masuk tahun {manager.selectedYear}</p>
                    
                    <DonutChart 
                        segments={manager.insuranceSegments} 
                        totalValue={manager.totalPasienDirawatYearly} 
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

        <!-- 3. Ward Comparison Table -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center">
                <div>
                    <h2 class="text-base font-black text-slate-900 tracking-tight">Kapasitas & Okupansi Per Bangsal</h2>
                    <p class="text-xs text-slate-400 font-medium mt-0.5">
                        Rekap real-time ·
                        <span class="text-emerald-600 font-bold">Tersedia</span> (&lt;60%) ·
                        <span class="text-amber-500 font-bold">Waspada</span> (60-84%) ·
                        <span class="text-rose-500 font-bold">Penuh</span> (&gt;= 85%)
                    </p>
                </div>
                <span class="text-[10px] font-black px-3 py-1.5 bg-slate-50 border border-slate-100 rounded-lg text-slate-500 uppercase tracking-wider">
                    {manager.wards.length} Bangsal
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 text-[10px] font-black text-slate-400 uppercase tracking-widest">
                            <th class="px-6 py-3 text-left">Nama Bangsal</th>
                            <th class="px-4 py-3 text-center">Status</th>
                            <th class="px-4 py-3 text-right w-24">Terisi</th>
                            <th class="px-4 py-3 text-right">Admisi H.I.</th>
                            <th class="px-4 py-3 text-center">Okupansi (BOR)</th>
                            <th class="px-4 py-3 text-right">ALOS</th>
                            <th class="px-4 py-3 text-center">Tren Layanan</th>
                            <th class="px-6 py-3 text-right">vs Minggu Lalu</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        {#if manager.wards.length === 0}
                            <tr>
                                <td colspan="8" class="px-6 py-10 text-center text-slate-400 font-medium">Data bangsal tidak tersedia.</td>
                            </tr>
                        {/if}
                        {#each manager.wards as ward (ward.id)}
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="px-6 py-4 font-black text-slate-800">{ward.name}</td>
                                <td class="px-4 py-4 text-center">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-black uppercase
                                        {ward.status === 'available' ? 'bg-emerald-50 text-emerald-700' : ward.status === 'warning' ? 'bg-amber-50 text-amber-700' : 'bg-rose-50 text-rose-700'}">
                                        <span class="w-1.5 h-1.5 rounded-full {statusDot[ward.status]}"></span>
                                        {statusLabels[ward.status]}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-right">
                                    <span class="font-black text-slate-900 tabular-nums">{ward.active}</span>
                                    <span class="text-xs text-slate-400 font-bold"> / {ward.capacity}</span>
                                </td>
                                <td class="px-4 py-4 text-right font-black text-slate-900 tabular-nums">{ward.admitsToday}</td>
                                <td class="px-4 py-4">
                                    <div class="flex items-center gap-2 min-w-[100px] justify-center">
                                        <span class="text-xs font-black tabular-nums w-8 text-right" style="color: {capacityColor(ward.bor)}">{ward.bor}%</span>
                                        <div class="flex-1 bg-slate-100 rounded-full h-2 overflow-hidden max-w-[80px]">
                                            <div class="h-2 rounded-full" style="width: {Math.min(ward.bor, 100)}%; background-color: {capacityColor(ward.bor)}"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-right font-black tabular-nums" style="color: {alosColor(ward.alos)}">
                                    {ward.alos} <span class="text-[10px]">hari</span>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="flex justify-center">
                                        <Sparkline
                                            data={ward.weeklyTrend}
                                            color={ward.vsLastWeek >= 0 ? '#0093dd' : '#ef4444'}
                                        />
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <span class="text-xs font-black px-2 py-0.5 rounded {deltaClass(ward.vsLastWeek)}">
                                        {deltaIcon(ward.vsLastWeek)} {Math.abs(ward.vsLastWeek)}%
                                    </span>
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
