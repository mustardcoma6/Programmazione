<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ 
    myData: Object, 
    myPlayers: Array, 
    rosterValue: Number 
});

// --- LOGICA COLORI RUOLI ---
const getRoleClass = (role) => {
    if (role === 'P') return 'role-P';
    if (role === 'D') return 'role-D';
    if (role === 'C') return 'role-C';
    if (role === 'A') return 'role-A';
    return '';
};

// --- LOGICA RINNOVO E CLAUSOLA ---
const addedYears = ref({});
const addedClausola = ref({}); 

props.myPlayers.forEach(p => { 
    addedYears.value[p.id] = 0; 
    addedClausola.value[p.id] = 0; 
});

const changeAdded = (playerId, delta) => {
    const newVal = addedYears.value[playerId] + delta;
    if (newVal >= 0) addedYears.value[playerId] = newVal;
};

const getExpirationDate = (currentYears, added = 0) => {
    const totalYears = currentYears + added;
    return `30/06/${2026 + totalYears}`;
};

const yearForm = useForm({ roster_id: null, new_years: 1, clausola_investment: 0 });

const saveContract = (item) => {
    const yearsToAdd = addedYears.value[item.id];
    const finalTotalYears = item.contract_years + yearsToAdd;
    const clausolaPlus = parseInt(addedClausola.value[item.id]) || 0;
    
    if (confirm(`Confermi il rinnovo per ${item.player.name}?`)) {
        yearForm.roster_id = item.id;
        yearForm.new_years = finalTotalYears;
        yearForm.clausola_investment = clausolaPlus;
        yearForm.post(route('market.update-years'), { 
            preserveScroll: true, 
            onSuccess: () => { 
                addedYears.value[item.id] = 0; 
                addedClausola.value[item.id] = 0; 
                alert("Operazione completata!");
            } 
        });
    }
};
</script>

