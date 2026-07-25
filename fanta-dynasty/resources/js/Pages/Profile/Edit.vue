<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import UpdateTeamNameForm from './Partials/UpdateTeamNameForm.vue'; // <-- AGGIUNTO
import { Head } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: Boolean,
    status: String,
    teamName: String, // <-- AGGIUNTO
});
</script>

<template>
    <Head title="Profilo" />
    <AuthenticatedLayout>
        <template #header><h2 class="font-semibold text-xl text-gray-800 leading-tight">Impostazioni Profilo</h2></template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <!-- BLOCCO 1: NOME SQUADRA (FANTACALCIO) -->
                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg border-l-8 border-orange-500">
                    <UpdateTeamNameForm :team-name="teamName" class="max-w-xl" />
                </div>

                <!-- BLOCCO 2: DATI PERSONALI (NOME E EMAIL) -->
                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                    <UpdateProfileInformationForm :must-verify-email="mustVerifyEmail" :status="status" class="max-w-xl" />
                </div>

                <!-- BLOCCO 3: PASSWORD -->
                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                    <UpdatePasswordForm class="max-w-xl" />
                </div>

                <!-- BLOCCO 4: ELIMINA ACCOUNT -->
                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                    <DeleteUserForm class="max-w-xl" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>