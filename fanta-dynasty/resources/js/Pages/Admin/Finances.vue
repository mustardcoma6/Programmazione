<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    league: Object,
    participants: Array,
    stats: Object
});
</script>

<template>
    <Head title="Gestione Finanze" />
    <AuthenticatedLayout>
        <template #header><h2 class="font-black text-xl uppercase text-gray-800">Bilancio di Lega (Admin)</h2></template>
        
        <div class="py-12 max-w-7xl mx-auto px-4 space-y-8">
            
            <!-- RECAP GLOBALE -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-gray-900 text-white p-6 rounded-2xl shadow-xl">
                    <p class="text-[10px] font-black uppercase text-green-400 mb-1">Liquidità Totale Lega</p>
                    <p class="text-4xl font-black font-mono">{{ stats.totalCredits }} cr</p>
                </div>
                <div class="bg-indigo-600 text-white p-6 rounded-2xl shadow-xl">
                    <p class="text-[10px] font-black uppercase text-indigo-200 mb-1">Capacità Contrattuale Totale</p>
                    <p class="text-4xl font-black font-mono">{{ stats.totalYears }} y</p>
                </div>
                <div class="bg-white p-6 rounded-2xl shadow-xl border border-gray-100">
                    <p class="text-[10px] font-black uppercase text-gray-400 mb-1">Media Crediti per Squadra</p>
                    <p class="text-4xl font-black font-mono text-gray-800">{{ stats.avgCredits }} cr</p>
                </div>
            </div>

            <!-- DETTAGLIO SOCIETÀ -->
            <div class="bg-white shadow-xl rounded-2xl overflow-hidden border border-gray-100">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-gray-50 text-[10px] font-black uppercase text-gray-400 border-b">
                            <th class="p-4">Club</th>
                            <th class="p-4">Presidente</th>
                            <th class="p-4 text-center">Salute Finanziaria</th>
                            <th class="p-4 text-right">Crediti</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="p in participants" :key="p.id" class="border-b last:border-0 hover:bg-gray-50 transition">
                            <td class="p-4">
                                <span class="font-black uppercase text-sm text-gray-800">{{ p.team_name }}</span>
                            </td>
                            <td class="p-4 text-xs font-bold text-gray-400 uppercase">
                                {{ p.user.name }}
                            </td>
                            <td class="p-4 text-center">
                                <div class="w-full bg-gray-100 rounded-full h-2 max-w-[100px] mx-auto">
                                    <div class="bg-green-500 h-2 rounded-full" :style="{ width: (p.remaining_budget / league.initial_budget * 100) + '%' }"></div>
                                </div>
                            </td>
                            <td class="p-4 text-right font-mono font-black text-green-600 text-lg">
                                {{ p.remaining_budget }} cr
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>