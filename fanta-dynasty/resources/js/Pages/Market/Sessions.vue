<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({ league: Object, sessions: Array });

// Inizializziamo il form con l'ID della lega già pronto
const form = useForm({ 
    league_id: props.league.id, 
    start_at: '', 
    end_at: '' 
});

const submit = () => {
    form.post(route('market.sessions.store'), { 
        preserveScroll: true,
        onSuccess: () => {
            form.reset('start_at', 'end_at');
            alert("Sessione salvata con successo!");
        },
        onError: () => {
            alert("Errore nel salvataggio. Controlla le date.");
        }
    });
};

const stopMarket = () => {
    if(confirm("Annullare tutte le aste e chiudere il mercato ORA?")) {
        useForm({}).post(route('market.close-all', props.league.id));
    }
};
</script>

<template>
    <Head title="Calendario Mercato" />
    <AuthenticatedLayout>
        <template #header><h2 class="font-black text-xl uppercase">Calendario Mercato</h2></template>
        
        <div class="py-12 max-w-4xl mx-auto px-4 space-y-8">
            
            <!-- FORM NUOVA SESSIONE -->
            <div class="bg-white p-6 shadow rounded-xl border-t-4 border-blue-600">
                <h3 class="font-bold mb-4 uppercase text-gray-700">Programma Nuova Sessione</h3>
                
                <form @submit.prevent="submit" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-black uppercase text-gray-400">Inizio Sessione</label>
                            <input type="datetime-local" v-model="form.start_at" class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500">
                            <!-- Messaggio Errore -->
                            <div v-if="form.errors.start_at" class="text-red-500 text-[10px] mt-1 font-bold uppercase">{{ form.errors.start_at }}</div>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black uppercase text-gray-400">Fine Sessione</label>
                            <input type="datetime-local" v-model="form.end_at" class="w-full rounded-lg border-gray-300 shadow-sm focus:ring-blue-500">
                            <!-- Messaggio Errore -->
                            <div v-if="form.errors.end_at" class="text-red-500 text-[10px] mt-1 font-bold uppercase">{{ form.errors.end_at }}</div>
                        </div>
                    </div>
                    
                    <button 
                        :disabled="form.processing"
                        class="w-full bg-blue-600 text-white py-3 rounded-lg font-black uppercase tracking-widest hover:bg-blue-700 transition disabled:opacity-50"
                    >
                        {{ form.processing ? 'Salvataggio...' : 'Aggiungi al Calendario' }}
                    </button>
                </form>

                <button @click="stopMarket" class="mt-6 w-full bg-red-50 text-red-600 py-2 rounded-lg text-[10px] font-black uppercase border border-red-100 hover:bg-red-100 transition">
                    Interrompi sessione attiva e annulla tutte le aste
                </button>
            </div>

            <!-- TABELLA CRONOLOGIA -->
            <div class="bg-white p-6 shadow rounded-xl">
                <h3 class="font-bold mb-4 uppercase text-gray-500 text-sm">Sessioni in Archivio</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-gray-50 text-[10px] font-black uppercase text-gray-400">
                                <th class="p-3">Inizio</th>
                                <th class="p-3">Fine</th>
                                <th class="p-3 text-right">Stato</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="s in sessions" :key="s.id" class="border-b last:border-0">
                                <td class="p-3 text-sm font-medium">{{ new Date(s.start_at).toLocaleString() }}</td>
                                <td class="p-3 text-sm font-medium">{{ new Date(s.end_at).toLocaleString() }}</td>
                                <td class="p-3 text-right">
                                    <span v-if="new Date() > new Date(s.end_at)" class="text-[10px] font-bold uppercase text-gray-400 bg-gray-100 px-2 py-0.5 rounded">Conclusa</span>
                                    <span v-else-if="new Date() >= new Date(s.start_at)" class="text-[10px] font-bold uppercase text-green-600 bg-green-100 px-2 py-0.5 rounded">Attiva</span>
                                    <span v-else class="text-[10px] font-bold uppercase text-blue-500 bg-blue-50 px-2 py-0.5 rounded">In Arrivo</span>
                                </td>
                            </tr>
                            <tr v-if="sessions.length === 0">
                                <td colspan="3" class="p-10 text-center text-gray-400 italic text-sm">Nessuna sessione programmata.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>