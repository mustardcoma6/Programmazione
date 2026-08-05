<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({ players: Array });

// Form aggiunta singola
const form = useForm({ name: '', role: 'D', real_team: '' });
const submit = () => form.post(route('admin.players.store'), { onSuccess: () => form.reset() });

// --- LOGICA AGGIORNAMENTO MASSIVO (CORRETTA) ---
const bulkText = ref('');
const bulkForm = useForm({ data: [] });

const processBulk = () => {
    if (!bulkText.value.trim()) return alert("Incolla prima i dati!");

    const lines = bulkText.value.split('\n');
    const updateData = [];
    
    lines.forEach(line => {
        // Supportiamo sia la virgola che il punto e virgola (tipico di Excel)
        const separator = line.includes(';') ? ';' : ',';
        const parts = line.split(separator);
        
        if (parts.length >= 2) {
            updateData.push({
                name: parts[0].trim(),
                quotation: parts[1].trim().replace(/[^0-9]/g, '') // Prende solo i numeri
            });
        }
    });

    if (updateData.length === 0) {
        alert("Formato non riconosciuto. Usa: Nome,Quotazione");
        return;
    }
    
    // Spediamo i dati
    bulkForm.data = updateData;
    bulkForm.post(route('admin.players.mass-update'), {
        preserveScroll: true,
        onSuccess: () => {
            bulkText.value = '';
            alert("✅ Tutte le quotazioni trovate sono state aggiornate!");
        },
        onError: () => alert("❌ Errore nel server durante l'aggiornamento.")
    });
};

const deletePlayer = (p) => {
    if (confirm(`Eliminare definitivamente ${p.name}?`)) {
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
                    <h3 class="font-black uppercase text-xs mb-2 text-gray-400">Aggiorna Quotazioni (Incolla da Excel)</h3>
                    <p class="text-[9px] text-gray-400 uppercase mb-4 italic leading-tight">
                        Copia due colonne (Nome e Quotazione) e incollale qui.<br>
                        Il formato deve essere: Nome,Valore
                    </p>
                    <textarea 
                        v-model="bulkText" 
                        rows="5" 
                        class="w-full rounded-lg border-gray-300 text-xs font-mono bg-gray-50 p-3"
                        placeholder="Lautaro Martinez,42&#10;Nicolo Barella,20"
                    ></textarea>
                    <button 
                        @click="processBulk" 
                        :disabled="bulkForm.processing"
                        class="mt-4 w-full bg-green-600 text-white py-3 rounded-lg font-black uppercase text-xs shadow-md hover:bg-green-700 transition"
                    >
                        {{ bulkForm.processing ? 'Aggiornamento in corso...' : 'Aggiorna Tutto' }}
                    </button>
                </div>
            </div>

            <!-- TABELLA -->
            <div class="bg-white shadow rounded-xl overflow-hidden border">
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
                            <td class="p-4 text-center">
                                <span class="font-mono font-black text-green-600 text-lg">{{ p.quotation }}</span>
                                <span class="text-[10px] text-gray-400 ml-1">cr</span>
                            </td>
                            <td class="p-4 text-right">
                                <button @click="deletePlayer(p)" class="text-red-500 font-bold uppercase text-[10px]">Elimina</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>