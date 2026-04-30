<script lang="ts">
    import Skeleton from './Skeleton.svelte';

    interface DataPoint {
        label: string;
        value: number;
        prevValue?: number;
    }

    interface Props {
        data: DataPoint[];
        color?: string;
        isLoading?: boolean;
        width?: number;
        height?: number;
        padX?: number;
        padY?: number;
    }

    let { 
        data = [], 
        color = '#0093dd', 
        isLoading = false, 
        width = 560, 
        height = 320,
        padX = 46,
        padY = 20
    }: Props = $props();

    const chartMax = $derived.by(() => {
        if (data.length === 0) return 10;
        const m = Math.max(...data.map(p => p.value || 0), ...data.map(p => p.prevValue || 0), 1);
        return Math.ceil(m / 10) * 10;
    });

    function buildPts(values: number[], max: number) {
        const innerW = width - padX * 2;
        const innerH = height - padY * 2;
        const stepX  = values.length > 1 ? innerW / (values.length - 1) : 0;
        return values.map((v, i) => ({
            x: padX + i * stepX,
            y: padY + innerH - (v / (max || 1)) * innerH,
            value: v,
        }));
    }

    const lastDataIdx = $derived.by(() => {
        if (data.length === 0) return 0;
        for (let i = data.length - 1; i >= 0; i--) {
            if ((data[i].value || 0) > 0) return i;
        }
        return 0;
    });

    const activeData = $derived(data.slice(0, lastDataIdx + 1));
    const currPts = $derived(buildPts(activeData.map(p => p.value || 0), chartMax));
    const prevPts = $derived(buildPts(data.map(p => p.prevValue || 0), chartMax));
    
    // Adjust x-coordinates for activeData to match the full width scale
    const stepX = $derived(data.length > 1 ? (width - padX * 2) / (data.length - 1) : 0);
    const activePts = $derived(activeData.map((p, i) => ({
        x: padX + i * stepX,
        y: padY + (height - padY * 2) - ((p.value || 0) / (chartMax || 1)) * (height - padY * 2),
        value: p.value || 0
    })));

    const currLine = $derived(activePts.map(p => `${p.x},${p.y}`).join(' '));
    const prevLine = $derived(prevPts.map(p => `${p.x},${p.y}`).join(' '));

    const areaPath = $derived.by(() => {
        if (activePts.length === 0) return '';
        const bottom = height - padY;
        return `${activePts[0].x},${bottom} ` +
               activePts.map(p => `${p.x},${p.y}`).join(' ') +
               ` ${activePts[activePts.length - 1].x},${bottom}`;
    });

    const peakIdx = $derived(
        activeData.reduce((mi, p, i, arr) => (p.value || 0) >= (arr[mi]?.value || 0) ? i : mi, 0)
    );
</script>

{#if isLoading}
    <div class="w-full" style="height: {height + 40}px">
        <div class="w-full h-full flex items-end gap-2 px-10">
            {#each Array(Math.max(data.length, 12)) as _, i (i)}
                <Skeleton class="flex-1" style="height: {Math.random() * 60 + 20}%" />
            {/each}
        </div>
    </div>
{:else}
    <svg width="100%" viewBox="0 0 {width} {height + 40}" class="overflow-visible">
        <defs>
            <linearGradient id="areaGrad" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%"   stop-color={color} stop-opacity="0.18"/>
                <stop offset="100%" stop-color={color} stop-opacity="0"/>
            </linearGradient>
        </defs>

        {#each [0.25, 0.5, 0.75, 1.0] as frac, gi (gi)}
            {@const yPos = padY + (1 - frac) * (height - padY * 2)}
            <line x1={padX} y1={yPos} x2={width - padX} y2={yPos} stroke="#f1f5f9" stroke-width="1"/>
            <text x={padX - 8} y={yPos + 3} text-anchor="end" font-size="9" fill="#94a3b8" font-family="inherit" font-weight="600">
                {Math.round((chartMax || 1) * frac)}
            </text>
        {/each}

        <polygon points={areaPath} fill="url(#areaGrad)" class="transition-all duration-1000 ease-in-out" />
        
        {#if data.some(p => p.prevValue !== undefined)}
            <polyline points={prevLine} fill="none" stroke="#94a3b8" stroke-width="2" stroke-dasharray="6 4" opacity="0.8"/>
        {/if}
        
        <polyline points={currLine} fill="none" stroke={color} stroke-width="3" stroke-linecap="round" stroke-linejoin="round" class="animate-draw"/>

        {#each data as p, i (i)}
            {@const x = padX + i * stepX}
            <text 
                x={x} 
                y={height + 8} 
                text-anchor="middle" 
                font-size="9" 
                fill="#64748b" 
                font-weight="800" 
                font-family="inherit"
            >
                {p.label}
            </text>
        {/each}

        {#each activePts as pt, i (i)}
            <circle cx={pt.x} cy={pt.y} r="4" fill={color} class="transition-all duration-500" />

            {#if i === peakIdx && pt.value > 0}
                <rect x={pt.x - 20} y={pt.y - 28} width="40" height="20" rx="6" fill="#0f172a" class="animate-fade-in shadow-lg"/>
                <text x={pt.x} y={pt.y - 15} text-anchor="middle" font-size="10" fill="white" font-weight="900" font-family="inherit">
                    {pt.value}
                </text>
                <path d="M{pt.x-4} {pt.y-8} L{pt.x+4} {pt.y-8} L{pt.x} {pt.y-2} Z" fill="#0f172a" />
            {/if}
        {/each}
    </svg>
{/if}

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
