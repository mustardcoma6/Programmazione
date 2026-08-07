<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';

defineProps({
    myData: Object,
    ranking: Array
});

const user = usePage().props.auth.user;
</script>

<template>
    <Head title="Finanze" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-black text-xl uppercase tracking-tighter text-gray-800 italic">Analisi Patrimoniale Società</h2>
        </template>

        <div class="py-12 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8 px-4">
            
            <!-- CLASSIFICA VALORE QUOTAZIONI -->
            <div class="bg-white shadow-2xl rounded-3xl overflow-hidden border border-gray-100">
                <div class="bg-gray-900 p-6 text-white">
                    <h3 class="font-black uppercase tracking-widest text-sm flex items-center gap-2">
                        <span class="text-xl">📈</span> Ranking Valore Rosa (Quotazioni Attuali)
                    </h3>
                    <p class="text-[10px] text-gray-400 mt-1 uppercase">Il valore totale basato sulla quotazione di mercato di ogni giocatore in rosa</p>
                </div>

                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 text-[10px] font-black uppercase text-gray-500 border-b">
                            <th class="p-4 w-20 text-center">Pos</th>
                            <th class="p-4">Club / Presidente</th>
                            <th class="p-4 text-right">Valore Quotazioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(team, index) in ranking" :key="team.id" 
                            class="border-b last:border-0 transition"
                            :class="team.user_id === user.id ? 'bg-green-50' : 'hover:bg-gray-50'">
                            
                            <td class="p-4 text-center">
                                <span class="w-8 h-8 rounded-xl flex items-center justify-center font-black text-sm shadow-sm"
                                      :class="index === 0 ? 'bg-yellow-400 text-yellow-900' : 'bg-gray-100 text-gray-400'">
                                    {{ index + 1 }}
                                </span>
                            </td>

                            <td class="p-4">
                                <div class="flex flex-col">
                                    <span class="font-black uppercase text-sm" :class="team.user_id === user.id ? 'text-green-700' : 'text-gray-800'">
                                        {{ team.team_name }}
                                    </span>
                                    <span class="text-[10px] text-gray-400 uppercase font-bold">{{ team.user.name }}</span>
                                </div>
                            </td>

                            <td class="p-4 text-right">
                                <div class="flex flex-col">
                                    <span class="font-mono font-black text-xl" :class="team.user_id === user.id ? 'text-green-600' : 'text-gray-700'">
                                        {{ team.total_quotation_value }} <span class="text-xs uppercase">cr</span>
                                    </span>
                                    <span class="text-[9px] text-gray-400 uppercase">Valore Asset</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Box Informativo -->
            <div class="bg-blue-900 p-8 rounded-3xl shadow-xl text-white relative overflow-hidden">
                <div class="absolute right-0 top-0 opacity-10 text-9xl font-black -mr-10 -mt-10">€</div>
                <h4 class="text-xs font-black uppercase text-blue-300 mb-2 tracking-[0.2em]">Nota della Federazione</h4>
                <p class="text-sm leading-relaxed italic">
                    "Il valore della rosa è calcolato sommando le quotazioni attuali di tutti i calciatori (Prima Squadra + Primavera). 
                    Questo dato rappresenta il potenziale di smobilizzo e la forza economica della vostra società sul mercato globale."
                </p>
            </div>

        </div>
    </AuthenticatedLayout>
</template>