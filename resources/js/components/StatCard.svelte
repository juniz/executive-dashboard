<script lang="ts">
    import type { Snippet } from 'svelte';
    import { cn } from '@/lib/utils';
    
    interface Props {
        title: string;
        value: string | number;
        description?: string;
        trend?: {
            value: number;
            direction: 'up' | 'down';
        };
        icon?: Snippet;
        class?: string;
    }

    let { title, value, description, trend, icon, class: className }: Props = $props();
</script>

<div class={cn(
    "card-solid p-7 rounded-xl relative flex flex-col justify-between",
    className
)}>
    <div class="flex items-start justify-between">
        <div>
            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest leading-none mb-3">{title}</p>
            <h3 class="text-4xl font-extrabold text-slate-900 tracking-tight">
                {value}
            </h3>
        </div>
        
        {#if icon}
            <div class="p-3.5 rounded-lg bg-slate-50 text-primary border border-slate-100 shadow-sm">
                {@render icon()}
            </div>
        {/if}

    </div>

    {#if description || trend}
        <div class="mt-6 pt-5 border-t border-slate-50 flex items-center justify-between">
            {#if description}
                <p class="text-sm font-medium text-slate-500">{description}</p>
            {/if}

            {#if trend}
                <span class={cn(
                    "text-xs font-bold px-2.5 py-1 rounded flex items-center gap-1.5",
                    trend.direction === 'up' ? "bg-emerald-50 text-emerald-700" : "bg-rose-50 text-rose-700"
                )}>
                    {#if trend.direction === 'up'}
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 10l7-7m0 0l7 7m-7-7v18" />
                        </svg>
                    {:else}
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                        </svg>
                    {/if}
                    {trend.value}%
                </span>
            {/if}
        </div>
    {/if}
</div>

<style>
    div :global(svg) {
        width: 28px;
        height: 28px;
    }
</style>

