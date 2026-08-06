<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ 
    myData: Object, 
    myPlayers: Array,
    rosterValue: Number // Riceviamo il valore totale dal server
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
    if (newVal >= 0) {
        addedYears.value[playerId] = newVal;
        if (newVal === 0) addedClausola.value[playerId] = 0;
    }
};

const getExpirationDate = (currentYears, added = 0) => {
    const totalYears = currentYears + added;
    const baseYear = 2026;
    return `30/06/${baseYear + totalYears}`;
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

        <div class="py-6 md:py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
                
                <!-- TRE BANNER: BUDGET ANNI, CREDITI E VALORE ROSA (NUOVO) -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-white p-6 shadow-lg rounded-3xl border-l-8 border-blue-600 flex justify-between items-center">
                        <span class="font-bold text-gray-400 uppercase text-[10px] tracking-widest">Budget Anni</span>
                        <p class="text-4xl font-black font-mono text-blue-600">{{ myData.years_budget }}</p>
                    </div>
                    <div class="bg-white p-6 shadow-lg rounded-3xl border-l-8 border-green-500 flex justify-between items-center">
                        <span class="font-bold text-gray-400 uppercase text-[10px] tracking-widest">Crediti</span>
                        <p class="text-4xl font-black font-mono text-green-600">{{ myData.remaining_budget }}</p>
                    </div>
                    <!-- BANNER VALORE ROSA -->
                    <div class="bg-white p-6 shadow-lg rounded-3xl border-l-8 border-yellow-500 flex justify-between items-center">
                        <span class="font-bold text-gray-400 uppercase text-[10px] tracking-widest">Valore Rosa</span>
                        <p class="text-4xl font-black font-mono text-yellow-500">{{ rosterValue }}</p>
                    </div>
                </div>

                <!-- 1. VERSIONE DESKTOP (Tabella ordinata per ruolo) -->
                <div class="hidden md:block bg-white shadow-xl rounded-3xl overflow-hidden border border-gray-200">
                    <table class="w-full text-left">
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
                            <tr v-for="item in myPlayers" :key="item.id" class="border-b transition" 
    :class="item.is_primavera ? 'bg-violet-50 hover:bg-violet-100' : 'hover:bg-gray-50'">
    
    <td class="p-4 uppercase text-sm font-black text-gray-800">
        <span class="mr-2" :class="getRoleClass(item.player.role)">{{ item.player.role }}</span> 
        {{ item.player.name }}
        <span v-if="item.is_primavera" class="ml-2 text-[8px] bg-violet-600 text-white px-1 rounded">PRIMAVERA</span>
    </td>

    <!-- Mostra anni e rinnovo SOLO se non è primavera -->
    <template v-if="!item.is_primavera">
        <!-- ... (qui i td della clausola, anni, scadenza e rinnova che hai già) ... -->
    </template>
    <template v-else>
        <td colspan="4" class="text-center text-[10px] font-bold text-violet-400 uppercase tracking-widest">Contratto Primavera (Senza Scadenza)</td>
    </template>

    <td class="p-4 text-center font-mono text-gray-400 text-xs">{{ item.purchase_price }} cr</td>
</tr>
                        </tbody>
                    </table>
                </div>

                <!-- 2. VERSIONE MOBILE (Schede ordinate per ruolo) -->
                <div class="md:hidden space-y-4">
                    <div v-for="item in myPlayers" :key="item.id" class="bg-white rounded-3xl shadow-md border border-gray-100 overflow-hidden">
                        <div class="p-4 bg-gray-50 border-b flex justify-between items-center">
                            <div class="flex items-center gap-2">
                                <span class="font-black px-2 py-0.5 rounded-lg text-xs" :class="getRoleClass(item.player.role)">{{ item.player.role }}</span>
                                <span class="font-black uppercase text-gray-900">{{ item.player.name }}</span>
                            </div>
                            <span class="text-[10px] font-mono font-bold text-gray-400">Costo: {{ item.purchase_price }} cr</span>
                        </div>
                        <div class="p-4 grid grid-cols-2 gap-4">
                            <div class="space-y-1">
                                <p class="text-[9px] font-bold text-gray-400 uppercase">Stato Attuale</p>
                                <p class="text-sm font-black text-gray-700">{{ item.contract_years }} Anni</p>
                                <p class="text-[10px] font-mono text-gray-500">{{ getExpirationDate(item.contract_years) }}</p>
                                <p class="mt-2 text-[9px] font-bold text-orange-500 uppercase">Clausola: {{ item.release_clause > 0 ? item.release_clause + ' cr' : 'No' }}</p>
                            </div>
                            <div class="flex flex-col items-center justify-center bg-blue-50/50 rounded-2xl p-2 border border-blue-100">
                                <p class="text-[9px] font-black text-blue-600 uppercase mb-2">Rinnova</p>
                                <div class="flex items-center bg-white border border-blue-200 rounded-xl shadow-sm overflow-hidden mb-2">
                                    <button @click="changeAdded(item.id, -1)" :disabled="addedYears[item.id] === 0" class="px-4 py-2 text-red-600 font-black text-lg">-</button>
                                    <span class="px-4 font-black text-blue-700">+{{ addedYears[item.id] }}</span>
                                    <button @click="changeAdded(item.id, 1)" class="px-4 py-2 text-green-600 font-black text-lg">+</button>
                                </div>
                                <input v-if="addedYears[item.id] > 0" type="number" v-model="addedClausola[item.id]" class="w-full text-center text-xs border-orange-200 rounded-lg mb-2" placeholder="+ cr">
                                <button v-if="addedYears[item.id] > 0" @click="saveContract(item)" class="w-full bg-green-600 text-white py-2 rounded-xl text-[10px] font-black uppercase shadow-lg">Salva</button>
                            </div>
                        </div>
                    </div>
                </div>

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