<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({ myData: Object, myPlayers: Array });

// Logica Rinnovi
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

const totalYearsUsed = computed(() => {
    const currentOnDb = props.myPlayers.reduce((acc, p) => acc + p.contract_years, 0);
    const inDraft = Object.values(addedYears.value).reduce((acc, val) => acc + val, 0);
    return currentOnDb + inDraft;
});

const yearForm = useForm({ roster_id: null, new_years: 1, clausola_investment: 0 });

const saveContract = (item) => {
    const yearsToAdd = addedYears.value[item.id];
    const clausolaPlus = parseInt(addedClausola.value[item.id]) || 0;
    const finalTotalYears = item.contract_years + yearsToAdd;
    
    if (confirm(`Rinnovare ${item.player.name} per un totale di ${finalTotalYears} anni?`)) {
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
        <template #header><h2 class="font-black text-xl uppercase text-gray-800">Ufficio Contratti</h2></template>
        <div class="py-12 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <div class="bg-white p-6 shadow rounded-xl border-l-8 border-blue-600 flex justify-between items-center">
                <span class="font-bold text-gray-500 uppercase">Budget Anni Dynasty</span>
                <p class="text-4xl font-black font-mono" :class="totalYearsUsed > myData.years_budget ? 'text-red-600' : 'text-blue-600'">
                    {{ totalYearsUsed }} / {{ myData.years_budget }}
                </p>
            </div>

            <div class="bg-white shadow rounded-xl overflow-hidden border">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-gray-100 text-[10px] font-black uppercase text-gray-600 border-b">
                            <th class="p-4">Calciatore</th>
                            <th class="p-4 text-center">Clausola</th>
                            <th class="p-4 text-center">Anni</th>
                            <th class="p-4 text-center">Rinnova</th>
                            <th class="p-4 text-center">Costo Orig.</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in myPlayers" :key="item.id" class="border-b hover:bg-gray-50">
                            <td class="p-4 uppercase text-sm font-black text-gray-800">
                                <span class="text-blue-600 mr-1">{{ item.player.role }}</span> {{ item.player.name }}
                            </td>
                            <td class="p-4 text-center">
                                <div v-if="addedYears[item.id] > 0" class="flex flex-col items-center">
                                    <input type="number" v-model="addedClausola[item.id]" class="w-16 p-1 text-center border-orange-300 rounded text-xs">
                                </div>
                                <span v-else-if="item.release_clause > 0" class="font-mono font-black text-orange-500">{{ item.release_clause }} cr</span>
                                <span v-else class="text-gray-400 text-[10px]">NO</span>
                            </td>
                            <td class="p-4 text-center font-mono font-bold text-gray-400">{{ item.contract_years }}</td>
                            <td class="p-4">
                                <div class="flex flex-col items-center gap-2">
                                    <div class="flex items-center bg-gray-50 border rounded-lg overflow-hidden">
                                        <button @click="changeAdded(item.id, -1)" :disabled="addedYears[item.id] === 0" class="px-2 text-red-600">-</button>
                                        <span class="px-3 font-black text-blue-600">+{{ addedYears[item.id] }}</span>
                                        <button @click="changeAdded(item.id, 1)" class="px-2 text-green-600">+</button>
                                    </div>
                                    <button v-if="addedYears[item.id] > 0" @click="saveContract(item)" class="text-[9px] bg-green-600 text-white px-3 py-1 rounded-full font-black uppercase">Salva</button>
                                </div>
                            </td>
                            <td class="p-4 text-center font-mono text-gray-400 text-xs">{{ item.purchase_price }} cr</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>