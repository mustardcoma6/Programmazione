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

const limits = { P: 3, D: 8, C: 8, A: 6 };

const missingPlayers = computed(() => {
    const counts = { P: 0, D: 0, C: 0, A: 0 };
    if (props.myPlayers) {
        props.myPlayers.forEach(p => { 
            if (p.player && p.player.role) counts[p.player.role]++;
        });
    }
    return {
        P: Math.max(0, limits.P - counts.P),
        D: Math.max(0, limits.D - counts.D),
        C: Math.max(0, limits.C - counts.C),
        A: Math.max(0, limits.A - counts.A)
    };
});

const totalMissing = computed(() => Object.values(missingPlayers.value).reduce((a, b) => a + b, 0));

const strategyAdvice = computed(() => {
    if (!props.myData || totalMissing.value === 0) return "Rosa al completo. Pensa solo alla formazione!";
    const budget = props.myData.remaining_budget;
    const avg = totalMissing.value > 0 ? Math.floor(budget / totalMissing.value) : 0;
    return `Hai circa ${avg} cr per ogni slot libero. Gestiscili con intelligenza.`;
});

const getSlotStyle = (role) => {
    switch(role) {
        case 'P': return 'bg-yellow-50 border-yellow-400 text-yellow-700';
        case 'D': return 'bg-green-50 border-green-800 text-green-900';
        case 'C': return 'bg-blue-50 border-blue-600 text-blue-800';
        case 'A': return 'bg-red-50 border-red-600 text-red-800';
        default: return 'bg-gray-50 border-gray-200 text-gray-500';
    }
};

const getRoleClass = (role) => {
    if (role === 'P') return 'role-P';
    if (role === 'D') return 'role-D';
    if (role === 'C') return 'role-C';
    if (role === 'A') return 'role-A';
    return '';
};
</script>

<template>
    <Head title="Dashboard" />
    <AuthenticatedLayout>
        <div class="py-10 px-4">
            <div class="max-w-7xl mx-auto space-y-8">
                
                <!-- INTESTAZIONE -->
                <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-6 px-2">
                    <div>
                        <h1 class="text-6xl font-black text-gray-900 tracking-tighter italic">Benvenuto Pres!</h1>
                        <p class="text-xs text-blue-600 font-bold uppercase tracking-[0.4em] mt-2 ml-1">Dashboard Direzionale</p>
                    </div>
                    <div v-if="stats" class="flex gap-4">
                        <div class="bg-indigo-600 text-white px-6 py-3 rounded-3xl shadow-xl border-b-4 border-indigo-900 text-center">
                            <p class="text-[10px] font-black uppercase text-indigo-200 tracking-widest">Ranking</p>
                            <p class="text-3xl font-black">{{ stats.generalRank }}°</p>
                        </div>
                    </div>
                </div>

                <!-- CLASSIFICA CAMPIONATO -->
                <div v-if="classifica && classifica.length > 0" class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="bg-blue-600 px-6 py-4">
                        <h3 class="font-black text-white uppercase italic tracking-tighter">Classifica Campionato</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="bg-gray-50 text-[10px] uppercase font-black text-gray-400 border-b">
                                    <th class="px-4 py-3">Pos</th>
                                    <th class="px-4 py-3">Squadra</th>
                                    <th class="px-2 py-3 text-center">Punti</th>
                                    <th class="px-2 py-3 text-center text-gray-300">G</th>
                                    <th class="px-2 py-3 text-center text-blue-600">Punteggio</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr v-for="(squadra, index) in classifica" :key="squadra.id" class="hover:bg-blue-50 transition">
                                    <td class="px-4 py-4 font-black text-gray-300 italic">#{{ index + 1 }}</td>
                                    <td class="px-4 py-4 font-bold text-gray-800">{{ squadra.user.name }}</td>
                                    <td class="px-2 py-4 text-center font-black text-gray-900 text-lg">{{ squadra.league_points }}</td>
                                    <td class="px-2 py-4 text-center text-gray-400 text-xs">{{ squadra.games_played }}</td>
                                    <td class="px-2 py-4 text-center font-black text-blue-600">{{ squadra.total_points }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ALTRE SEZIONI (BANNER, CONSIGLI, CAMPO) -->
                <div v-if="leagues && leagues.length > 0 && myData" class="space-y-8">
                    <!-- Banner Patrimonio -->
                    <div class="bg-white p-8 shadow-xl rounded-[2.5rem] border-l-[16px] border-indigo-600">
                        <div class="flex flex-col lg:flex-row justify-between items-center gap-8">
                            <div class="flex-1">
                                <h3 class="text-4xl font-black uppercase text-gray-900 tracking-tight">{{ myData.team_name }}</h3>
                                <p class="text-gray-400 font-bold text-sm uppercase">Lega: {{ leagues[0].name }}</p>
                            </div>
                            <div class="flex gap-4 font-mono font-black">
                                <div class="bg-gray-100 p-6 rounded-3xl text-center w-32 border">
                                    <p class="text-[10px] text-gray-400 uppercase">Crediti</p>
                                    <p class="text-3xl">{{ myData.remaining_budget }}</p>
                                </div>
                                <div class="bg-indigo-600 p-6 rounded-3xl text-center w-32 text-white shadow-lg">
                                    <p class="text-[10px] text-indigo-200 uppercase">Anni</p>
                                    <p class="text-3xl">{{ myData.years_budget }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.role-P { color: #856404; background-color: #fff3cd; padding: 2px 8px; border-radius: 6px; font-weight: 900; }
.role-D { color: #155724; background-color: #d4edda; padding: 2px 8px; border-radius: 6px; font-weight: 900; }
.role-C { color: #004085; background-color: #cce5ff; padding: 2px 8px; border-radius: 6px; font-weight: 900; }
.role-A { color: #721c24; background-color: #f8d7da; padding: 2px 8px; border-radius: 6px; font-weight: 900; }
</style>