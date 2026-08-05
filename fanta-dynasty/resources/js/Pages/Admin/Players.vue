<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({ players: Array });

// Form per aggiunta singola
const singleForm = useForm({ name: '', role: 'D', real_team: '' });
const submitSingle = () => singleForm.post(route('admin.players.store'), { onSuccess: () => singleForm.reset() });

// --- LOGICA AGGIORNAMENTO MASSIVO ---
const bulkText = ref('');
// USIAMO UN NOME UNIVOCO: list_to_update
const bulkUpdateForm = useForm({ 
    list_to_update: [] 
});

const processBulkUpdate = () => {
    if (!bulkText.value.trim()) {
        alert("Incolla i dati da Excel!");
        return;
    }

    const lines = bulkText.value.split('\n');
    const results = [];
    
    lines.forEach(line => {
        const separator = line.includes(';') ? ';' : ',';
        const parts = line.split(separator);
        
        if (parts.length >= 2) {
            const name = parts[0].trim();
            const quote = parts[1].trim().replace(/[^0-9]/g, '');
            if (name && quote) {
                results.push({ name: name, quotation: quote });
            }
        }
    });

    if (results.length === 0) {
        alert("Formato non valido. Usa: Nome,Quotazione");
        return;
    }
    
    // Assegnazione dati e invio
    bulkUpdateForm.list_to_update = results;
    bulkUpdateForm.post(route('admin.players.mass-update'), {
        preserveScroll: true,
        onSuccess: () => {
            bulkText.value = '';
            alert("✅ Quotazioni aggiornate con successo!");
        },
        onError: () => {
            alert("❌ Errore durante l'aggiornamento.");
        }
    });
};

const deletePlayer = (p) => {
    if (confirm(`Eliminare definitivamente ${p.name}?`)) {
        useForm({}).delete(route('admin.players.destroy', p.id), { preserveScroll: true });
    }
};

const search = ref('');
const filteredPlayers = computed(() => {
    return props.players.filter(p => p.name.toLowerCase().includes(search.value.toLowerCase()));
});
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
                    <form @submit.prevent="submitSingle" class="space-y-4">
                        <input v-model="singleForm.name" type="text" class="w-full rounded-lg border-gray-300 text-sm" placeholder="Nome">
                        <div class="flex gap-2">
                            <select v-model="singleForm.role" class="flex-1 rounded-lg border-gray-300 text-sm">
                                <option value="P">P</option><option value="D">D</option><option value="C">C</option><option value="A">A</option>
                            </select>
                            <input v-model="singleForm.real_team" type="text" class="flex-1 rounded-lg border-gray-300 text-sm" placeholder="Squadra Reale">
                        </div>
                        <button class="w-full bg-indigo-600 text-white py-2 rounded-lg font-black uppercase text-xs">Aggiungi</button>
                    </form>
                </div>

                <!-- AGGIORNAMENTO MASSIVO -->
                <div class="bg-white p-6 shadow rounded-xl border-t-4 border-green-600">
                    <h3 class="font-black uppercase text-xs mb-2 text-gray-400">Aggiorna Quotazioni (Bulk)</h3>
                    <p class="text-[9px] text-gray-400 uppercase mb-4 italic">Incolla da Excel: Nome,Valore</p>
                    <textarea 
                        v-model="bulkText" 
                        rows="5" 
                        class="w-full rounded-lg border-gray-300 text-xs font-mono bg-gray-50 p-3"
                        placeholder="Lautaro Martinez,42"
                    ></textarea>
                    <button 
                        @click="processBulkUpdate" 
                        :disabled="bulkUpdateForm.processing"
                        class="mt-4 w-full bg-green-600 text-white py-3 rounded-lg font-black uppercase text-xs shadow-md"
                    >
                        {{ bulkUpdateForm.processing ? 'Sincronizzazione...' : 'Aggiorna Tutto' }}
                    </button>
                </div>
            </div>

            <!-- TABELLA -->
            <div class="bg-white shadow rounded-xl overflow-hidden border border-gray-200">
                <div class="p-4 bg-gray-50 border-b flex justify-between items-center">
                    <input v-model="search" type="text" placeholder="Cerca nel listone..." class="w-full max-w-sm rounded-lg border-gray-300 text-sm">
                    <span class="text-[10px] font-black text-gray-400 uppercase">Totale: {{ players.length }}</span>
                </div>
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
                            <td class="p-4 font-black uppercase text-sm text-gray-800">{{ p.name }}</td>
                            <td class="p-4 text-center font-mono font-black text-green-600 text-lg">{{ p.quotation }}</td>
                            <td class="p-4 text-right">
                                <button @click="deletePlayer(p)" class="text-red-500 font-bold uppercase text-[10px] hover:underline">Elimina</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>