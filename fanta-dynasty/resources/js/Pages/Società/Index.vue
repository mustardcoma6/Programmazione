<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    league: Object,
    participants: Array
});
</script>

<template>
    <Head title="Società della Lega" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-black text-xl uppercase tracking-tighter text-gray-800">
                Anagrafe Societaria: {{ league.name }}
            </h2>
        </template>

        <div class="py-12 max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded-xl overflow-hidden border border-gray-200">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-gray-100 text-[10px] font-black uppercase text-gray-500 border-b">
                            <th class="p-4">Logo / Nome Società</th>
                            <th class="p-4">Presidente</th>
                            <th class="p-4 text-center">Crediti Residui</th>
                            <th class="p-4 text-center">Anni Impegnati</th>
                            <th class="p-4 text-center">Anni Liberi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="p in participants" :key="p.id" class="border-b hover:bg-gray-50 transition" :class="{'bg-blue-50/30': p.user_id === $page.props.auth.user.id}">
                            <td class="p-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-indigo-600 rounded-lg flex items-center justify-center text-white font-black shadow-sm">
                                        {{ p.team_name.substring(0, 2).toUpperCase() }}
                                    </div>
                                    <span class="font-black uppercase text-sm text-gray-800">
                                        {{ p.team_name }}
                                        <span v-if="p.user_id === $page.props.auth.user.id" class="text-[9px] ml-1 text-blue-500">(Tu)</span>
                                    </span>
                                </div>
                            </td>
                            <td class="p-4 text-sm font-medium text-gray-500 uppercase">
                                {{ p.user.name }}
                            </td>
                            <td class="p-4 text-center">
                                <span class="font-mono font-black text-green-600 text-lg">{{ p.remaining_budget }} cr</span>
                            </td>
                            <td class="p-4 text-center">
                                <span class="font-mono font-bold text-gray-600">{{ p.years_used }}</span>
                            </td>
                            <td class="p-4 text-center">
                                <span class="font-mono font-black" :class="p.years_budget - p.years_used < 5 ? 'text-red-500' : 'text-blue-600'">
                                    {{ p.years_budget - p.years_used }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="mt-6 p-4 bg-gray-50 rounded-lg border border-dashed border-gray-300 text-center">
                <p class="text-[10px] text-gray-400 font-bold uppercase">
                    In questa sezione puoi monitorare la potenza economica e contrattuale di tutti i tuoi avversari.
                </p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>