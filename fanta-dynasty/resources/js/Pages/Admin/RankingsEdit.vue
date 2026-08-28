<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useForm, Head } from '@inertiajs/vue3';

const props = defineProps({
    participants: Array
});

// Prepariamo il modulo con i dati attuali
const form = useForm({
    rankings: props.participants.map(p => ({
        id: p.id,
        name: p.user.name,
        total_points: p.total_points || 0,
        games_played: p.games_played || 0
    }))
});

const submit = () => {
    form.post(route('admin.rankings.update'), {
        onFinish: () => alert('Classifica salvata!'),
    });
};
</script>

<template>
    <Head title="Gestione Classifica" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Inserimento Punti e Partite</h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white shadow-xl sm:rounded-2xl p-6">
                    <form @submit.prevent="submit">
                        <div class="divide-y divide-gray-100">
                            <div v-for="(rank, index) in form.rankings" :key="rank.id" class="py-4 flex items-center justify-between">
                                <div class="w-1/3">
                                    <span class="font-black text-gray-700">{{ rank.name }}</span>
                                </div>
                                
                                <div class="flex gap-4 w-2/3 justify-end">
                                    <div class="flex flex-col">
                                        <label class="text-[10px] font-bold uppercase text-gray-400">Punti Totali</label>
                                        <input type="number" step="0.1" v-model="rank.total_points" class="border-gray-200 rounded-lg w-24 text-center font-bold">
                                    </div>
                                    <div class="flex flex-col">
                                        <label class="text-[10px] font-bold uppercase text-gray-400">Partite</label>
                                        <input type="number" v-model="rank.games_played" class="border-gray-200 rounded-lg w-20 text-center font-bold">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-8 border-t pt-6">
                            <button type="submit" :disabled="form.processing" class="w-full bg-blue-600 text-white py-4 rounded-xl font-black uppercase tracking-widest hover:bg-blue-700 transition disabled:opacity-50">
                                Aggiorna Classifica Generale
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>