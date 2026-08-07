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
const sortOrder = ref('role'); // Default: ordina per Ruolo

// --- LOGICA DI ESTRAZIONE SQUADRE UNICHE ---
const availableTeams = computed(() => {
    const teams = props.players.map(p => p.real_team);
    return [...new Set(teams)].sort();
});

// --- LOGICA DI FILTRAGGIO E ORDINAMENTO ---
const filteredPlayers = computed(() => {
    // 1. Filtriamo la lista
    let list = props.players.filter(p => {
        const matchesName = p.name.toLowerCase().includes(searchQuery.value.toLowerCase());
        const matchesRole = roleFilter.value ? p.role === roleFilter.value : true;
        const matchesTeam = teamFilter.value ? p.real_team === teamFilter.value : true;
        return matchesName && matchesRole && matchesTeam;
    });

    // 2. Ordiniamo la lista filtrata
    if (sortOrder.value === 'high-price') {
        list.sort((a, b) => b.quotation - a.quotation);
    } else if (sortOrder.value === 'low-price') {
        list.sort((a, b) => a.quotation - b.quotation);
    } else {
        // Ordine di default: Ruolo (P-D-C-A) e poi Nome
        const rolePriority = { 'P': 1, 'D': 2, 'C': 3, 'A': 4 };
        list.sort((a, b) => {
            if (rolePriority[a.role] !== rolePriority[b.role]) {
                return rolePriority[a.role] - rolePriority[b.role];
            }
            return a.name.localeCompare(b.name);
        });
    }

    return list;
});

// Helper per i colori dei ruoli (Coerente con tutto il sito)
const roleClass = (role) => {
    switch (role) {
        case 'P': return 'role-P';
        case 'D': return 'role-D';
        case 'C': return 'role-C';
        case 'A': return 'role-A';
        default: return '';
    }
};
</script>

<template>
    <Head title="Listone Svincolati" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-black text-xl uppercase tracking-tighter text-gray-800">
                Listone Svincolati
            </h2>
        </template>

        <div class="py-12 px-4">
            <div class="max-w-7xl mx-auto space-y-6">
                
                <!-- BARRA FILTRI POTENZIATA -->
                <div class="bg-white p-6 shadow-sm rounded-2xl border border-gray-100">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <!-- Cerca per Nome -->
                        <div>
                            <label class="block text-[10px] font-black uppercase text-gray-400 mb-1 ml-1">Cerca Nome</label>
                            <input v-model="searchQuery" type="text" placeholder="Es: Lautaro..." class="w-full rounded-xl border-gray-200 text-sm">
                        </div>

                        <!-- Filtra per Ruolo -->
                        <div>
                            <label class="block text-[10px] font-black uppercase text-gray-400 mb-1 ml-1">Ruolo</label>
                            <select v-model="roleFilter" class="w-full rounded-xl border-gray-200 text-sm font-bold">
                                <option value="">Tutti</option>
                                <option value="P">P</option><option value="D">D</option><option value="C">C</option><option value="A">A</option>
                            </select>
                        </div>

                        <!-- Filtra per Squadra -->
                        <div>
                            <label class="block text-[10px] font-black uppercase text-gray-400 mb-1 ml-1">Squadra</label>
                            <select v-model="teamFilter" class="w-full rounded-xl border-gray-200 text-sm">
                                <option value="">Tutte</option>
                                <option v-for="team in availableTeams" :key="team" :value="team">{{ team }}</option>
                            </select>
                        </div>

                        <!-- ORDINA PER QUOTAZIONE (IL NUOVO FILTRO) -->
                        <div>
                            <label class="block text-[10px] font-black uppercase text-blue-500 mb-1 ml-1">Ordina Per</label>
                            <select v-model="sortOrder" class="w-full rounded-xl border-blue-200 bg-blue-50 text-blue-700 text-sm font-black">
                                <option value="role">Default (Ruolo)</option>
                                <option value="high-price">Quotazione: ↑ Alta</option>
                                <option value="low-price">Quotazione: ↓ Bassa</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- TABELLA RISULTATI -->
                <div class="bg-white shadow-xl rounded-2xl overflow-hidden border border-gray-100">
                    <div class="p-4 bg-gray-50 border-b flex justify-between items-center">
                        <span class="text-[10px] font-black uppercase text-gray-400">Giocatori: {{ filteredPlayers.length }}</span>
                    </div>

                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="text-[10px] uppercase font-black text-gray-500 border-b">
                                <th class="p-4">Ruolo</th>
                                <th class="p-4">Nome Calciatore</th>
                                <th class="p-4">Squadra</th>
                                <th class="p-4 text-right">Quotazione</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="p in filteredPlayers" :key="p.id" class="border-b last:border-0 hover:bg-blue-50/30 transition">
                                <td class="p-4">
                                    <span class="font-black px-2 py-1 rounded text-xs shadow-sm" :class="roleClass(p.role)">
                                        {{ p.role }}
                                    </span>
                                </td>
                                <td class="p-4">
                                    <span class="font-black uppercase text-sm text-gray-800 tracking-tight">{{ p.name }}</span>
                                </td>
                                <td class="p-4 uppercase text-gray-400 text-[10px] font-bold">
                                    {{ p.real_team }}
                                </td>
                                <td class="p-4 text-right font-mono font-black text-green-600">
                                    {{ p.quotation }} cr
                                </td>
                            </tr>
                            <tr v-if="filteredPlayers.length === 0">
                                <td colspan="4" class="p-20 text-center text-gray-300 italic uppercase font-black">Nessun risultato</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.role-P { color: #856404 !important; background-color: #fff3cd !important; border: 1px solid #ffeeba; }
.role-D { color: #155724 !important; background-color: #d4edda !important; border: 1px solid #c3e6cb; }
.role-C { color: #004085 !important; background-color: #cce5ff !important; border: 1px solid #b8daff; }
.role-A { color: #721c24 !important; background-color: #f8d7da !important; border: 1px solid #f5c6cb; }
</style>