<template>
    <Head title="La mia Rosa" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-black text-xl uppercase tracking-tighter text-gray-800 italic">Gestione Asset Societari</h2>
        </template>
        
        <div class="py-6 md:py-12 px-4">
            <div class="max-w-7xl mx-auto space-y-6">
                
                <!-- BANNERS RISORSE (Sempre Visibili) -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-white p-6 shadow-lg rounded-3xl border-l-8 border-blue-600 flex justify-between items-center">
                        <span class="font-bold text-gray-400 uppercase text-[10px]">Budget Anni</span>
                        <p class="text-4xl font-black font-mono text-blue-600">{{ myData.years_budget }}</p>
                    </div>
                    <div class="bg-white p-6 shadow-lg rounded-3xl border-l-8 border-green-500 flex justify-between items-center">
                        <span class="font-bold text-gray-400 uppercase text-[10px]">Crediti</span>
                        <p class="text-4xl font-black font-mono text-green-600">{{ myData.remaining_budget }}</p>
                    </div>
                    <div class="bg-white p-6 shadow-lg rounded-3xl border-l-8 border-yellow-500 flex justify-between items-center">
                        <span class="font-bold text-gray-400 uppercase text-[10px]">Valore Rosa</span>
                        <p class="text-4xl font-black font-mono text-yellow-500">{{ rosterValue }}</p>
                    </div>
                </div>

                <!-- 1. VERSIONE DESKTOP (Tabella - Scompare su schermi piccoli) -->
                <div class="hidden md:block bg-white shadow-xl rounded-3xl overflow-hidden border">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-100 text-[10px] font-black uppercase text-gray-600 border-b">
                                <th class="p-4">Calciatore</th>
                                <th class="p-4 text-center">Clausola</th>
                                <th class="p-4 text-center">Anni</th>
                                <th class="p-4 text-center">Scadenza</th>
                                <th class="p-4 text-center">Rinnova</th>
                                <th class="p-4 text-center">Costo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in myPlayers" :key="item.id" class="border-b transition" :class="item.is_primavera ? 'bg-violet-50/50' : 'hover:bg-gray-50'">
                                <td class="p-4 uppercase text-sm font-black text-gray-800">
                                    <span class="mr-2" :class="getRoleClass(item.player.role)">{{ item.player.role }}</span> 
                                    {{ item.player.name }}
                                    <span v-if="item.is_primavera" class="ml-2 text-[8px] bg-violet-600 text-white px-1 rounded">PRIMAVERA</span>
                                </td>
                                <td class="p-4 text-center">
                                    <template v-if="!item.is_primavera">
                                        <div v-if="addedYears[item.id] > 0"><input type="number" v-model="addedClausola[item.id]" class="w-16 p-1 text-center border-orange-300 rounded text-xs" placeholder="+ cr"></div>
                                        <span v-else-if="item.release_clause > 0" class="font-mono font-black text-orange-500">{{ item.release_clause }} cr</span>
                                        <span v-else class="text-gray-400 text-[10px]">NO</span>
                                    </template>
                                    <span v-else class="text-[9px] text-violet-400 font-bold uppercase tracking-widest">VIVAIO</span>
                                </td>
                                <td class="p-4 text-center font-mono font-bold text-gray-400">{{ item.is_primavera ? '-' : item.contract_years }}</td>
                                <td class="p-4 text-center font-mono font-bold text-xs text-gray-600">{{ item.is_primavera ? 'Indeterminata' : getExpirationDate(item.contract_years, addedYears[item.id]) }}</td>
                                <td class="p-4">
                                    <div v-if="!item.is_primavera" class="flex flex-col items-center gap-2">
                                        <div class="flex items-center bg-gray-50 border rounded-lg overflow-hidden"><button @click="changeAdded(item.id, -1)" :disabled="addedYears[item.id] === 0" class="px-2 text-red-600">-</button><span class="px-3 font-black text-blue-600">+{{ addedYears[item.id] }}</span><button @click="changeAdded(item.id, 1)" class="px-2 text-green-600">+</button></div>
                                        <button v-if="addedYears[item.id] > 0" @click="saveContract(item)" class="text-[9px] bg-green-600 text-white px-3 py-1 rounded-full font-black uppercase shadow">Salva</button>
                                    </div>
                                    <span v-else class="block text-center text-[8px] font-black text-violet-400 uppercase italic">Gestito da Admin</span>
                                </td>
                                <td class="p-4 text-center font-mono text-gray-400 text-xs">{{ item.purchase_price }} cr</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- 2. VERSIONE MOBILE (CARD GROSSE - Compare solo su smartphone) -->
                <div class="block md:hidden space-y-6">
                    <div v-for="item in myPlayers" :key="item.id" class="bg-white rounded-[2rem] shadow-xl border-l-[12px] overflow-hidden" :class="item.is_primavera ? 'border-violet-500' : 'border-blue-600'">
                        <!-- Titolo Card -->
                        <div class="p-5 border-b bg-gray-50 flex justify-between items-center">
                            <div class="flex items-center gap-3">
                                <span class="font-black px-3 py-1 rounded-xl text-xs shadow-sm" :class="getRoleClass(item.player.role)">{{ item.player.role }}</span>
                                <span class="font-black uppercase text-xl text-gray-900 tracking-tighter">{{ item.player.name }}</span>
                            </div>
                            <span v-if="item.is_primavera" class="text-[8px] bg-violet-600 text-white px-2 py-1 rounded-full font-black">PRIMAVERA</span>
                        </div>

                        <div class="p-5 space-y-6">
                            <!-- Dati Attuali -->
                            <div class="flex justify-between items-end">
                                <div class="space-y-1">
                                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Contratto & Scadenza</p>
                                    <p class="text-xl font-black text-gray-800">{{ item.is_primavera ? 'VIVAIO' : item.contract_years + ' ANNI' }}</p>
                                    <p class="text-sm font-mono font-bold" :class="addedYears[item.id] > 0 ? 'text-blue-600 animate-pulse' : 'text-gray-500'">
                                        {{ item.is_primavera ? 'Senza Scadenza' : getExpirationDate(item.contract_years, addedYears[item.id]) }}
                                    </p>
                                    <p class="text-[9px] font-bold text-orange-500 uppercase mt-2">Clausola: {{ item.release_clause > 0 ? item.release_clause + ' cr' : 'Nessuna' }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-[10px] font-bold text-gray-400 uppercase">Acquisto</p>
                                    <p class="text-lg font-black text-gray-900 font-mono">{{ item.purchase_price }} cr</p>
                                </div>
                            </div>

                            <!-- Sezione Azioni (Solo per Pro) -->
                            <div v-if="!item.is_primavera" class="bg-blue-50/50 rounded-[1.5rem] p-5 border border-blue-100 space-y-4">
                                <div class="flex justify-between items-center">
                                    <span class="text-xs font-black text-blue-600 uppercase">Rinnova Contratto</span>
                                    <div class="flex items-center bg-white rounded-2xl shadow-sm border border-blue-200 overflow-hidden">
                                        <button @click="changeAdded(item.id, -1)" :disabled="addedYears[item.id] === 0" class="px-6 py-2 text-red-600 font-black text-xl">-</button>
                                        <span class="px-4 font-black text-blue-700 text-2xl font-mono">+{{ addedYears[item.id] }}</span>
                                        <button @click="changeAdded(item.id, 1)" class="px-6 py-2 text-green-600 font-black text-xl">+</button>
                                    </div>
                                </div>

                                <!-- Box Clausola Mobile -->
                                <div v-if="addedYears[item.id] > 0" class="bg-white p-4 rounded-2xl border border-orange-200 shadow-inner">
                                    <label class="block text-[10px] font-black text-orange-500 uppercase text-center mb-2">Aggiungi Crediti a Clausola</label>
                                    <input type="number" v-model="addedClausola[item.id]" class="w-full text-center text-2xl font-black font-mono border-none bg-orange-50 rounded-xl focus:ring-orange-500" placeholder="0">
                                    <p class="text-[10px] text-center text-orange-400 font-bold uppercase mt-2">Nuova Clausola: {{ (item.release_clause > 0 ? item.release_clause : item.purchase_price) + (parseInt(addedClausola[item.id]) || 0) }} cr</p>
                                </div>

                                <button v-if="addedYears[item.id] > 0" @click="saveContract(item)" class="w-full bg-green-600 text-white py-5 rounded-2xl font-black uppercase tracking-widest shadow-lg transform active:scale-95 transition-all text-sm">
                                    Salva Modifiche
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- FINE MOBILE -->

            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.role-P { color: #856404 !important; background-color: #fff3cd !important; padding: 2px 8px; border-radius: 6px; font-weight: 900; }
.role-D { color: #155724 !important; background-color: #d4edda !important; padding: 2px 8px; border-radius: 6px; font-weight: 900; }
.role-C { color: #004085 !important; background-color: #cce5ff !important; padding: 2px 8px; border-radius: 6px; font-weight: 900; }
.role-A { color: #721c24 !important; background-color: #f8d7da !important; padding: 2px 8px; border-radius: 6px; font-weight: 900; }
</style>