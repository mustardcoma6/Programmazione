<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ myData: Object, myPlayers: Array, rosterValue: Number });

const getRoleClass = (role) => {
    if (role === 'P') return 'role-P';
    if (role === 'D') return 'role-D';
    if (role === 'C') return 'role-C';
    if (role === 'A') return 'role-A';
    return '';
};

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
    if (confirm(`Rinnovare ${item.player.name}?`)) {
        yearForm.roster_id = item.id;
        yearForm.new_years = finalTotalYears;
        yearForm.clausola_investment = addedClausola.value[item.id];
        yearForm.post(route('market.update-years'), { 
            preserveScroll: true, 
            onSuccess: () => { addedYears.value[item.id] = 0; addedClausola.value[item.id] = 0; } 
        });
    }
};
</script>

<template>
    <Head title="La mia Rosa" />
    <AuthenticatedLayout>
        <template #header><h2 class="font-black text-xl uppercase tracking-tighter text-gray-800 italic">Gestione Asset Societari</h2></template>
        
        <div class="py-6 md:py-12 px-4">
            <div class="max-w-7xl mx-auto space-y-6">
                <!-- BANNERS -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-white p-6 shadow rounded-3xl border-l-8 border-blue-600 flex justify-between items-center"><span class="font-bold text-gray-400 uppercase text-[10px]">Budget Anni</span><p class="text-4xl font-black font-mono text-blue-600">{{ myData.years_budget }}</p></div>
                    <div class="bg-white p-6 shadow rounded-3xl border-l-8 border-green-500 flex justify-between items-center"><span class="font-bold text-gray-400 uppercase text-[10px]">Crediti</span><p class="text-4xl font-black font-mono text-green-600">{{ myData.remaining_budget }}</p></div>
                    <div class="bg-white p-6 shadow rounded-3xl border-l-8 border-yellow-500 flex justify-between items-center"><span class="font-bold text-gray-400 uppercase text-[10px]">Valore Rosa</span><p class="text-4xl font-black font-mono text-yellow-500">{{ rosterValue }}</p></div>
                </div>

                <!-- TABELLA DESKTOP -->
                <div class="hidden md:block bg-white shadow-xl rounded-3xl overflow-hidden border">
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
                            <tr v-for="item in myPlayers" :key="item.id" class="border-b transition" :class="item.is_primavera ? 'bg-violet-50/50' : 'hover:bg-gray-50'">
                                <td class="p-4 uppercase text-sm font-black text-gray-800">
                                    <span class="mr-2" :class="getRoleClass(item.player.role)">{{ item.player.role }}</span> 
                                    {{ item.player.name }}
                                    <span v-if="item.is_primavera" class="ml-2 text-[8px] bg-violet-600 text-white px-1 rounded">PRIMAVERA</span>
                                </td>
                                
                                <td class="p-4 text-center">
                                    <template v-if="!item.is_primavera">
                                        <div v-if="addedYears[item.id] > 0"><input type="number" v-model="addedClausola[item.id]" class="w-16 p-1 text-center border-orange-300 rounded text-xs"></div>
                                        <span v-else-if="item.release_clause > 0" class="font-mono font-black text-orange-500">{{ item.release_clause }} cr</span>
                                        <span v-else class="text-gray-400 text-[10px]">NO</span>
                                    </template>
                                    <span v-else class="text-[9px] text-violet-400 font-bold uppercase tracking-widest">Contratto Vivaio</span>
                                </td>

                                <td class="p-4 text-center font-mono font-bold text-gray-400">
                                    {{ item.is_primavera ? '-' : item.contract_years }}
                                </td>
                                
                                <td class="p-4 text-center font-mono font-bold text-xs text-gray-600">
                                    {{ item.is_primavera ? 'Indeterminata' : getExpirationDate(item.contract_years, addedYears[item.id]) }}
                                </td>

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

                <!-- CARDS MOBILE -->
                <div class="md:hidden space-y-4">
                    <div v-for="item in myPlayers" :key="item.id" class="rounded-3xl shadow-md border overflow-hidden" :class="item.is_primavera ? 'bg-violet-50 border-violet-200' : 'bg-white border-gray-100'">
                        <div class="p-4 border-b flex justify-between items-center" :class="item.is_primavera ? 'bg-violet-100' : 'bg-gray-50'">
                            <div class="flex items-center gap-2">
                                <span class="font-black px-2 py-0.5 rounded-lg text-xs" :class="getRoleClass(item.player.role)">{{ item.player.role }}</span>
                                <span class="font-black uppercase text-gray-900">{{ item.player.name }}</span>
                            </div>
                            <span v-if="item.is_primavera" class="text-[8px] bg-violet-600 text-white px-2 py-0.5 rounded-full font-black uppercase">Primavera</span>
                        </div>
                        <div class="p-4 grid grid-cols-2 gap-4">
                            <div class="space-y-1">
                                <p class="text-[9px] font-bold text-gray-400 uppercase">Stato</p>
                                <p class="text-sm font-black text-gray-700">{{ item.is_primavera ? 'Vivaio' : item.contract_years + ' Anni' }}</p>
                                <p class="text-[10px] font-mono text-gray-500">{{ item.is_primavera ? 'No Scadenza' : getExpirationDate(item.contract_years) }}</p>
                            </div>
                            <div v-if="!item.is_primavera" class="flex flex-col items-center bg-blue-50/50 rounded-2xl p-2 border border-blue-100">
                                <div class="flex items-center bg-white border border-blue-200 rounded-xl shadow-sm overflow-hidden mb-2">
                                    <button @click="changeAdded(item.id, -1)" :disabled="addedYears[item.id] === 0" class="px-4 py-2 text-red-600 font-black">-</button>
                                    <span class="px-4 font-black text-blue-700">+{{ addedYears[item.id] }}</span>
                                    <button @click="changeAdded(item.id, 1)" class="px-4 py-2 text-green-600 font-black">+</button>
                                </div>
                                <button v-if="addedYears[item.id] > 0" @click="saveContract(item)" class="w-full bg-green-600 text-white py-2 rounded-xl text-[10px] font-black uppercase">Salva</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.role-P { color: #856404 !important; background-color: #fff3cd !important; padding: 2px 6px; border-radius: 4px; }
.role-D { color: #155724 !important; background-color: #d4edda !important; padding: 2px 6px; border-radius: 4px; }
.role-C { color: #004085 !important; background-color: #cce5ff !important; padding: 2px 6px; border-radius: 4px; }
.role-A { color: #721c24 !important; background-color: #f8d7da !important; padding: 2px 6px; border-radius: 4px; }
</style>