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
        
        // Protezione: usiamo ?. e || '' per evitare errori se un giocatore non ha nome
        const matchesPlayer = searchPlayer.value === '' || (team.players || []).some(p => 
            (p.name || '').toLowerCase().includes(searchPlayer.value.toLowerCase())
        );

        return matchesTeam && matchesPlayer;
    });
});

const getRoleStyle = (role) => {
    switch (role) {
        case 'P': return 'bg-yellow-100 border-yellow-400 text-yellow-800';
        case 'D': return 'bg-green-100 border-green-800 text-green-900';
        case 'C': return 'bg-blue-100 border-blue-600 text-blue-800';
        case 'A': return 'bg-red-100 border-red-600 text-red-800';
        default: return 'bg-gray-100 border-gray-400 text-gray-800';
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
        <template #header><h2 class="font-black text-xl uppercase tracking-tighter text-gray-800">Rose Avversarie</h2></template>
        <div class="py-12 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8 px-4">
            <!-- FILTRI -->
            <div class="bg-white p-6 shadow-sm rounded-2xl border border-gray-100 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] font-black uppercase text-gray-400 mb-1">Cerca Squadra</label>
                    <input v-model="searchTeam" type="text" placeholder="Nome squadra..." class="w-full rounded-xl border-gray-200 text-sm">
                </div>
                <div>
                    <label class="block text-[10px] font-black uppercase text-gray-400 mb-1">Cerca Calciatore nelle rose</label>
                    <input v-model="searchPlayer" type="text" placeholder="Nome giocatore..." class="w-full rounded-xl border-gray-200 text-sm">
                </div>
            </div>
            <!-- LISTA -->
            <div v-for="team in filteredTeams" :key="team.id" class="bg-white shadow-xl rounded-3xl overflow-hidden border border-gray-200 mb-8">
                <div class="bg-gray-900 p-5 flex justify-between items-center text-white border-b-4 border-blue-600">
                    <div>
                        <h3 class="text-2xl font-black uppercase tracking-tight">{{ team.team_name }}</h3>
                        <p class="text-[10px] text-gray-400 uppercase font-bold">{{ team.user?.name }}</p>
                    </div>
                    <div class="text-right"><p class="text-2xl font-mono font-black text-green-400">{{ team.remaining_budget }} cr</p></div>
                </div>
                <div class="p-6 bg-gray-50">
                    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                        <div v-for="p in team.players" :key="p.id" class="p-3 border-2 rounded-2xl shadow-sm flex flex-col items-center justify-center text-center transition hover:scale-105" :class="p.is_primavera ? 'bg-violet-100 border-violet-400 text-violet-900' : getRoleStyle(p.role)">
                            <span class="text-[10px] font-black text-white px-2 py-0.5 rounded-full mb-1 shadow-sm" :class="getBadgeRole(p.role)">{{ p.role }}</span>
                            <span class="font-black uppercase text-[11px] leading-tight mb-1">{{ p.name }}</span>
                            <div class="flex justify-between w-full mt-2 px-1 border-t border-black/5 pt-1">
                                <span class="text-[8px] font-bold opacity-50 uppercase">{{ p.is_primavera ? 'VIVAIO' : p.real_team }}</span>
                                <span class="text-[9px] font-black font-mono">{{ p.purchase_price }} cr</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>