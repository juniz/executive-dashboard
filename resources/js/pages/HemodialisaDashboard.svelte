<script lang="ts">
    import { router, Link } from '@inertiajs/svelte';
    import AppHead from '../components/AppHead.svelte';
    import DashboardHeader from '../components/DashboardHeader.svelte';
    import Skeleton from '../components/Skeleton.svelte';
    import TrendChart from '../components/TrendChart.svelte';
    import DonutChart from '../components/DonutChart.svelte';
    import BarChart from '../components/BarChart.svelte';
    import { HdlManager } from '../models/HdlManager.svelte';

    interface Props {
        database: any;
    }

    let { database }: Props = $props();
    const manager = new HdlManager();
    
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

    function deltaClass(v: number) {
        return v >= 0 ? 'text-emerald-600 bg-emerald-50' : 'text-rose-600 bg-rose-50';
    }
    function deltaIcon(v: number) { return v >= 0 ? '▲' : '▼'; }

    const statusLabels: Record<string, string> = {
        active: 'Selesai', break: 'Proses', closed: 'Antre',
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

<AppHead title="Dashboard Hemodialisa" />

<div class="min-h-screen bg-slate-100 flex flex-col font-sans">

    <DashboardHeader 
        title="Hemodialisa" 
        subtitle="Monitor Layanan HD"
        iconPath="M22 12h-4l-3 9L9 3l-3 9H2"
    />

    <main class="flex-1 max-w-[1600px] w-full mx-auto px-4 sm:px-8 py-4 sm:py-8 flex flex-col gap-6 sm:gap-8">

        <!-- 1. KPI Overview -->
        <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

            {#each [
                { label: 'Total Kunjungan',     value: manager.totalVisitsToday,        unit: '',    delta:  manager.visitsDelta, sub: 'Hari ini' },
                { label: 'Selesai Tindakan',    value: manager.finishedToday,           unit: '',    delta:  null, sub: 'Sudah dilayani' },
                { label: 'Menunggu Antrean',    value: manager.waitingToday,            unit: '',    delta:  null, sub: 'Belum dilayani' },
                { label: 'Rerata Durasi',       value: manager.avgDuration,             unit: 'mnt', delta:  null, sub: 'Per sesi' },
            ] as kpi, ki (ki)}
                <div class="bg-white rounded-xl p-5 shadow-sm border border-slate-100 flex flex-col gap-3 animate-fade-in" style="animation-delay: {ki * 100}ms">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{kpi.label}</p>
                    {#if manager.isLoading}
                        <Skeleton class="h-10 w-2/3 mt-1" />
                    {:else}
                        <p class="text-4xl font-black text-slate-900 tabular-nums leading-none">
                            {kpi.value}{#if kpi.unit}<span class="text-lg font-bold ml-1">{kpi.unit}</span>{/if}
                        </p>
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
        </section>

        <!-- 2. Charts -->
        <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">

            <!-- Trend Chart -->
            <div class="xl:col-span-7 bg-white rounded-xl shadow-sm border border-slate-100 p-6 animate-fade-in" style="animation-delay: 500ms">
                <div class="flex flex-col sm:flex-row justify-between items-start mb-5 gap-3">
                    <div>
                        <h2 class="text-base font-black text-slate-900">Tren Kunjungan HD</h2>
                        <p class="text-xs text-slate-400 font-medium mt-0.5">Ringkasan kunjungan bulanan unit Hemodialisa</p>
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
                                <span class="text-[10px] font-bold text-slate-400">Pasien</span>
                            </div>
                        </div>
                        <div class="flex flex-col border-l border-slate-100 pl-4">
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest leading-tight">Vs Tahun Lalu</span>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="text-2xl font-black {trendSummary.growth >= 0 ? 'text-emerald-600' : 'text-rose-600'} leading-none">
                                    {trendSummary.growth >= 0 ? '+' : ''}{Math.round(trendSummary.growth)}%
                                </span>
                            </div>
                        </div>
                    </div>
            </div>

            <!-- Right column: Donut + Bar -->
            <div class="xl:col-span-5 flex flex-col gap-6">

                <!-- Donut: Insurance -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-6 flex-1">
                    <h2 class="text-base font-black text-slate-900 mb-1">Populasi Penjamin</h2>
                    <p class="text-xs text-slate-400 font-medium mb-6">Jaminan kesehatan pasien HD tahun {manager.selectedYear}</p>
                    
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

        <!-- 3. Patient Detail Table -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center">
                <div>
                    <h2 class="text-base font-black text-slate-900 tracking-tight">Daftar Pasien HD Hari Ini</h2>
                    <p class="text-xs text-slate-400 font-medium mt-0.5">Monitoring status tindakan dan rincian teknis</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 text-[10px] font-black text-slate-400 uppercase tracking-widest">
                            <th class="px-6 py-3 text-left">Nama Pasien</th>
                            <th class="px-4 py-3 text-center">Status</th>
                            <th class="px-4 py-3 text-right">Durasi</th>
                            <th class="px-4 py-3 text-left">Akses</th>
                            <th class="px-4 py-3 text-left">Dialyzer</th>
                            <th class="px-4 py-3 text-right">QB</th>
                            <th class="px-4 py-3 text-right">QD</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        {#each manager.patients as pt (pt.id)}
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="px-6 py-4 font-black text-slate-800">{pt.patient}</td>
                                <td class="px-4 py-4 text-center">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-black uppercase
                                        {pt.statusColor === 'active' ? 'bg-emerald-50 text-emerald-700' : pt.statusColor === 'break' ? 'bg-amber-50 text-amber-700' : 'bg-slate-100 text-slate-500'}">
                                        <span class="w-1.5 h-1.5 rounded-full {statusDot[pt.statusColor]}"></span>
                                        {pt.statusLabel}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-right font-black text-slate-900 tabular-nums">
                                    {pt.duration || '-'} {pt.duration ? 'mnt' : ''}
                                </td>
                                <td class="px-4 py-4 text-xs text-slate-500 font-medium">{pt.access || '-'}</td>
                                <td class="px-4 py-4 text-xs text-slate-500 font-medium">{pt.dialyzer || '-'}</td>
                                <td class="px-4 py-4 text-right font-medium text-slate-600">{pt.qb || '-'}</td>
                                <td class="px-4 py-4 text-right font-medium text-slate-600">{pt.qd || '-'}</td>
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
