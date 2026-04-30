<script lang="ts">
    import { Link, router } from '@inertiajs/svelte';
    import AppHead from '../components/AppHead.svelte';

    const dashboards = [
        {
            title: 'Rawat Jalan',
            description: 'Monitoring kunjungan, poliklinik, dan performa dokter.',
            href: '/dashboard/rawat-jalan',
            icon: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01',
            color: 'from-blue-500 to-indigo-600',
            bg: 'bg-blue-50'
        },
        {
            title: 'Rawat Inap',
            description: 'Indikator BOR, ALOS, BTO, TOI, dan hunian bangsal.',
            href: '/dashboard/rawat-inap',
            icon: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
            color: 'from-emerald-500 to-teal-600',
            bg: 'bg-emerald-50'
        },
        {
            title: 'IGD',
            description: 'Monitoring kegawatdaruratan dan waktu respon.',
            href: '/dashboard/igd',
            icon: 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z',
            color: 'from-rose-500 to-pink-600',
            bg: 'bg-rose-50'
        },
        {
            title: 'Laboratorium',
            description: 'Statistik pemeriksaan lab dan tren kunjungan.',
            href: '/dashboard/lab',
            icon: 'M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.628.293a2 2 0 01-1.647 0l-.646-.303a6 6 0 00-3.158-.366l-1.785.357a2 2 0 00-1.687 1.967v5.116a2 2 0 002.447 1.92l1.996-.449a6 6 0 012.83-.092l.648.162a2 2 0 001.242 0l.648-.162a6 6 0 012.83.092l1.996.449a2 2 0 002.447-1.92v-5.116z',
            color: 'from-amber-500 to-orange-600',
            bg: 'bg-amber-50'
        },
        {
            title: 'Radiologi',
            description: 'Ringkasan pemeriksaan radiologi dan modalitas.',
            href: '/dashboard/radiologi',
            icon: 'M12 4v1m6 4h1m-7 7h1m-7-7H4m12 12c0-4.418-3.582-8-8-8s-8 3.582-8 8M0 21h24',
            color: 'from-violet-500 to-purple-600',
            bg: 'bg-violet-50'
        },
        {
            title: 'Hemodialisa',
            description: 'Monitoring layanan cuci darah dan jadwal pasien.',
            href: '/dashboard/hemodialisa',
            icon: 'M13 10V3L4 14h7v7l9-11h-7z',
            color: 'from-cyan-500 to-blue-600',
            bg: 'bg-cyan-50'
        },
        {
            title: 'Rekam Medis',
            description: 'Kelengkapan berkas dan indikator mutu rekam medis.',
            href: '/dashboard/rekam-medis',
            icon: 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253',
            color: 'from-slate-700 to-slate-900',
            bg: 'bg-slate-100'
        }
    ];
</script>

<AppHead title="Executive Dashboard - Menu" />

