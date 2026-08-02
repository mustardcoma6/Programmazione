<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({ 
    players: Array 
});

// --- STATO DEI FILTRI ---
const searchQuery = ref('');
const roleFilter = ref('');
const teamFilter = ref('');

// --- LOGICA DI ESTRAZIONE SQUADRE UNICHE ---
const availableTeams = computed(() => {
    // Prende tutte le squadre dei giocatori, rimuove i duplicati e ordina alfabeticamente
    const teams = props.players.map(p => p.real_team);
    return [...new Set(teams)].sort();
});

// --- LOGICA DI FILTRAGGIO ---
const filteredPlayers = computed(() => {
    return props.players.filter(p => {
        const matchesName = p.name.toLowerCase().includes(searchQuery.value.toLowerCase());
        const matchesRole = roleFilter.value ? p.role === roleFilter.value : true;
        const matchesTeam = teamFilter.value ? p.real_team === teamFilter.value : true;
        
        return matchesName && matchesRole && matchesTeam;
    });
});

// Helper per i colori dei ruoli
const roleClass = (role) => {
    switch (role) {
        case 'P': return 'text-orange-600 bg-orange-50';
        case 'D': return 'text-green-600 bg-green-50';
        case 'C': return 'text-blue-600 bg-blue-50';
        case 'A': return 'text-red-600 bg-red-50';
        default: return 'text-gray-600 bg-gray-50';
    }
};
</script>

<template>
    <Head title="Listone Svincolati" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-black text-xl uppercase tracking-tighter text-gray-800">
                Svincolati (Senza Squadra)
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <!-- BARRA FILTRI -->
                <div class="bg-white p-6 shadow-sm rounded-2xl border border-gray-100">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Cerca per Nome -->
                        <div>
                            <label class="block text-[10px] font-black uppercase text-gray-400 mb-1 ml-1">Cerca Calciatore</label>
                            <input 
                                v-model="searchQuery" 
                                type="text" 
                                placeholder="Es: Lautaro..." 
                                class="w-full rounded-xl border-gray-200 focus:ring-blue-500 focus:border-blue-500 text-sm"
                            >
                        </div>

                        <!-- Filtra per Ruolo -->
                        <div>
                            <label class="block text-[10px] font-black uppercase text-gray-400 mb-1 ml-1">Ruolo</label>
                            <select v-model="roleFilter" class="w-full rounded-xl border-gray-200 focus:ring-blue-500 focus:border-blue-500 text-sm uppercase font-bold">
                                <option value="">Tutti i ruoli</option>
                                <option value="P">Portieri</option>
                                <option value="D">Difensori</option>
                                <option value="C">Centrocampisti</option>
                                <option value="A">Attaccanti</option>
                            </select>
                        </div>

                        <!-- Filtra per Squadra -->
                        <div>
                            <label class="block text-[10px] font-black uppercase text-gray-400 mb-1 ml-1">Squadra Reale</label>
                            <select v-model="teamFilter" class="w-full rounded-xl border-gray-200 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                <option value="">Tutte le squadre</option>
                                <option v-for="team in availableTeams" :key="team" :value="team">
                                    {{ team }}
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- TABELLA RISULTATI -->
                <div class="bg-white shadow-xl rounded-2xl overflow-hidden border border-gray-100">
                    <div class="p-4 bg-gray-50 border-b flex justify-between items-center">
                        <span class="text-[10px] font-black uppercase text-gray-400 tracking-widest">
                            Giocatori trovati: {{ filteredPlayers.length }}
                        </span>
                        <span class="text-[10px] text-gray-400 italic">Prezzi validi solo per chiamata asta</span>
                    </div>

                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="text-[10px] uppercase font-black text-gray-500 border-b">
                                <th class="p-4">Ruolo</th>
                                <th class="p-4">Nome Calciatore</th>
                                <th class="p-4">Squadra Reale</th>
                                <th class="p-4 text-right">Quotazione</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="p in filteredPlayers" :key="p.id" class="border-b last:border-0 hover:bg-blue-50/30 transition">
                                <td class="p-4">
                                    <span class="font-black px-2 py-1 rounded text-xs" :class="roleClass(p.role)">
                                        {{ p.role }}
                                    </span>
                                </td>
                                <td class="p-4">
                                    <span class="font-black uppercase text-sm text-gray-800 tracking-tight">{{ p.name }}</span>
                                </td>
                                <td class="p-4">
                                    <span class="text-gray-500 text-xs font-medium">{{ p.real_team }}</span>
                                </td>
                                <td class="p-4 text-right">
                                    <span class="font-mono font-black text-gray-700">{{ p.initial_value }} cr</span>
                                </td>
                            </tr>

                            <!-- Messaggio se la ricerca è vuota -->
                            <tr v-if="filteredPlayers.length === 0">
                                <td colspan="4" class="p-20 text-center text-gray-400 italic">
                                    Nessun calciatore corrisponde ai filtri selezionati.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="p-4 bg-indigo-50 rounded-xl border border-indigo-100 text-center">
                    <p class="text-[10px] text-indigo-400 font-bold uppercase tracking-tighter">
                        Per acquistare questi giocatori, recati nella sezione "Calciomercato" durante una sessione aperta.
                    </p>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>