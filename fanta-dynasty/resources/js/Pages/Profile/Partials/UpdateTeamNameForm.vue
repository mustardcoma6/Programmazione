<script setup>
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { useForm, usePage } from '@inertiajs/vue3';

const props = defineProps({
    teamName: String,
});

const form = useForm({
    team_name: props.teamName,
});

const updateTeamName = () => {
    form.post(route('profile.team.update'), {
        preserveScroll: true,
        onSuccess: () => alert('Nome Squadra salvato!'),
    });
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900">Informazioni Squadra</h2>
            <p class="mt-1 text-sm text-gray-600">Cambia il nome della tua società in questa lega.</p>
        </header>

        <form @submit.prevent="updateTeamName" class="mt-6 space-y-6">
            <div>
                <InputLabel for="team_name" value="Nome Squadra" />
                <TextInput id="team_name" type="text" class="mt-1 block w-full" v-model="form.team_name" required />
                <InputError class="mt-2" :message="form.errors.team_name" />
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton :disabled="form.processing">Salva Nome Squadra</PrimaryButton>
                <Transition enter-active-class="transition ease-in-out" enter-from-class="opacity-0" leave-active-class="transition ease-in-out" leave-to-class="opacity-0">
                    <p v-if="form.recentlySuccessful" class="text-sm text-gray-600">Salvato.</p>
                </Transition>
            </div>
        </form>
    </section>
</template>