<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    leagues: Array, 
    myData: Object, 
    myPlayers: Array, 
    allParticipants: Array, 
    currentLineup: Object
});

// CALCOLO ANNI RIMANENTI (Logica identica alla pagina Rosa)
const remainingYears = computed(() => {
    if (!props.myData) return 0;
    const yearsUsed = props.myPlayers ? props.myPlayers.reduce((acc, p) => acc + p.contract_years, 0) : 0;
    return props.myData.years_budget - yearsUsed;
});

const isMarketActive = () => {
    if (!props.leagues || props.leagues.length === 0) return false;
    const league = props.leagues[0];
    if (!league.market_start_at || !league.market_end_at) return false;
    const now = new Date();
    const start = new Date(league.market_start_at);
    const end = new Date(league.market_end_at);
    return now >= start && now <= end;
};
</script>

<template>
    <Head title="Dashboard" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">La mia Scrivania</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <!-- CASO A: L'UTENTE È ISCRITTO A UNA LEGA -->
                <div v-if="leagues && leagues.length > 0" class="space-y-6">
                    
                    <!-- BOX RIEPILOGO SOCIETÀ -->
                    <div class="bg-white p-6 shadow rounded-xl border-l-8 border-indigo-600">
                        <div class="flex flex-col md:flex-row justify-between items-start gap-6">
                            <div class="flex-1">
                                <h3 class="text-3xl font-black uppercase text-gray-900">{{ myData?.team_name || 'La tua Squadra' }}</h3>
                                <p class="text-gray-500 font-bold italic">Lega: {{ leagues[0].name }}</p>
                                
                                <div class="mt-4 p-3 bg-blue-50 border border-blue-100 rounded-lg inline-block">
                                    <span class="text-[10px] text-blue-400 uppercase font-black block">Codice Invito:</span>
                                    <span class="text-xl font-mono font-black tracking-widest text-blue-700">
                                        {{ leagues[0].invite_code }}
                                    </span>
                                </div>

                                <div class="mt-4 flex items-center gap-2">
                                    <span class="w-3 h-3 rounded-full" :class="isMarketActive() ? 'bg-green-500 animate-pulse' : 'bg-red-500'"></span>
                                    <span class="text-xs font-bold uppercase" :class="isMarketActive() ? 'text-green-600' : 'text-red-600'">
                                        Mercato {{ isMarketActive() ? 'Aperto' : 'Chiuso' }}
                                    </span>
                                </div>
                            </div>

                            <!-- MINI DASHBOARD RISORSE -->
                            <div class="flex gap-4">
                                <div class="bg-green-50 p-4 rounded-xl text-center border border-green-200 w-32 shadow-sm">
                                    <p class="text-[10px] font-bold text-green-700 uppercase">Crediti</p>
                                    <p class="text-3xl font-black text-green-600 font-mono">{{ myData?.remaining_budget }}</p>
                                </div>
                                <!-- MODIFICATO: Mostra solo gli Anni Disponibili (rimanenti) -->
                                <div class="bg-blue-50 p-4 rounded-xl text-center border border-blue-200 w-32 shadow-sm">
                                    <p class="text-[10px] font-bold text-blue-700 uppercase">Anni Liberi</p>
                                    <p class="text-3xl font-black text-blue-600 font-mono" :class="remainingYears < 0 ? 'text-red-600' : 'text-blue-600'">
                                        {{ remainingYears }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- MENU NAVIGAZIONE RAPIDA -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6">
                        <a :href="route('lineup.index')" class="bg-indigo-600 text-white p-4 rounded-xl shadow-md text-center font-black uppercase text-[10px] hover:bg-indigo-700 transition">⚽ Campo</a>
                        <a :href="route('roster.index')" class="bg-white text-gray-700 p-4 rounded-xl shadow-md text-center font-black uppercase text-[10px] border hover:bg-gray-50 transition">📋 Rosa</a>
                        <a :href="route('market.auctions')" class="bg-blue-600 text-white p-4 rounded-xl shadow-md text-center font-black uppercase text-[10px] hover:bg-blue-700 transition">🛒 Mercato</a>
                        <a :href="route('teams.index')" class="bg-white text-gray-700 p-4 rounded-xl shadow-md text-center font-black uppercase text-[10px] border hover:bg-gray-50 transition">🏆 Squadre</a>
                    </div>

                    <!-- FORMAZIONE SCHIERATA (IL CAMPO) -->
                    <div v-if="currentLineup" class="bg-green-700 p-8 shadow-2xl rounded-2xl border-4 border-green-800 text-white relative overflow-hidden">
                        <h3 class="font-black uppercase mb-6 flex justify-between items-center relative z-10">
                            <span>Schieramento Domenicale</span>
                            <span class="text-xs bg-green-900/50 px-3 py-1 rounded-full border border-white/20">{{ currentLineup.module }}</span>
                        </h3>

                        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4 relative z-10">
                            <div v-for="detail in currentLineup.details" :key="detail.id" 
                                 class="p-3 rounded-lg bg-white/10 border border-white/10 backdrop-blur-sm text-center">
                                <p class="text-[10px] font-black text-green-300 uppercase">{{ detail.player.role }}</p>
                                <p class="text-sm font-bold truncate uppercase tracking-tighter">{{ detail.player.name }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CASO B: L'UTENTE NON HA ANCORA UNA LEGA -->
                <div v-else class="bg-white p-12 text-center shadow rounded-2xl border-2 border-dashed border-gray-200">
                    <h3 class="text-2xl font-black uppercase mb-4 text-gray-800">Benvenuto su FANTAgest!</h3>
                    <p class="text-gray-500 mb-8">Crea una nuova lega o unisciti a una esistente per iniziare.</p>
                    <div class="flex flex-col md:flex-row gap-4 justify-center">
                        <a :href="route('leagues.create')" class="bg-green-600 text-white px-8 py-4 rounded-xl font-bold uppercase hover:bg-green-700 transition">Crea Lega</a>
                        <a :href="route('leagues.join')" class="bg-orange-500 text-white px-8 py-4 rounded-xl font-bold uppercase hover:bg-orange-600 transition">Unisciti</a>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>