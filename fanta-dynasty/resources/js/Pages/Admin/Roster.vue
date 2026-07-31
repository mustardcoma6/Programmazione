<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({ league: Object, teams: Array, availablePlayers: Array });

// Logica per assegnazione
const selectedTeamId = ref('');
const assignForm = useForm({ player_id: null, user_id: null, price: 1, years: 1 });

const assign = (player) => {
    if (!selectedTeamId.value) return alert("Seleziona prima una squadra!");
    assignForm.player_id = player.id;
    assignForm.user_id = selectedTeamId.value;
    assignForm.post(route('admin.assign'), { preserveScroll: true });
};

// Logica per rimozione
const remove = (rosterId) => {
    if (confirm("Rimuovere il giocatore e rimborsare i crediti?")) {
        useForm({ roster_id: rosterId }).post(route('admin.remove'), { preserveScroll: true });
    }
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
        <template #header><h2 class="font-black text-xl uppercase">Gestione Rose (Admin)</h2></template>
        <div class="py-12 max-w-7xl mx-auto px-4 space-y-10">
            
            <!-- 1. ASSEGNAZIONE RAPIDA -->
            <div class="bg-white p-6 shadow-xl rounded-xl border-t-4 border-green-600">
                <h3 class="font-black uppercase text-sm mb-4">Assegnazione Manuale</h3>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                    <div class="md:col-span-1">
                        <label class="block text-[10px] font-black uppercase text-gray-400">Squadra Destinataria</label>
                        <select v-model="selectedTeamId" class="w-full rounded-lg border-gray-300 text-sm">
                            <option value="">Scegli squadra...</option>
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
                            <input type="number" v-model="assignForm.price" class="w-1/2 rounded-lg border-gray-300 text-sm" placeholder="cr">
                            <input type="number" v-model="assignForm.years" class="w-1/2 rounded-lg border-gray-300 text-sm" placeholder="y">
                        </div>
                    </div>
                </div>
                <!-- Risultati ricerca -->
                <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-2">
                    <div v-for="p in filteredPlayers" :key="p.id" class="flex justify-between items-center p-2 bg-gray-50 rounded border">
                        <span class="text-xs font-bold uppercase"><b>{{ p.role }}</b> {{ p.name }}</span>
                        <button @click="assign(p)" class="bg-green-600 text-white px-3 py-1 rounded text-[10px] font-black">ASSEGNA</button>
                    </div>
                </div>
            </div>

            <!-- 2. ELENCO ROSE ATTUALI -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div v-for="t in teams" :key="t.id" class="bg-white p-4 shadow rounded-xl border">
                    <div class="flex justify-between border-b pb-2 mb-4">
                        <h4 class="font-black uppercase text-blue-600">{{ t.team_name }}</h4>
                        <span class="font-mono font-bold text-xs">{{ t.remaining_budget }} cr</span>
                    </div>
                    <div class="space-y-1">
                        <div v-for="r in t.players" :key="r.id" class="flex justify-between items-center p-2 hover:bg-red-50 group rounded">
                            <span class="text-xs uppercase"><b>{{ r.player.role }}</b> {{ r.player.name }}</span>
                            <button @click="remove(r.id)" class="text-red-500 opacity-0 group-hover:opacity-100 font-bold">✕</button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>