<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    leagues: Array, myData: Object, myPlayers: Array, 
    allParticipants: Array, currentLineup: Object,
    isMarketOpen: Boolean, stats: Object
});

// Limiti Ruoli
const limits = { P: 3, D: 8, C: 8, A: 6 };

// Calcolo ruoli mancanti
const missingPlayers = computed(() => {
    const counts = { P: 0, D: 0, C: 0, A: 0 };
    props.myPlayers.forEach(p => { counts[p.player.role]++; });
    
    return {
        P: Math.max(0, limits.P - counts.P),
        D: Math.max(0, limits.D - counts.D),
        C: Math.max(0, limits.C - counts.C),
        A: Math.max(0, limits.A - counts.A)
    };
});

const totalMissing = computed(() => Object.values(missingPlayers.value).reduce((a, b) => a + b, 0));

// Consiglio del Pres
const strategyAdvice = computed(() => {
    if (!props.myData || totalMissing.value === 0) return "Rosa completa, Pres! Pensa solo a ottimizzare i contratti.";
    
    const budgetPerPlayer = Math.floor(props.myData.remaining_budget / totalMissing.value);
    
    if (missingPlayers.value.A > 0) {
        return `Attenzione Pres, ti mancano ancora ${missingPlayers.value.A} punte. Ti consiglio di tenere almeno ${budgetPerPlayer * 2} cr per ogni attaccante!`;
    }
    if (props.myData.remaining_budget > 100) {
        return "Hai un ottimo budget! Puoi permetterti un colpo di mercato per blindare il centrocampo.";
    }
    return `Hai circa ${budgetPerPlayer} crediti per ogni giocatore rimanente. Gestiscili con prudenza!`;
});

const remainingYears = computed(() => {
    if (!props.myData) return 0;
    const yearsUsed = props.myPlayers ? props.myPlayers.reduce((acc, p) => acc + p.contract_years, 0) : 0;
    return props.myData.years_budget - yearsUsed;
});
</script>

