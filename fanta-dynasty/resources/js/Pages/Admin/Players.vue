<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({ players: Array });

// Form Singolo
const singleForm = useForm({ name: '', role: 'D', real_team: '', nationality: 'Italia' });
const submitSingle = () => singleForm.post(route('admin.players.store'), { onSuccess: () => singleForm.reset() });

// --- LOGICA IMPORTATORE MASTER (MIGLIORATA) ---
const bulkText = ref('');
const bulkForm = useForm({ players_list: [] });

const processBulkImport = () => {
    if (!bulkText.value.trim()) return alert("Incolla i dati da Excel!");

    const lines = bulkText.value.split('\n');
    const results = [];
    
    lines.forEach(line => {
        if (!line.trim()) return; // Salta righe vuote

        // Prova a dividere per Tab (Excel), Punto e virgola o Virgola
        let parts = line.split('\t'); 
        if (parts.length < 4) parts = line.split(';');
        if (parts.length < 4) parts = line.split(',');

        // Mappatura sicura: se una riga ha almeno 4 colonne procediamo
        if (parts.length >= 4) {
            results.push({ 
                name: parts[0] ? parts[0].trim() : '', 
                role: parts[1] ? parts[1].trim().toUpperCase() : '', 
                team: parts[2] ? parts[2].trim() : '', 
                quotation: parts[3] ? parts[3].trim().replace(/[^0-9]/g, '') : '1',
                nationality: parts[4] ? parts[4].trim() : 'Italia' 
            });
        }
    });

    if (results.length === 0) {
        alert("Formato non riconosciuto! Assicurati di copiare le colonne corrette.");
        return;
    }
    
    bulkForm.players_list = results;
    bulkForm.post(route('admin.players.bulk-import'), { 
        preserveScroll: true, 
        onSuccess: () => { 
            bulkText.value = ''; 
            alert(`✅ ${results.length} giocatori processati con successo!`); 
        },
        onError: () => alert("❌ Errore durante l'importazione. Controlla il formato.")
    });
};

const deletePlayer = (p) => {
    if (confirm(`Eliminare ${p.name}?`)) useForm({}).delete(route('admin.players.destroy', p.id), { preserveScroll: true });
};

const search = ref('');
const filteredPlayers = computed(() => (props.players || []).filter(p => p.name.toLowerCase().includes(search.value.toLowerCase())));
</script>

<template>
    <Head title="Gestione Listone" />
    <AuthenticatedLayout>
        <template #header><h2 class="font-black text-xl uppercase tracking-tighter text-gray-800">Direzione Listone Mondiale</h2></template>
        
        <div class="py-12 max-w-7xl mx-auto px-4 space-y-10">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- BOX A: SINGOLO -->
                <div class="bg-white p-6 shadow-xl rounded-2xl border-t-4 border-indigo-600">
                    <h3 class="font-black text-xs uppercase mb-4 text-gray-400">Aggiunta Singola</h3>
                    <form @submit.prevent="submitSingle" class="space-y-4">
                        <input v-model="singleForm.name" type="text" class="w-full rounded-xl border-gray-300" placeholder="Nome" required>
                        <div class="grid grid-cols-3 gap-2">
                            <select v-model="singleForm.role" class="rounded-xl border-gray-300 text-sm"><option value="P">P</option><option value="D">D</option><option value="C">C</option><option value="A">A</option></select>
                            <input v-model="singleForm.real_team" type="text" class="rounded-xl border-gray-300 text-sm" placeholder="Squadra" required>
                            <input v-model="singleForm.nationality" type="text" class="rounded-xl border-gray-300 text-sm" placeholder="Nazione" required>
                        </div>
                        <button class="w-full bg-indigo-600 text-white py-2 rounded-lg font-black uppercase text-xs">Aggiungi</button>
                    </form>
                </div>

                <!-- BOX B: EXCEL (MIGLIORATO) -->
                <div class="bg-white p-6 shadow-xl rounded-2xl border-t-4 border-green-600">
                    <h3 class="font-black text-xs uppercase mb-2 text-green-600">Importatore Master (Excel/Google)</h3>
                    <p class="text-[9px] text-gray-400 uppercase mb-4">Ordine: Nome | Ruolo | Squadra | Quota | Nazione</p>
                    <textarea v-model="bulkText" rows="4" class="w-full rounded-xl border-gray-300 text-xs font-mono bg-gray-50 focus:ring-green-500" placeholder="Lautaro Martinez	A	Inter	42	Argentina"></textarea>
                    <button @click="processBulkImport" :disabled="bulkForm.processing" class="mt-4 w-full bg-green-600 text-white py-3 rounded-xl font-black uppercase text-xs shadow-md">
                        Sincronizza Listone
                    </button>
                </div>
            </div>

            <!-- TABELLA -->
            <div class="bg-white shadow-2xl rounded-3xl overflow-hidden border border-gray-100">
                <div class="p-4 bg-gray-50 border-b flex justify-between items-center px-8">
                    <input v-model="search" type="text" placeholder="Cerca nel database..." class="w-full max-w-sm rounded-xl border-gray-300 text-sm shadow-sm">
                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Totale: {{ (players || []).length }}</span>
                </div>
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-gray-100 text-[10px] font-black uppercase text-gray-500">
                            <th class="p-6">Ruolo</th>
                            <th class="p-6">Nome / Squadra</th>
                            <th class="p-6 text-center">Quotazione</th>
                            <th class="p-6 text-right">Azione</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="p in filteredPlayers" :key="p.id" class="border-b last:border-0 hover:bg-gray-50 transition">
                            <td class="p-6"><span class="font-black px-2 py-1 rounded text-xs" :class="'role-'+p.role">{{ p.role }}</span></td>
                            <td class="p-6"><p class="font-black uppercase text-sm text-gray-800">{{ p.name }}</p><p class="text-[10px] text-gray-400 uppercase">{{ p.real_team }} - {{ p.nationality }}</p></td>
                            <td class="p-6 text-center font-mono font-black text-green-600 text-lg">{{ p.quotation }}</td>
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