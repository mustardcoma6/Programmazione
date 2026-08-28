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
                
                <!-- 1. VALORE SOCIETARIO TOTALE (IL CUORE DEL PANNELLO) -->
                <div class="bg-indigo-950 p-10 rounded-[3rem] shadow-2xl relative overflow-hidden border-b-8 border-indigo-800 text-center">
                    <!-- Sfondo decorativo stile borsa -->
                    <div class="absolute top-0 right-0 p-8 opacity-10">
                        <span class="text-9xl font-black text-white italic">NYSE</span>
                    </div>
                    
                    <h4 class="relative z-10 text-[10px] font-black text-indigo-300 uppercase tracking-[0.4em] mb-4">Market Capitalization</h4>
                    <div class="relative z-10 flex flex-col items-center">
                        <p class="text-7xl md:text-8xl font-black text-white font-mono tracking-tighter">
                            {{ formatEuro(stats.valore_monetario) }}<span class="text-3xl text-indigo-400 ml-2">€</span>
                        </p>
                        <div class="mt-4 px-4 py-1 bg-green-500/20 border border-green-500/50 rounded-full">
                            <span class="text-green-400 text-xs font-black uppercase tracking-widest">Ticker: {{ myData?.team_name }}</span>
                        </div>
                    </div>
                </div>

                <!-- 2. GRIGLIA SOTTOSTANTE (ASSET E PERFORMANCE) -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    
                    <!-- VALORE ROSA (ASSET) -->
                    <div class="bg-white p-8 rounded-[2.5rem] shadow-xl border border-gray-100 flex flex-col justify-between">
                        <div>
                            <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Squad Asset Value</h4>
                            <p class="text-4xl font-black text-gray-900 font-mono italic">
                                {{ formatEuro(stats.valore_asset) }} <span class="text-sm">€</span>
                            </p>
                        </div>
                        <div class="mt-6 pt-4 border-t border-gray-50 flex justify-between items-end">
                            <span class="text-[10px] text-gray-400 font-bold uppercase">Based on Quotations</span>
                            <span class="text-xs font-black text-gray-800">{{ stats.somma_quotations }} CR</span>
                        </div>
                    </div>

                    <!-- INDEX PERFORMANCE -->
                    <div class="bg-white p-8 rounded-[2.5rem] shadow-xl border border-gray-100 flex flex-col justify-between">
                        <div>
                            <h4 class="text-[10px] font-black text-blue-400 uppercase tracking-widest mb-1">Performance Index</h4>
                            <p class="text-5xl font-black text-blue-600 font-mono">
                                <span class="text-2xl italic">x</span> {{ stats.index_performance }}
                            </p>
                        </div>
                        <div class="mt-6 pt-4 border-t border-gray-50 flex justify-between items-end">
                            <span class="text-[10px] text-gray-400 font-bold uppercase">Efficiency Rating</span>
                            <span class="text-xs font-black text-blue-600">{{ stats.gol_prodotti }} GOALS/AVG</span>
                        </div>
                    </div>
                </div>

                <!-- 3. PARAMETRI TECNICI (STILE TABELLA BORSA) -->
                <div class="bg-white rounded-[2.5rem] shadow-sm border border-gray-100 overflow-hidden">
                    <div class="bg-gray-50 px-8 py-4 border-b border-gray-100">
                        <h3 class="font-black text-gray-400 text-[10px] uppercase tracking-[0.2em]">Technical Analysis Parameters</h3>
                    </div>
                    <div class="p-8 grid grid-cols-2 md:grid-cols-4 gap-8">
                        <div class="space-y-1">
                            <p class="text-[9px] font-black text-gray-400 uppercase">Avg Pts</p>
                            <p class="text-xl font-black text-gray-800 font-mono">{{ stats.media_punti }}</p>
                        </div>
                        <div class="space-y-1">
                            <p class="text-[9px] font-black text-gray-400 uppercase">League Avg Goals</p>
                            <p class="text-xl font-black text-gray-800 font-mono">{{ stats.media_gol_lega }}</p>
                        </div>
                        <div class="space-y-1">
                            <p class="text-[9px] font-black text-gray-400 uppercase">Credit Cost</p>
                            <p class="text-xl font-black text-gray-800 font-mono">0,26 €</p>
                        </div>
                        <div class="space-y-1">
                            <p class="text-[9px] font-black text-gray-400 uppercase">Goal Threshold</p>
                            <p class="text-sm font-black text-gray-800">66/70/75/80</p>
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