<div class="min-h-screen bg-[#f8fafc] flex flex-col font-sans overflow-x-hidden">
    <!-- Decorative Background Elements -->
    <div class="fixed inset-0 pointer-events-none overflow-hidden z-0">
        <div class="absolute -top-[10%] -right-[10%] w-[40%] h-[40%] bg-blue-100/50 blur-[120px] rounded-full"></div>
        <div class="absolute top-[20%] -left-[5%] w-[30%] h-[30%] bg-emerald-100/40 blur-[100px] rounded-full"></div>
        <div class="absolute bottom-[10%] right-[20%] w-[25%] h-[25%] bg-purple-100/30 blur-[80px] rounded-full"></div>
    </div>

    <!-- Floating Logout Button (High Z-Index) -->
    <div class="fixed top-4 right-4 sm:top-8 sm:right-8 z-[100]">
        <button 
            onclick={() => router.post('/logout')}
            class="bg-white/80 backdrop-blur-md border border-white/40 hover:bg-white text-slate-600 hover:text-rose-600 px-3 py-2 sm:px-5 sm:py-2.5 rounded-xl sm:rounded-2xl shadow-xl transition-all flex items-center gap-2 font-black text-xs uppercase tracking-wider active:scale-95 group"
        >
            <svg class="w-4 h-4 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
            </svg>
            <span class="hidden sm:inline">Keluar Sesi</span>
        </button>
    </div>

    <!-- Header -->
    <header class="relative z-10 pt-12 pb-8 px-6 text-center">

        <!-- Hospital Logo -->
        <div class="mb-6 flex justify-center animate-fade-in-down">
            <div class="w-16 h-16 bg-white p-2 rounded-[1.25rem] shadow-xl border border-white/60">
                <img src="/images/logo-rs.png" alt="Logo RS" class="w-full h-full object-contain" />
            </div>
        </div>

        <h1 class="text-4xl md:text-5xl font-black text-slate-900 tracking-tight mb-4 animate-fade-in-up">
            Dashboard <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-indigo-600">Eksekutif</span>
        </h1>
    </header>

    <!-- Main Content -->
    <main class="relative z-10 flex-1 max-w-7xl w-full mx-auto px-6 pb-20">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {#each dashboards as item, i (i)}
                <Link 
                    href={item.href}
                    class="group relative bg-white/70 backdrop-blur-sm rounded-[2rem] p-8 border border-white/60 shadow-[0_8px_30px_rgb(0,0,0,0.04)] hover:shadow-[0_20px_50px_rgba(0,0,0,0.1)] transition-all duration-500 hover:-translate-y-2 animate-fade-in-up overflow-hidden"
                    style="animation-delay: {200 + i * 50}ms"
                >
                    <!-- Hover Effect Background -->
                    <div class="absolute inset-0 bg-gradient-to-br {item.color} opacity-0 group-hover:opacity-[0.03] transition-opacity duration-500"></div>
                    
                    <div class="flex flex-col h-full relative z-10">
                        <div class="flex items-center justify-between mb-8">
                            <div class="w-14 h-14 {item.bg} rounded-2xl flex items-center justify-center group-hover:scale-110 transition-transform duration-500 shadow-sm">
                                <svg class="w-7 h-7 text-transparent bg-clip-text bg-gradient-to-br {item.color}" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="stroke: url(#grad-{i})">
                                    <defs>
                                        <linearGradient id="grad-{i}" x1="0%" y1="0%" x2="100%" y2="100%">
                                            <stop offset="0%" class="stop-color-start" />
                                            <stop offset="100%" class="stop-color-end" />
                                        </linearGradient>
                                    </defs>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d={item.icon} />
                                </svg>
                            </div>
                            <div class="p-2 rounded-full bg-slate-50 group-hover:bg-white transition-colors duration-300">
                                <svg class="w-5 h-5 text-slate-300 group-hover:text-blue-500 transform group-hover:translate-x-1 transition-all duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                </svg>
                            </div>
                        </div>

                        <h3 class="text-xl font-black text-slate-900 mb-3 group-hover:text-blue-600 transition-colors duration-300 tracking-tight">
                            {item.title}
                        </h3>
                        <p class="text-slate-500 text-sm leading-relaxed mb-6 flex-1">
                            {item.description}
                        </p>

                        <div class="flex items-center gap-2">
                            <span class="text-[10px] font-black uppercase tracking-wider text-slate-400 group-hover:text-slate-600 transition-colors">Buka Dashboard</span>
                            <div class="h-px flex-1 bg-slate-100 group-hover:bg-slate-200 transition-colors"></div>
                        </div>
                    </div>
                </Link>
            {/each}
        </div>

    </main>

    <!-- Footer -->
    <footer class="relative z-10 py-8 px-6 text-center border-t border-slate-200/50 bg-white/30 backdrop-blur-sm">
        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em]">
            SIMRS Khanza · Executive Insight v2.0
        </p>
    </footer>
</div>

<style>
    :global(body) {
        background-color: #f8fafc;
    }

    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    @keyframes fadeInDown {
        from { opacity: 0; transform: translateY(-20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .animate-fade-in-up {
        animation: fadeInUp 0.8s cubic-bezier(0.2, 0.8, 0.2, 1) forwards;
        opacity: 0;
    }
    .animate-fade-in-down {
        animation: fadeInDown 0.8s cubic-bezier(0.2, 0.8, 0.2, 1) forwards;
        opacity: 0;
    }

    /* SVG Gradient stop color hacks for Tailwind */
    .stop-color-start { stop-color: #3b82f6; }
    .stop-color-end { stop-color: #2563eb; }

    /* Custom scrollbar for premium feel */
    ::-webkit-scrollbar {
        width: 8px;
    }
    ::-webkit-scrollbar-track {
        background: transparent;
    }
    ::-webkit-scrollbar-thumb {
        background: #e2e8f0;
        border-radius: 10px;
    }
    ::-webkit-scrollbar-thumb:hover {
        background: #cbd5e1;
    }
</style>
