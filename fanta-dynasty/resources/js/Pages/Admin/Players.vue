<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({ players: Array });

const getRoleClass = (role) => {
    if (role === 'P') return 'role-P';
    if (role === 'D') return 'role-D';
    if (role === 'C') return 'role-C';
    if (role === 'A') return 'role-A';
    return '';
};

const singleForm = useForm({ name: '', role: 'D', real_team: '' });
const submitSingle = () => singleForm.post(route('admin.players.store'), { onSuccess: () => singleForm.reset() });

// --- LOGICA IMPORTATORE MASTER ---
const bulkText = ref('');
const bulkForm = useForm({ players_list: [] });

const processBulkImport = () => {
    if (!bulkText.value.trim()) return alert("Incolla i dati!");
    const lines = bulkText.value.split('\n');
    const results = [];
    lines.forEach(line => {
        let parts = line.split('\t'); 
        if (parts.length < 4) parts = line.split(';');
        if (parts.length < 4) parts = line.split(',');
        if (parts.length >= 4) {
            results.push({ name: parts[0].trim(), role: parts[1].trim().toUpperCase(), team: parts[2].trim(), quotation: parts[3].trim().replace(/[^0-9]/g, '') });
        }
    });
    if (results.length === 0) return alert("Formato Errato!");
    bulkForm.players_list = results;
    bulkForm.post(route('admin.players.bulk-import'), { preserveScroll: true, onSuccess: () => { bulkText.value = ''; alert("Sincronizzazione completata!"); } });
};

const deletePlayer = (p) => {
    if (confirm(`Eliminare ${p.name}?`)) useForm({}).delete(route('admin.players.destroy', p.id), { preserveScroll: true });
};

const search = ref('');
const filteredPlayers = computed(() => {
    const list = props.players || [];
    return list.filter(p => p.name.toLowerCase().includes(search.value.toLowerCase()));
});
</script>

<template>
    <Head title="Gestione Listone" />
    <AuthenticatedLayout>
        <template #header><h2 class="font-black text-xl uppercase tracking-tighter text-gray-800">Direzione Listone Mondiale</h2></template>
        <div class="py-12 max-w-7xl mx-auto px-4 space-y-10">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <div class="bg-white p-6 shadow-xl rounded-2xl border-t-4 border-indigo-600">
                    <h3 class="font-black uppercase text-xs mb-4 text-gray-400">Aggiunta Singola</h3>
                    <form @submit.prevent="submitSingle" class="space-y-4">
                        <input v-model="singleForm.name" type="text" class="w-full rounded-xl border-gray-300 text-sm" placeholder="Nome" required>
                        <div class="flex gap-2">
                            <select v-model="singleForm.role" class="flex-1 rounded-xl border-gray-300 text-sm"><option value="P">P</option><option value="D">D</option><option value="C">C</option><option value="A">A</option></select>
                            <input v-model="singleForm.real_team" type="text" class="flex-1 rounded-xl border-gray-300 text-sm" placeholder="Squadra" required>
                        </div>
                        <button class="w-full bg-indigo-600 text-white py-3 rounded-xl font-black uppercase text-xs">Salva</button>
                    </form>
                </div>
                <div class="bg-white p-6 shadow-xl rounded-2xl border-t-4 border-green-600">
                    <h3 class="font-black uppercase text-xs mb-2 text-green-600">Importatore Excel</h3>
                    <textarea v-model="bulkText" rows="4" class="w-full rounded-xl border-gray-300 text-xs font-mono" placeholder="Nome	Ruolo	Squadra	Quota"></textarea>
                    <button @click="processBulkImport" :disabled="bulkForm.processing" class="mt-4 w-full bg-green-600 text-white py-3 rounded-xl font-black uppercase text-xs">Sincronizza Listone</button>
                </div>
            </div>
            <div class="bg-white shadow-2xl rounded-3xl overflow-hidden border border-gray-100">
                <div class="p-4 bg-gray-50 border-b flex justify-between items-center px-8">
                    <input v-model="search" type="text" placeholder="Cerca..." class="w-full max-w-sm rounded-xl border-gray-300 text-sm">
                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Totale: {{ (players || []).length }}</span>
                </div>
                <table class="w-full text-left">
                    <thead><tr class="bg-gray-100 text-[10px] font-black uppercase text-gray-500"><th class="p-6">Ruolo</th><th class="p-6">Nome</th><th class="p-6 text-center">Quota</th><th class="p-6"></th></tr></thead>
                    <tbody>
                        <tr v-for="p in filteredPlayers" :key="p.id" class="border-b last:border-0 hover:bg-gray-50 transition">
                            <td class="p-6"><span class="font-black px-3 py-1 rounded-full text-xs shadow-sm" :class="getRoleClass(p.role)">{{ p.role }}</span></td>
                            <td class="p-6"><p class="font-black uppercase text-sm text-gray-800">{{ p.name }}</p><p class="text-[10px] text-gray-400 font-bold uppercase">{{ p.real_team }}</p></td>
                            <td class="p-6 text-center font-mono font-black text-green-600 text-lg">{{ p.quotation }}</td>
                            <td class="p-6 text-right"><button @click="deletePlayer(p)" class="text-red-500 font-black text-[10px] border border-red-100 px-3 py-1 rounded-lg hover:bg-red-50 transition">Elimina</button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.role-P { color: #856404 !important; background-color: #fff3cd !important; border: 1px solid #ffeeba; }
.role-D { color: #155724 !important; background-color: #d4edda !important; border: 1px solid #c3e6cb; }
.role-C { color: #004085 !important; background-color: #cce5ff !important; border: 1px solid #b8daff; }
.role-A { color: #721c24 !important; background-color: #f8d7da !important; border: 1px solid #f5c6cb; }
</style>