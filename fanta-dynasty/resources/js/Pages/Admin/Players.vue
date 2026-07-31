<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({ players: Array });

const form = useForm({ name: '', role: 'D', real_team: '' });
const submit = () => form.post(route('admin.players.store'), { onSuccess: () => form.reset() });

const deletePlayer = (p) => {
    let msg = `Eliminare definitivamente ${p.name}?`;
    if (p.owner) {
        msg = `ATTENZIONE: ${p.name} appartiene alla squadra "${p.owner.user.name}". \nSe lo elimini, verrà rimosso anche dalla sua rosa senza rimborsi automatici. Vuoi procedere?`;
    }
    
    if (confirm(msg)) {
        useForm({}).delete(route('admin.players.destroy', p.id), { preserveScroll: true });
    }
};

const search = ref('');
const filteredPlayers = computed(() => {
    return props.players.filter(p => p.name.toLowerCase().includes(search.value.toLowerCase()));
});
</script>

<template>
    <Head title="Gestione Listone" />
    <AuthenticatedLayout>
        <template #header><h2 class="font-black text-xl uppercase">Gestione Listone Mondiale</h2></template>
        
        <div class="py-12 max-w-7xl mx-auto px-4 space-y-8">
            
            <!-- FORM AGGIUNTA -->
            <div class="bg-white p-6 shadow rounded-xl border-t-4 border-indigo-600">
                <h3 class="font-black uppercase text-xs mb-4 text-gray-400">Crea Nuovo Calciatore</h3>
                <form @submit.prevent="submit" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                    <input v-model="form.name" type="text" class="rounded-lg border-gray-300 text-sm" placeholder="Nome" required>
                    <select v-model="form.role" class="rounded-lg border-gray-300 text-sm">
                        <option value="P">Portiere</option><option value="D">Difensore</option><option value="C">Centrocampista</option><option value="A">Attaccante</option>
                    </select>
                    <input v-model="form.real_team" type="text" class="rounded-lg border-gray-300 text-sm" placeholder="Squadra Reale" required>
                    <button class="bg-indigo-600 text-white py-2.5 rounded-lg font-black uppercase text-xs">Aggiungi al sistema</button>
                </form>
            </div>

            <!-- TABELLA COMPLETA -->
            <div class="bg-white shadow rounded-xl overflow-hidden border border-gray-200">
                <div class="p-4 bg-gray-50 border-b flex justify-between items-center">
                    <input v-model="search" type="text" placeholder="Cerca nel listone..." class="w-full max-w-sm rounded-lg border-gray-300 text-sm">
                    <span class="text-[10px] font-bold text-gray-400 uppercase">Totale: {{ players.length }} giocatori</span>
                </div>
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-gray-100 text-[10px] font-black uppercase text-gray-400">
                            <th class="p-4">Ruolo</th>
                            <th class="p-4">Nome</th>
                            <th class="p-4">Stato Attuale</th>
                            <th class="p-4 text-right">Azione</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="p in filteredPlayers" :key="p.id" class="border-b hover:bg-gray-50 transition">
                            <td class="p-4"><span class="font-bold text-blue-600">{{ p.role }}</span></td>
                            <td class="p-4">
                                <p class="font-black uppercase text-sm text-gray-800">{{ p.name }}</p>
                                <p class="text-[10px] text-gray-400 italic">{{ p.real_team }}</p>
                            </td>
                            <td class="p-4">
                                <span v-if="p.owner" class="text-[10px] bg-red-100 text-red-700 px-2 py-1 rounded font-black uppercase">
                                    In Squadra: {{ p.owner.user.name }}
                                </span>
                                <span v-else class="text-[10px] bg-green-100 text-green-700 px-2 py-1 rounded font-black uppercase">
                                    Svincolato
                                </span>
                            </td>
                            <td class="p-4 text-right">
                                <button @click="deletePlayer(p)" class="text-red-500 hover:bg-red-500 hover:text-white px-3 py-1 rounded transition font-bold uppercase text-[10px]">Elimina</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>