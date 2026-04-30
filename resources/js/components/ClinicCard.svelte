<script lang="ts">
    import { cn } from '@/lib/utils';
    import type { Clinic } from '../models/ClinicManager.svelte';

    interface Props {
        clinic: Clinic;
    }

    let { clinic }: Props = $props();

    const statusColors = {
        active: 'bg-emerald-500',
        break: 'bg-amber-500',
        closed: 'bg-slate-400'
    };
    const statusLabels = {
        active: 'Aktif',
        break: 'Istirahat',
        closed: 'Tutup'
    };
</script>

<div class="card-solid p-6 rounded-xl flex flex-col gap-5 border-t-4" 
     style="border-top-color: {clinic.status === 'active' ? 'var(--color-primary)' : '#cbd5e1'}">
    
    <div class="flex justify-between items-start">
        <div>
            <h4 class="text-lg font-extrabold text-slate-900 leading-tight mb-1">{clinic.name}</h4>
            <div class="flex items-center gap-2">
                <span class="header-pill !lowercase !px-2">Spesialis</span>
                <p class="text-sm text-slate-500 font-bold">{clinic.doctor}</p>
            </div>
        </div>
        <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-slate-50 border border-slate-100 shadow-inner">
            <span class={cn("w-2.5 h-2.5 rounded-full shadow-sm", statusColors[clinic.status])}></span>
            <span class="text-[11px] font-extrabold uppercase tracking-widest text-slate-600">{statusLabels[clinic.status]}</span>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div class="bg-primary-light/30 p-4 rounded-lg flex flex-col items-center justify-center text-center">
            <p class="text-[10px] uppercase font-extrabold text-primary mb-1 tracking-wider">Status Antrean</p>
            <p class="text-2xl font-black text-primary-deep">{clinic.queueCount}</p>
        </div>
        <div class="bg-slate-50 p-4 rounded-lg flex flex-col items-center justify-center text-center">
            <p class="text-[10px] uppercase font-extrabold text-slate-400 mb-1 tracking-wider">Waktu Tunggu</p>
            <p class="text-2xl font-black text-slate-800">{clinic.avgWaitTime}<span class="text-xs font-bold ml-1">mnt</span></p>
        </div>
    </div>

    <button class="w-full py-3 text-sm font-extrabold text-primary hover:bg-primary hover:text-white rounded-lg transition-all border-2 border-primary/10 hover:border-primary active:scale-[0.98]">
        Monitor Status Langsung
    </button>
</div>

