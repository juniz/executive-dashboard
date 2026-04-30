<script lang="ts">
    interface Props {
        labels: string[];
        values: number[];
        highlightLabel?: string;
        width?: number;
        height?: number;
        padX?: number;
        padY?: number;
        color?: string;
        inactiveColor?: string;
    }

    let {
        labels = [],
        values = [],
        highlightLabel = '',
        width = 296,
        height = 150,
        padX = 24,
        padY = 16,
        color = '#0093dd',
        inactiveColor = '#e2e8f0'
    }: Props = $props();

    const barData = $derived.by(() => {
        const max   = Math.max(...values, 1);
        const innerH = height - padY * 2;
        const groupW = (width - padX * 2) / (labels.length || 1);
        const bw    = groupW * 0.6;
        
        return labels.map((label, i) => ({
            label, 
            x: padX + i * groupW, 
            groupW, 
            bw,
            h: (values[i] / max) * innerH,
            bottom: height - padY,
        }));
    });
</script>

<svg width="100%" viewBox="0 0 {width} {height}" class="overflow-visible">
    {#each barData as bar, i (i)}
        <rect 
            x={bar.x + (bar.groupW - bar.bw) / 2} 
            y={bar.bottom - bar.h} 
            width={bar.bw} 
            height={bar.h} 
            rx="3" 
            fill={bar.label === highlightLabel ? color : inactiveColor} 
            class="animate-bar" 
            style="animation-delay: {400 + i * 50}ms"
        />
        <text 
            x={bar.x + bar.groupW / 2} 
            y={bar.bottom + 12} 
            text-anchor="middle" 
            font-size="9" 
            fill="#94a3b8" 
            font-weight="700" 
            font-family="inherit"
        >
            {bar.label}
        </text>
    {/each}
</svg>

<style>
    @keyframes scaleIn {
        from { transform: scaleY(0); transform-origin: bottom; }
        to { transform: scaleY(1); transform-origin: bottom; }
    }
    .animate-bar {
        animation: scaleIn 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
    }
</style>
