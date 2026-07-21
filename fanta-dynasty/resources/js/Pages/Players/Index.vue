<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({ players: Array });
</script>

<template>
    <Head title="Listone Svincolati" />
    <AuthenticatedLayout>
        <template #header><h2 class="font-black text-xl uppercase">Svincolati (Senza Squadra)</h2></template>
        <div class="py-12 max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white p-6 shadow rounded-xl border-t-4 border-gray-400">
                <p class="text-xs text-gray-400 mb-6 italic">Nota: Per acquistare questi giocatori, recati nella sezione "Calciomercato" durante una sessione aperta.</p>
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-gray-50 border-b text-[10px] uppercase font-black text-gray-500">
                            <th class="p-4">Ruolo</th>
                            <th class="p-4">Nome Calciatore</th>
                            <th class="p-4">Squadra Reale</th>
                            <th class="p-4 text-right">Quotazione</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="p in players" :key="p.id" class="border-b hover:bg-gray-50 transition">
                            <td class="p-4"><span class="font-bold text-blue-600 uppercase">{{ p.role }}</span></td>
                            <td class="p-4 font-black uppercase text-gray-700">{{ p.name }}</td>
                            <td class="p-4 text-gray-500 text-sm italic">{{ p.real_team }}</td>
                            <td class="p-4 text-right font-mono font-bold">{{ p.initial_value }} cr</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>