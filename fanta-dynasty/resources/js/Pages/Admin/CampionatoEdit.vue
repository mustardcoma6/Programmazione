<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useForm, Head } from '@inertiajs/vue3';

const props = defineProps({
    participants: Array
});

// Prepariamo il modulo con i dati attuali
const form = useForm({
    classifica: props.participants.map(p => ({
        id: p.id,
        name: p.team_name, // <--- Cambiato da p.user.name a p.team_name
        league_points: p.league_points || 0,
        total_points: p.total_points || 0,
        games_played: p.games_played || 0
    }))
});

const submit = () => {
    form.post(route('admin.campionato.update'), {
        onSuccess: () => alert('Classifica Campionato salvata con successo!'),
    });
};
</script>

<template>
    <Head title="Gestione Campionato" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-bold text-xl text-gray-800 uppercase italic tracking-tighter">🏆 Gestione Classifica Campionato</h2>
        </template>

        <div class="py-12 px-4">
            <div class="max-w-4xl mx-auto bg-white shadow-2xl rounded-[2.5rem] overflow-hidden">
                
                <!-- INTESTAZIONE COLONNE -->
                <div class="bg-gray-50 border-b border-gray-100 px-8 py-4 hidden sm:flex justify-between items-center">
                    <div class="w-1/3 text-[10px] font-black text-gray-400 uppercase tracking-widest">Squadra</div>
                    <div class="flex gap-4 w-2/3 justify-end pr-2">
                        <div class="w-20 text-center text-[10px] font-black text-blue-600 uppercase tracking-widest">Punti Class.</div>
                        <div class="w-16 text-center text-[10px] font-black text-gray-400 uppercase tracking-widest">Partite</div>
                        <div class="w-24 text-center text-[10px] font-black text-gray-400 uppercase tracking-widest">Punteggio Tot.</div>
                    </div>
                </div>

                <form @submit.prevent="submit" class="p-8">
                    <div class="divide-y divide-gray-100">
                        <div v-for="team in form.classifica" :key="team.id" class="py-5 flex flex-col sm:flex-row items-center justify-between gap-4">
                            
                            <!-- Nome Squadra -->
                            <div class="w-full sm:w-1/3 text-center sm:text-left">
                                <span class="font-black text-blue-900 uppercase text-sm tracking-tight">{{ team.name }}</span>
                            </div>
                            
                            <!-- Campi Input -->
                            <div class="flex items-center gap-4">
                                <!-- Punti Classifica -->
                                <div class="flex flex-col items-center">
                                    <label class="sm:hidden text-[10px] font-bold text-blue-500 uppercase mb-1">Punti Class.</label>
                                    <input type="number" v-model="team.league_points" class="w-20 border-gray-200 rounded-xl font-black text-center bg-blue-50 text-blue-700 focus:ring-blue-500 focus:border-blue-500">
                                </div>

                                <!-- Partite Giocate -->
                                <div class="flex flex-col items-center">
                                    <label class="sm:hidden text-[10px] font-bold text-gray-400 uppercase mb-1">Partite</label>
                                    <input type="number" v-model="team.games_played" class="w-16 border-gray-200 rounded-xl font-bold text-center focus:ring-blue-500">
                                </div>

                                <!-- Punteggio Totale (Voti) -->
                                <div class="flex flex-col items-center">
                                    <label class="sm:hidden text-[10px] font-bold text-gray-400 uppercase mb-1">Punteggio Tot.</label>
                                    <input type="number" step="0.5" v-model="team.total_points" class="w-24 border-gray-200 rounded-xl font-bold text-center focus:ring-blue-500">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pulsante Salva -->
                    <div class="mt-10">
                        <button type="submit" :disabled="form.processing" class="w-full bg-blue-600 text-white py-4 rounded-2xl font-black uppercase tracking-[0.2em] shadow-xl shadow-blue-200 hover:bg-blue-700 transition active:scale-95 disabled:opacity-50">
                            {{ form.processing ? 'Salvataggio...' : 'Salva Classifica Campionato' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>