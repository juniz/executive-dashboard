<script lang="ts">
    import { router, Link } from '@inertiajs/svelte';
    import AppHead from '../components/AppHead.svelte';
    import DashboardHeader from '../components/DashboardHeader.svelte';
    import Skeleton from '../components/Skeleton.svelte';
    import TrendChart from '../components/TrendChart.svelte';
    import { RekamMedisManager } from '../models/RekamMedisManager.svelte';

    interface Props {
        database: any;
    }

    let { database }: Props = $props();
    const manager = new RekamMedisManager();
    
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

    function capacityColor(pct: number) {
        return pct >= 85 ? '#ef4444' : pct >= 60 ? '#f59e0b' : '#22c55e';
    }
    
    function alosColor(days: number) {
        return days >= 12 ? '#ef4444' : days >= 9 ? '#f59e0b' : '#22c55e';
    }

    function deltaClass(v: number) {
        return v >= 0 ? 'text-emerald-600 bg-emerald-50' : 'text-rose-600 bg-rose-50';
    }
    function deltaIcon(v: number) { return v >= 0 ? '▲' : '▼'; }

    // NDR & GDR trend data mapping
    const chartW = 560;
    const chartH = 240;
    const padX = 40;
    const padY = 20;

    function buildPts(values: number[], max: number) {
        const innerW = chartW - padX * 2;
        const innerH = chartH - padY * 2;
        const stepX  = values.length > 1 ? innerW / (values.length - 1) : 0;
        return values.map((v, i) => ({
            x: padX + i * stepX,
            y: padY + innerH - (v / (max || 1)) * innerH,
            value: v,
        }));
    }

    const deathMax = $derived(Math.ceil(Math.max(...manager.deathRateTrend.ndr, ...manager.deathRateTrend.gdr, 1) / 5) * 5 + 5);
    const ndrPts = $derived(buildPts(manager.deathRateTrend.ndr, deathMax));
    const gdrPts = $derived(buildPts(manager.deathRateTrend.gdr, deathMax));
    const ndrLine = $derived(ndrPts.map(p => `${p.x},${p.y}`).join(' '));
    const gdrLine = $derived(gdrPts.map(p => `${p.x},${p.y}`).join(' '));

</script>

<AppHead title="Command Center - Rekam Medis" />

