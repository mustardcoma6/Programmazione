<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    league: Object,
    movements: Array
});
</script>

<template>
    <Head title="Cronologia Movimenti" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-black text-xl uppercase tracking-tighter">Cronologia Movimenti Lega</h2>
        </template>

        <div class="py-12 max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Messaggio informativo -->
            <div class="mb-6 bg-blue-50 border-l-4 border-blue-400 p-4 rounded shadow-sm text-blue-700 text-xs font-bold uppercase">
                Qui puoi consultare tutti gli acquisti definitivi avvenuti in questa lega.
            </div>

            <div class="bg-white shadow rounded-xl overflow-hidden border border-gray-200">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-100 text-[10px] font-black uppercase text-gray-600">
                            <th class="p-4 border-b">Data/Ora</th>
                            <th class="p-4 border-b">Calciatore</th>
                            <th class="p-4 border-b">Squadra</th>
                            <th class="p-4 border-b text-center">Prezzo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="m in movements" :key="m.id" class="hover:bg-gray-50 border-b">
                            <td class="p-4 text-xs text-gray-400 font-mono">
                                {{ new Date(m.created_at).toLocaleString() }}
                            </td>
                            <td class="p-4">
                                <span class="font-bold text-blue-600 mr-2 text-xs uppercase">{{ m.player.role }}</span>
                                <span class="font-black uppercase text-sm text-gray-800">{{ m.player.name }}</span>
                            </td>
                            <td class="p-4 font-bold uppercase text-gray-600 text-xs">
                                {{ m.user.name }}
                            </td>
                            <td class="p-4 text-center font-mono font-black text-green-600">
                                {{ m.purchase_price }} cr
                            </td>
                        </tr>
                        <tr v-if="movements.length === 0">
                            <td colspan="4" class="p-20 text-center text-gray-400 italic text-sm">
                                Nessun movimento registrato finora in questa lega.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>