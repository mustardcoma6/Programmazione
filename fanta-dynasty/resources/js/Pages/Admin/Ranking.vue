<template>
    <div class="p-6 bg-white rounded shadow">
        <h1 class="text-xl font-bold mb-4">Gestione Punti Classifica</h1>
        <div v-for="p in participants" :key="p.id" class="mb-4 flex items-center gap-4 border-b pb-2">
            <span class="w-1/4 font-bold">{{ p.user.name }}</span>
            
            <label>Punti Tot:</label>
            <input type="number" step="0.5" v-model="form.rankings[p.id].total_points" class="border p-1 w-20">
            
            <label>Partite:</label>
            <input type="number" v-model="form.rankings[p.id].games_played" class="border p-1 w-16">
        </div>
        <button @click="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Salva Tutto</button>
    </div>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';

const props = defineProps(['participants']);

// Prepariamo i dati da inviare
const initialData = {};
props.participants.forEach(p => {
    initialData[p.id] = { id: p.id, total_points: p.total_points, games_played: p.games_played };
});

const form = useForm({ rankings: initialData });

const submit = () => {
    form.post(route('admin.rankings.update'));
};
</script>