<div class="min-h-screen bg-slate-100 flex flex-col font-sans">

    <DashboardHeader 
        title="Indikator Rekam Medis" 
        subtitle="Hospital Performance Indicators"
        iconPath="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
    >
        {#snippet filters()}
            <div class="relative group">
                <select 
                    value={manager.selectedYear}
                    onchange={handleYearChange}
                    class="bg-white/10 border border-white/20 text-white text-[11px] font-black uppercase tracking-widest rounded-lg px-4 py-2 appearance-none cursor-pointer hover:bg-white/20 transition-all outline-none"
                >
                    {#each manager.availableYears as year (year)}
                        <option value={year} class="bg-primary-deep text-white">Tahun {year}</option>
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
        <section class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
            {#each [
                { label: 'BOR', value: manager.summary.bor, unit: '%', target: '60-85%', color: capacityColor(manager.summary.bor || 0) },
                { label: 'ALOS', value: manager.summary.alos, unit: 'hari', target: '6-9 hari', color: alosColor(manager.summary.alos || 0) },
                { label: 'BTO', value: manager.summary.bto, unit: 'kali', target: '40-50 kali', color: '#f59e0b' },
                { label: 'TOI', value: manager.summary.toi, unit: 'hari', target: '1-3 hari', color: '#0891b2' },
                { label: 'NDR', value: manager.summary.ndr, unit: '‰', target: '< 25‰', color: '#e11d48' },
                { label: 'GDR', value: manager.summary.gdr, unit: '‰', target: '< 45‰', color: '#334155' },
            ] as kpi, ki (ki)}
                <div class="bg-white rounded-xl p-5 shadow-sm border border-slate-100 flex flex-col gap-3 animate-fade-in" style="animation-delay: {ki * 100}ms">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{kpi.label}</p>
                    {#if manager.isLoading}
                        <Skeleton class="h-10 w-2/3 mt-1" />
                    {:else}
                        <p class="text-4xl font-black tabular-nums leading-none" style="color: {kpi.color}">
                            {kpi.value}<span class="text-sm font-bold ml-1 text-slate-400">{kpi.unit}</span>
                        </p>
                    {/if}
                    <div class="flex items-center justify-between mt-auto pt-3 border-t border-slate-50">
                        <p class="text-[9px] text-slate-400 font-bold uppercase tracking-tighter">Target: {kpi.target}</p>
                        <span class="text-[10px] font-black px-2 py-0.5 rounded bg-slate-50 text-slate-500">Bulan Ini</span>
                    </div>
                </div>
            {/each}
        </section>

        <!-- 2. Charts -->
        <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">

            <!-- Chart 1: BOR Trend -->
            <div class="xl:col-span-6 bg-white rounded-xl shadow-sm border border-slate-100 p-6 animate-fade-in" style="animation-delay: 500ms">
                <div class="flex flex-col sm:flex-row justify-between items-start mb-5 gap-3">
                    <div>
                        <h2 class="text-base font-black text-slate-900 uppercase tracking-tight">Tren Okupansi (BOR)</h2>
                        <p class="text-xs text-slate-400 font-medium mt-0.5 text-ellipsis">Ringkasan utilisasi tempat tidur tahun {manager.selectedYear}</p>
                    </div>
                    <div class="flex items-center gap-4 text-[9px] font-black uppercase text-slate-400">
                        <span class="flex items-center gap-1.5">
                            <span class="w-3 h-3 rounded bg-primary-deep inline-block"></span>{manager.selectedYear}
                        </span>
                    </div>
                </div>

                <TrendChart 
                    data={manager.borTrend.map((v, i) => ({
                        label: manager.monthlyTrends[i].month,
                        value: v,
                        prevValue: 0 // No comparison for BOR yet
                    }))} 
                    isLoading={manager.isLoading} 
                />
            </div>

            <!-- Chart 2: Death Rates -->
            <div class="xl:col-span-6 bg-white rounded-xl shadow-sm border border-slate-100 p-6 animate-fade-in" style="animation-delay: 600ms">
                <div class="flex justify-between items-start mb-5">
                    <div>
                        <h2 class="text-base font-black text-slate-900 uppercase tracking-tight">Angka Kematian (NDR & GDR)</h2>
                        <p class="text-xs text-slate-400 font-medium mt-0.5">Tren NDR (Merah) & GDR (Slate)</p>
                    </div>
                </div>

                {#if manager.isLoading}
                    <div class="w-full h-[240px] flex items-center justify-center">
                        <Skeleton class="w-full h-full" />
                    </div>
                {:else}
                    <svg width="100%" viewBox="0 0 {chartW} {chartH + 40}" class="overflow-visible">
                        {#each [0, 0.25, 0.5, 0.75, 1.0] as frac (frac)}
                            {@const yPos = padY + (1 - frac) * (chartH - padY * 2)}
                            <line x1={padX} y1={yPos} x2={chartW - padX} y2={yPos} stroke="#f1f5f9" stroke-width="1"/>
                            <text x={padX - 8} y={yPos + 3} text-anchor="end" font-size="9" fill="#94a3b8" font-family="inherit" font-weight="600">
                                {Math.round((deathMax || 1) * frac)}‰
                            </text>
                        {/each}

                        <polyline points={ndrLine} fill="none" stroke="#e11d48" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="animate-draw"/>
                        <polyline points={gdrLine} fill="none" stroke="#334155" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" opacity="0.6"/>

                        {#each ndrPts as pt, i (i)}
                            <circle cx={pt.x} cy={pt.y} r="3" fill="#e11d48" />
                            <text x={pt.x} y={chartH + 8} text-anchor="middle" font-size="9" fill="#94a3b8" font-weight="700">{manager.monthlyTrends[i].month}</text>
                        {/each}
                    </svg>
                {/if}

                <div class="mt-8 flex gap-6 border-t border-slate-50 pt-4">
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded bg-rose-500"></span>
                        <span class="text-[10px] font-black text-slate-500">NDR</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded bg-slate-500"></span>
                        <span class="text-[10px] font-black text-slate-500">GDR</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Ward Comparison Table -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center">
                <div>
                    <h2 class="text-base font-black text-slate-900 tracking-tight uppercase">Kinerja Indikator Per Bangsal</h2>
                    <p class="text-xs text-slate-400 font-medium mt-0.5">
                        Rekapitulasi bulan berjalan · 
                        <span class="text-emerald-600 font-bold">Baik</span> · 
                        <span class="text-amber-500 font-bold">Perhatian</span> · 
                        <span class="text-rose-500 font-bold">Kritis</span>
                    </p>
                </div>
                <span class="text-[10px] font-black px-3 py-1.5 bg-slate-50 border border-slate-100 rounded-lg text-slate-500 uppercase tracking-wider">
                    {manager.wardPerformance.length} Bangsal
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 text-[10px] font-black text-slate-400 uppercase tracking-widest">
                            <th class="px-6 py-3 text-left">Nama Bangsal</th>
                            <th class="px-4 py-3 text-center">TT</th>
                            <th class="px-4 py-3 text-center">HP</th>
                            <th class="px-4 py-3 text-center">Keluar</th>
                            <th class="px-4 py-3 text-center">BOR (%)</th>
                            <th class="px-4 py-3 text-center">ALOS</th>
                            <th class="px-4 py-3 text-center">BTO</th>
                            <th class="px-4 py-3 text-center">TOI</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        {#each manager.wardPerformance as ward (ward.name)}
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="px-6 py-4 font-black text-slate-800">{ward.name}</td>
                                <td class="px-4 py-4 text-center tabular-nums font-bold text-slate-600">{ward.beds}</td>
                                <td class="px-4 py-4 text-center tabular-nums font-bold text-slate-500">{ward.hp}</td>
                                <td class="px-4 py-4 text-center tabular-nums font-bold text-slate-500">{ward.keluar}</td>
                                <td class="px-4 py-4 text-center">
                                    <div class="flex items-center gap-2 justify-center">
                                        <span class="text-xs font-black tabular-nums w-8 text-right" style="color: {capacityColor(ward.bor)}">{ward.bor}%</span>
                                        <div class="w-16 bg-slate-100 rounded-full h-1.5 overflow-hidden">
                                            <div class="h-1.5 rounded-full" style="width: {Math.min(ward.bor, 100)}%; background-color: {capacityColor(ward.bor)}"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-center font-black tabular-nums" style="color: {alosColor(ward.alos)}">{ward.alos}</td>
                                <td class="px-4 py-4 text-center font-black tabular-nums text-amber-600">{ward.bto}</td>
                                <td class="px-4 py-4 text-center font-black tabular-nums text-cyan-600">{ward.toi}</td>
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
    .animate-draw {
        stroke-dasharray: 5000;
        stroke-dashoffset: 5000;
        animation: draw 2.5s ease-out forwards;
    }
    .animate-fade-in {
        animation: fadeIn 0.6s ease-out forwards;
    }
</style>
