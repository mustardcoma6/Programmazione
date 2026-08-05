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
        years: t.years_budget,
        primavera: t.remaining_primavera_budget // AGGIUNTO: Crediti Primavera
    };
});

const updateResources = (teamId) => {
    useForm({
        participant_id: teamId,
        new_credits: resourceInputs[teamId].credits,
        new_years: resourceInputs[teamId].years,
        new_primavera_credits: resourceInputs[teamId].primavera // AGGIUNTO: Invio crediti Primavera
    }).post(route('admin.resources.update'), { 
        preserveScroll: true,
        onSuccess: () => alert("Risorse della squadra aggiornate correttamente!") 
    });
};
</script>

<template>
    <Head title="Gestione Budget" />
    <AuthenticatedLayout>
        <template #header><h2 class="font-black text-xl uppercase tracking-tight text-gray-800">Ufficio Tesoreria & Contratti (Admin)</h2></template>
        
        <div class="py-12 max-w-7xl mx-auto px-4">
            <div class="bg-white shadow-xl rounded-2xl overflow-hidden border border-gray-100">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-100 text-[10px] font-black uppercase text-gray-400">
                            <th class="p-6">Squadra / Presidente</th>
                            <th class="p-6 text-center text-green-600">Crediti Prima Rosa</th>
                            <th class="p-6 text-center text-violet-600">Crediti Primavera</th> <!-- NUOVA COLONNA -->
                            <th class="p-6 text-center text-blue-600">Anni Totali</th>
                            <th class="p-6 text-right">Azione</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="t in teams" :key="t.id" class="border-b last:border-0 hover:bg-gray-50 transition">
                            <td class="p-6">
                                <p class="font-black uppercase text-blue-600 text-sm">{{ t.team_name }}</p>
                                <p class="text-xs text-gray-400 font-bold uppercase">{{ t.user.name }}</p>
                            </td>
                            
                            <!-- GESTIONE CREDITI PRIMA ROSA -->
                            <td class="p-6 text-center">
                                <div class="flex flex-col items-center gap-1">
                                    <span class="text-[9px] text-gray-400 uppercase font-bold">Attuali: {{ t.remaining_budget }}</span>
                                    <input 
                                        type="number" 
                                        v-model="resourceInputs[t.id].credits" 
                                        class="w-24 p-2 text-sm border-gray-300 rounded-lg font-mono font-black text-green-600"
                                    >
                                </div>
                            </td>

                            <!-- GESTIONE CREDITI PRIMAVERA (VIOLA) -->
                            <td class="p-6 text-center">
                                <div class="flex flex-col items-center gap-1">
                                    <span class="text-[9px] text-gray-400 uppercase font-bold">Attuali: {{ t.remaining_primavera_budget }}</span>
                                    <input 
                                        type="number" 
                                        v-model="resourceInputs[t.id].primavera" 
                                        class="w-24 p-2 text-sm border-violet-300 rounded-lg font-mono font-black text-violet-600 focus:ring-violet-500"
                                    >
                                </div>
                            </td>

                            <!-- GESTIONE ANNI -->
                            <td class="p-6 text-center">
                                <div class="flex flex-col items-center gap-1">
                                    <span class="text-[9px] text-gray-400 uppercase font-bold">Attuali: {{ t.years_budget }}</span>
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
            
            <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="p-4 bg-blue-50 rounded-lg border border-blue-100">
                    <p class="text-[10px] text-blue-700 font-bold uppercase">Nota Anni:</p>
                    <p class="text-xs text-blue-600">Modifica il tetto massimo degli anni per i rinnovi.</p>
                </div>
                <div class="p-4 bg-green-50 rounded-lg border border-green-100">
                    <p class="text-[10px] text-green-700 font-bold uppercase">Nota Crediti Prima Rosa:</p>
                    <p class="text-xs text-green-600">Budget per il mercato e le aste principali.</p>
                </div>
                <div class="p-4 bg-violet-50 rounded-lg border border-violet-100">
                    <p class="text-[10px] text-violet-700 font-bold uppercase">Nota Crediti Primavera:</p>
                    <p class="text-xs text-violet-600">Budget separato per gli acquisti del settore giovanile.</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>