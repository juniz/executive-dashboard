<script lang="ts">
    interface Props {
        class?: string;
        circle?: boolean;
    }
    let { class: className = '', circle = false }: Props = $props();
</script>

<div class="relative overflow-hidden bg-slate-200/60 {circle ? 'rounded-full' : 'rounded-lg'} {className}">
    <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/40 to-transparent translate-x-[-100%] animate-shimmer"></div>
</div>

<style>
    @keyframes shimmer {
        100% {
            transform: translateX(100%);
        }
    }
    .animate-shimmer {
        animation: shimmer 1.5s infinite;
        will-change: transform;
    }
</style>
