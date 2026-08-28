<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    leagues: Object,
    myData: Object,
    myPlayers: Array,
    currentLineup: Object,
    allParticipants: Array,
    isMarketOpen: Boolean,
    stats: Object,
    classifica: Array 
});

// Consiglio del Pres dinamico
const strategyAdvice = computed(() => {
    if (!props.myData) return "Benvenuto Pres!";
    const countA = props.myPlayers ? props.myPlayers.filter(p => p.player?.role === 'A').length : 0;
    if (countA < 6) return `Rosa in costruzione. Ti mancano delle punte per completare il reparto!`;
    return "Rosa al completo. Pensa solo alla formazione e alla prossima giornata!";
});
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <div class="py-10 px-4">
            <div class="max-w-7xl mx-auto space-y-8">
                
                <!-- 1. INTESTAZIONE: SALUTO E RANKING -->
                <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-6 px-2">
                    <div>
                        <h1 class="text-6xl font-black text-gray-900 tracking-tighter italic">Benvenuto Pres!</h1>
                        <p class="text-xs text-blue-600 font-bold uppercase tracking-[0.4em] mt-2 ml-1">Dashboard Direzionale</p>
                    </div>

                    <div v-if="stats" class="flex gap-4">
                        <div class="bg-indigo-600 text-white px-6 py-3 rounded-3xl shadow-xl border-b-4 border-indigo-900 text-center min-w-[120px]">
                            <p class="text-[10px] font-black uppercase text-indigo-200 tracking-widest">Ranking</p>
                            <p class="text-3xl font-black">{{ stats.generalRank }}°</p>
                        </div>
                        <div class="bg-gray-900 text-white px-6 py-3 rounded-3xl shadow-xl border-b-4 border-yellow-500 text-center min-w-[120px]">
                            <p class="text-[10px] font-black uppercase text-yellow-500 tracking-widest">Ranking Crediti</p>
                            <p class="text-3xl font-black">{{ stats.rank }}°</p>
                        </div>
                    </div>
                </div>

                <!-- 2. BANNER PATRIMONIO (Con Codice Invito Ripristinato) -->
                <div v-if="leagues && leagues.length > 0 && myData" class="bg-white p-8 shadow-xl rounded-[2.5rem] border-l-[16px] border-indigo-600">
                    <div class="flex flex-col lg:flex-row justify-between items-center gap-8">
                        <div class="flex-1 text-center lg:text-left">
                            <h3 class="text-4xl font-black uppercase text-gray-900 tracking-tight mb-2">{{ myData.team_name }}</h3>
                            <p class="text-gray-400 font-bold text-sm uppercase italic">Lega: {{ leagues[0].name }}</p>
                            
                            <!-- PILLS: Mercato e Codice Invito -->
                            <div class="mt-4 flex flex-wrap justify-center lg:justify-start gap-3">
                                <div class="px-4 py-1.5 rounded-full text-[10px] font-black uppercase border bg-green-50 text-green-600 border-green-200">
                                    Lega Attiva
                                </div>
                                <div class="px-4 py-1.5 bg-yellow-50 border border-yellow-200 rounded-full text-[10px] font-mono font-bold text-yellow-700 uppercase">
                                    Codice Invito: {{ leagues[0].invite_code }}
                                </div>
                            </div>
                        </div>

                        <div class="flex gap-4 font-mono font-black">
                            <div class="bg-gray-100 p-6 rounded-3xl text-center w-32 border border-gray-200">
                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Crediti</p>
                                <p class="text-3xl text-gray-900">{{ myData.remaining_budget }}</p>
                            </div>
                            <div class="bg-indigo-600 p-6 rounded-3xl text-center w-32 shadow-lg shadow-indigo-200 text-white border-b-4 border-indigo-800">
                                <p class="text-[10px] font-black text-indigo-200 uppercase tracking-widest">Anni</p>
                                <p class="text-3xl">{{ myData.years_budget }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. GRIGLIA A DUE COLONNE -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
                    
                    <!-- COLONNA SINISTRA (Grande) -->
                    <div class="lg:col-span-2 space-y-8">
                        
                        <!-- ANALISI E CONSIGLI (Dati Top Player Corretti) -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="bg-white p-8 rounded-[2rem] shadow-lg border border-gray-100 flex flex-col justify-center text-center">
                                <h4 class="text-[10px] font-black uppercase text-blue-400 mb-2 tracking-widest">💎 Top Player in Rosa</h4>
                                <p class="text-2xl font-black text-gray-900 uppercase tracking-tighter">
    {{ stats?.topPlayer || 'Nessuno' }}
</p>
<p class="text-4xl font-mono font-black text-green-500 mt-2">
    {{ stats?.topPrice || 0 }} <span class="text-xs uppercase">cr</span>
</p>
                                </div>
                            <div class="bg-indigo-900 p-8 rounded-[2rem] shadow-xl text-white border-b-8 border-indigo-500 flex flex-col justify-center">
                                <h4 class="text-[10px] font-black uppercase text-indigo-400 mb-3 tracking-widest">💡 Consiglio del Pres</h4>
                                <p class="text-base font-medium italic leading-relaxed text-indigo-50">"{{ strategyAdvice }}"</p>
                            </div>
                        </div>

                        <!-- Spazio decorativo -->
                        <div class="p-12 text-center border-2 border-dashed border-gray-100 rounded-[3rem]">
                            <p class="text-gray-300 font-bold uppercase text-[10px] tracking-[0.5em]">Area Direzionale - FantaGest</p>
                        </div>
                    </div>

                    <!-- COLONNA DESTRA (Piccola): CLASSIFICA -->
                    <div class="lg:col-span-1">
                        <div v-if="classifica && classifica.length > 0" class="bg-white rounded-[2rem] shadow-xl border border-gray-100 overflow-hidden sticky top-24">
                            <div class="bg-blue-600 px-5 py-4 text-center">
                                <h3 class="font-black text-white uppercase italic text-xs tracking-widest">Classifica Campionato</h3>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="w-full text-left">
                                    <thead>
                                        <tr class="bg-gray-50 text-[9px] uppercase font-black text-gray-400 border-b">
                                            <th class="pl-4 py-3">#</th>
                                            <th class="py-3 text-left">Squadra</th>
                                            <th class="py-3 text-center">PT</th>
                                            <th class="pr-4 py-3 text-center">TOT</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <tr v-for="(squadra, index) in classifica" :key="squadra.id" 
                                            :class="{'bg-blue-50': squadra.user_id === myData?.user_id}"
                                            class="hover:bg-gray-50 transition">
                                            <td class="pl-4 py-3 text-[10px] font-black text-gray-300 italic">#{{ index + 1 }}</td>
                                            <td class="py-3">
                                                <div class="font-bold text-gray-700 text-[11px] truncate max-w-[110px] uppercase tracking-tighter">{{ squadra.team_name }}</div>
                                            </td>
                                            <td class="py-3 text-center font-black text-gray-900 text-sm">{{ squadra.league_points }}</td>
                                            <td class="pr-4 py-3 text-center font-black text-blue-600 text-[11px]">{{ squadra.total_points }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="bg-gray-50 p-3 text-center border-t border-gray-100">
                                <p class="text-[9px] text-gray-400 font-bold uppercase">Ord. Punti / Punteggio</p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>