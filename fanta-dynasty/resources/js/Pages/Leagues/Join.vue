<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useForm, Head } from '@inertiajs/vue3';

const form = useForm({
    invite_code: '',
});

const submit = () => {
    form.post(route('leagues.join.store'));
};
</script>

<template>
    <Head title="Unisciti a una Lega" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Unisciti a una Lega</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white p-8 shadow sm:rounded-lg max-w-md mx-auto">
                    <form @submit.prevent="submit">
                        <label class="block font-medium text-sm text-gray-700">Inserisci il Codice Invito</label>
                        <input v-model="form.invite_code" type="text" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm uppercase" placeholder="ES: A1B2C3D4" required>
                        
                        <p v-if="form.errors.invite_code" class="text-red-600 text-sm mt-2">{{ form.errors.invite_code }}</p>

                        <button type="submit" class="mt-6 w-full bg-blue-600 text-white py-2 rounded-md font-bold hover:bg-blue-700">
                            Entra nella Lega
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>