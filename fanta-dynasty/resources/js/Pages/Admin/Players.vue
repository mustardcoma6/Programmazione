<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({ players: Array });

// Form per aggiunta nuovo
const form = useForm({ name: '', role: 'D', real_team: '' });

const submit = () => {
    form.post(route('admin.players.store'), {
        onSuccess: () => form.reset()
    });
};

// Logica per eliminazione
const deletePlayer = (id, name) => {
    if (confirm(`Eliminare definitivamente ${name} dal listone di sistema?`)) {
        useForm({}).delete(route('admin.players.destroy', id));
    }
};

// Ricerca locale
const search = ref('');
const filteredPlayers = computed(() => {
    return props.players.filter(p => p.name.toLowerCase().includes(search.value.toLowerCase()));
});
</script>

<template>
    <Head title="Gestione Listone" />
    <AuthenticatedLayout>
        <template #header><h2 class="font-black text-xl uppercase">Gestione Listone Calciatori</h2></template>
        
        <div class="py-12 max-w-7xl mx-auto px-4 space-y-8">
            
            <!-- FORM AGGIUNTA -->
            <div class="bg-white p-6 shadow rounded-xl border-t-4 border-indigo-600">
                <h3 class="font-black uppercase text-xs mb-4">Aggiungi Nuovo Calciatore al Sistema</h3>
                <form @submit.prevent="submit" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                    <div>
                        <label class="block text-[10px] font-black uppercase text-gray-400">Nome</label>
                        <input v-model="form.name" type="text" class="w-full rounded-lg border-gray-300 text-sm" placeholder="Es: Lautaro Martinez" required>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black uppercase text-gray-400">Ruolo</label>
                        <select v-model="form.role" class="w-full rounded-lg border-gray-300 text-sm">
                            <option value="P">Portiere</option>
                            <option value="D">Difensore</option>
                            <option value="C">Centrocampista</option>
                            <option value="A">Attaccante</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black uppercase text-gray-400">Squadra Reale</label>
                        <input v-model="form.real_team" type="text" class="w-full rounded-lg border-gray-300 text-sm" placeholder="Es: Inter" required>
                    </div>
                    <button class="bg-indigo-600 text-white py-2.5 rounded-lg font-black uppercase text-xs hover:bg-indigo-700">Aggiungi</button>
                </form>
            </div>

            <!-- TABELLA LISTONE -->
            <div class="bg-white shadow rounded-xl overflow-hidden border border-gray-200">
                <div class="p-4 bg-gray-50 border-b">
                    <input v-model="search" type="text" placeholder="Cerca nel listone..." class="w-full max-w-sm rounded-lg border-gray-300 text-sm">
                </div>
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-gray-100 text-[10px] font-black uppercase text-gray-400">
                            <th class="p-4">Ruolo</th>
                            <th class="p-4">Nome</th>
                            <th class="p-4">Squadra Reale</th>
                            <th class="p-4 text-right">Azione</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="p in filteredPlayers" :key="p.id" class="border-b hover:bg-gray-50 transition">
                            <td class="p-4"><span class="font-bold text-blue-600">{{ p.role }}</span></td>
                            <td class="p-4 font-black uppercase text-sm text-gray-800">{{ p.name }}</td>
                            <td class="p-4 text-gray-500 text-xs italic">{{ p.real_team }}</td>
                            <td class="p-4 text-right">
                                <button @click="deletePlayer(p.id, p.name)" class="text-red-500 hover:text-red-700 font-bold uppercase text-[10px]">Elimina</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </div>
    </AuthenticatedLayout>
</template>