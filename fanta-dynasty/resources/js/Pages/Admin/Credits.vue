<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { reactive } from 'vue';

const props = defineProps({ league: Object, teams: Array });

// Stato locale per gestire gli input di ogni riga
const resourceInputs = reactive({});
props.teams.forEach(t => { 
    resourceInputs[t.id] = {
        credits: t.remaining_budget,
        years: t.years_budget
    };
});

const updateResources = (teamId) => {
    useForm({
        participant_id: teamId,
        new_credits: resourceInputs[teamId].credits,
        new_years: resourceInputs[teamId].years
    }).post(route('admin.resources.update'), { 
        preserveScroll: true,
        onSuccess: () => alert("Risorse della squadra aggiornate!") 
    });
};
</script>

<template>
    <Head title="Gestione Budget" />
    <AuthenticatedLayout>
        <template #header><h2 class="font-black text-xl uppercase tracking-tight text-gray-800">Ufficio Tesoreria & Contratti (Admin)</h2></template>
        
        <div class="py-12 max-w-6xl mx-auto px-4">
            <div class="bg-white shadow-xl rounded-2xl overflow-hidden border border-gray-100">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-100 text-[10px] font-black uppercase text-gray-400">
                            <th class="p-6">Squadra / Presidente</th>
                            <th class="p-6 text-center">Budget Crediti</th>
                            <th class="p-6 text-center">Budget Anni Totali</th>
                            <th class="p-6 text-right">Azione</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="t in teams" :key="t.id" class="border-b last:border-0 hover:bg-gray-50 transition">
                            <td class="p-6">
                                <p class="font-black uppercase text-blue-600 text-sm">{{ t.team_name }}</p>
                                <p class="text-xs text-gray-400 font-bold uppercase">{{ t.user.name }}</p>
                            </td>
                            
                            <!-- GESTIONE CREDITI -->
                            <td class="p-6 text-center">
                                <div class="flex flex-col items-center gap-1">
                                    <span class="text-[9px] text-gray-400 uppercase font-bold">Attuali: {{ t.remaining_budget }} cr</span>
                                    <input 
                                        type="number" 
                                        v-model="resourceInputs[t.id].credits" 
                                        class="w-24 p-2 text-sm border-gray-300 rounded-lg font-mono font-black text-green-600"
                                    >
                                </div>
                            </td>

                            <!-- GESTIONE ANNI -->
                            <td class="p-6 text-center">
                                <div class="flex flex-col items-center gap-1">
                                    <span class="text-[9px] text-gray-400 uppercase font-bold">Attuali: {{ t.years_budget }} y</span>
                                    <input 
                                        type="number" 
                                        v-model="resourceInputs[t.id].years" 
                                        class="w-24 p-2 text-sm border-gray-300 rounded-lg font-mono font-black text-blue-600"
                                    >
                                </div>
                            </td>

                            <td class="p-6 text-right">
                                <button 
                                    @click="updateResources(t.id)" 
                                    class="bg-gray-800 text-white px-6 py-2 rounded-xl font-black uppercase text-xs hover:bg-black transition shadow-md"
                                >
                                    Aggiorna
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="p-4 bg-blue-50 rounded-lg border border-blue-100">
                    <p class="text-[10px] text-blue-700 font-bold uppercase">Nota Anni:</p>
                    <p class="text-xs text-blue-600">Modificando gli Anni Totali (es. da 40 a 45), aumenterai la capacità della squadra di rinnovare i propri giocatori.</p>
                </div>
                <div class="p-4 bg-green-50 rounded-lg border border-green-100">
                    <p class="text-[10px] text-green-700 font-bold uppercase">Nota Crediti:</p>
                    <p class="text-xs text-green-600">Il valore inserito sovrascrive il budget attuale. Utile per bonus vittoria o sanzioni.</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>