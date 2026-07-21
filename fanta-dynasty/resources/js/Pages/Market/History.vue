<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    league: Object,
    movements: Array
});
</script>

<template>
    <Head title="Cronologia Mercato" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-black text-xl uppercase">Cronologia Mercati</h2>
        </template>

        <div class="py-12 max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded-xl overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-100 text-xs font-black uppercase text-gray-600">
                            <th class="p-4 border-b">Data/Ora</th>
                            <th class="p-4 border-b">Calciatore</th>
                            <th class="p-4 border-b">Squadra Acquirente</th>
                            <th class="p-4 border-b text-center">Prezzo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="m in movements" :key="m.id" class="hover:bg-gray-50 border-b">
                            <td class="p-4 text-xs text-gray-400">
                                {{ new Date(m.created_at).toLocaleString() }}
                            </td>
                            <td class="p-4">
                                <span class="font-bold text-blue-600 mr-2">{{ m.player.role }}</span>
                                <span class="font-bold uppercase">{{ m.player.name }}</span>
                            </td>
                            <td class="p-4 font-medium uppercase text-gray-700">
                                {{ m.user.name }}
                            </td>
                            <td class="p-4 text-center font-mono font-bold text-green-600">
                                {{ m.purchase_price }} cr
                            </td>
                        </tr>
                        <tr v-if="movements.length === 0">
                            <td colspan="4" class="p-10 text-center text-gray-400 italic">
                                Nessun movimento registrato finora.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>