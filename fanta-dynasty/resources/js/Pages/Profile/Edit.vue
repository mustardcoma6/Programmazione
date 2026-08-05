<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import UpdateTeamNameForm from './Partials/UpdateTeamNameForm.vue';
import UpdateTeamLogoForm from './Partials/UpdateTeamLogoForm.vue'; // <-- AGGIUNTO
import { Head } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: Boolean,
    status: String,
    teamName: String,
    teamLogo: String, // <-- AGGIUNTO
});
</script>

<template>
    <Head title="Profilo" />
    <AuthenticatedLayout>
        <template #header><h2 class="font-semibold text-xl text-gray-800 leading-tight">Ufficio di Presidenza</h2></template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <!-- BOX NOME SQUADRA -->
                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg border-l-8 border-orange-500">
                    <UpdateTeamNameForm :team-name="teamName" class="max-w-xl" />
                </div>

                <!-- BOX LOGO SQUADRA (IL NUOVO PUNTO 5) -->
                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg border-l-8 border-blue-600">
                    <UpdateTeamLogoForm :team-logo="teamLogo" class="max-w-xl" />
                </div>

                <!-- DATI PERSONALI -->
                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                    <UpdateProfileInformationForm :must-verify-email="mustVerifyEmail" :status="status" class="max-w-xl" />
                </div>

                <!-- PASSWORD -->
                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                    <UpdatePasswordForm class="max-w-xl" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>