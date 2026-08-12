<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({ players: Array });

// ... (logica store e bulkImport rimangono uguali) ...
const singleForm = useForm({ name: '', role: 'D', real_team: '' });
const submitSingle = () => singleForm.post(route('admin.players.store'), { onSuccess: () => singleForm.reset() });
const bulkText = ref('');
const bulkForm = useForm({ players_list: [] });
const processBulkImport = () => {
    const lines = bulkText.value.split('\n');
    const results = [];
    lines.forEach(line => {
        let parts = line.split('\t'); if (parts.length < 4) parts = line.split(';'); if (parts.length < 4) parts = line.split(',');
        if (parts.length >= 4) results.push({ name: parts[0].trim(), role: parts[1].trim(), team: parts[2].trim(), quotation: parts[3].trim().replace(/[^0-9]/g, '') });
    });
    if (results.length === 0) return alert("Formato Errato!");
    bulkForm.players_list = results;
    bulkForm.post(route('admin.players.bulk-import'), { preserveScroll: true, onSuccess: () => { bulkText.value = ''; alert("Sincronizzato!"); } });
};

// --- FIX TASTO ELIMINA ---
const deletePlayer = (p) => {
    if (confirm(`Vuoi eliminare definitivamente ${p.name}? Verrà rimosso da ogni squadra e asta.`)) {
        router.delete(route('admin.players.destroy', p.id), {
            preserveScroll: true,
            onSuccess: () => alert("Giocatore rimosso ovunque.")
        });
    }
};

const search = ref('');
const filteredPlayers = computed(() => (props.players || []).filter(p => p.name.toLowerCase().includes(search.value.toLowerCase())));
</script>

<template>
    <Head title="Gestione Listone" />
    <AuthenticatedLayout>
        <template #header><h2 class="font-black text-xl uppercase text-gray-800">Direzione Listone</h2></template>
        <div class="py-12 max-w-7xl mx-auto px-4 space-y-10">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <div class="bg-white p-6 shadow rounded-xl border-t-4 border-indigo-600">
                    <h3 class="font-black text-xs uppercase mb-4 text-gray-400">Aggiunta Rapida</h3>
                    <form @submit.prevent="submitSingle" class="space-y-4">
                        <input v-model="singleForm.name" type="text" class="w-full rounded-lg border-gray-300 shadow-sm" placeholder="Nome" required>
                        <div class="grid grid-cols-2 gap-2">
                            <select v-model="singleForm.role" class="rounded-lg border-gray-300 shadow-sm"><option value="P">P</option><option value="D">D</option><option value="C">C</option><option value="A">A</option></select>
                            <input v-model="singleForm.real_team" type="text" class="rounded-lg border-gray-300 shadow-sm" placeholder="Squadra" required>
                        </div>
                        <button class="w-full bg-indigo-600 text-white py-2 rounded-lg font-black uppercase text-xs">Salva</button>
                    </form>
                </div>
                <div class="bg-white p-6 shadow rounded-xl border-t-4 border-green-600">
                    <h3 class="font-black text-xs uppercase mb-2 text-green-600">Importatore Excel</h3>
                    <textarea v-model="bulkText" rows="4" class="w-full rounded-lg border-gray-300 text-xs font-mono" placeholder="Nome	Ruolo	Squadra	Quota"></textarea>
                    <button @click="processBulkImport" :disabled="bulkForm.processing" class="mt-4 w-full bg-green-600 text-white py-3 rounded-xl font-black uppercase text-xs">Sincronizza</button>
                </div>
            </div>
            <div class="bg-white shadow rounded-xl overflow-hidden border">
                <div class="p-4 bg-gray-50 border-b flex justify-between items-center"><input v-model="search" type="text" placeholder="Cerca..." class="w-full max-w-sm rounded-xl border-gray-300 text-sm"></div>
                <table class="w-full text-left">
                    <thead><tr class="bg-gray-100 text-[10px] font-black uppercase text-gray-500"><th class="p-6">Ruolo</th><th class="p-6">Nome</th><th class="p-6 text-center">Quota</th><th class="p-4"></th></tr></thead>
                    <tbody>
                        <tr v-for="p in filteredPlayers" :key="p.id" class="border-b hover:bg-gray-50 transition">
                            <td class="p-6"><b>{{ p.role }}</b></td>
                            <td class="p-6"><p class="font-black uppercase text-sm text-gray-800">{{ p.name }}</p><p class="text-[10px] text-gray-400">{{ p.real_team }}</p></td>
                            <td class="p-6 text-center font-mono font-black text-green-600">{{ p.quotation }}</td>
                            <td class="p-6 text-right"><button @click="deletePlayer(p)" class="text-red-500 font-bold uppercase text-[10px] hover:underline">Elimina</button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.role-P { color: #856404 !important; background-color: #fff3cd !important; }
.role-D { color: #155724 !important; background-color: #d4edda !important; }
.role-C { color: #004085 !important; background-color: #cce5ff !important; }
.role-A { color: #721c24 !important; background-color: #f8d7da !important; }
</style>