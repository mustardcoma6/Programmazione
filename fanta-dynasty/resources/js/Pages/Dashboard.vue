<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    leagues: Array, 
    myData: Object, 
    myPlayers: Array, 
    allParticipants: Array, 
    currentLineup: Object,
    isMarketOpen: Boolean
});
</script>

<template>
    <Head title="Home" />
    <AuthenticatedLayout>
        <div class="py-10">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
                
                <!-- SALUTO PRESIDENTE (SPOSTATO NEL CORPO) -->
                <div class="flex flex-col mb-4">
                    <h1 class="text-4xl font-black text-gray-900 tracking-tighter italic">Benvenuto Pres!</h1>
                    <p class="text-xs text-blue-600 font-bold uppercase tracking-[0.3em] mt-1 ml-1">Controllo Direzionale</p>
                </div>

                <!-- SE SEI IN UNA LEGA -->
                <div v-if="leagues && leagues.length > 0" class="space-y-8">
                    
                    <!-- 1. BANNER STATUS E RISORSE -->
                    <div class="bg-white p-8 shadow-xl rounded-3xl border-l-[12px] border-indigo-600">
                        <div class="flex flex-col lg:flex-row justify-between items-center gap-8">
                            <div class="flex-1 text-center lg:text-left">
                                <h3 class="text-4xl font-black uppercase text-gray-900 leading-none mb-2">
                                    {{ myData?.team_name || 'La tua Squadra' }}
                                </h3>
                                <p class="text-gray-400 font-bold text-lg uppercase tracking-tighter">Lega: {{ leagues[0].name }}</p>
                                
                                <div class="mt-4 flex items-center justify-center lg:justify-start gap-3">
                                    <div class="px-4 py-1.5 rounded-full text-xs font-black uppercase flex items-center gap-2 border" 
                                         :class="isMarketOpen ? 'bg-green-50 text-green-600 border-green-200' : 'bg-red-50 text-red-600 border-red-200'">
                                        <span class="w-2 h-2 rounded-full" :class="isMarketOpen ? 'bg-green-500 animate-pulse' : 'bg-red-500'"></span>
                                        Mercato {{ isMarketOpen ? 'Aperto' : 'Chiuso' }}
                                    </div>
                                    <span class="text-[10px] font-mono text-gray-300 uppercase tracking-widest">Codice: {{ leagues[0].invite_code }}</span>
                                </div>
                            </div>

                            <!-- BOX RISORSE (VALORI TOTALI) -->
                            <div class="flex gap-6">
                                <div class="bg-gray-900 p-6 rounded-2xl text-center shadow-lg w-40 transform hover:scale-105 transition">
                                    <p class="text-[10px] font-black text-green-400 uppercase tracking-widest mb-1">Crediti</p>
                                    <p class="text-4xl font-black text-white font-mono">{{ myData?.remaining_budget }}</p>
                                </div>
                                <div class="bg-indigo-600 p-6 rounded-2xl text-center shadow-lg w-40 transform hover:scale-105 transition">
                                    <p class="text-[10px] font-black text-indigo-200 uppercase tracking-widest mb-1">Budget Anni</p>
                                    <p class="text-4xl font-black text-white font-mono">{{ myData?.years_budget }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. NAVIGAZIONE -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                        <a :href="route('players.index')" class="group bg-white p-6 rounded-2xl shadow-md border-b-4 border-gray-200 hover:border-blue-500 transition-all text-center">
                            <span class="text-4xl block mb-2 group-hover:scale-110 transition">📋</span>
                            <span class="font-black uppercase text-[11px] text-gray-500 group-hover:text-blue-600">Svincolati</span>
                        </a>

                        <a :href="route('roster.index')" class="group bg-white p-6 rounded-2xl shadow-md border-b-4 border-gray-200 hover:border-indigo-500 transition-all text-center">
                            <span class="text-4xl block mb-2 group-hover:scale-110 transition">🧥</span>
                            <span class="font-black uppercase text-[11px] text-gray-500 group-hover:text-indigo-600">Gestione Rosa</span>
                        </a>

                        <a :href="route('market.auctions')" class="group bg-blue-600 p-6 rounded-2xl shadow-lg border-b-4 border-blue-800 hover:bg-blue-700 transition-all text-center">
                            <span class="text-4xl block mb-2 group-hover:scale-110 transition">🛒</span>
                            <span class="font-black uppercase text-[11px] text-blue-100">Calciomercato</span>
                        </a>

                        <a :href="route('teams.index')" class="group bg-white p-6 rounded-2xl shadow-md border-b-4 border-gray-200 hover:border-orange-500 transition-all text-center">
                            <span class="text-4xl block mb-2 group-hover:scale-110 transition">🏆</span>
                            <span class="font-black uppercase text-[11px] text-gray-500 group-hover:text-orange-600">Altre Squadre</span>
                        </a>
                    </div>

                    <!-- 3. FORMAZIONE -->
                    <div v-if="currentLineup" class="bg-green-700 p-8 shadow-2xl rounded-2xl border-[6px] border-green-800 text-white relative overflow-hidden">
                        <div class="absolute inset-0 opacity-10 pointer-events-none">
                            <div class="w-full h-full border-2 border-white rounded-full scale-150 -translate-y-1/2"></div>
                        </div>
                        <div class="flex justify-between items-center mb-8 relative z-10">
                            <h3 class="font-black uppercase text-xl">Schieramento Domenicale <span class="text-green-300 ml-2">{{ currentLineup.module }}</span></h3>
                            <a :href="route('lineup.index')" class="bg-green-900/50 hover:bg-green-900 px-4 py-2 rounded-xl text-xs font-bold transition border border-white/10 uppercase">Modifica Campo</a>
                        </div>
                        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4 relative z-10">
                            <div v-for="detail in currentLineup.details" :key="detail.id" class="p-4 rounded-xl bg-white/10 border border-white/10 backdrop-blur-md text-center">
                                <p class="text-[10px] font-black text-green-300 uppercase mb-1">{{ detail.player.role }}</p>
                                <p class="text-sm font-black truncate uppercase tracking-tighter">{{ detail.player.name }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CASO B: SENZA LEGA -->
                <div v-else class="bg-white p-16 text-center shadow-2xl rounded-3xl border-2 border-dashed border-indigo-200">
                    <div class="max-w-md mx-auto">
                        <span class="text-7xl mb-6 block animate-bounce">⚽</span>
                        <h3 class="text-3xl font-black text-gray-900 uppercase">Benvenuto Pres!</h3>
                        <p class="text-gray-500 mt-4 mb-10 text-lg">Inizia la tua carriera. Crea una lega o unisciti a una esistente.</p>
                        <div class="flex flex-col gap-4">
                            <a :href="route('leagues.create')" class="bg-green-600 text-white py-5 rounded-2xl font-black uppercase tracking-widest hover:bg-green-700 transition shadow-lg">➕ Crea una Nuova Lega</a>
                            <a :href="route('leagues.join')" class="bg-orange-500 text-white py-5 rounded-2xl font-black uppercase tracking-widest hover:bg-orange-600 transition shadow-lg">🤝 Unisciti a una Lega</a>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>