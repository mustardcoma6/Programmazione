<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({ 
    players: Array 
});

// --- LOGICA COLORI RUOLI ---
const getRoleClass = (role) => {
    if (role === 'P') return 'role-P'; // Giallo Oro
    if (role === 'D') return 'role-D'; // Verde Scuro
    if (role === 'C') return 'role-C'; // Blu
    if (role === 'A') return 'role-A'; // Rosso
    return '';
};

// --- AGGIUNTA SINGOLA ---
const singleForm = useForm({ name: '', role: 'D', real_team: '' });
const submitSingle = () => singleForm.post(route('admin.players.store'), { 
    onSuccess: () => {
        singleForm.reset();
        alert("Giocatore aggiunto!");
    }
});

// --- IMPORTATORE MASTER EXCEL ---
const bulkText = ref('');
const bulkUpdateForm = useForm({ 
    list_to_update: [] // Usiamo il nome concordato col Controller
});

const processBulkUpdate = () => {
    if (!bulkText.value.trim()) return alert("Incolla i dati da Excel!");

    const lines = bulkText.value.split('\n');
    const results = [];
    
    lines.forEach(line => {
        // Riconoscimento automatico separatore (Tab, Punto e virgola o Virgola)
        let parts = line.split('\t'); 
        if (parts.length < 4) parts = line.split(';');
        if (parts.length < 4) parts = line.split(',');

        if (parts.length >= 4) {
            results.push({
                name: parts[0].trim(),
                role: parts[1].trim().toUpperCase(),
                team: parts[2].trim(),
                quotation: parts[3].trim().replace(/[^0-9]/g, '')
            });
        }
    });

    if (results.length === 0) {
        alert("Formato non valido! Copia 4 colonne: Nome, Ruolo, Squadra, Quotazione.");
        return;
    }
    
    if (confirm(`Rilevati ${results.length} giocatori. Sincronizzare il listone?`)) {
        bulkUpdateForm.list_to_update = results;
        bulkUpdateForm.post(route('admin.players.mass-update'), {
            preserveScroll: true,
            onSuccess: () => {
                bulkText.value = '';
                alert("✅ Listone sincronizzato correttamente!");
            }
        });
    }
};

// --- ELIMINAZIONE ---
const deletePlayer = (p) => {
    if (confirm(`Eliminare definitivamente ${p.name} dal sistema?`)) {
        useForm({}).delete(route('admin.players.destroy', p.id), { preserveScroll: true });
    }
};

// --- RICERCA LOCALE ---
const search = ref('');
const filteredPlayers = computed(() => {
    const list = props.players || [];
    return list.filter(p => p.name.toLowerCase().includes(search.value.toLowerCase()));
});
</script>

