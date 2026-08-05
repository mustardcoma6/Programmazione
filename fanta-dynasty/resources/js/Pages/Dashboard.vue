<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    leagues: Array, myData: Object, myPlayers: Array, 
    allParticipants: Array, currentLineup: Object,
    isMarketOpen: Boolean, stats: Object
});

const freeYears = computed(() => {
    if (!props.myData) return 0;
    const yearsUsed = props.myPlayers ? props.myPlayers.reduce((acc, p) => acc + p.contract_years, 0) : 0;
    return props.myData.years_budget - yearsUsed;
});

const getRoleClass = (role) => {
    if (role === 'P') return 'role-P';
    if (role === 'D') return 'role-D';
    if (role === 'C') return 'role-C';
    if (role === 'A') return 'role-A';
    return '';
};
</script>

<template>
    <Head title="Home" />
    <AuthenticatedLayout>
        <div class="py-10">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
                
                <!-- SALUTO PRESIDENTE -->
                <div class="px-4 sm:px-0 flex flex-col md:flex-row justify-between items-start md:items-end gap-4">
                    <div>
                        <h1 class="text-5xl font-black text-gray-900 tracking-tighter italic">Benvenuto Pres!</h1>
                        <p class="text-xs text-blue-600 font-bold uppercase tracking-[0.3em] mt-1 ml-1">Dashboard Direzionale</p>
                    </div>
                    <div v-if="stats" class="bg-gray-900 text-white px-6 py-3 rounded-2xl shadow-xl border-b-4 border-yellow-500 text-center">
                        <p class="text-[9px] font-black uppercase text-yellow-500 tracking-widest">Ranking Crediti</p>
                        <p class="text-2xl font-black">{{ stats.rank }}° <span class="text-xs text-gray-400">/ {{ stats.totalParticipants }}</span></p>
                    </div>
                </div>

                <!-- SE SEI IN UNA LEGA -->
                <div v-if="leagues && leagues.length > 0 && myData" class="space-y-8">
                    
                    <!-- BANNER PATRIMONIO CON LOGO -->
                    <div class="bg-white p-8 shadow-xl rounded-3xl border-l-[12px] border-indigo-600">
                        <div class="flex flex-col lg:flex-row justify-between items-center gap-8">
                            <div class="flex flex-col md:flex-row items-center gap-6 flex-1">
                                <!-- BOX LOGO SQUADRA -->
                                <div class="w-24 h-24 bg-gray-100 rounded-2xl border-2 border-dashed border-gray-300 flex items-center justify-center overflow-hidden shadow-inner shrink-0">
                                    <img v-if="myData.logo_path" :src="myData.logo_path" class="w-full h-full object-cover">
                                    <span v-else class="text-3xl">🛡️</span>
                                </div>

                                <div class="text-center md:text-left">
                                    <h3 class="text-4xl font-black uppercase text-gray-900 leading-none mb-2">{{ myData.team_name }}</h3>
                                    <p class="text-gray-400 font-bold text-sm uppercase tracking-widest">Lega: {{ leagues[0].name }}</p>
                                    <div class="mt-4 flex items-center justify-center md:justify-start gap-3">
                                        <div class="px-4 py-1.5 rounded-full text-xs font-black uppercase border" :class="isMarketOpen ? 'bg-green-50 text-green-600 border-green-200' : 'bg-red-50 text-red-600 border-red-200'">
                                            Mercato {{ isMarketOpen ? 'Aperto' : 'Chiuso' }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="flex gap-4">
                                <div class="bg-gray-100 p-6 rounded-2xl text-center w-36 shadow-sm">
                                    <p class="text-[10px] font-black text-gray-400 uppercase">Crediti</p>
                                    <p class="text-3xl font-black text-gray-900 font-mono">{{ myData.remaining_budget }}</p>
                                </div>
                                <div class="bg-indigo-600 p-6 rounded-2xl text-center w-36 shadow-sm">
                                    <p class="text-[10px] font-black text-white uppercase opacity-70">Budget Anni</p>
                                    <p class="text-3xl font-black text-white font-mono">{{ myData.years_budget }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- PANNELLI ANALISI (RIMOSSO SPESA OGGI) -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-white p-6 rounded-3xl shadow-lg border border-gray-100 flex flex-col justify-center text-center">
                            <h4 class="text-xs font-black uppercase text-gray-400 mb-2 tracking-widest">💎 Top Player in Rosa</h4>
                            <p class="text-xl font-black text-gray-900 uppercase tracking-tighter">{{ stats?.topPlayer }}</p>
                            <p class="text-3xl font-mono font-black text-green-500">{{ stats?.topPrice }} cr</p>
                        </div>
                        <div class="bg-indigo-900 p-6 rounded-3xl shadow-lg text-white border-b-4 border-indigo-500">
                            <h4 class="text-xs font-black uppercase text-indigo-400 mb-2 tracking-widest">💡 Consiglio del Pres</h4>
                            <p class="text-sm font-medium italic">"Hai ancora {{ freeYears }} anni contrattuali da poter investire."</p>
                        </div>
                    </div>

                    <!-- LA TUA ROSA -->
                    <div class="bg-white p-8 shadow-xl rounded-3xl border border-gray-100">
                        <h3 class="font-black uppercase text-sm mb-6 border-b pb-2 tracking-widest text-gray-700 italic">Lista Asset Societari ({{ myPlayers.length }})</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div v-for="item in myPlayers" :key="item.id" class="p-3 bg-gray-50 border rounded-2xl flex justify-between items-center shadow-sm">
                                <div class="flex flex-col">
                                    <span class="text-[10px] font-black uppercase" :class="getRoleClass(item.player.role)">{{ item.player.role }}</span>
                                    <span class="text-sm font-black uppercase text-gray-800 tracking-tighter">{{ item.player.name }}</span>
                                </div>
                                <span class="text-[10px] font-mono text-gray-400">{{ item.purchase_price }} cr</span>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.role-P { color: #FFD700 !important; }
.role-D { color: #006400 !important; }
.role-C { color: #1e40af !important; }
.role-A { color: #dc2626 !important; }
</style>