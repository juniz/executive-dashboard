<script lang="ts">
    interface Segment {
        label: string;
        value: number;
        color: string;
    }

    interface Props {
        segments: Segment[];
        totalValue: number | string;
        totalLabel?: string;
        size?: number;
        showLegend?: boolean;
    }

    let { 
        segments = [], 
        totalValue, 
        totalLabel = 'PASIEN', 
        size = 152,
        showLegend = true
    }: Props = $props();

    const donutR = 52;
    const donutStroke = 22;
    const circumference = 2 * Math.PI * donutR;
    const center = size / 2;
    const scale = size / 152; // Original design was based on 152px

    const donutArcs = $derived.by(() => {
        const total = segments.reduce((s, seg) => s + seg.value, 0);
        let offset = 0;
        return segments.map(seg => {
            const dash = (seg.value / (total || 1)) * circumference;
            const gap  = circumference - dash;
            const pct  = Math.round((seg.value / (total || 1)) * 100);
            const arc  = { ...seg, dash, gap, offset, pct };
            offset += dash;
            return arc;
        });
    });
</script>

<div class="flex flex-col sm:flex-row items-center gap-8 w-full">
    <div class="relative flex-shrink-0" style="width: {size}px; height: {size}px">
        <svg width={size} height={size} viewBox="0 0 152 152" class="flex-shrink-0">
            {#each donutArcs as arc, i (i)}
                <circle 
                    cx={76} 
                    cy={76} 
                    r={donutR} 
                    fill="none" 
                    stroke={arc.color}
                    stroke-width={donutStroke} 
                    stroke-dasharray="{arc.dash} {circumference}" 
                    stroke-dashoffset={-arc.offset}
                    transform="rotate(-90 76 76)" 
                    class="transition-all duration-500" 
                />
            {/each}
            <text x={76} y={71} text-anchor="middle" font-size="20" font-weight="900" fill="#0f172a" font-family="inherit">
                {totalValue}
            </text>
            <text x={76} y={88} text-anchor="middle" font-size="8" font-weight="700" fill="#94a3b8" font-family="inherit">
                {totalLabel}
            </text>
        </svg>
    </div>
    
    {#if showLegend}
        <div class="flex flex-col gap-3 flex-1 w-full">
            {#if segments.length === 0}
                <p class="text-xs text-slate-400 mt-10 text-center">Belum ada data</p>
            {/if}
            {#each donutArcs as arc, i (i)}
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <span class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-sm flex-shrink-0" style="background-color: {arc.color}"></span>
                            <span class="text-[11px] font-bold text-slate-600 leading-tight truncate max-w-[150px]">{arc.label}</span>
                        </span>
                        <span class="text-sm font-black text-slate-900 tabular-nums">{arc.pct}%</span>
                    </div>
                    <div class="w-full bg-slate-50 rounded-full h-1 overflow-hidden">
                        <div class="h-1 rounded-full transition-all duration-500" style="width: {arc.pct}%; background-color: {arc.color}"></div>
                    </div>
                </div>
            {/each}
        </div>
    {/if}
</div>
