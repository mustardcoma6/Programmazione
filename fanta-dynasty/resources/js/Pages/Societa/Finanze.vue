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
                
                <!-- Box Principale: Market Cap -->
<div class="bg-indigo-950 p-10 rounded-[3rem] shadow-2xl relative border-b-8 border-indigo-800 text-center">
    <h4 class="text-[10px] font-black text-indigo-300 uppercase tracking-[0.4em] mb-4">Potenziale Vincita Stimata</h4>
    <p class="text-7xl font-black text-white font-mono tracking-tighter">
        {{ formatEuro(stats.valore_monetario) }}<span class="text-3xl text-indigo-400 ml-2">€</span>
    </p>
    <p class="text-[10px] text-indigo-400 mt-4 uppercase font-bold">Target Prize: {{ stats.premio_target }}€</p>
</div>

<!-- Griglia: Asset e Efficiency -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
    <!-- Asset Quality -->
    <div class="bg-white p-8 rounded-[2.5rem] shadow-xl border border-gray-100 text-center">
        <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Asset Quality (Rosa)</h4>
        <p class="text-5xl font-black text-gray-900 font-mono">{{ stats.asset_quality }}%</p>
        <p class="text-[9px] text-gray-400 mt-2 uppercase">Rispetto ai 25 Top del Listone</p>
    </div>

    <!-- Winning Efficiency -->
    <div class="bg-white p-8 rounded-[2.5rem] shadow-xl border border-gray-100 text-center">
        <h4 class="text-[10px] font-black text-blue-400 uppercase tracking-widest mb-1">Winning Efficiency</h4>
        <p class="text-5xl font-black text-blue-600 font-mono">{{ stats.winning_efficiency }}%</p>
        <p class="text-[9px] text-gray-400 mt-2 uppercase">Capacità di fare gol rispetto al 1°</p>
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