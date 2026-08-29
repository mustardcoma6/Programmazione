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
                
                <!-- VALORE SOCIETARIO (MARKET CAP) -->
<div class="bg-indigo-950 p-10 rounded-[3.5rem] shadow-2xl border-b-8 border-indigo-800 text-center relative overflow-hidden">
    <h4 class="text-[10px] font-black text-indigo-300 uppercase tracking-[0.4em] mb-4">Valore di Mercato (Prezzo di Vendita)</h4>
    <p class="text-7xl md:text-8xl font-black text-white font-mono tracking-tighter">
        {{ formatEuro(stats?.valore_monetario) }}<span class="text-3xl text-indigo-400 ml-2">€</span>
    </p>
    <div class="mt-6 inline-block px-6 py-2 bg-green-500/20 border border-green-500/50 rounded-full">
        <span class="text-green-400 text-xs font-black uppercase tracking-widest">Capitale Sociale: 130€ + Performance Premium</span>
    </div>
</div>

<!-- INDICI DI PERFORMANCE -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
    <!-- Asset Quality -->
    <div class="bg-white p-8 rounded-[2.5rem] shadow-xl border border-gray-100">
        <div class="flex justify-between items-start mb-4">
            <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-widest italic">Asset Quality Index</h4>
            <span class="text-xs font-black text-gray-900">{{ stats?.asset_quality_perc }}%</span>
        </div>
        <p class="text-[9px] text-gray-400 mb-4 uppercase leading-tight">Potenza della tua rosa rispetto ai 25 Top del Listone ({{ stats?.benchmark_val }} cr)</p>
        <div class="w-full bg-gray-100 h-2 rounded-full overflow-hidden">
            <div class="bg-indigo-600 h-full" :style="{ width: stats?.asset_quality_perc + '%' }"></div>
        </div>
    </div>

    <!-- Winning Efficiency -->
    <div class="bg-white p-8 rounded-[2.5rem] shadow-xl border border-gray-100">
        <div class="flex justify-between items-start mb-4">
            <h4 class="text-[10px] font-black text-blue-500 uppercase tracking-widest italic">Winning Efficiency</h4>
            <span class="text-xs font-black text-blue-600">{{ stats?.winning_efficiency_perc }}%</span>
        </div>
        <p class="text-[9px] text-gray-400 mb-4 uppercase leading-tight">Probabilità di vittoria basata sui tuoi gol ({{ stats?.tuoi_gol }}) rispetto al Leader ({{ stats?.leader_gol }})</p>
        <div class="w-full bg-blue-50 h-2 rounded-full overflow-hidden">
            <div class="bg-blue-500 h-full" :style="{ width: stats?.winning_efficiency_perc + '%' }"></div>
        </div>
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