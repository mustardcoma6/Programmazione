<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({ league: Object, teams: Array, availablePlayers: Array });
const selectedTeamId = ref('');
const search = ref('');
const assignForm = useForm({ player_id: null, user_id: null, price: 1 });

const filteredPlayers = computed(() => {
    if (!search.value) return [];
    return props.availablePlayers.filter(p => p.name.toLowerCase().includes(search.value.toLowerCase())).slice(0, 8);
});

const assign = (player) => {
    if (!selectedTeamId.value) return alert("Seleziona squadra!");
    assignForm.player_id = player.id;
    assignForm.user_id = selectedTeamId.value;
    assignForm.post(route('admin.primavera.assign'), { preserveScroll: true, onSuccess: () => search.value = '' });
};

const remove = (id) => {
    if (confirm("Rimuovere dalla Primavera?")) {
        useForm({ roster_id: id }).post(route('admin.primavera.remove'), { preserveScroll: true });
    }
};
</script>

<template>
    <Head title="Gestione Primavera" />
    <AuthenticatedLayout>
        <template #header><h2 class="font-black text-xl uppercase text-violet-600">Gestione Primavera (Admin)</h2></template>
        <div class="py-12 max-w-7xl mx-auto px-4 space-y-10">
            
            <div class="bg-white p-6 shadow-xl rounded-xl border-t-4 border-violet-600">
                <h3 class="font-black uppercase text-xs mb-4 text-violet-400">Assegnazione Vivaio (Sottrae crediti Prima Rosa)</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                    <select v-model="selectedTeamId" class="rounded-lg border-gray-300 text-sm">
                        <option value="">Squadra...</option>
                        <option v-for="t in teams" :key="t.id" :value="t.user_id">{{ t.team_name }}</option>
                    </select>
                    <input v-model="search" type="text" class="rounded-lg border-gray-300 text-sm" placeholder="Cerca giovane...">
                    <input type="number" v-model="assignForm.price" class="rounded-lg border-gray-300 text-sm" placeholder="Prezzo">
                </div>
                <div class="mt-4 grid grid-cols-2 gap-2">
                    <div v-for="p in filteredPlayers" :key="p.id" class="flex justify-between items-center p-2 bg-violet-50 rounded border border-violet-100">
                        <span class="text-xs font-bold uppercase text-violet-700"><b>{{ p.role }}</b> {{ p.name }}</span>
                        <button @click="assign(p)" class="bg-violet-600 text-white px-3 py-1 rounded text-[10px] font-black uppercase">Metti in Primavera</button>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div v-for="t in teams" :key="t.id" class="bg-white p-4 shadow rounded-xl border-l-4 border-violet-500">
                    <h4 class="font-black uppercase text-violet-600 text-xs mb-4 border-b pb-2">{{ t.team_name }} (Vivaio)</h4>
                    <div class="space-y-1">
                        <div v-for="r in t.primavera_players" :key="r.id" class="flex justify-between items-center p-2 bg-violet-50/50 rounded-lg">
                            <span class="text-xs font-bold uppercase text-violet-900">{{ r.player.role }} - {{ r.player.name }}</span>
                            <div class="flex items-center gap-2">
                                <span class="text-[9px] font-mono text-violet-400">{{ r.purchase_price }} cr</span>
                                <button @click="remove(r.id)" class="text-red-500 font-bold">✕</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>