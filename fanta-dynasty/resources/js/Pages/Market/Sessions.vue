<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({ league: Object, sessions: Array });

const form = useForm({ 
    start_at: '', 
    end_at: '' 
});

const submit = () => {
    form.post(route('market.sessions.store'), { 
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            alert("✅ Sessione salvata con successo!");
        },
        onError: (errors) => {
            console.error(errors);
            alert("❌ Errore nel salvataggio. Controlla i dati inseriti.");
        }
    });
};

const stopMarket = () => {
    if(confirm("⚠️ ATTENZIONE: Questo chiuderà il mercato immediatamente e ANNULLERÀ tutte le aste in corso. I giocatori torneranno svincolati. Procedere?")) {
        useForm({}).post(route('market.close-all', props.league.id), {
            onSuccess: () => alert("Mercato interrotto e aste cancellate.")
        });
    }
};
</script>

<template>
    <Head title="Calendario Mercato" />
    <AuthenticatedLayout>
        <template #header><h2 class="font-black text-xl uppercase">Gestione Sessioni</h2></template>
        
        <div class="py-12 max-w-2xl mx-auto px-4 space-y-8">
            <!-- BOX CREAZIONE -->
            <div class="bg-white p-6 shadow-xl rounded-2xl border-t-4 border-blue-600">
                <h3 class="font-bold mb-4 uppercase text-gray-700">Nuova Sessione</h3>
                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <label class="block text-[10px] font-black uppercase text-gray-400">Inizio</label>
                        <input type="datetime-local" v-model="form.start_at" class="w-full rounded-xl border-gray-300">
                        <p v-if="form.errors.start_at" class="text-red-500 text-[10px] font-bold mt-1 uppercase">{{ form.errors.start_at }}</p>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black uppercase text-gray-400">Fine</label>
                        <input type="datetime-local" v-model="form.end_at" class="w-full rounded-xl border-gray-300">
                        <p v-if="form.errors.end_at" class="text-red-500 text-[10px] font-bold mt-1 uppercase">{{ form.errors.end_at }}</p>
                    </div>
                    <button :disabled="form.processing" class="w-full bg-blue-600 text-white py-4 rounded-xl font-black uppercase shadow-lg hover:bg-blue-700 transition">
                        {{ form.processing ? 'Salvataggio...' : 'Aggiungi al Calendario' }}
                    </button>
                </form>

                <!-- TASTO EMERGENZA RIPRISTINATO -->
                <button @click="stopMarket" class="mt-8 w-full bg-red-50 text-red-600 py-3 rounded-xl text-[10px] font-black uppercase border border-red-100 hover:bg-red-100 transition">
                    🚨 Chiudi sessione attiva e annulla tutte le aste
                </button>
            </div>

            <!-- TABELLA CRONOLOGIA -->
            <div class="bg-white shadow rounded-2xl overflow-hidden">
                <table class="w-full text-left">
                    <thead class="bg-gray-50 border-b">
                        <tr class="text-[10px] font-black uppercase text-gray-400">
                            <th class="p-4">Inizio</th>
                            <th class="p-4">Fine</th>
                            <th class="p-4 text-right">Stato</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="s in sessions" :key="s.id" class="border-b last:border-0 hover:bg-gray-50 transition">
                            <td class="p-4 text-xs font-bold">{{ new Date(s.start_at).toLocaleString() }}</td>
                            <td class="p-4 text-xs font-bold">{{ new Date(s.end_at).toLocaleString() }}</td>
                            <td class="p-4 text-right">
                                <span v-if="new Date() > new Date(s.end_at)" class="text-[9px] bg-gray-100 px-2 py-0.5 rounded font-black uppercase text-gray-400">Chiuso</span>
                                <span v-else-if="new Date() >= new Date(s.start_at)" class="text-[9px] bg-green-100 px-2 py-0.5 rounded font-black uppercase text-green-600">Attivo</span>
                                <span v-else class="text-[9px] bg-blue-100 px-2 py-0.5 rounded font-black uppercase text-blue-500">Programmato</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>