<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { reactive } from 'vue';

const props = defineProps({ league: Object, teams: Array });

// Stato locale per gli input
const creditInputs = reactive({});
props.teams.forEach(t => { creditInputs[t.id] = t.remaining_budget; });

const updateCredits = (teamId) => {
    useForm({
        participant_id: teamId,
        new_credits: creditInputs[teamId]
    }).post(route('admin.credits.update'), { 
        preserveScroll: true,
        onSuccess: () => alert("Crediti aggiornati con successo!") 
    });
};
</script>

<template>
    <Head title="Gestione Crediti" />
    <AuthenticatedLayout>
        <template #header><h2 class="font-black text-xl uppercase tracking-tight text-gray-800">Ufficio Tesoreria (Admin)</h2></template>
        
        <div class="py-12 max-w-4xl mx-auto px-4">
            <div class="bg-white shadow-xl rounded-2xl overflow-hidden border border-gray-100">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-100 text-[10px] font-black uppercase text-gray-400">
                            <th class="p-6">Squadra / Presidente</th>
                            <th class="p-6 text-center">Crediti Attuali</th>
                            <th class="p-6 text-right">Nuovo Valore</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="t in teams" :key="t.id" class="border-b last:border-0 hover:bg-gray-50 transition">
                            <td class="p-6">
                                <p class="font-black uppercase text-blue-600 text-sm">{{ t.team_name }}</p>
                                <p class="text-xs text-gray-400 font-bold uppercase">{{ t.user.name }}</p>
                            </td>
                            <td class="p-6 text-center font-mono font-bold text-gray-400">
                                {{ t.remaining_budget }} cr
                            </td>
                            <td class="p-6">
                                <div class="flex items-center justify-end gap-2">
                                    <input 
                                        type="number" 
                                        v-model="creditInputs[t.id]" 
                                        class="w-24 p-2 text-sm border-gray-300 rounded-lg font-mono font-black text-green-600 focus:ring-green-500 focus:border-green-500"
                                    >
                                    <button 
                                        @click="updateCredits(t.id)" 
                                        class="bg-green-600 text-white px-4 py-2 rounded-lg font-black uppercase text-[10px] shadow-sm hover:bg-green-700 transition"
                                    >
                                        Imposta
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p class="mt-4 text-[10px] text-gray-400 uppercase text-center font-bold italic">
                Nota: Il valore inserito sovrascriverà completamente il budget attuale della squadra.
            </p>
        </div>
    </AuthenticatedLayout>
</template>