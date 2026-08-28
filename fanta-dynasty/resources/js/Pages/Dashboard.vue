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
                
                <!-- 1. INTESTAZIONE (Saluto e Ranking) -->
                <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-6 px-2">
                    <!-- ... il tuo codice del saluto ... -->
                </div>

                <!-- 2. BANNER PATRIMONIO (Sopra a tutto) -->
                <div v-if="myData" class="bg-white p-8 shadow-xl rounded-[2.5rem] border-l-[16px] border-indigo-600">
                    <!-- ... il tuo codice del banner (SANTOS, Crediti, Anni) ... -->
                </div>

                <!-- 3. GRIGLIA PRINCIPALE (Due Colonne) -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
                    
                    <!-- COLONNA SINISTRA (Grande: Consigli, DS, Campo) -->
                    <div class="lg:col-span-2 space-y-8">
                        
                        <!-- Analisi e Consigli -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- ... il tuo codice dei consigli ... -->
                        </div>

                        <!-- Nota del DS (Slot Liberi) -->
                        <div class="bg-white p-8 shadow-lg rounded-[2rem] border border-gray-100">
                            <!-- ... il tuo codice degli slot ... -->
                        </div>

                        <!-- Campo da Gioco -->
                        <div v-if="currentLineup" class="bg-green-700 p-8 shadow-2xl rounded-[3rem] border-[8px] border-green-800 text-white relative overflow-hidden">
                            <!-- ... il tuo codice del campo ... -->
                        </div>
                    </div>

                    <!-- COLONNA DESTRA (Piccola: Classifica Campionato) -->
                    <div class="lg:col-span-1">
                        <div v-if="classifica && classifica.length > 0" class="bg-white rounded-[2rem] shadow-xl border border-gray-100 overflow-hidden sticky top-24">
                            <div class="bg-blue-600 px-5 py-4">
                                <h3 class="font-black text-white uppercase italic text-xs tracking-widest text-center">Classifica Campionato</h3>
                            </div>
                            <div class="p-0">
                                <table class="w-full text-left">
                                    <thead>
                                        <tr class="bg-gray-50 text-[9px] uppercase font-black text-gray-400 border-b">
                                            <th class="pl-4 py-3">#</th>
                                            <th class="py-3">Squadra</th>
                                            <th class="py-3 text-center">PT</th>
                                            <th class="pr-4 py-3 text-center">TOT</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <tr v-for="(squadra, index) in classifica" :key="squadra.id" 
                                            :class="{'bg-blue-50/50': squadra.user_id === myData.user_id}"
                                            class="hover:bg-gray-50 transition">
                                            <td class="pl-4 py-3 text-[10px] font-black text-gray-300">#{{ index + 1 }}</td>
                                            <td class="py-3">
                                                <div class="font-bold text-gray-700 text-xs truncate max-w-[100px] uppercase">{{ squadra.team_name }}</div>
                                            </td>
                                            <td class="py-3 text-center font-black text-gray-900 text-sm">{{ squadra.league_points }}</td>
                                            <td class="pr-4 py-3 text-center font-black text-blue-600 text-[11px]">{{ squadra.total_points }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="bg-gray-50 p-3 text-center border-t border-gray-100">
                                <p class="text-[9px] text-gray-400 font-bold uppercase">Primo criterio: Punti / Secondo: Punteggio</p>
                            </div>
                        </div>
                    </div>

                </div> <!-- Fine Griglia -->

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