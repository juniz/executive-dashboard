<script lang="ts">
    interface Props {
        data: number[];
        color?: string;
        width?: number;
        height?: number;
    }

    let { data, color = '#0093dd', width = 80, height = 32 }: Props = $props();

    const points = $derived.by(() => {
        if (data.length < 2) return '';
        const max = Math.max(...data);
        const min = Math.min(...data);
        const range = max - min || 1;
        const stepX = width / (data.length - 1);
        return data
            .map((v, i) => `${i * stepX},${height - ((v - min) / range) * height}`)
            .join(' ');
    });

    const lastPt = $derived.by(() => {
        if (data.length === 0) return null;
        const max = Math.max(...data);
        const min = Math.min(...data);
        const range = max - min || 1;
        return {
            x: width,
            y: height - ((data[data.length - 1] - min) / range) * height,
        };
    });
</script>

<svg {width} {height} viewBox="0 0 {width} {height}" class="overflow-visible">
    <polyline
        {points}
        fill="none"
        stroke={color}
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
    />
    {#if lastPt}
        <circle cx={lastPt.x} cy={lastPt.y} r="3" fill={color} />
    {/if}
</svg>
