<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({ league: Object, sessions: Array });

const form = useForm({ 
    start_at: '', 
    end_at: '',
    auction_time: '01:30',
    roles: ['P', 'D', 'C', 'A']
});

const submit = () => {
    form.post(route('market.sessions.store'), { 
        preserveScroll: true,
        onSuccess: () => {
            form.reset('start_at', 'end_at');
            alert("✅ Sessione salvata con successo!");
        }
    });
};

const formatDuration = (totalMinutes) => {
    const hours = Math.floor(totalMinutes / 60);
    const minutes = totalMinutes % 60;
    return `${hours.toString().padStart(2, '0')}h ${minutes.toString().padStart(2, '0')}m`;
};

const stopMarket = () => {
    if(confirm("⚠️ ATTENZIONE: Questo chiuderà il mercato e annullerà tutte le aste in corso. Procedere?")) {
        useForm({}).post(route('market.close-all'), {
            preserveScroll: true,
            onSuccess: () => alert("Mercato interrotto e aste cancellate.")
        });
    }
};
</script>

<template>
    <Head title="Calendario" />
    <AuthenticatedLayout>
        <template #header><h2 class="font-black text-xl uppercase text-gray-800">Calendario Mercato</h2></template>
        
        <div class="py-12 max-w-4xl mx-auto px-4 space-y-8">
            <div class="bg-white p-6 shadow-xl rounded-2xl border-t-4 border-blue-600">
                <form @submit.prevent="submit" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-[10px] font-black uppercase text-gray-400 mb-1">Inizio Mercato</label>
                            <input type="datetime-local" v-model="form.start_at" class="w-full rounded-xl border-gray-300">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black uppercase text-gray-400 mb-1">Fine Mercato</label>
                            <input type="datetime-local" v-model="form.end_at" class="w-full rounded-xl border-gray-300">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-[10px] font-black uppercase text-gray-400 mb-1">Durata Asta (Ore:Min)</label>
                            <input type="time" v-model="form.auction_time" class="w-full rounded-xl border-gray-300 font-mono font-bold text-lg">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black uppercase text-gray-400 mb-2">Ruoli Abilitati</label>
                            <div class="flex gap-4">
                                <label v-for="r in ['P','D','C','A']" :key="r" class="flex items-center gap-1 cursor-pointer">
                                    <input type="checkbox" :value="r" v-model="form.roles" class="rounded text-blue-600">
                                    <span class="font-black text-sm">{{ r }}</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    <button :disabled="form.processing" class="w-full bg-blue-600 text-white py-4 rounded-xl font-black uppercase shadow-lg hover:bg-blue-700 transition">Aggiungi al Calendario</button>
                </form>

                <button @click="stopMarket" class="mt-8 w-full text-red-600 text-[10px] font-black uppercase hover:underline">⚠️ Emergenza: Chiudi tutto e annulla aste</button>
            </div>

            <div class="bg-white shadow rounded-2xl overflow-hidden border">
                <table class="w-full text-left text-sm">
                    <thead><tr class="bg-gray-50 text-[10px] font-black uppercase text-gray-400"><th class="p-4">Periodo</th><th class="p-4 text-center">Durata Asta</th><th class="p-4 text-center">Ruoli</th></tr></thead>
                    <tbody>
                        <tr v-for="s in sessions" :key="s.id" class="border-b">
                            <td class="p-4 text-xs font-bold">{{ new Date(s.start_at).toLocaleString() }}<br><span class="text-gray-400">{{ new Date(s.end_at).toLocaleString() }}</span></td>
                            <td class="p-4 text-center font-mono font-bold text-blue-600">{{ formatDuration(s.auction_duration) }}</td>
                            <td class="p-4 text-center uppercase font-black text-gray-400 text-[10px]">{{ s.allowed_roles }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>