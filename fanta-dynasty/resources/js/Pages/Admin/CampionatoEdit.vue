<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useForm, Head } from '@inertiajs/vue3';

const props = defineProps({ participants: Array });

const form = useForm({
    classifica: props.participants.map(p => ({
        id: p.id,
        name: p.user.name,
        total_points: p.total_points || 0,
        games_played: p.games_played || 0
    }))
});

const submit = () => {
    form.post(route('admin.campionato.update'), {
        onSuccess: () => alert('Classifica Campionato salvata!'),
    });
};
</script>

<template>
    <Head title="Gestione Campionato" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-bold text-xl text-gray-800 uppercase">🏆 Gestione Classifica Campionato</h2>
        </template>
        <div class="py-12 px-4">
            <div class="max-w-3xl mx-auto bg-white shadow-2xl rounded-3xl p-8">
                <form @submit.prevent="submit">
                    <div v-for="team in form.classifica" :key="team.id" class="flex items-center justify-between py-4 border-b">
                        <span class="font-black text-gray-700 w-1/3">{{ team.name }}</span>
                        <div class="flex gap-4">
                            <div class="text-center">
                                <label class="block text-[10px] font-bold text-gray-400 uppercase">Punti</label>
                                <input type="number" step="0.5" v-model="team.total_points" class="w-20 border-gray-200 rounded-lg font-bold text-center">
                            </div>
                            <div class="text-center">
                                <label class="block text-[10px] font-bold text-gray-400 uppercase">Partite</label>
                                <input type="number" v-model="team.games_played" class="w-16 border-gray-200 rounded-lg font-bold text-center">
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="w-full mt-8 bg-green-600 text-white py-4 rounded-2xl font-black uppercase tracking-widest hover:bg-green-700 transition">
                        Salva Classifica Campionato
                    </button>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>