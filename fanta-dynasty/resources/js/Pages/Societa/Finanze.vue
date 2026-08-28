<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    leagues: Object,
    myData: Object,
    stats: Object,
    message: String
});

// Funzione sicura per formattare i numeri in Euro
const formatEuro = (value) => {
    if (value === undefined || value === null) return '0,00';
    return Number(value).toLocaleString('it-IT', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};
</script>

<template>
    <Head title="Finanze" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-bold text-xl text-gray-800 uppercase italic tracking-tighter">💰 Bilancio Societario</h2>
        </template>

        <div class="py-12 px-4">
            <div class="max-w-7xl mx-auto space-y-8">
                
                <!-- AVVISO SE MANCANO DATI -->
                <div v-if="message" class="bg-blue-50 border-l-4 border-blue-400 p-6 rounded-2xl mb-8">
                    <p class="text-blue-700 font-bold uppercase text-[10px] tracking-widest">{{ message }}</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- VALORE ROSA -->
                    <div class="bg-white p-8 rounded-[2.5rem] shadow-xl border border-gray-100">
                        <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Valore Asset (Rosa)</h4>
                        <p class="text-4xl font-black text-gray-900 font-mono">
                            {{ formatEuro(stats.valore_asset) }} <span class="text-sm">€</span>
                        </p>
                    </div>

                    <!-- INDEX PERFORMANCE -->
                    <div class="bg-white p-8 rounded-[2.5rem] shadow-xl border-t-8 border-blue-600">
                        <h4 class="text-[10px] font-black text-blue-400 uppercase tracking-widest mb-2">Index Performance</h4>
                        <p class="text-5xl font-black text-blue-600 font-mono">x {{ stats.index_performance }}</p>
                    </div>

                    <!-- VALORE SOCIETARIO -->
                    <div class="bg-indigo-900 p-8 rounded-[2.5rem] shadow-2xl text-white">
                        <h4 class="text-[10px] font-black text-indigo-300 uppercase tracking-widest mb-2">Valore Societario Totale</h4>
                        <p class="text-5xl font-black text-green-400 font-mono">
                            {{ formatEuro(stats.valore_monetario) }} <span class="text-sm text-white">€</span>
                        </p>
                    </div>
                </div>

                <!-- PARAMETRI -->
                <div class="bg-white p-8 rounded-[2.5rem] shadow-sm border border-gray-100">
                    <h3 class="font-black text-gray-800 uppercase italic mb-6">Parametri Tecnici</h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
                        <div>
                            <p class="text-[9px] font-black text-gray-400 uppercase">Tua Media Punti</p>
                            <p class="text-xl font-black text-gray-800">{{ stats.media_punti }}</p>
                        </div>
                        <div>
                            <p class="text-[9px] font-black text-gray-400 uppercase">Gol Prodotti</p>
                            <p class="text-xl font-black text-gray-800">{{ stats.gol_prodotti }}</p>
                        </div>
                        <div>
                            <p class="text-[9px] font-black text-gray-400 uppercase">Media Gol Lega</p>
                            <p class="text-xl font-black text-gray-800">{{ stats.media_gol_lega }}</p>
                        </div>
                        <div>
                            <p class="text-[9px] font-black text-gray-400 uppercase">Costo Credito</p>
                            <p class="text-xl font-black text-gray-800">0,26 €</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>