<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed, reactive } from 'vue';

const props = defineProps({ league: Object, teams: Array, availablePlayers: Array });

// Logica per assegnazione
const selectedTeamId = ref('');
const assignForm = useForm({ player_id: null, user_id: null, price: 1, years: 1 });
const assign = (player) => {
    if (!selectedTeamId.value) return alert("Seleziona una squadra!");
    assignForm.player_id = player.id;
    assignForm.user_id = selectedTeamId.value;
    assignForm.post(route('admin.assign'), { preserveScroll: true });
};

// Logica per rimozione giocatore
const remove = (rosterId) => {
    if (confirm("Rimuovere il giocatore e rimborsare i crediti?")) {
        useForm({ roster_id: rosterId }).post(route('admin.remove'), { preserveScroll: true });
    }
};

// --- LOGICA MODIFICA CREDITI ---
const creditInputs = reactive({});
props.teams.forEach(t => { creditInputs[t.id] = t.remaining_budget; });

const updateCredits = (teamId) => {
    useForm({
        participant_id: teamId,
        new_credits: creditInputs[teamId]
    }).post(route('admin.credits.update'), { 
        preserveScroll: true,
        onSuccess: () => alert("Budget aggiornato!") 
    });
};

// Ricerca giocatori
const search = ref('');
const filteredPlayers = computed(() => {
    return props.availablePlayers.filter(p => p.name.toLowerCase().includes(search.value.toLowerCase())).slice(0, 10);
});
</script>

<template>
    <Head title="Gestione Rose" />
    <AuthenticatedLayout>
        <template #header><h2 class="font-black text-xl uppercase">Pannello di Controllo Admin</h2></template>
        <div class="py-12 max-w-7xl mx-auto px-4 space-y-10">
            
            <!-- 1. ASSEGNAZIONE MANUALE -->
            <div class="bg-white p-6 shadow-xl rounded-xl border-t-4 border-green-600">
                <h3 class="font-black uppercase text-sm mb-4 text-gray-500">Assegnazione Rapida (Bypassa Asta)</h3>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                    <div class="md:col-span-1">
                        <label class="block text-[10px] font-black uppercase text-gray-400">Squadra</label>
                        <select v-model="selectedTeamId" class="w-full rounded-lg border-gray-300 text-sm">
                            <option value="">Scegli...</option>
                            <option v-for="t in teams" :key="t.id" :value="t.user_id">{{ t.team_name }}</option>
                        </select>
                    </div>
                    <div class="md:col-span-1">
                        <label class="block text-[10px] font-black uppercase text-gray-400">Cerca Calciatore</label>
                        <input v-model="search" type="text" class="w-full rounded-lg border-gray-300 text-sm" placeholder="Nome...">
                    </div>
                    <div class="md:col-span-1">
                        <label class="block text-[10px] font-black uppercase text-gray-400">Prezzo / Anni</label>
                        <div class="flex gap-1">
                            <input type="number" v-model="assignForm.price" class="w-1/2 rounded-lg border-gray-300 text-sm">
                            <input type="number" v-model="assignForm.years" class="w-1/2 rounded-lg border-gray-300 text-sm">
                        </div>
                    </div>
                </div>
                <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-2">
                    <div v-for="p in filteredPlayers" :key="p.id" class="flex justify-between items-center p-2 bg-gray-50 rounded border">
                        <span class="text-xs font-bold uppercase"><b>{{ p.role }}</b> {{ p.name }}</span>
                        <button @click="assign(p)" class="bg-green-600 text-white px-3 py-1 rounded text-[10px] font-black">ASSEGNA</button>
                    </div>
                </div>
            </div>

            <!-- 2. ROSE ATTUALI E MODIFICA CREDITI -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div v-for="t in teams" :key="t.id" class="bg-white p-4 shadow rounded-xl border">
                    <div class="flex justify-between items-center border-b pb-2 mb-4 bg-gray-50 p-2 rounded">
                        <div>
                            <h4 class="font-black uppercase text-blue-600 text-sm">{{ t.team_name }}</h4>
                            <p class="text-[10px] text-gray-400 uppercase font-bold">{{ t.user.name }}</p>
                        </div>
                        
                        <!-- MODIFICA CREDITI -->
                        <div class="flex items-center gap-1">
                            <input type="number" v-model="creditInputs[t.id]" class="w-16 p-1 text-xs border-gray-300 rounded font-mono font-bold text-green-600">
                            <button @click="updateCredits(t.id)" class="bg-gray-800 text-white px-2 py-1 rounded text-[8px] font-black uppercase hover:bg-black">Set</button>
                        </div>
                    </div>

                    <div class="space-y-1">
                        <div v-for="r in t.players" :key="r.id" class="flex justify-between items-center p-2 hover:bg-red-50 group rounded border border-transparent hover:border-red-100 transition">
                            <span class="text-xs uppercase"><b>{{ r.player.role }}</b> {{ r.player.name }}</span>
                            <div class="flex items-center gap-2">
                                <span class="text-[9px] text-gray-400 font-mono">{{ r.purchase_price }} cr / {{ r.contract_years }}y</span>
                                <button @click="remove(r.id)" class="text-red-500 opacity-0 group-hover:opacity-100 font-bold">✕</button>
                            </div>
                        </div>
                        <div v-if="!t.players || t.players.length === 0" class="text-center py-4 text-gray-300 text-[10px] uppercase italic">Rosa vuota</div>
                    </div>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>