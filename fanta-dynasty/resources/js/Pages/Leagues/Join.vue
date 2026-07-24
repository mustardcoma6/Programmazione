<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    invite_code: '',
    team_name: '', // <--- Nuovo campo
});

const submit = () => {
    form.post(route('leagues.join.store'));
};
</script>

<template>
    <Head title="Unisciti" />
    <AuthenticatedLayout>
        <template #header><h2 class="font-black text-xl uppercase">Unisciti a una Lega</h2></template>
        <div class="py-12 max-w-md mx-auto">
            <div class="bg-white p-6 shadow rounded-xl space-y-4">
                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-400">Codice Invito</label>
                        <input v-model="form.invite_code" type="text" class="w-full rounded-lg border-gray-300 uppercase font-mono" placeholder="A1B2C3D4" required>
                        <p v-if="form.errors.invite_code" class="text-red-500 text-xs mt-1">{{ form.errors.invite_code }}</p>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-gray-400">Nome della tua Squadra</label>
                        <input v-model="form.team_name" type="text" class="w-full rounded-lg border-gray-300" placeholder="Es: AC Picchia" required>
                    </div>
                    <button class="w-full bg-orange-500 text-white py-3 rounded-xl font-black uppercase tracking-widest">Entra in Lega</button>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>