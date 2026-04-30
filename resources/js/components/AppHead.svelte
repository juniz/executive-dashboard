<script lang="ts">
    import type { Snippet } from 'svelte';

    let {
        title = '',
        description = 'Dashboard Eksekutif SIMRS Khanza - Monitor kinerja rumah sakit secara real-time.',
        ogImage = '/og-image.png',
        children,
    }: {
        title?: string;
        description?: string;
        ogImage?: string;
        children?: Snippet;
    } = $props();

    const appName = import.meta.env.VITE_APP_NAME || 'Executive Dashboard';
    const fullTitle = $derived(title ? `${title} - ${appName}` : appName);
</script>

<svelte:head>
    <title>{fullTitle}</title>
    <meta name="description" content={description} />
    
    <!-- Open Graph / Facebook -->
    <meta property="og:title" content={fullTitle} />
    <meta property="og:description" content={description} />
    <meta property="og:image" content={ogImage} />
    
    <!-- Twitter -->
    <meta property="twitter:title" content={fullTitle} />
    <meta property="twitter:description" content={description} />
    <meta property="twitter:image" content={ogImage} />

    {@render children?.()}
</svelte:head>
