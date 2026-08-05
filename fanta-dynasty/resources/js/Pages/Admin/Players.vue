<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({ players: Array });

// Form aggiunta singola
const form = useForm({ name: '', role: 'D', real_team: '' });
const submit = () => form.post(route('admin.players.store'), { onSuccess: () => form.reset() });

// --- LOGICA AGGIORNAMENTO MASSIVO ---
const bulkText = ref('');
const bulkForm = useForm({ data: [] });

const processBulk = () => {
    // Trasformiamo il testo incollato in un elenco che il computer capisce
    // Formato richiesto: Nome,Quotazione (una per riga)
    const lines = bulkText.value.split('\n');
    const updateData = [];
    
    lines.forEach(line => {
        const parts = line.split(',');
        if (parts.length === 2) {
            updateData.push({
                name: parts[0].trim(),
                quotation: parseInt(parts[1].trim())
            });
        }
    });

    if (updateData.length === 0) return alert("Formato non valido! Usa: Nome,Quotazione");
    
    bulkForm.data = updateData;
    bulkForm.post(route('admin.players.mass-update'), {
        onSuccess: () => {
            bulkText.value = '';
            alert("Quotazioni aggiornate!");
        }
    });
};

const deletePlayer = (p) => {
    if (confirm(`Eliminare ${p.name}?`)) {
        useForm({}).delete(route('admin.players.destroy', p.id), { preserveScroll: true });
    }
};

const search = ref('');
const filteredPlayers = computed(() => props.players.filter(p => p.name.toLowerCase().includes(search.value.toLowerCase())));
</script>

<template>
    <Head title="Gestione Listone" />
    <AuthenticatedLayout>
        <template #header><h2 class="font-black text-xl uppercase">Gestione Mondiale Listone</h2></template>
        
        <div class="py-12 max-w-7xl mx-auto px-4 space-y-10">
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- AGGIUNTA SINGOLA -->
                <div class="bg-white p-6 shadow rounded-xl border-t-4 border-indigo-600">
                    <h3 class="font-black uppercase text-xs mb-4 text-gray-400">Aggiungi Singolo</h3>
                    <form @submit.prevent="submit" class="space-y-4">
                        <input v-model="form.name" type="text" class="w-full rounded-lg border-gray-300 text-sm" placeholder="Nome">
                        <div class="flex gap-2">
                            <select v-model="form.role" class="flex-1 rounded-lg border-gray-300 text-sm">
                                <option value="P">P</option><option value="D">D</option><option value="C">C</option><option value="A">A</option>
                            </select>
                            <input v-model="form.real_team" type="text" class="flex-1 rounded-lg border-gray-300 text-sm" placeholder="Squadra Reale">
                        </div>
                        <button class="w-full bg-indigo-600 text-white py-2 rounded-lg font-black uppercase text-xs">Aggiungi</button>
                    </form>
                </div>

                <!-- AGGIORNAMENTO MASSIVO QUOTAZIONI -->
                <div class="bg-white p-6 shadow rounded-xl border-t-4 border-green-600">
                    <h3 class="font-black uppercase text-xs mb-2 text-gray-400">Aggiorna Quotazioni (Bulk)</h3>
                    <p class="text-[9px] text-gray-400 uppercase mb-4 italic">Incolla da Excel/Sheets nel formato: Nome,Quotazione</p>
                    <textarea 
                        v-model="bulkText" 
                        rows="5" 
                        class="w-full rounded-lg border-gray-300 text-xs font-mono"
                        placeholder="Lautaro Martinez,42&#10;Mike Maignan,19"
                    ></textarea>
                    <button @click="processBulk" class="mt-4 w-full bg-green-600 text-white py-2 rounded-lg font-black uppercase text-xs">Aggiorna Tutto</button>
                </div>
            </div>

            <!-- TABELLA -->
            <div class="bg-white shadow rounded-xl overflow-hidden border">
                <div class="p-4 bg-gray-50 border-b"><input v-model="search" type="text" placeholder="Cerca..." class="w-full max-w-sm rounded-lg border-gray-300 text-sm"></div>
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-gray-100 text-[10px] font-black uppercase text-gray-400">
                            <th class="p-4">Ruolo</th>
                            <th class="p-4">Nome</th>
                            <th class="p-4 text-center">Quotazione</th>
                            <th class="p-4 text-right">Azione</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="p in filteredPlayers" :key="p.id" class="border-b hover:bg-gray-50 transition">
                            <td class="p-4"><span class="font-bold text-blue-600">{{ p.role }}</span></td>
                            <td class="p-4 font-black uppercase text-sm">{{ p.name }}</td>
                            <td class="p-4 text-center font-mono font-bold text-green-600">{{ p.quotation }} cr</td>
                            <td class="p-4 text-right"><button @click="deletePlayer(p)" class="text-red-500 font-bold uppercase text-[10px]">Elimina</button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>