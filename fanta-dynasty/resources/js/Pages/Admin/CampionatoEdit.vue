<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useForm, Head } from '@inertiajs/vue3';

const props = defineProps({ participants: Array });

const form = useForm({
    classifica: props.participants.map(p => ({
        id: p.id,
        name: p.user.name,
        league_points: p.league_points || 0,
        total_points: p.total_points || 0,
        games_played: p.games_played || 0
    }))
});

const submit = () => { form.post(route('admin.campionato.update')); };
</script>

<template>
    <Head title="Gestione" />
    <AuthenticatedLayout>
        <div class="py-12 max-w-4xl mx-auto px-4">
            <div class="bg-white p-8 rounded-3xl shadow">
                <form @submit.prevent="submit" class="divide-y">
                    <div v-for="team in form.classifica" :key="team.id" class="py-4 flex justify-between items-center">
                        <span class="font-bold w-1/3">{{ team.name }}</span>
                        <div class="flex gap-2">
                            <input type="number" v-model="team.league_points" class="w-16 border-gray-200 rounded text-center" title="Punti Classifica">
                            <input type="number" v-model="team.games_played" class="w-16 border-gray-200 rounded text-center" title="Partite">
                            <input type="number" step="0.5" v-model="team.total_points" class="w-20 border-gray-200 rounded text-center" title="Somma Voti">
                        </div>
                    </div>
                    <button type="submit" class="w-full mt-6 bg-blue-600 text-white py-3 rounded-xl font-bold uppercase">Salva</button>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>