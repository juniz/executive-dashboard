<script lang="ts">
    import { onMount } from 'svelte';
    import { fly, fade } from 'svelte/transition';

    interface Props { status: number }
    let { status }: Props = $props();

    const errors: Record<number, { title: string; description: string; suggestion: string }> = {
        404: {
            title: 'Halaman Tidak Ditemukan',
            description: 'Halaman yang Anda cari tidak ada atau telah dipindahkan.',
            suggestion: 'Periksa kembali URL atau kembali ke dashboard utama.',
        },
        403: {
            title: 'Akses Ditolak',
            description: 'Anda tidak memiliki izin untuk mengakses halaman ini.',
            suggestion: 'Hubungi administrator sistem jika Anda memerlukan akses.',
        },
        500: {
            title: 'Kesalahan Server',
            description: 'Terjadi kesalahan internal pada server kami.',
            suggestion: 'Tim kami telah diberitahu. Coba lagi dalam beberapa menit.',
        },
        503: {
            title: 'Layanan Tidak Tersedia',
            description: 'Server sedang dalam pemeliharaan atau kelebihan beban.',
            suggestion: 'Silakan coba lagi dalam beberapa saat.',
        },
    };

    const info = errors[status] ?? {
        title: 'Terjadi Kesalahan',
        description: 'Sesuatu yang tidak terduga terjadi.',
        suggestion: 'Coba muat ulang halaman atau kembali ke dashboard.',
    };

    let particles: { x: number; y: number; size: number; delay: number; dur: number }[] = [];
    let mounted = $state(false);

    onMount(() => {
        mounted = true;
        particles = Array.from({ length: 18 }, () => ({
            x: Math.random() * 100,
            y: Math.random() * 100,
            size: Math.random() * 3 + 1,
            delay: Math.random() * 4,
            dur: Math.random() * 6 + 6,
        }));
    });

    function goHome() { window.location.href = '/'; }
    function goBack() { history.back(); }
    function reload()  { window.location.reload(); }
</script>

