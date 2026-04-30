<script lang="ts">
    import { useForm } from '@inertiajs/svelte';
    import { fade, fly } from 'svelte/transition';
    import AppHead from '../components/AppHead.svelte';

    const form = useForm({
        id_user: '',
        password: '',
    });

    let isSubmitting = $state(false);
    let showPassword = $state(false);

    const stats = [
        { value: '7', label: 'Unit Layanan', icon: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-2 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4' },
        { value: '24/7', label: 'Monitoring', icon: 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z' },
        { value: '100%', label: 'Data Real-time', icon: 'M13 10V3L4 14h7v7l9-11h-7z' },
    ];

    function handleSubmit(e: Event) {
        e.preventDefault();
        isSubmitting = true;
        form.post('/login', {
            onFinish: () => {
                isSubmitting = false;
            },
        });
    }
</script>

<AppHead 
    title="Executive Dashboard SIMRS"
    description="Platform pemantauan indikator kinerja rumah sakit secara real-time dan terintegrasi."
    ogImage="/images/og-image.png"
    />

<div class="login-root min-h-screen flex font-sans">

    <!-- ═══════════════════════════════════
         LEFT PANEL — Brand & Info
    ════════════════════════════════════ -->
    <div class="left-panel hidden lg:flex lg:w-[55%] relative flex-col overflow-hidden">
        <!-- Background Image -->
        <div class="absolute inset-0">
            <img
                src="/images/login-bg.png"
                alt="Hospital Command Center"
                class="w-full h-full object-cover"
                width="1024"
                height="768"
            />
            <!-- Layered gradient for readability -->
            <div class="absolute inset-0 bg-gradient-to-br from-azure-900/90 via-azure-800/80 to-azure-700/60"></div>
        </div>

        <!-- Decorative geometric grid -->
        <div class="absolute inset-0 opacity-[0.04]">
            <svg width="100%" height="100%" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse">
                        <path d="M 40 0 L 0 0 0 40" fill="none" stroke="white" stroke-width="1"/>
                    </pattern>
                </defs>
                <rect width="100%" height="100%" fill="url(#grid)" />
            </svg>
        </div>

        <!-- Content -->
        <div class="relative z-10 flex flex-col h-full p-12 xl:p-16">
            <!-- Hospital Logo / Brand -->
            <div class="flex items-center gap-3 mb-auto">
                <div class="w-12 h-12 bg-white rounded-xl flex items-center justify-center border border-white/20 shadow-lg p-1.5">
                    <img src="/images/logo-rs.png" alt="Logo RS" class="w-full h-full object-contain" />
                </div>
                <div>
                    <p class="text-white text-sm font-black tracking-wide">RS BHAYANGKARA NGAJUK</p>
                    <p class="text-white/50 text-[10px] font-medium uppercase tracking-widest">Sistem Informasi Manajemen RS</p>
                </div>
            </div>

            <!-- Hero Text -->
            <div class="mt-auto mb-16"
                 in:fly={{ x: -24, duration: 800, delay: 200 }}>
                <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-white/10 border border-white/20 rounded-full mb-6">
                    <span class="w-1.5 h-1.5 bg-emerald-400 rounded-full animate-pulse"></span>
                    <span class="text-white/70 text-[10px] font-black uppercase tracking-[0.2em]">Sistem Aktif</span>
                </div>

                <h1 class="text-4xl xl:text-5xl font-black text-white leading-[1.1] tracking-tight mb-5">
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-sky-300 to-blue-200">Dashboard Eksekutif</span>
                </h1>
                <p class="text-white/60 text-base leading-relaxed max-w-xs">
                    Platform pemantauan indikator kinerja rumah sakit secara <em class="not-italic text-white/90 font-semibold">real-time</em> dan terintegrasi.
                </p>
            </div>

            <!-- Stats Row -->
            <div class="grid grid-cols-3 gap-4"
                 in:fly={{ y: 20, duration: 800, delay: 400 }}>
                {#each stats as stat, i (i)}
                    <div class="bg-white/10 backdrop-blur-sm border border-white/15 rounded-2xl p-4 hover:bg-white/15 transition-colors">
                        <div class="w-8 h-8 bg-white/15 rounded-lg flex items-center justify-center mb-3">
                            <svg class="w-4 h-4 text-sky-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d={stat.icon} />
                            </svg>
                        </div>
                        <p class="text-2xl font-black text-white leading-none">{stat.value}</p>
                        <p class="text-white/50 text-[10px] font-medium mt-1 uppercase tracking-wider">{stat.label}</p>
                    </div>
                {/each}
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════
         RIGHT PANEL — Login Form
    ════════════════════════════════════ -->
    <div class="right-panel flex-1 flex flex-col items-center justify-center bg-[#f0f4f9] px-6 py-12 lg:px-16 xl:px-24 relative overflow-hidden">

        <!-- Subtle background accents -->
        <div class="absolute top-0 right-0 w-64 h-64 bg-azure-100 rounded-full blur-3xl opacity-60 -translate-y-1/2 translate-x-1/2 pointer-events-none"></div>
        <div class="absolute bottom-0 left-0 w-48 h-48 bg-sky-100 rounded-full blur-3xl opacity-50 translate-y-1/3 -translate-x-1/3 pointer-events-none"></div>

        <div class="w-full max-w-sm relative z-10"
             in:fly={{ y: 24, duration: 700, delay: 100 }}>

            <!-- Mobile-only logo -->
            <div class="lg:hidden flex items-center gap-3 mb-10">
                <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center shadow-sm p-1">
                    <img src="/images/logo-rs.png" alt="Logo RS" class="w-full h-full object-contain" />
                </div>
                <div>
                    <p class="text-slate-800 text-sm font-black">SIMRS Khanza</p>
                    <p class="text-slate-400 text-[10px] uppercase tracking-widest">Executive Dashboard</p>
                </div>
            </div>

            <!-- Heading -->
            <div class="mb-10">
                <h2 class="text-2xl xl:text-3xl font-black text-slate-900 tracking-tight mb-2">Selamat Datang</h2>
                <p class="text-slate-500 text-sm leading-relaxed">Masukkan kredensial SIMRS Anda untuk mengakses dashboard eksekutif.</p>
            </div>

            <!-- Error Alert -->
            {#if form.errors.id_user}
                <div
                    class="mb-6 bg-rose-50 border border-rose-200 text-rose-700 text-sm py-3.5 px-4 rounded-2xl flex items-start gap-3"
                    role="alert"
                    aria-live="assertive"
                    in:fade={{ duration: 200 }}
                >
                    <svg class="w-4 h-4 flex-shrink-0 mt-0.5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>{form.errors.id_user}</span>
                </div>
            {/if}

            <!-- Form -->
            <form onsubmit={handleSubmit} class="space-y-5" novalidate>
                <!-- User ID -->
                <div>
                    <label for="id_user" class="form-label">
                        ID User / NIP <span class="text-rose-500" aria-hidden="true">*</span>
                    </label>
                    <div class="input-wrapper">
                        <span class="input-icon">
                            <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </span>
                        <input
                            type="text"
                            id="id_user"
                            name="id_user"
                            autocomplete="username"
                            bind:value={form.id_user}
                            class="form-input"
                            placeholder="Masukkan ID User Anda"
                            required
                            aria-required="true"
                            aria-describedby={form.errors.id_user ? 'error-id' : undefined}
                        />
                    </div>
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="form-label">
                        Password <span class="text-rose-500" aria-hidden="true">*</span>
                    </label>
                    <div class="input-wrapper">
                        <span class="input-icon">
                            <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </span>
                        <input
                            type={showPassword ? 'text' : 'password'}
                            id="password"
                            name="password"
                            autocomplete="current-password"
                            bind:value={form.password}
                            class="form-input pr-12"
                            placeholder="••••••••"
                            required
                            aria-required="true"
                        />
                        <button
                            type="button"
                            class="absolute right-0 inset-y-0 px-4 text-slate-400 hover:text-azure-600 transition-colors"
                            onclick={() => showPassword = !showPassword}
                            aria-label={showPassword ? 'Sembunyikan password' : 'Tampilkan password'}
                        >
                            {#if showPassword}
                                <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                </svg>
                            {:else}
                                <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            {/if}
                        </button>
                    </div>
                </div>

                <!-- Submit Button -->
                <button
                    type="submit"
                    disabled={isSubmitting}
                    class="btn-primary"
                    aria-busy={isSubmitting}
                >
                    {#if isSubmitting}
                        <svg class="animate-spin w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>Memproses...</span>
                    {:else}
                        <span>Masuk ke Dashboard</span>
                        <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                        </svg>
                    {/if}
                </button>
            </form>

            <!-- Trust Badges -->
            <div class="mt-10 pt-8 border-t border-slate-200 flex flex-col items-center gap-4">
                <div class="flex items-center gap-5 text-slate-400">
                    <div class="trust-badge">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        <span>Enkripsi Aman</span>
                    </div>
                    <div class="w-1 h-1 rounded-full bg-slate-300"></div>
                    <div class="trust-badge">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        <span>Akses Terproteksi</span>
                    </div>
                </div>
                <p class="text-slate-400 text-[10px] text-center uppercase tracking-widest">
                    © {new Date().getFullYear()} IT RS Bhayangkara Ngajuk · Hak Cipta Dilindungi
                </p>
            </div>
        </div>
    </div>
</div>

<style>
    /* ─── Color Tokens ─────────────── */
    :root {
        --azure-50:  #eff6ff;
        --azure-100: #dbeafe;
        --azure-200: #bfdbfe;
        --azure-500: #3b82f6;
        --azure-600: #1d6fdb;
        --azure-700: #1e56b0;
        --azure-800: #1e3f8a;
        --azure-900: #172554;
    }

    .bg-azure-100 { background-color: var(--azure-100); }
    .bg-azure-600 { background-color: var(--azure-600); }
    .bg-azure-900 { background-color: var(--azure-900); }
    .text-azure-600 { color: var(--azure-600); }
    .from-azure-900\/90 { --tw-gradient-from: rgb(23 37 84 / 0.90); }
    .via-azure-800\/80  { --tw-gradient-via: rgb(30 63 138 / 0.80); }
    .to-azure-700\/60   { --tw-gradient-to: rgb(30 86 176 / 0.60); }

    /* ─── Form Labels ────────────────── */
    .form-label {
        display: block;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #475569;
        margin-bottom: 8px;
        margin-left: 2px;
    }

    /* ─── Input Wrapper ──────────────── */
    .input-wrapper {
        position: relative;
        display: flex;
        align-items: center;
    }
    .input-icon {
        position: absolute;
        left: 0;
        padding-left: 14px;
        color: #94a3b8;
        pointer-events: none;
        display: flex;
        align-items: center;
    }
    .input-icon svg { width: 18px; height: 18px; }

    /* ─── Form Input ─────────────────── */
    .form-input {
        width: 100%;
        background-color: #ffffff;
        border: 1.5px solid #e2e8f0;
        border-radius: 14px;
        padding: 14px 16px 14px 44px;
        font-size: 14px;
        font-weight: 500;
        color: #0f172a;
        outline: none;
        transition: border-color 0.15s, box-shadow 0.15s;
        box-shadow: 0 1px 2px rgba(0,0,0,0.04);
        min-height: 48px;
    }
    .form-input::placeholder { color: #94a3b8; font-weight: 400; }
    .form-input:focus {
        border-color: var(--azure-600);
        box-shadow: 0 0 0 3px rgb(29 111 219 / 0.12);
    }
    .form-input:hover:not(:focus) { border-color: #cbd5e1; }

    /* ─── Button Primary ─────────────── */
    .btn-primary {
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 15px 24px;
        background: linear-gradient(135deg, var(--azure-600) 0%, #1a5ec4 100%);
        color: white;
        font-size: 15px;
        font-weight: 800;
        border-radius: 14px;
        border: none;
        cursor: pointer;
        box-shadow: 0 4px 14px rgba(29, 111, 219, 0.35);
        transition: transform 0.15s, box-shadow 0.15s, opacity 0.15s;
        min-height: 52px;
        touch-action: manipulation;
    }
    .btn-primary:hover:not(:disabled) {
        transform: translateY(-1px);
        box-shadow: 0 6px 20px rgba(29, 111, 219, 0.45);
    }
    .btn-primary:active:not(:disabled) {
        transform: translateY(0) scale(0.98);
        box-shadow: 0 2px 8px rgba(29, 111, 219, 0.3);
    }
    .btn-primary:disabled {
        opacity: 0.65;
        cursor: not-allowed;
    }
    .btn-primary:focus-visible {
        outline: 3px solid var(--azure-200);
        outline-offset: 2px;
    }

    /* ─── Trust Badges ───────────────── */
    .trust-badge {
        display: flex;
        align-items: center;
        gap: 5px;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #94a3b8;
    }

    /* ─── Left Panel Gradient Utility ── */
    .bg-gradient-to-br { background-image: linear-gradient(to bottom right, var(--tw-gradient-from), var(--tw-gradient-via, transparent), var(--tw-gradient-to)); }
</style>
