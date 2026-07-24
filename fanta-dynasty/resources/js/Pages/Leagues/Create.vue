<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    initial_budget: 500,
    team_name: '', // <--- Nuovo campo
});

const submit = () => {
    form.post(route('leagues.store'));
};
</script>

<template>
    <Head title="Crea Lega" />
    <AuthenticatedLayout>
        <template #header><h2 class="font-black text-xl uppercase">Nuova Lega</h2></template>
        <div class="py-12 max-w-md mx-auto">
            <div class="bg-white p-6 shadow rounded-xl space-y-4">
                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-400">Nome della Lega</label>
                        <input v-model="form.name" type="text" class="w-full rounded-lg border-gray-300" placeholder="Es: Lega degli Amici" required>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-400">Il nome della tua Squadra</label>
                        <input v-model="form.team_name" type="text" class="w-full rounded-lg border-gray-300" placeholder="Es: Real Madrink" required>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-400">Budget Iniziale</label>
                        <input v-model="form.initial_budget" type="number" class="w-full rounded-lg border-gray-300" required>
                    </div>
                    <button class="w-full bg-green-600 text-white py-3 rounded-xl font-black uppercase tracking-widest">Crea e Inizia</button>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>