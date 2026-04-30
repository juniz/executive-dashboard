<script lang="ts">
    import { Link, router } from '@inertiajs/svelte';
    import type { Snippet } from 'svelte';

    interface Props {
        title: string;
        subtitle?: string;
        statusLabel?: string;
        backHref?: string;
        showBack?: boolean;
        iconPath?: string;
        exportLabel?: string;
        showExport?: boolean;
        onExport?: () => void;
        filters?: Snippet;
        actions?: Snippet;
    }

    let { 
        title, 
        subtitle = '', 
        statusLabel = 'Live',
        backHref = '/dashboard',
        showBack = true,
        iconPath = '',
        exportLabel = 'Ekspor',
        showExport = true,
        onExport = () => window.print(),
        filters,
        actions
    } = $props();
</script>

<header class="bg-primary-deep text-white shadow-xl z-20 sticky top-0">
    <div class="max-w-[1600px] mx-auto px-4 sm:px-8 py-3 sm:py-4 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div class="flex items-center gap-4">
            {#if showBack}
                <Link 
                    href={backHref}
                    class="p-2 bg-white/10 rounded-lg border border-white/20 hover:bg-white/20 transition-all group"
                    title="Kembali"
                >
                    <svg class="w-5 h-5 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                    </svg>
                </Link>

                <div class="h-8 w-px bg-white/20 mx-1 hidden sm:block"></div>
            {/if}

            <div class="hidden sm:flex w-10 h-10 bg-white p-1 rounded-lg shadow-sm">
                <img src="/images/logo-rs.png" alt="Logo RS" class="w-full h-full object-contain" />
            </div>

            <div class="flex items-center gap-4">
                <!-- {#if iconPath}
                    <div class="p-2 bg-white/10 rounded-lg border border-white/20">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                            <path stroke-linecap="round" stroke-linejoin="round" d={iconPath} />
                        </svg>
                    </div>
                {/if} -->
                <div>
                    <h1 class="text-lg font-black tracking-tight leading-none uppercase">{title}</h1>
                    <p class="text-[9px] font-bold text-primary-light/60 uppercase tracking-widest mt-0.5 flex items-center gap-1.5">
                        {#if statusLabel.toLowerCase() === 'live'}
                            <span class="w-1.5 h-1.5 bg-emerald-400 rounded-full animate-pulse inline-block"></span>
                        {/if}
                        {subtitle} · {statusLabel}
                    </p>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3 sm:gap-4">
            {#if filters}
                {@render filters()}
            {/if}
            
            {#if actions}
                {@render actions()}
            {/if}

            <!-- {#if showExport}
                <button 
                    onclick={onExport}
                    class="bg-white/10 text-white hover:bg-white/20 border border-white/20 px-4 py-2 rounded-lg font-black text-xs shadow-lg active:scale-95 transition-all flex items-center gap-2"
                >
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 1.144c.05.25.13.493.24.722M17.66 18H6.34m0 0-.229 1.144c-.05.25-.13.493-.24.722M19.34 18h3.75-3.75Zm0 0-1.455-7.276a1.5 1.5 0 0 0-1.47-1.205h-8.83a1.5 1.5 0 0 0-1.47 1.205L4.66 18m14.68 0H4.66m10.12-10.13V4.87c0-.83-.67-1.5-1.5-1.5h-2.56c-.83 0-1.5.67-1.5 1.5v2.97m5.56 0h-5.56" />
                    </svg>
                    {exportLabel}
                </button>
            {/if} -->

            <button 
                onclick={() => router.post('/logout')}
                class="bg-rose-500 hover:bg-rose-600 text-white px-4 py-2 rounded-lg font-black text-xs shadow-lg shadow-rose-500/20 active:scale-95 transition-all flex items-center gap-2"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                Keluar
            </button>
        </div>
    </div>
</header>
