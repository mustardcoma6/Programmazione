<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    myData: Object
});
</script>

<template>
    <Head title="Finanze Societarie" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-black text-xl uppercase tracking-tighter text-gray-800">
                Resoconto Finanziario: {{ myData.team_name }}
            </h2>
        </template>

        <div class="py-12 max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white p-12 shadow-xl rounded-3xl border-2 border-dashed border-gray-200 text-center">
                <span class="text-6xl mb-6 block">💰</span>
                <h3 class="text-2xl font-black text-gray-900 uppercase">Area Amministrativa</h3>
                <p class="text-gray-500 mt-4 max-w-md mx-auto">
                    Pres, questa sezione sarà il tuo libro mastro. Qui potrai monitorare entrate, uscite e flussi di cassa della tua società.
                </p>
                <div class="mt-10 inline-block bg-green-50 px-8 py-4 rounded-2xl border border-green-100">
                    <p class="text-xs font-black text-green-600 uppercase tracking-widest">Liquidità Attuale</p>
                    <p class="text-4xl font-black text-green-700 font-mono">{{ myData.remaining_budget }} cr</p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>