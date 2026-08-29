<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    leagues: Object,
    myData: Object,
    stats: Object,
    message: String
});

// Funzione per formattare i numeri in Euro
const formatEuro = (value) => {
    if (value === undefined || value === null) return '0,00';
    return Number(value).toLocaleString('it-IT', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};
</script>

<template>
    <Head title="Finanze" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center w-full">
                <h2 class="font-black text-xl text-gray-800 uppercase italic tracking-tighter">Financial Terminal</h2>
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Market Open</span>
                </div>
            </div>
        </template>

        <div class="py-12 px-4">
            <div class="max-w-4xl mx-auto space-y-6">
                
                <!-- Pannello Principale -->
<div class="bg-indigo-950 p-10 rounded-[3rem] shadow-2xl relative border-b-8 border-indigo-800 text-center">
    <h4 class="text-[10px] font-black text-indigo-300 uppercase tracking-[0.4em] mb-4">Valore di Mercato Società (Stima Vendita)</h4>
    <p class="text-7xl font-black text-white font-mono tracking-tighter">
        {{ formatEuro(stats.valore_monetario) }}<span class="text-3xl text-indigo-400 ml-2">€</span>
    </p>
    <div class="mt-4 flex justify-center gap-4 text-[10px] font-bold uppercase">
        <span class="text-indigo-400">Investimento Iniziale: {{ stats.investimento }}€</span>
        <span class="text-green-400">Target Prize: {{ stats.target }}€</span>
    </div>
</div>

<!-- Griglia Analisi -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
    <!-- Valore Asset -->
    <div class="bg-white p-8 rounded-[2.5rem] shadow-xl border border-gray-100">
        <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Valore Asset (Giocatori)</h4>
        <p class="text-4xl font-black text-gray-900 font-mono">{{ formatEuro(stats.valore_asset) }} €</p>
        <p class="text-[9px] text-gray-400 mt-2 uppercase">Valore reale basato sulle quotazioni attuali</p>
    </div>

    <!-- Plusvalore Merito -->
    <div class="bg-white p-8 rounded-[2.5rem] shadow-xl border border-gray-100">
        <h4 class="text-[10px] font-black text-blue-400 uppercase tracking-widest mb-1">Plusvalore (Potenziale Profitto)</h4>
        <p class="text-4xl font-black text-blue-600 font-mono">+ {{ formatEuro(stats.plusvalore) }} €</p>
        <p class="text-[9px] text-gray-400 mt-2 uppercase">Basato sulla Winning Efficiency ({{ stats.winning_efficiency }}%)</p>
    </div>
</div>

                <!-- AVVISO -->
                <div v-if="message" class="text-center">
                    <p class="text-[10px] font-black text-blue-400 uppercase tracking-widest">{{ message }}</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Effetto ombra extra per il pannello principale */
.bg-indigo-950 {
    background: linear-gradient(145deg, #1e1b4b 0%, #312e81 100%);
}
</style>