<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Storia" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-black text-xl uppercase tracking-tighter text-gray-800 italic">Albo d'Oro e Storia</h2>
        </template>
        <div class="py-12 max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white p-20 text-center shadow-xl rounded-3xl border-2 border-dashed border-gray-200">
                <span class="text-6xl mb-6 block">📜</span>
                <h3 class="text-2xl font-black text-gray-900 uppercase">Archivio Storico</h3>
                <p class="text-gray-400 mt-4 italic font-bold">Sezione in fase di redazione...</p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>