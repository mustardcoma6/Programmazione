<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ 
    myData: Object, 
    myPlayers: Array 
});

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
            }
        });
    }
};
</script>

<template>
    <Head title="La mia Rosa" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-black text-xl uppercase tracking-tighter text-gray-800">Ufficio Contratti</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <!-- DUE BANNER: Valori REALI impostati dall'Admin -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-white p-6 shadow rounded-xl border-l-8 border-blue-600 flex justify-between items-center">
                        <div>
                            <h3 class="text-xs font-black text-gray-400 uppercase tracking-widest">Budget Anni Totale</h3>
                            <p class="text-sm text-gray-500 font-medium">Capacità massima della rosa</p>
                        </div>
                        <div class="text-right">
                            <p class="text-5xl font-black font-mono text-blue-600">
                                {{ myData.years_budget }}
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
                                {{ myData.remaining_budget }}<span class="text-xl text-green-200"> cr</span>
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
                                <th class="p-4 text-center">Scadenza</th>
                                <th class="p-4 text-center">Rinnova</th>
                                <th class="p-4 text-center">Costo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in myPlayers" :key="item.id" class="border-b hover:bg-gray-50 transition">
                                <td class="p-4 uppercase text-sm font-black text-gray-800">
                                    <span class="text-blue-600 mr-1">{{ item.player.role }}</span> {{ item.player.name }}
                                </td>
                                <td class="p-4 text-center">
                                    <div v-if="addedYears[item.id] > 0" class="flex flex-col items-center">
                                        <input type="number" v-model="addedClausola[item.id]" class="w-16 p-1 text-center border-orange-300 rounded text-xs" placeholder="+ cr">
                                    </div>
                                    <span v-else-if="item.release_clause > 0" class="font-mono font-black text-orange-500">{{ item.release_clause }} cr</span>
                                    <span v-else class="text-gray-400 text-[10px]">NO</span>
                                </td>
                                <td class="p-4 text-center font-mono font-bold text-gray-400">{{ item.contract_years }}</td>
                                <td class="p-4 text-center font-mono font-bold text-xs text-gray-600">
                                    {{ getExpirationDate(item.contract_years, addedYears[item.id]) }}
                                </td>
                                <td class="p-4">
                                    <div class="flex flex-col items-center gap-2">
                                        <div class="flex items-center bg-gray-50 border rounded-lg overflow-hidden">
                                            <button @click="changeAdded(item.id, -1)" :disabled="addedYears[item.id] === 0" class="px-2 text-red-600">-</button>
                                            <span class="px-3 font-black text-blue-600">+{{ addedYears[item.id] }}</span>
                                            <button @click="changeAdded(item.id, 1)" class="px-2 text-green-600">+</button>
                                        </div>
                                        <button v-if="addedYears[item.id] > 0" @click="saveContract(item)" class="text-[9px] bg-green-600 text-white px-3 py-1 rounded-full font-black uppercase shadow">Salva</button>
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