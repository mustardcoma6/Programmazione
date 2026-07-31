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

// Funzione per lo stato del mercato
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
                
                <div v-if="leagues && leagues.length > 0" class="space-y-6">
                    <!-- BOX RIEPILOGO -->
                    <div class="bg-white p-6 shadow rounded-xl border-l-8 border-indigo-600">
                        <div class="flex flex-col md:flex-row justify-between items-start gap-6">
                            <div class="flex-1">
                                <h3 class="text-3xl font-black uppercase text-gray-900">{{ myData?.team_name || 'La tua Squadra' }}</h3>
                                <p class="text-gray-500 font-bold italic text-sm">Lega: {{ leagues[0].name }}</p>
                                
                                <div class="mt-4 p-3 bg-blue-50 border border-blue-100 rounded-lg inline-block">
                                    <span class="text-[10px] text-blue-400 uppercase font-black block">Codice Invito:</span>
                                    <span class="text-xl font-mono font-black tracking-widest text-blue-700">{{ leagues[0].invite_code }}</span>
                                </div>
                            </div>

                            <!-- BANNER RISORSE: Mostrano i valori reali inseriti dall'Admin -->
                            <div class="flex gap-4">
                                <div class="bg-green-50 p-4 rounded-xl text-center border border-green-200 w-32 shadow-sm">
                                    <p class="text-[10px] font-bold text-green-700 uppercase">Crediti</p>
                                    <p class="text-3xl font-black text-green-600 font-mono">{{ myData?.remaining_budget }}</p>
                                </div>
                                <div class="bg-blue-50 p-4 rounded-xl text-center border border-blue-200 w-32 shadow-sm">
                                    <p class="text-[10px] font-bold text-blue-700 uppercase">Budget Anni</p>
                                    <p class="text-3xl font-black text-blue-600 font-mono">{{ myData?.years_budget }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- MENU NAVIGAZIONE -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6">
                        <a :href="route('lineup.index')" class="bg-indigo-600 text-white p-4 rounded-xl shadow-md text-center font-black uppercase text-[10px]">⚽ Campo</a>
                        <a :href="route('roster.index')" class="bg-white text-gray-700 p-4 rounded-xl shadow-md text-center font-black uppercase text-[10px] border">📋 Rosa</a>
                        <a :href="route('market.auctions')" class="bg-blue-600 text-white p-4 rounded-xl shadow-md text-center font-black uppercase text-[10px]">🛒 Mercato</a>
                        <a :href="route('teams.index')" class="bg-white text-gray-700 p-4 rounded-xl shadow-md text-center font-black uppercase text-[10px] border">🏆 Squadre</a>
                    </div>
                </div>

                <div v-else class="bg-white p-12 text-center shadow rounded-2xl border-2 border-dashed border-gray-200">
                    <h3 class="text-2xl font-black uppercase mb-4">Benvenuto!</h3>
                    <div class="flex flex-col md:flex-row gap-4 justify-center">
                        <a :href="route('leagues.create')" class="bg-green-600 text-white px-8 py-4 rounded-xl font-bold uppercase">Crea Lega</a>
                        <a :href="route('leagues.join')" class="bg-orange-500 text-white px-8 py-4 rounded-xl font-bold uppercase">Unisciti</a>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>