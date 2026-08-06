<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    ranking: Array,
    leagueName: String
});
</script>

<template>
    <Head title="Ranking Lega" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-black text-xl uppercase tracking-tighter text-gray-800">Classifica Generale: {{ leagueName }}</h2>
        </template>

        <div class="py-12 max-w-4xl mx-auto px-4">
            <div class="bg-white shadow-2xl rounded-3xl overflow-hidden border border-gray-100">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-900 text-white text-[10px] font-black uppercase tracking-widest">
                            <th class="p-6">Pos</th>
                            <th class="p-6">Club</th>
                            <th class="p-6 text-right">Punti Totali</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(team, index) in ranking" :key="index" 
                            class="border-b last:border-0 hover:bg-blue-50/50 transition"
                            :class="{'bg-yellow-50/50': index === 0}">
                            <td class="p-6">
                                <span class="w-8 h-8 rounded-full flex items-center justify-center font-black text-sm" 
                                      :class="index < 3 ? 'bg-yellow-400 text-yellow-900' : 'bg-gray-100 text-gray-500'">
                                    {{ index + 1 }}
                                </span>
                            </td>
                            <td class="p-6">
                                <span class="font-black uppercase text-sm" :class="index === 0 ? 'text-xl' : 'text-gray-800'">
                                    {{ team.name }}
                                </span>
                            </td>
                            <td class="p-6 text-right">
                                <span class="font-mono font-black text-2xl" :class="index < 3 ? 'text-blue-600' : 'text-gray-400'">
                                    {{ team.points }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>