<template>
    <Head title="Gestione Listone" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-black text-xl uppercase tracking-tighter text-gray-800">Direzione Listone Mondiale</h2>
        </template>
        
        <div class="py-12 max-w-7xl mx-auto px-4 space-y-10">
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- BOX AGGIUNTA RAPIDA -->
                <div class="bg-white p-6 shadow-xl rounded-2xl border-t-4 border-indigo-600">
                    <h3 class="font-black uppercase text-xs mb-4 text-gray-400">Aggiunta Singola</h3>
                    <form @submit.prevent="submitSingle" class="space-y-4">
                        <input v-model="singleForm.name" type="text" class="w-full rounded-xl border-gray-300 text-sm" placeholder="Nome Calciatore" required>
                        <div class="flex gap-2">
                            <select v-model="singleForm.role" class="flex-1 rounded-xl border-gray-300 text-sm font-bold">
                                <option value="P">P - Portiere</option>
                                <option value="D">D - Difensore</option>
                                <option value="C">C - Centrocampista</option>
                                <option value="A">A - Attaccante</option>
                            </select>
                            <input v-model="singleForm.real_team" type="text" class="flex-1 rounded-xl border-gray-300 text-sm" placeholder="Squadra Reale" required>
                        </div>
                        <button class="w-full bg-indigo-600 text-white py-3 rounded-xl font-black uppercase text-xs shadow-lg hover:bg-indigo-700 transition">Salva nel Sistema</button>
                    </form>
                </div>

                <!-- BOX AGGIORNAMENTO MASSIVO -->
                <div class="bg-white p-6 shadow-xl rounded-2xl border-t-4 border-green-600">
                    <h3 class="font-black uppercase text-xs mb-2 text-green-600">Importatore Master (Excel/Google)</h3>
                    <p class="text-[9px] text-gray-400 uppercase mb-4 italic leading-relaxed">
                        Copia 4 colonne dal tuo foglio e incollale qui.<br>
                        Formato: <b class="text-gray-600">Nome | Ruolo | Squadra | Quotazione</b>
                    </p>
                    <textarea 
                        v-model="bulkText" 
                        rows="4" 
                        class="w-full rounded-xl border-gray-300 text-xs font-mono bg-gray-50 p-3 focus:ring-green-500"
                        placeholder="Lautaro Martinez	A	Inter	42"
                    ></textarea>
                    <button 
                        @click="processBulkUpdate" 
                        :disabled="bulkUpdateForm.processing"
                        class="mt-4 w-full bg-green-600 text-white py-3 rounded-xl font-black uppercase text-xs shadow-lg hover:bg-green-700 transition"
                    >
                        {{ bulkUpdateForm.processing ? 'Sincronizzazione in corso...' : 'Sincronizza Listone' }}
                    </button>
                </div>
            </div>

            <!-- TABELLA COMPLETA -->
            <div class="bg-white shadow-2xl rounded-3xl overflow-hidden border border-gray-100">
                <div class="p-4 bg-gray-50 border-b flex justify-between items-center px-8">
                    <input v-model="search" type="text" placeholder="Cerca nel database..." class="w-full max-w-sm rounded-xl border-gray-300 text-sm shadow-sm">
                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Giocatori Totali: {{ (players || []).length }}</span>
                </div>
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-gray-100 text-[10px] font-black uppercase text-gray-500">
                            <th class="p-6">Ruolo</th>
                            <th class="p-6">Nome / Squadra</th>
                            <th class="p-6 text-center">Quotazione</th>
                            <th class="p-6 text-right">Manutenzione</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="p in filteredPlayers" :key="p.id" class="border-b last:border-0 hover:bg-gray-50 transition">
                            <td class="p-6">
                                <span class="font-black px-3 py-1 rounded-full text-xs shadow-sm" :class="getRoleClass(p.role)">
                                    {{ p.role }}
                                </span>
                            </td>
                            <td class="p-6">
                                <p class="font-black uppercase text-sm text-gray-800 tracking-tighter">{{ p.name }}</p>
                                <p class="text-[10px] text-gray-400 font-bold uppercase">{{ p.real_team }}</p>
                            </td>
                            <td class="p-6 text-center font-mono font-black text-green-600 text-lg">
                                {{ p.quotation }}
                            </td>
                            <td class="p-6 text-right">
                                <button @click="deletePlayer(p)" class="text-red-500 hover:text-red-700 font-black uppercase text-[10px] border border-red-100 px-3 py-1 rounded-lg hover:bg-red-50 transition">Elimina</button>
                            </td>
                        </tr>
                        <tr v-if="filteredPlayers.length === 0">
                            <td colspan="4" class="p-20 text-center text-gray-300 italic">Nessun giocatore trovato.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.role-P { color: #856404 !important; background-color: #fff3cd !important; border: 1px solid #ffeeba; } /* Giallo Oro Scuro per leggibilità */
.role-D { color: #155724 !important; background-color: #d4edda !important; border: 1px solid #c3e6cb; } /* Verde Scuro */
.role-C { color: #004085 !important; background-color: #cce5ff !important; border: 1px solid #b8daff; } /* Blu */
.role-A { color: #721c24 !important; background-color: #f8d7da !important; border: 1px solid #f5c6cb; } /* Rosso */
</style>