<script setup>
import { useForm } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    teamLogo: String,
});

const form = useForm({
    logo: null,
});

const submit = () => {
    // Usiamo post perché i file non possono essere inviati con "patch" o "put" facilmente
    form.post(route('profile.team.logo'), {
        onSuccess: () => alert('Logo caricato!'),
    });
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900 uppercase font-black">Logo della Società</h2>
            <p class="mt-1 text-sm text-gray-600 italic">Carica lo stemma ufficiale della tua squadra.</p>
        </header>

        <div class="mt-6 flex items-center gap-6">
            <!-- Anteprima attuale -->
            <div class="w-20 h-20 bg-gray-100 rounded-xl border-2 border-dashed border-gray-300 flex items-center justify-center overflow-hidden">
                <img v-if="teamLogo" :src="teamLogo" class="w-full h-full object-cover">
                <span v-else class="text-2xl">🛡️</span>
            </div>

            <form @submit.prevent="submit" class="space-y-4">
                <input 
                    type="file" 
                    @input="form.logo = $event.target.files[0]" 
                    class="text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                    accept="image/*"
                />
                
                <div v-if="form.errors.logo" class="text-red-500 text-xs font-bold">{{ form.errors.logo }}</div>

                <div class="flex items-center gap-4">
                    <PrimaryButton :disabled="form.processing">Carica Logo</PrimaryButton>
                    <p v-if="form.recentlySuccessful" class="text-sm text-gray-600">Caricato.</p>
                </div>
            </form>
        </div>
    </section>
</template>