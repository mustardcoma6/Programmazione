<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({ 
    myData: Object, 
    myPlayers: Array 
});

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

// Calcolo Budget Anni RIMANENTE
const remainingYears = computed(() => {
    const currentOnDb = props.myPlayers.reduce((acc, p) => acc + p.contract_years, 0);
    const inDraft = Object.values(addedYears.value).reduce((acc, val) => acc + val, 0);
    return props.myData.years_budget - (currentOnDb + inDraft);
});

// Calcolo crediti rimanenti in tempo reale
const tempRemainingCredits = computed(() => {
    const investment = Object.values(addedClausola.value).reduce((acc, val) => acc + (parseInt(val) || 0), 0);
    return props.myData.remaining_budget - investment;
});

// --- FUNZIONE PER CALCOLARE LA DATA DI SCADENZA ---
const getExpirationDate = (currentYears, added = 0) => {
    const totalYears = currentYears + added;
    const baseYear = 2026; // Anno corrente della stagione
    return `30/06/${baseYear + totalYears}`;
};

const yearForm = useForm({ roster_id: null, new_years: 1, clausola_investment: 0 });

const saveContract = (item) => {
    const yearsToAdd = addedYears.value[item.id];
    const finalTotalYears = item.contract_years + yearsToAdd;
    const clausolaPlus = parseInt(addedClausola.value[item.id]) || 0;
    
    if (confirm(`Rinnovare ${item.player.name} fino al ${getExpirationDate(item.contract_years, yearsToAdd)}?`)) {
        yearForm.roster_id = item.id;
        yearForm.new_years = finalTotalYears;
        yearForm.clausola_investment = clausolaPlus;
        yearForm.post(route('market.update-years'), { 
            preserveScroll: true,
            onSuccess: () => {
                addedYears.value[item.id] = 0;
                addedClausola.value[item.id] = 0;
            }
        });
    }
};
</script>

<template>
    <Head title="La mia Rosa" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-black text-xl uppercase tracking-tighter text-gray-800">Gestione Contratti</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <!-- DUE BANNER AFFIANCATI -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-white p-6 shadow rounded-xl border-l-8 border-blue-600 flex justify-between items-center">
                        <div>
                            <h3 class="text-xs font-black text-gray-400 uppercase tracking-widest">Anni Disponibili</h3>
                            <p class="text-sm text-gray-500 font-medium">Budget contrattuale residuo</p>
                        </div>
                        <div class="text-right">
                            <p class="text-5xl font-black font-mono" :class="remainingYears < 0 ? 'text-red-600' : 'text-blue-600'">
                                {{ remainingYears }}
                            </p>
                        </div>
                    </div>

                    <div class="bg-white p-6 shadow rounded-xl border-l-8 border-green-500 flex justify-between items-center">
                        <div>
                            <h3 class="text-xs font-black text-gray-400 uppercase tracking-widest">Crediti Disponibili</h3>
                            <p class="text-sm text-gray-500 font-medium">Per mercato e clausole</p>
                        </div>
                        <div class="text-right">
                            <p class="text-4xl font-black font-mono text-green-600">
                                {{ tempRemainingCredits }}<span class="text-xl text-green-200"> cr</span>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- TABELLA ROSA -->
                <div class="bg-white shadow rounded-xl overflow-hidden border border-gray-200">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-gray-100 text-[10px] font-black uppercase text-gray-600 border-b">
                                <th class="p-4">Calciatore</th>
                                <th class="p-4 text-center">Clausola</th>
                                <th class="p-4 text-center">Anni</th>
                                <th class="p-4 text-center">Scadenza</th> <!-- NUOVA COLONNA -->
                                <th class="p-4 text-center">Rinnova (+ Anni)</th>
                                <th class="p-4 text-center">Costo Orig.</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in myPlayers" :key="item.id" class="border-b hover:bg-gray-50 transition">
                                <td class="p-4 uppercase text-sm font-black text-gray-800">
                                    <span class="text-blue-600 mr-1">{{ item.player.role }}</span> {{ item.player.name }}
                                </td>

                                <td class="p-4 text-center">
                                    <div v-if="addedYears[item.id] > 0" class="flex flex-col items-center gap-1">
                                        <input type="number" v-model="addedClausola[item.id]" class="w-20 p-1 text-center border-orange-300 rounded text-xs font-bold" placeholder="+ cr">
                                    </div>
                                    <span v-else-if="item.release_clause > 0" class="font-mono font-black text-orange-500">{{ item.release_clause }} cr</span>
                                    <span v-else class="text-xs font-bold text-gray-400 uppercase">No</span>
                                </td>

                                <td class="p-4 text-center">
                                    <span class="font-mono font-bold text-gray-400 text-lg">{{ item.contract_years }}</span>
                                </td>

                                <!-- 1. COLONNA SCADENZA DINAMICA -->
                                <td class="p-4 text-center">
                                    <div class="flex flex-col">
                                        <span class="font-mono font-bold" :class="addedYears[item.id] > 0 ? 'text-blue-600' : 'text-gray-600'">
                                            {{ getExpirationDate(item.contract_years, addedYears[item.id]) }}
                                        </span>
                                        <span v-if="addedYears[item.id] > 0" class="text-[9px] font-black text-blue-400 uppercase">Anteprima</span>
                                    </div>
                                </td>

                                <td class="p-4">
                                    <div class="flex flex-col items-center gap-2">
                                        <div class="flex items-center bg-gray-50 border-2 rounded-xl overflow-hidden" :class="addedYears[item.id] > 0 ? 'border-blue-500' : 'border-gray-200'">
                                            <button @click="changeAdded(item.id, -1)" :disabled="addedYears[item.id] === 0" class="px-3 py-1 text-red-600 font-bold disabled:opacity-0 transition">-</button>
                                            <div class="px-3 text-center min-w-[50px]">
                                                <span class="font-black text-xl font-mono" :class="addedYears[item.id] > 0 ? 'text-blue-600' : 'text-gray-300'">+{{ addedYears[item.id] }}</span>
                                            </div>
                                            <button @click="changeAdded(item.id, 1)" class="px-3 py-1 text-green-600 font-bold transition">+</button>
                                        </div>
                                        <button v-if="addedYears[item.id] > 0" @click="saveContract(item)" class="text-[9px] bg-green-600 text-white px-4 py-1.5 rounded-full font-black uppercase shadow animate-bounce transition">Salva</button>
                                    </div>
                                </td>

                                <td class="p-4 text-center font-mono text-gray-400 text-xs">{{ item.purchase_price }} cr</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>