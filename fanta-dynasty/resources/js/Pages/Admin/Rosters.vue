<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed, reactive } from 'vue';

const props = defineProps({ league: Object, teams: Array, availablePlayers: Array });

// --- LOGICA ASSEGNAZIONE DA LISTONE ---
const selectedTeamId = ref('');
const search = ref('');
const assignForm = useForm({ player_id: null, user_id: null, price: 1, years: 1 });
const filteredPlayers = computed(() => {
    return props.availablePlayers.filter(p => p.name.toLowerCase().includes(search.value.toLowerCase())).slice(0, 8);
});
const assign = (player) => {
    if (!selectedTeamId.value) return alert("Seleziona una squadra!");
    assignForm.player_id = player.id;
    assignForm.user_id = selectedTeamId.value;
    assignForm.post(route('admin.assign'), { preserveScroll: true });
};

// --- LOGICA INSERIMENTO MANUALE (NUOVA) ---
const manualForm = useForm({
    name: '',
    role: 'D',
    real_team: '',
    user_id: '',
    price: 1,
    years: 1
});
const submitManual = () => {
    if (!manualForm.user_id) return alert("Seleziona squadra!");
    manualForm.post(route('admin.assign.manual'), { 
        preserveScroll: true,
        onSuccess: () => manualForm.reset('name', 'real_team')
    });
};

// --- LOGICA CREDITI E RIMOZIONE ---
const creditInputs = reactive({});
props.teams.forEach(t => { creditInputs[t.id] = t.remaining_budget; });
const updateCredits = (teamId) => {
    useForm({ participant_id: teamId, new_credits: creditInputs[teamId] }).post(route('admin.credits.update'), { preserveScroll: true });
};
const remove = (rosterId) => {
    if (confirm("Rimuovere il giocatore?")) {
        useForm({ roster_id: rosterId }).post(route('admin.remove'), { preserveScroll: true });
    }
};
</script>

<template>
    <Head title="Gestione Rose" />
    <AuthenticatedLayout>
        <template #header><h2 class="font-black text-xl uppercase">Controllo Rose & Budget</h2></template>
        
        <div class="py-12 max-w-7xl mx-auto px-4 space-y-8">
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- BOX A: ASSEGNA DA LISTONE -->
                <div class="bg-white p-6 shadow-xl rounded-xl border-t-4 border-green-600">
                    <h3 class="font-black uppercase text-xs mb-4 text-gray-400">Assegna da Svincolati</h3>
                    <div class="space-y-4">
                        <select v-model="selectedTeamId" class="w-full rounded-lg border-gray-300 text-sm">
                            <option value="">Seleziona Squadra Destinataria...</option>
                            <option v-for="t in teams" :key="t.id" :value="t.user_id">{{ t.team_name }}</option>
                        </select>
                        <input v-model="search" type="text" class="w-full rounded-lg border-gray-300 text-sm" placeholder="Cerca calciatore nel database...">
                        <div class="grid grid-cols-2 gap-2">
                            <div v-for="p in filteredPlayers" :key="p.id" class="flex justify-between items-center p-2 bg-gray-50 rounded border text-[10px]">
                                <span class="font-bold uppercase"><b>{{ p.role }}</b> {{ p.name }}</span>
                                <button @click="assign(p)" class="bg-green-600 text-white px-2 py-1 rounded font-black">VAI</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- BOX B: INSERIMENTO MANUALE (IL NUOVO PEZZO) -->
                <div class="bg-white p-6 shadow-xl rounded-xl border-t-4 border-indigo-600">
                    <h3 class="font-black uppercase text-xs mb-4 text-gray-400">Inserimento Manuale (Crea Nuovo)</h3>
                    <form @submit.prevent="submitManual" class="space-y-3">
                        <div class="grid grid-cols-2 gap-3">
                            <input v-model="manualForm.name" type="text" placeholder="Nome Calciatore" class="rounded-lg border-gray-300 text-sm shadow-sm" required>
                            <select v-model="manualForm.role" class="rounded-lg border-gray-300 text-sm shadow-sm">
                                <option value="P">Portiere</option>
                                <option value="D">Difensore</option>
                                <option value="C">Centrocampista</option>
                                <option value="A">Attaccante</option>
                            </select>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <input v-model="manualForm.real_team" type="text" placeholder="Squadra Reale" class="rounded-lg border-gray-300 text-sm shadow-sm" required>
                            <select v-model="manualForm.user_id" class="rounded-lg border-gray-300 text-sm shadow-sm">
                                <option value="">Assegna a...</option>
                                <option v-for="t in teams" :key="t.id" :value="t.user_id">{{ t.team_name }}</option>
                            </select>
                        </div>
                        <div class="grid grid-cols-3 gap-3 items-center">
                            <input type="number" v-model="manualForm.price" placeholder="Prezzo" class="rounded-lg border-gray-300 text-sm">
                            <input type="number" v-model="manualForm.years" placeholder="Anni" class="rounded-lg border-gray-300 text-sm">
                            <button class="bg-indigo-600 text-white py-2 rounded-lg font-black uppercase text-[10px]">Crea e Inserisci</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ROSE ATTUALI -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div v-for="t in teams" :key="t.id" class="bg-white p-4 shadow rounded-xl border">
                    <div class="flex justify-between items-center border-b pb-2 mb-4 bg-gray-50 p-2 rounded">
                        <h4 class="font-black uppercase text-blue-600 text-xs">{{ t.team_name }}</h4>
                        <div class="flex items-center gap-1">
                            <input type="number" v-model="creditInputs[t.id]" class="w-16 p-1 text-xs border-gray-300 rounded font-mono font-bold text-green-600">
                            <button @click="updateCredits(t.id)" class="bg-gray-800 text-white px-2 py-1 rounded text-[8px] font-black uppercase">Set</button>
                        </div>
                    </div>
                    <div class="space-y-1">
                        <div v-for="r in t.players" :key="r.id" class="flex justify-between items-center p-2 hover:bg-red-50 group rounded border border-transparent transition">
                            <span class="text-xs uppercase"><b>{{ r.player.role }}</b> {{ r.player.name }}</span>
                            <div class="flex items-center gap-2">
                                <span class="text-[9px] text-gray-400">{{ r.purchase_price }} cr / {{ r.contract_years }}y</span>
                                <button @click="remove(r.id)" class="text-red-500 opacity-0 group-hover:opacity-100 font-bold">✕</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>