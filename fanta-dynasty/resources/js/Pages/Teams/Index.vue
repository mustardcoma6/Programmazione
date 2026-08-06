<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({ teams: Array, leagueName: String });
const searchTeam = ref('');
const searchPlayer = ref('');

const filteredTeams = computed(() => {
    return (props.teams || []).filter(team => {
        const matchesTeam = (team.team_name || '').toLowerCase().includes(searchTeam.value.toLowerCase());
        const matchesPlayer = searchPlayer.value === '' || (team.players || []).some(p => 
            (p.player?.name || '').toLowerCase().includes(searchPlayer.value.toLowerCase())
        );
        return matchesTeam && matchesPlayer;
    });
});

const getRoleStyle = (role) => {
    switch (role) {
        case 'P': return 'bg-yellow-50 border-yellow-200 text-yellow-800';
        case 'D': return 'bg-green-50 border-green-200 text-green-900';
        case 'C': return 'bg-blue-50 border-blue-200 text-blue-800';
        case 'A': return 'bg-red-50 border-red-200 text-red-800';
        default: return 'bg-gray-50 border-gray-200 text-gray-800';
    }
};

const getBadgeRole = (role) => {
    switch (role) {
        case 'P': return 'bg-yellow-500'; case 'D': return 'bg-green-900';
        case 'C': return 'bg-blue-600'; case 'A': return 'bg-red-600';
        default: return 'bg-gray-500';
    }
};
</script>

<template>
    <Head title="Rose Avversarie" />
    <AuthenticatedLayout>
        <template #header><h2 class="font-black text-xl uppercase tracking-tighter">Analisi Rose Lega</h2></template>
        <div class="py-12 max-w-7xl mx-auto px-4 space-y-8">
            <!-- FILTRI -->
            <div class="bg-white p-6 shadow-sm rounded-2xl border border-gray-100 grid grid-cols-1 md:grid-cols-2 gap-4">
                <input v-model="searchTeam" type="text" placeholder="Cerca Squadra..." class="w-full rounded-xl border-gray-200 text-sm">
                <input v-model="searchPlayer" type="text" placeholder="Cerca Giocatore nelle rose..." class="w-full rounded-xl border-gray-200 text-sm">
            </div>

            <!-- LISTA SQUADRE -->
            <div v-for="team in filteredTeams" :key="team.id" class="bg-white shadow-xl rounded-3xl overflow-hidden border mb-8">
                <div class="bg-gray-900 p-5 flex justify-between items-center text-white border-b-4 border-blue-600">
                    <div>
                        <h3 class="text-2xl font-black uppercase">{{ team.team_name }}</h3>
                        <p class="text-[10px] text-gray-400 uppercase font-bold">{{ team.user?.name }}</p>
                    </div>
                    <div class="text-right"><p class="text-2xl font-mono font-black text-green-400">{{ team.remaining_budget }} cr</p></div>
                </div>
                <div class="p-6 bg-gray-50">
                    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                        <div v-for="p in team.players" :key="p.id" 
                             class="p-3 border-2 rounded-2xl shadow-sm flex flex-col items-center justify-center text-center transition"
                             :class="p.is_primavera ? 'bg-violet-100 border-violet-400 text-violet-900 shadow-md' : getRoleStyle(p.player.role)">
                            
                            <span class="text-[9px] font-black text-white px-2 py-0.5 rounded-full mb-1" :class="getBadgeRole(p.player.role)">{{ p.player.role }}</span>
                            <span class="font-black uppercase text-[11px] leading-tight mb-1">{{ p.player.name }}</span>
                            
                            <div class="flex justify-between w-full mt-2 px-1 border-t border-black/5 pt-1">
                                <span class="text-[8px] font-bold opacity-50 uppercase">{{ p.is_primavera ? 'VIVAIO' : p.player.real_team }}</span>
                                <span class="text-[9px] font-black font-mono">{{ p.purchase_price }} cr</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>