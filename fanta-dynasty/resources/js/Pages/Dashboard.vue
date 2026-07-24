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

const yearsUsed = computed(() => {
    return props.myPlayers ? props.myPlayers.reduce((acc, p) => acc + p.contract_years, 0) : 0;
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
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Home Squadra</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <!-- SE SEI IN UNA LEGA -->
                <div v-if="leagues && leagues.length > 0">
                    
                    <!-- BOX INFO SOCIETÀ -->
                    <div class="bg-white p-6 shadow rounded-xl border-l-8 border-indigo-600">
                        <div class="flex flex-col md:flex-row justify-between items-start gap-6">
                            <div class="flex-1">
                                <h3 class="text-3xl font-black uppercase text-gray-900">{{ myData?.team_name || 'La tua Squadra' }}</h3>
                                <p class="text-gray-500 font-bold italic">Lega: {{ leagues[0].name }}</p>
                                
                                <!-- CODICE INVITO (Spostato e reso più visibile) -->
                                <div class="mt-4 p-3 bg-blue-50 border border-blue-100 rounded-lg">
                                    <span class="text-[10px] text-blue-400 uppercase font-black block">Codice Segreto per invitare amici:</span>
                                    <span class="text-2xl font-mono font-black tracking-[0.2em] text-blue-700">
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
                            
                            <div class="flex gap-4">
                                <div class="bg-green-50 p-4 rounded-xl text-center border border-green-200 w-32 shadow-sm">
                                    <p class="text-[10px] font-bold text-green-700 uppercase">Crediti</p>
                                    <p class="text-3xl font-black text-green-600 font-mono">{{ myData?.remaining_budget }}</p>
                                </div>
                                <div class="bg-blue-50 p-4 rounded-xl text-center border border-blue-200 w-32 shadow-sm">
                                    <p class="text-[10px] font-bold text-blue-700 uppercase">Anni Rosa</p>
                                    <p class="text-3xl font-black text-blue-600 font-mono">{{ yearsUsed }}/{{ myData?.years_budget }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- MENU -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6">
                        <a :href="route('lineup.index')" class="bg-indigo-600 text-white p-4 rounded-xl shadow-md text-center font-black uppercase text-[10px] hover:bg-indigo-700 transition">⚽ Campo</a>
                        <a :href="route('roster.index')" class="bg-white text-gray-700 p-4 rounded-xl shadow-md text-center font-black uppercase text-[10px] border hover:bg-gray-50 transition">📋 Rosa</a>
                        <a :href="route('market.auctions')" class="bg-blue-600 text-white p-4 rounded-xl shadow-md text-center font-black uppercase text-[10px] hover:bg-blue-700 transition">🛒 Mercato</a>
                        <a :href="route('teams.index')" class="bg-white text-gray-700 p-4 rounded-xl shadow-md text-center font-black uppercase text-[10px] border hover:bg-gray-50 transition">🏆 Squadre</a>
                    </div>
                </div>

                <!-- SE NON SEI IN UNA LEGA -->
                <div v-else class="bg-white p-12 text-center shadow rounded-2xl border-2 border-dashed border-gray-200">
                    <h3 class="text-2xl font-black uppercase mb-4">Benvenuto!</h3>
                    <p class="text-gray-500 mb-8">Non sei in nessuna lega. Inizia ora:</p>
                    <div class="flex flex-col md:flex-row gap-4 justify-center">
                        <a :href="route('leagues.create')" class="bg-green-600 text-white px-8 py-4 rounded-xl font-bold uppercase">Crea Lega</a>
                        <a :href="route('leagues.join')" class="bg-orange-500 text-white px-8 py-4 rounded-xl font-bold uppercase">Unisciti</a>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>