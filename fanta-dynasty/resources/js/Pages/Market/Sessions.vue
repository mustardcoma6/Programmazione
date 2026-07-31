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
            alert("✅ Sessione salvata!");
        },
        onError: () => alert("❌ Errore. Controlla le date.")
    });
};
</script>

<template>
    <Head title="Calendario" />
    <AuthenticatedLayout>
        <template #header><h2 class="font-black text-xl uppercase">Calendario Mercato</h2></template>
        
        <div class="py-12 max-w-2xl mx-auto px-4 space-y-6">
            <div class="bg-white p-6 shadow-xl rounded-2xl border-t-4 border-blue-600">
                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <label class="block text-[10px] font-black uppercase text-gray-400">Inizio</label>
                        <input type="datetime-local" v-model="form.start_at" class="w-full rounded-xl border-gray-300">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black uppercase text-gray-400">Fine</label>
                        <input type="datetime-local" v-model="form.end_at" class="w-full rounded-xl border-gray-300">
                    </div>
                    <button class="w-full bg-blue-600 text-white py-4 rounded-xl font-black uppercase shadow-lg">
                        Salva Sessione
                    </button>
                </form>
            </div>

            <div class="bg-white shadow rounded-2xl overflow-hidden">
                <table class="w-full text-left">
                    <tr v-for="s in sessions" :key="s.id" class="border-b">
                        <td class="p-4 text-xs font-bold">{{ new Date(s.start_at).toLocaleString() }}</td>
                        <td class="p-4 text-xs font-bold">{{ new Date(s.end_at).toLocaleString() }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>