<template>
    <Head title="Home" />
    <AuthenticatedLayout>
        <div class="py-10">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
                
                <!-- SALUTO PRESIDENTE -->
                <div class="px-4 sm:px-0 flex justify-between items-end">
                    <div>
                        <h1 class="text-5xl font-black text-gray-900 tracking-tighter italic">Benvenuto Pres!</h1>
                        <p class="text-xs text-blue-600 font-bold uppercase tracking-[0.3em] mt-1 ml-1">Dashboard Direzionale</p>
                    </div>
                    <!-- CLASSIFICA CREDITI -->
                    <div v-if="stats" class="bg-gray-900 text-white px-6 py-3 rounded-2xl shadow-xl border-b-4 border-yellow-500 text-center">
                        <p class="text-[9px] font-black uppercase text-yellow-500">Ranking Economico</p>
                        <p class="text-2xl font-black">{{ stats.rank }}° <span class="text-xs text-gray-400">/ {{ stats.totalParticipants }}</span></p>
                    </div>
                </div>

                <!-- SE SEI IN UNA LEGA -->
                <div v-if="leagues && leagues.length > 0 && myData" class="space-y-8">
                    
                    <!-- 1. BANNER RISORSE -->
                    <div class="bg-white p-8 shadow-xl rounded-3xl border-l-[12px] border-indigo-600 flex flex-col lg:flex-row justify-between items-center gap-8">
                        <div class="flex-1 text-center lg:text-left">
                            <h3 class="text-4xl font-black uppercase text-gray-900 leading-none mb-2">{{ myData.team_name }}</h3>
                            <div class="flex items-center justify-center lg:justify-start gap-3">
                                <div class="px-4 py-1.5 rounded-full text-xs font-black uppercase border" :class="isMarketOpen ? 'bg-green-50 text-green-600 border-green-200' : 'bg-red-50 text-red-600 border-red-200'">Mercato {{ isMarketOpen ? 'Aperto' : 'Chiuso' }}</div>
                            </div>
                        </div>
                        <div class="flex gap-4">
                            <div class="bg-gray-100 p-6 rounded-2xl text-center w-36"><p class="text-[10px] font-black text-gray-400 uppercase">Crediti</p><p class="text-3xl font-black text-gray-900">{{ myData.remaining_budget }}</p></div>
                            <div class="bg-indigo-600 p-6 rounded-2xl text-center w-36"><p class="text-[10px] font-black text-white uppercase opacity-70">Budget Anni</p><p class="text-3xl font-black text-white">{{ myData.years_budget }}</p></div>
                        </div>
                    </div>

                    <!-- 2. NAVIGAZIONE -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                        <a :href="route('players.index')" class="bg-white p-6 rounded-3xl shadow-md border-b-4 border-gray-100 hover:border-blue-500 transition text-center">
                            <span class="text-3xl block mb-2">📋</span><span class="font-black uppercase text-[10px] text-gray-500">Svincolati</span>
                        </a>
                        <a :href="route('roster.index')" class="bg-white p-6 rounded-3xl shadow-md border-b-4 border-gray-100 hover:border-indigo-500 transition text-center">
                            <span class="text-3xl block mb-2">👔</span><span class="font-black uppercase text-[10px] text-gray-500">Gestione Rosa</span>
                        </a>
                        <a :href="route('market.auctions')" class="bg-blue-600 p-6 rounded-3xl shadow-xl border-b-4 border-blue-800 hover:bg-blue-700 transition text-center text-white">
                            <span class="text-3xl block mb-2">🛒</span><span class="font-black uppercase text-[10px]">Mercato</span>
                        </a>
                        <a :href="route('teams.index')" class="bg-white p-6 rounded-3xl shadow-md border-b-4 border-gray-100 hover:border-orange-500 transition text-center">
                            <span class="text-3xl block mb-2">🏆</span><span class="font-black uppercase text-[10px] text-gray-500">Squadre</span>
                        </a>
                    </div>

                    <!-- 3. PANNELLI ANALISI (NUOVI) -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Spesa Oggi -->
                        <div class="bg-white p-6 rounded-3xl shadow-lg border border-gray-100">
                            <h4 class="text-xs font-black uppercase text-gray-400 mb-4 flex items-center gap-2">📊 Movimenti Oggi</h4>
                            <div class="space-y-4">
                                <div class="flex justify-between items-end">
                                    <span class="text-sm font-bold text-gray-600">Crediti Spesi</span>
                                    <span class="text-xl font-black text-red-500">{{ stats?.spentToday }} cr</span>
                                </div>
                                <div class="flex justify-between items-end">
                                    <span class="text-sm font-bold text-gray-600">Anni Contrattualizzati</span>
                                    <span class="text-xl font-black text-blue-500">{{ stats?.yearsToday }} y</span>
                                </div>
                            </div>
                        </div>

                        <!-- Top Player -->
                        <div class="bg-white p-6 rounded-3xl shadow-lg border border-gray-100 flex flex-col justify-center text-center">
                            <h4 class="text-xs font-black uppercase text-gray-400 mb-2">💎 Top Purchase</h4>
                            <p class="text-xl font-black text-gray-900 uppercase tracking-tighter">{{ stats?.topPlayer }}</p>
                            <p class="text-3xl font-mono font-black text-green-500">{{ stats?.topPrice }} cr</p>
                        </div>

                        <!-- Consiglio Strategico -->
                        <div class="bg-indigo-900 p-6 rounded-3xl shadow-lg text-white">
                            <h4 class="text-xs font-black uppercase text-indigo-400 mb-2">💡 Consiglio del Pres</h4>
                            <p class="text-sm font-medium leading-relaxed italic">"{{ strategyAdvice }}"</p>
                        </div>
                    </div>

                    <!-- 4. STATO ROSA (MANCANTI) -->
                    <div class="bg-white p-6 shadow-xl rounded-3xl border border-gray-100">
                        <h4 class="text-xs font-black uppercase text-gray-400 mb-4">🧩 Obiettivi di Mercato (Giocatori da acquistare)</h4>
                        <div class="grid grid-cols-4 gap-4">
                            <div v-for="(count, role) in missingPlayers" :key="role" class="text-center p-3 rounded-2xl" :class="count === 0 ? 'bg-green-50 opacity-50' : 'bg-gray-50 border'">
                                <p class="text-lg font-black" :class="count > 0 ? 'text-gray-900' : 'text-green-600'">{{ count }}</p>
                                <p class="text-[9px] font-bold uppercase text-gray-400">{{ role }} da prendere</p>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- CASO B: L'UTENTE È NUOVO -->
                <div v-else class="bg-white p-16 text-center shadow-2xl rounded-3xl border-2 border-dashed border-indigo-200">
                    <h3 class="text-3xl font-black text-gray-900 uppercase">Benvenuto Pres!</h3>
                    <p class="text-gray-500 mt-4 mb-10 text-lg">Inizia la tua carriera. Crea una lega o unisciti a una esistente.</p>
                    <div class="flex flex-col md:flex-row gap-4 justify-center">
                        <a :href="route('leagues.create')" class="bg-green-600 text-white px-8 py-4 rounded-xl font-bold uppercase">Crea Lega</a>
                        <a :href="route('leagues.join')" class="bg-orange-500 text-white px-8 py-4 rounded-xl font-bold uppercase">Unisciti</a>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>