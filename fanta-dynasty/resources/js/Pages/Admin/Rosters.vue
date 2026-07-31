<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed, reactive } from 'vue';

const props = defineProps({ league: Object, teams: Array, availablePlayers: Array });

// --- LOGICA ASSEGNAZIONE DA LISTONE ---
const selectedTeamId = ref('');
const search = ref('');
// Inizializziamo il form con valori di default 1 e 1
const assignForm = useForm({ player_id: null, user_id: null, price: 1, years: 1 });

const filteredPlayers = computed(() => {
    if (!search.value) return [];
    return props.availablePlayers.filter(p => p.name.toLowerCase().includes(search.value.toLowerCase())).slice(0, 10);
});

const assign = (player) => {
    if (!selectedTeamId.value) return alert("Seleziona prima una squadra di destinazione!");
    assignForm.player_id = player.id;
    assignForm.user_id = selectedTeamId.value;
    assignForm.post(route('admin.assign'), { 
        preserveScroll: true,
        onSuccess: () => {
            search.value = '';
            alert(`${player.name} assegnato correttamente!`);
        }
    });
};

// --- LOGICA INSERIMENTO MANUALE ---
const manualForm = useForm({
    name: '',
    role: 'D',
    real_team: '',
    user_id: '',
    price: 1,
    years: 1
});
const submitManual = () => {
    if (!manualForm.user_id) return alert("Seleziona una squadra!");
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
    if (confirm("Rimuovere il giocatore dalla squadra? I crediti verranno rimborsati.")) {
        useForm({ roster_id: rosterId }).post(route('admin.remove'), { preserveScroll: true });
    }
};
</script>

<template>
    <Head title="Gestione Rose" />
    <AuthenticatedLayout>
        <template #header><h2 class="font-black text-xl uppercase tracking-tight">Pannello di Controllo Admin</h2></template>
        
        <div class="py-12 max-w-7xl mx-auto px-4 space-y-8">
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- BOX A: ASSEGNA DA LISTONE (CORRETTO) -->
                <div class="bg-white p-6 shadow-xl rounded-xl border-t-4 border-green-600">
                    <h3 class="font-black uppercase text-xs mb-4 text-green-600">Assegna da Svincolati</h3>
                    <div class="space-y-4">
                        <!-- 1. SQUADRA -->
                        <div>
                            <label class="block text-[10px] font-black uppercase text-gray-400">1. Squadra Destinataria</label>
                            <select v-model="selectedTeamId" class="w-full rounded-lg border-gray-300 text-sm">
                                <option value="">Scegli la squadra...</option>
                                <option v-for="t in teams" :key="t.id" :value="t.user_id">{{ t.team_name }}</option>
                            </select>
                        </div>
                        
                        <!-- 2. PARAMETRI ACQUISTO (ECCO LE BARRE RIPRISTINATE) -->
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] font-black uppercase text-gray-400">2. Crediti Acquisto</label>
                                <input type="number" v-model="assignForm.price" class="w-full rounded-lg border-gray-300 text-sm font-mono" min="0">
                            </div>
                            <div>
                                <label class="block text-[10px] font-black uppercase text-gray-400">3. Anni Contratto</label>
                                <input type="number" v-model="assignForm.years" class="w-full rounded-lg border-gray-300 text-sm font-mono" min="1">
                            </div>
                        </div>

                        <!-- 3. RICERCA -->
                        <div>
                            <label class="block text-[10px] font-black uppercase text-gray-400">4. Cerca e Conferma</label>
                            <input v-model="search" type="text" class="w-full rounded-lg border-gray-300 text-sm" placeholder="Scrivi il nome del calciatore...">
                        </div>

                        <!-- RISULTATI -->
                        <div class="space-y-2">
                            <div v-for="p in filteredPlayers" :key="p.id" class="flex justify-between items-center p-3 bg-gray-50 rounded-lg border hover:border-green-500 transition">
                                <span class="font-bold text-xs uppercase"><b class="text-blue-600 mr-2">{{ p.role }}</b> {{ p.name }}</span>
                                <button @click="assign(p)" class="bg-green-600 text-white px-4 py-1.5 rounded-lg font-black text-[10px] uppercase shadow-sm">Assegna</button>
                            </div>
                            <p v-if="search && filteredPlayers.length === 0" class="text-center text-xs text-gray-400 italic">Nessun calciatore trovato nel listone.</p>
                        </div>
                    </div>
                </div>

                <!-- BOX B: INSERIMENTO MANUALE -->
                <div class="bg-white p-6 shadow-xl rounded-xl border-t-4 border-indigo-600">
                    <h3 class="font-black uppercase text-xs mb-4 text-indigo-600">Crea e Inserisci Manualmente</h3>
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
                                <option value="">Assegna alla squadra...</option>
                                <option v-for="t in teams" :key="t.id" :value="t.user_id">{{ t.team_name }}</option>
                            </select>
                        </div>
                        <div class="grid grid-cols-3 gap-3 items-center">
                            <input type="number" v-model="manualForm.price" placeholder="Prezzo" class="rounded-lg border-gray-300 text-sm font-mono">
                            <input type="number" v-model="manualForm.years" placeholder="Anni" class="rounded-lg border-gray-300 text-sm font-mono">
                            <button class="bg-indigo-600 text-white py-2 rounded-lg font-black uppercase text-[10px] shadow-sm">Crea e Assegna</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ROSE ATTUALI -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div v-for="t in teams" :key="t.id" class="bg-white p-5 shadow-lg rounded-2xl border border-gray-100">
                    <div class="flex justify-between items-center border-b border-gray-100 pb-3 mb-4">
                        <div>
                            <h4 class="font-black uppercase text-blue-600 text-sm">{{ t.team_name }}</h4>
                            <p class="text-[10px] text-gray-400 font-bold uppercase">{{ t.user.name }}</p>
                        </div>
                        <div class="flex items-center gap-1 bg-green-50 p-1.5 rounded-lg border border-green-100">
                            <input type="number" v-model="creditInputs[t.id]" class="w-20 p-1 text-xs border-none bg-transparent font-mono font-black text-green-600 focus:ring-0">
                            <button @click="updateCredits(t.id)" class="bg-green-600 text-white px-2 py-1 rounded text-[8px] font-black uppercase hover:bg-green-700 transition">Set</button>
                        </div>
                    </div>
                    <div class="space-y-1">
                        <div v-for="r in t.players" :key="r.id" class="flex justify-between items-center p-2 hover:bg-red-50 group rounded-lg transition border border-transparent hover:border-red-100">
                            <span class="text-xs uppercase font-medium"><b class="text-blue-500 mr-2">{{ r.player.role }}</b> {{ r.player.name }}</span>
                            <div class="flex items-center gap-3">
                                <span class="text-[10px] text-gray-400 font-mono">{{ r.purchase_price }} cr / {{ r.contract_years }}y</span>
                                <button @click="remove(r.id)" class="text-red-300 opacity-0 group-hover:opacity-100 font-bold hover:text-red-600 transition">✕</button>
                            </div>
                        </div>
                        <div v-if="!t.players || t.players.length === 0" class="text-center py-6 text-gray-300 text-[10px] uppercase font-bold italic tracking-widest">Nessun giocatore in rosa</div>
                    </div>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>