<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    teams: Array,
    leagueName: String
});

// --- LOGICA FILTRI ---
const searchTeam = ref('');
const searchPlayer = ref('');

const filteredTeams = computed(() => {
    const list = props.teams || [];
    return list.filter(team => {
        const matchesTeam = (team.team_name || '').toLowerCase().includes(searchTeam.value.toLowerCase());
        const matchesPlayer = searchPlayer.value === '' || (team.players || []).some(p => 
            (p.player?.name || '').toLowerCase().includes(searchPlayer.value.toLowerCase())
        );
        return matchesTeam && matchesPlayer;
    });
});

// --- LOGICA COLORI RUOLI ---
const getRoleStyle = (role) => {
    switch (role) {
        case 'P': return 'bg-yellow-50 border-yellow-200 text-yellow-800'; // Giallo Oro
        case 'D': return 'bg-green-50 border-green-200 text-green-900';   // Verde Scuro
        case 'C': return 'bg-blue-50 border-blue-200 text-blue-800';     // Blu
        case 'A': return 'bg-red-50 border-red-200 text-red-800';        // Rosso
        default: return 'bg-gray-50 border-gray-200 text-gray-800';
    }
};

const getBadgeRole = (role) => {
    switch (role) {
        case 'P': return 'bg-yellow-500'; 
        case 'D': return 'bg-green-900'; 
        case 'C': return 'bg-blue-600'; 
        case 'A': return 'bg-red-600';
        default: return 'bg-gray-500';
    }
};
</script>

<template>
    <Head title="Rose Avversarie" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-black text-xl uppercase tracking-tighter text-gray-800">
                Rose Avversarie: {{ leagueName }}
            </h2>
        </template>

        <div class="py-12 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8 px-4">
            
            <!-- BARRA FILTRI -->
            <div class="bg-white p-6 shadow-sm rounded-2xl border border-gray-100 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">Cerca Squadra</label>
                    <input v-model="searchTeam" type="text" placeholder="Nome squadra..." class="w-full rounded-xl border-gray-200 text-sm focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-gray-400 uppercase mb-1">Cerca Calciatore nelle rose</label>
                    <input v-model="searchPlayer" type="text" placeholder="Nome giocatore..." class="w-full rounded-xl border-gray-200 text-sm focus:ring-blue-500">
                </div>
            </div>

            <!-- LISTA SQUADRE -->
            <div v-for="team in filteredTeams" :key="team.id" class="bg-white shadow-xl rounded-3xl overflow-hidden border border-gray-200 mb-8 transition-all">
                
                <!-- Intestazione Squadra con Crediti e Anni -->
                <div class="bg-gray-900 p-5 flex justify-between items-center text-white border-b-4 border-blue-600">
                    <div>
                        <h3 class="text-2xl font-black uppercase tracking-tight">{{ team.team_name }}</h3>
                        <p class="text-[10px] text-gray-400 uppercase font-bold">Presidente: {{ team.user?.name }}</p>
                    </div>
                    
                    <div class="text-right flex items-center gap-6">
                        <!-- BUDGET CREDITI -->
                        <div>
                            <p class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">Crediti</p>
                            <p class="text-2xl font-mono font-black text-green-400">{{ team.remaining_budget }} cr</p>
                        </div>
                        <!-- BUDGET ANNI TOTALE -->
                        <div>
                            <p class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">Anni</p>
                            <p class="text-2xl font-mono font-black text-blue-400">{{ team.years_budget }}</p>
                        </div>
                    </div>
                </div>

                <!-- Griglia Giocatori -->
                <div class="p-6 bg-gray-50">
                    <div v-if="!team.players || team.players.length === 0" class="text-center py-10 text-gray-400 uppercase font-bold text-xs italic">
                        Nessun calciatore in rosa
                    </div>
                    <div v-else class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                        <div v-for="p in team.players" :key="p.id" 
                             class="p-3 border-2 rounded-2xl shadow-sm flex flex-col items-center justify-center text-center transition hover:scale-105"
                             :class="p.is_primavera ? 'bg-violet-100 border-violet-400 text-violet-900' : getRoleStyle(p.player.role)">
                            
                            <span class="text-[9px] font-black text-white px-2 py-0.5 rounded-full mb-1" :class="getBadgeRole(p.player.role)">
                                {{ p.player.role }}
                            </span>
                            <span class="font-black uppercase text-[11px] leading-tight mb-1 truncate w-full px-1">{{ p.player.name }}</span>
                            
                            <div class="flex justify-between w-full mt-2 px-1 border-t border-black/5 pt-1">
                                <span class="text-[8px] font-bold opacity-50 uppercase">{{ p.is_primavera ? 'VIVAIO' : p.player.real_team }}</span>
                                <span class="text-[9px] font-black font-mono">{{ p.purchase_price }} cr</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="filteredTeams.length === 0" class="bg-white p-20 text-center rounded-3xl border-2 border-dashed border-gray-300">
                <p class="text-gray-400 font-black uppercase">Nessuna squadra o giocatore trovato</p>
            </div>

        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Colori fissi per ruoli */
.role-P { font-weight: 900; }
.role-D { font-weight: 900; }
.role-C { font-weight: 900; }
.role-A { font-weight: 900; }
</style>