<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({ league: Object, sessions: Array });

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
            alert("Sessione aggiunta con successo!");
        }
    });
};

const stopMarket = () => {
    if(confirm("Sei sicuro? Questo chiuderà il mercato e annullerà tutte le aste attive.")) {
        useForm({}).post(route('market.close-all', props.league.id));
    }
};
</script>

<template>
    <Head title="Calendario Mercato" />
    <AuthenticatedLayout>
        <template #header><h2 class="font-black text-xl uppercase text-gray-800">Calendario Sessioni</h2></template>
        
        <div class="py-12 max-w-4xl mx-auto px-4 space-y-8">
            
            <!-- CREAZIONE NUOVA SESSIONE -->
            <div class="bg-white p-6 shadow-xl rounded-2xl border-t-4 border-blue-600">
                <h3 class="font-bold mb-6 uppercase text-gray-700 tracking-tight">Programma Mercato</h3>
                
                <form @submit.prevent="submit" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-[10px] font-black uppercase text-gray-400 mb-1">Data Inizio</label>
                            <input type="datetime-local" v-model="form.start_at" class="w-full rounded-xl border-gray-300 shadow-sm focus:ring-blue-500 transition" :class="{'border-red-500': form.errors.start_at}">
                            <p v-if="form.errors.start_at" class="text-red-500 text-[10px] font-bold mt-1 uppercase">{{ form.errors.start_at }}</p>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black uppercase text-gray-400 mb-1">Data Fine</label>
                            <input type="datetime-local" v-model="form.end_at" class="w-full rounded-xl border-gray-300 shadow-sm focus:ring-blue-500 transition" :class="{'border-red-500': form.errors.end_at}">
                            <p v-if="form.errors.end_at" class="text-red-500 text-[10px] font-bold mt-1 uppercase">{{ form.errors.end_at }}</p>
                        </div>
                    </div>
                    
                    <button 
                        :disabled="form.processing"
                        class="w-full bg-blue-600 text-white py-4 rounded-xl font-black uppercase tracking-widest hover:bg-blue-700 transition-all shadow-lg active:scale-95 disabled:opacity-50"
                    >
                        {{ form.processing ? 'Invio in corso...' : 'Aggiungi al Calendario' }}
                    </button>
                </form>

                <button @click="stopMarket" class="mt-8 w-full text-red-600 text-[10px] font-black uppercase hover:underline">
                    ⚠️ Emergenza: Chiudi tutto e annulla aste
                </button>
            </div>

            <!-- TABELLA CRONOLOGIA -->
            <div class="bg-white shadow rounded-2xl overflow-hidden border border-gray-200">
                <div class="bg-gray-50 p-4 border-b">
                    <h3 class="font-bold text-gray-500 uppercase text-xs">Cronologia Sessioni</h3>
                </div>
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-gray-100 text-[10px] font-black uppercase text-gray-400">
                            <th class="p-4">Inizio</th>
                            <th class="p-4">Fine</th>
                            <th class="p-4 text-right">Stato</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="s in sessions" :key="s.id" class="border-b last:border-0 hover:bg-gray-50 transition">
                            <td class="p-4 text-sm font-medium">{{ new Date(s.start_at).toLocaleString() }}</td>
                            <td class="p-4 text-sm font-medium">{{ new Date(s.end_at).toLocaleString() }}</td>
                            <td class="p-4 text-right">
                                <span v-if="new Date() > new Date(s.end_at)" class="text-[10px] font-bold uppercase text-gray-400">Conclusa</span>
                                <span v-else-if="new Date() >= new Date(s.start_at)" class="text-[10px] font-bold uppercase text-green-600 bg-green-100 px-2 py-0.5 rounded">Attiva</span>
                                <span v-else class="text-[10px] font-bold uppercase text-blue-500">Programmata</span>
                            </td>
                        </tr>
                        <tr v-if="sessions.length === 0">
                            <td colspan="3" class="p-20 text-center text-gray-300 italic text-sm">Nessuna sessione trovata.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </div>
    </AuthenticatedLayout>
</template>