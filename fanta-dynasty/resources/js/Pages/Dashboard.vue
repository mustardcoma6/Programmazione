<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
const props = defineProps({ leagues: Array, myData: Object, myPlayers: Array, allParticipants: Array, currentLineup: Object, isMarketOpen: Boolean });

// FUNZIONE COLORI RUOLI
const getRoleColor = (role) => {
    if (role === 'P') return 'text-yellow-500'; // Giallo Oro
    if (role === 'D') return 'text-green-900'; // Verde Scuro
    if (role === 'C') return 'text-blue-600';  // Blu
    if (role === 'A') return 'text-red-600';   // Rosso
    return 'text-gray-500';
};
</script>

<template>
    <Head title="Home" />
    <AuthenticatedLayout>
        <div class="py-10">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
                <div class="px-4 sm:px-0 mb-4"><h1 class="text-5xl font-black text-gray-900 tracking-tighter italic">Benvenuto Pres!</h1></div>
                <div v-if="leagues && leagues.length > 0 && myData" class="space-y-8">
                    <div class="bg-white p-8 shadow-xl rounded-3xl border-l-[12px] border-indigo-600">
                        <div class="flex flex-col lg:flex-row justify-between items-center gap-8">
                            <div class="flex-1 text-center lg:text-left">
                                <h3 class="text-4xl font-black uppercase text-gray-900 leading-none mb-2">{{ myData.team_name }}</h3>
                                <p class="text-gray-400 font-bold text-lg uppercase tracking-tighter">Lega: {{ leagues[0].name }}</p>
                                <div class="mt-4 flex items-center justify-center lg:justify-start gap-3">
                                    <div class="px-4 py-1.5 rounded-full text-xs font-black uppercase border" :class="isMarketOpen ? 'bg-green-50 text-green-600 border-green-200' : 'bg-red-50 text-red-600 border-red-200'">Mercato {{ isMarketOpen ? 'Aperto' : 'Chiuso' }}</div>
                                    <span class="text-[10px] font-mono text-gray-300 uppercase tracking-widest">Codice: {{ leagues[0].invite_code }}</span>
                                </div>
                            </div>
                            <div class="flex gap-4">
                                <div class="bg-gray-900 p-6 rounded-2xl text-center shadow-lg w-40"><p class="text-[10px] font-black text-green-400 uppercase tracking-widest mb-1">Crediti</p><p class="text-4xl font-black text-white font-mono">{{ myData.remaining_budget }}</p></div>
                                <div class="bg-indigo-600 p-6 rounded-2xl text-center shadow-lg w-40 transform hover:scale-105 transition"><p class="text-[10px] font-black text-indigo-200 uppercase tracking-widest mb-1">Budget Anni</p><p class="text-4xl font-black text-white font-mono">{{ myData.years_budget }}</p></div>
                            </div>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                        <a :href="route('players.index')" class="group bg-white p-6 rounded-3xl shadow-md border-b-4 border-gray-100 hover:border-blue-500 transition text-center"><span class="text-4xl block mb-2">📋</span><span class="font-black uppercase text-[10px] text-gray-500">Svincolati</span></a>
                        <a :href="route('roster.index')" class="group bg-white p-6 rounded-3xl shadow-md border-b-4 border-gray-100 hover:border-indigo-500 transition text-center"><span class="text-4xl block mb-2">🧥</span><span class="font-black uppercase text-[10px] text-gray-500">Rosa</span></a>
                        <a :href="route('market.auctions')" class="group bg-blue-600 p-6 rounded-3xl shadow-xl border-b-4 border-blue-800 hover:bg-blue-700 transition text-center text-white"><span class="text-4xl block mb-2">🛒</span><span class="font-black uppercase text-[10px]">Mercato</span></a>
                        <a :href="route('teams.index')" class="group bg-white p-6 rounded-3xl shadow-md border-b-4 border-gray-100 hover:border-orange-500 transition text-center"><span class="text-4xl block mb-2">🏆</span><span class="font-black uppercase text-[10px] text-gray-500">Squadre</span></a>
                    </div>
                    <!-- CAMPO DA GIOCO -->
                    <div v-if="currentLineup" class="bg-green-700 p-8 shadow-2xl rounded-2xl border-[6px] border-green-800 text-white relative overflow-hidden">
                        <div class="flex justify-between items-center mb-8 relative z-10"><h3 class="font-black uppercase text-xl">Schieramento Domenicale <span class="text-green-300 ml-2">{{ currentLineup.module }}</span></h3></div>
                        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4 relative z-10">
                            <div v-for="detail in currentLineup.details" :key="detail.id" class="p-3 rounded-lg bg-white/10 border border-white/10 backdrop-blur-sm text-center">
                                <!-- RUOLO COLORATO -->
                               <p :class="'role-' + detail.player.role">{{ detail.player.role }}</p>
                                <p class="text-sm font-black truncate uppercase">{{ detail.player.name }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>