<div class="error-root">

    <!-- Animated background particles -->
    {#if mounted}
        {#each particles as p}
            <span
                class="particle"
                style="left:{p.x}%;top:{p.y}%;width:{p.size}px;height:{p.size}px;animation-delay:{p.delay}s;animation-duration:{p.dur}s"
            ></span>
        {/each}
    {/if}

    <!-- Radial glow blobs -->
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>

    <!-- Card -->
    {#if mounted}
        <div class="card" in:fly={{ y: 32, duration: 600, delay: 80 }}>

            <!-- Top accent bar -->
            <div class="accent-bar"></div>

            <!-- Status badge -->
            <div class="status-row" in:fade={{ duration: 400, delay: 200 }}>
                <span class="status-dot"></span>
                <span class="status-label">HTTP {status}</span>
            </div>

            <!-- Error code display -->
            <div class="code-display" in:fly={{ y: 16, duration: 500, delay: 250 }}>
                <span class="code-num">{status}</span>
                <div class="code-lines">
                    <span></span><span></span><span></span>
                </div>
            </div>

            <!-- Icon -->
            <div class="icon-wrap" in:fly={{ y: 12, duration: 400, delay: 300 }}>
                {#if status === 404}
                    <!-- Search / not found -->
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
                        <path d="M8 11h6M11 8v6" opacity=".4"/>
                    </svg>
                {:else if status === 403}
                    <!-- Lock / forbidden -->
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="11" width="18" height="11" rx="2"/>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                        <circle cx="12" cy="16" r="1.5" fill="currentColor"/>
                    </svg>
                {:else if status === 503}
                    <!-- Wrench / maintenance -->
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/>
                    </svg>
                {:else}
                    <!-- Server error -->
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 9v4M12 17h.01"/>
                        <path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                    </svg>
                {/if}
            </div>

            <!-- Text -->
            <div class="text-block" in:fly={{ y: 12, duration: 400, delay: 350 }}>
                <h1>{info.title}</h1>
                <p class="desc">{info.description}</p>
                <p class="suggestion">{info.suggestion}</p>
            </div>

            <!-- Divider -->
            <div class="divider" in:fade={{ duration: 300, delay: 400 }}></div>

            <!-- Actions -->
            <div class="actions" in:fly={{ y: 12, duration: 400, delay: 420 }}>
                <button class="btn btn-primary" onclick={goHome}>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                        <polyline points="9 22 9 12 15 12 15 22"/>
                    </svg>
                    Dashboard Utama
                </button>

                <button class="btn btn-secondary" onclick={goBack}>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m12 19-7-7 7-7"/><path d="M19 12H5"/>
                    </svg>
                    Kembali
                </button>

                <button class="btn btn-ghost" onclick={reload} aria-label="Muat ulang halaman">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/>
                        <path d="M21 3v5h-5"/><path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/>
                        <path d="M8 16H3v5"/>
                    </svg>
                    Muat Ulang
                </button>
            </div>

            <!-- Footer -->
            <p class="footer-note" in:fade={{ duration: 300, delay: 500 }}>
                © {new Date().getFullYear()} IT RS Bhayangkara Ngajuk · SIMRS Executive Dashboard
            </p>
        </div>
    {/if}
</div>

<style>
    /* ── Tokens ─────────────────────────────────────────────── */
    :root {
        --az-50:  #eff6ff;
        --az-100: #dbeafe;
        --az-200: #bfdbfe;
        --az-300: #93c5fd;
        --az-500: #3b82f6;
        --az-600: #1d6fdb;
        --az-700: #1e56b0;
        --az-800: #1e3f8a;
        --az-900: #172554;
    }

    /* ── Root ───────────────────────────────────────────────── */
    .error-root {
        position: relative;
        min-height: 100dvh;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #0c1a3a 0%, #0f2455 45%, #0a1c45 100%);
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
        overflow: hidden;
        padding: 24px;
    }

    /* ── Background blobs ───────────────────────────────────── */
    .blob {
        position: absolute;
        border-radius: 50%;
        filter: blur(80px);
        pointer-events: none;
    }
    .blob-1 {
        width: 480px; height: 480px;
        background: radial-gradient(circle, rgba(29,111,219,.28) 0%, transparent 70%);
        top: -120px; left: -120px;
        animation: blobFloat 14s ease-in-out infinite alternate;
    }
    .blob-2 {
        width: 360px; height: 360px;
        background: radial-gradient(circle, rgba(59,130,246,.18) 0%, transparent 70%);
        bottom: -80px; right: -80px;
        animation: blobFloat 18s ease-in-out infinite alternate-reverse;
    }
    @keyframes blobFloat {
        from { transform: translate(0, 0) scale(1); }
        to   { transform: translate(30px, 20px) scale(1.08); }
    }

    /* ── Particles ──────────────────────────────────────────── */
    .particle {
        position: absolute;
        border-radius: 50%;
        background: rgba(147, 197, 253, 0.35);
        animation: particleDrift linear infinite;
        pointer-events: none;
    }
    @keyframes particleDrift {
        0%   { transform: translateY(0) scale(1); opacity: 0; }
        20%  { opacity: 1; }
        80%  { opacity: .6; }
        100% { transform: translateY(-60px) scale(.6); opacity: 0; }
    }

    /* ── Card ───────────────────────────────────────────────── */
    .card {
        position: relative;
        width: 100%;
        max-width: 480px;
        background: rgba(255, 255, 255, 0.05);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.12);
        border-radius: 24px;
        padding: 40px 36px 32px;
        box-shadow:
            0 32px 64px rgba(0, 0, 0, 0.4),
            0 0 0 1px rgba(255,255,255,.06) inset,
            0 1px 0 rgba(255,255,255,.15) inset;
        overflow: hidden;
        text-align: center;
    }

    /* ── Accent bar ─────────────────────────────────────────── */
    .accent-bar {
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 3px;
        background: linear-gradient(90deg, var(--az-600), var(--az-300), var(--az-600));
        background-size: 200%;
        animation: shimmer 3s linear infinite;
    }
    @keyframes shimmer {
        0%   { background-position: -200% 0; }
        100% { background-position:  200% 0; }
    }

    /* ── Status badge ───────────────────────────────────────── */
    .status-row {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: rgba(29, 111, 219, 0.18);
        border: 1px solid rgba(29, 111, 219, 0.35);
        border-radius: 999px;
        padding: 4px 12px;
        margin-bottom: 20px;
    }
    .status-dot {
        width: 6px; height: 6px;
        border-radius: 50%;
        background: #f87171;
        animation: pulse 2s ease-in-out infinite;
    }
    @keyframes pulse {
        0%, 100% { opacity: 1; transform: scale(1); }
        50%       { opacity: .5; transform: scale(.8); }
    }
    .status-label {
        font-size: 10px;
        font-weight: 700;
        letter-spacing: .12em;
        text-transform: uppercase;
        color: var(--az-200);
    }

    /* ── Code display ───────────────────────────────────────── */
    .code-display {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 14px;
        margin-bottom: 24px;
    }
    .code-num {
        font-size: clamp(64px, 15vw, 96px);
        font-weight: 900;
        line-height: 1;
        letter-spacing: -.04em;
        background: linear-gradient(135deg, #ffffff 30%, var(--az-300) 100%);
        -webkit-background-clip: text;
        background-clip: text;
        -webkit-text-fill-color: transparent;
        filter: drop-shadow(0 4px 16px rgba(59, 130, 246, 0.4));
    }
    .code-lines {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }
    .code-lines span {
        display: block;
        height: 2px;
        border-radius: 2px;
        background: linear-gradient(90deg, var(--az-500), transparent);
        animation: linePulse 2.4s ease-in-out infinite;
    }
    .code-lines span:nth-child(1) { width: 48px; }
    .code-lines span:nth-child(2) { width: 32px; animation-delay: .4s; }
    .code-lines span:nth-child(3) { width: 40px; animation-delay: .8s; }
    @keyframes linePulse {
        0%, 100% { opacity: .3; }
        50%       { opacity: 1; }
    }

    /* ── Icon ───────────────────────────────────────────────── */
    .icon-wrap {
        width: 64px; height: 64px;
        border-radius: 18px;
        background: rgba(29, 111, 219, 0.18);
        border: 1px solid rgba(29, 111, 219, 0.3);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        color: var(--az-300);
        transition: transform .2s, box-shadow .2s;
    }
    .icon-wrap:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(29,111,219,.3); }
    .icon-wrap svg { width: 28px; height: 28px; }

    /* ── Text ───────────────────────────────────────────────── */
    .text-block { margin-bottom: 24px; }
    h1 {
        font-size: 22px;
        font-weight: 800;
        color: #ffffff;
        line-height: 1.25;
        margin: 0 0 10px;
        letter-spacing: -.02em;
    }
    .desc {
        font-size: 14px;
        color: rgba(255,255,255,.65);
        line-height: 1.6;
        margin: 0 0 8px;
    }
    .suggestion {
        font-size: 12px;
        color: var(--az-300);
        line-height: 1.5;
        margin: 0;
        font-weight: 500;
    }

    /* ── Divider ────────────────────────────────────────────── */
    .divider {
        height: 1px;
        background: rgba(255,255,255,.08);
        margin: 0 0 24px;
    }

    /* ── Actions ────────────────────────────────────────────── */
    .actions {
        display: flex;
        flex-direction: column;
        gap: 10px;
        margin-bottom: 28px;
    }
    .btn {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 13px 20px;
        border-radius: 12px;
        font-size: 14px;
        font-weight: 700;
        border: none;
        cursor: pointer;
        transition: transform .15s ease, box-shadow .15s ease, opacity .15s;
        min-height: 48px;
        touch-action: manipulation;
        text-decoration: none;
    }
    .btn svg { width: 16px; height: 16px; flex-shrink: 0; }
    .btn:active { transform: scale(.97); }
    .btn:focus-visible { outline: 3px solid var(--az-300); outline-offset: 2px; }

    .btn-primary {
        background: linear-gradient(135deg, var(--az-600) 0%, #1a5ec4 100%);
        color: #fff;
        box-shadow: 0 6px 20px rgba(29, 111, 219, 0.45);
    }
    .btn-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 10px 28px rgba(29, 111, 219, 0.55);
    }

    .btn-secondary {
        background: rgba(255,255,255,.08);
        color: rgba(255,255,255,.85);
        border: 1px solid rgba(255,255,255,.12);
    }
    .btn-secondary:hover {
        background: rgba(255,255,255,.13);
        transform: translateY(-1px);
    }

    .btn-ghost {
        background: transparent;
        color: var(--az-300);
        border: 1px solid rgba(29, 111, 219, 0.3);
    }
    .btn-ghost:hover {
        background: rgba(29, 111, 219, 0.1);
        transform: translateY(-1px);
    }

    /* ── Footer ─────────────────────────────────────────────── */
    .footer-note {
        font-size: 10px;
        font-weight: 600;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: rgba(255,255,255,.25);
        margin: 0;
    }

    /* ── Responsive ─────────────────────────────────────────── */
    @media (min-width: 480px) {
        .actions { flex-direction: row; flex-wrap: wrap; }
        .btn-primary { flex: 1 1 100%; }
        .btn-secondary, .btn-ghost { flex: 1; }
    }

    @media (max-width: 360px) {
        .card { padding: 32px 20px 24px; }
        .code-num { font-size: 72px; }
    }

    /* ── Reduced motion ─────────────────────────────────────── */
    @media (prefers-reduced-motion: reduce) {
        .particle, .blob-1, .blob-2, .accent-bar,
        .status-dot, .code-lines span { animation: none !important; }
    }
</style>
