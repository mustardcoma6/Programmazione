<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({ 
    myData: Object, 
    primaveraPlayers: Array 
});

// Funzione colori ruoli (coerente con Home e Rosa)
const getRoleClass = (role) => {
    if (role === 'P') return 'role-P';
    if (role === 'D') return 'role-D';
    if (role === 'C') return 'role-C';
    if (role === 'A') return 'role-A';
    return '';
};
</script>

<template>
    <Head title="Primavera" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-black text-xl uppercase tracking-tighter text-violet-600">Settore Giovanile</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <!-- BANNER BUDGET PRIMAVERA (VIOLA) -->
                <div class="bg-white p-6 shadow-xl rounded-3xl border-l-[12px] border-violet-600 flex justify-between items-center">
                    <div>
                        <h3 class="text-lg font-black text-gray-400 uppercase tracking-widest">Budget Primavera</h3>
                        <p class="text-sm text-gray-500 font-medium">Fondi riservati ai giovani talenti</p>
                    </div>
                    <div class="text-right">
                        <p class="text-5xl font-black font-mono text-violet-600">
                            {{ myData.remaining_primavera_budget }} <span class="text-xl">cr</span>
                        </p>
                    </div>
                </div>

                <!-- LISTA GIOCATORI PRIMAVERA -->
                <div class="bg-white p-8 shadow-xl rounded-3xl border border-gray-100">
                    <h3 class="font-black uppercase text-sm mb-6 border-b pb-2 tracking-widest text-violet-600 italic">I tuoi Giovani in Addestramento ({{ primaveraPlayers.length }})</h3>
                    
                    <div v-if="primaveraPlayers.length === 0" class="text-center py-20 text-gray-300 uppercase font-bold text-sm italic">
                        Il tuo vivaio è attualmente vuoto.
                    </div>

                    <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div v-for="item in primaveraPlayers" :key="item.id" class="p-4 bg-violet-50/50 border border-violet-100 rounded-2xl flex justify-between items-center shadow-sm">
                            <div class="flex flex-col">
                                <span class="text-[10px] font-black uppercase mb-1" :class="getRoleClass(item.player.role)">
                                    {{ item.player.role }}
                                </span>
                                <span class="text-sm font-black uppercase text-gray-800 tracking-tighter">
                                    {{ item.player.name }}
                                </span>
                                <span class="text-[9px] text-gray-400 uppercase font-bold">{{ item.player.real_team }}</span>
                            </div>
                            <div class="text-right">
                                <p class="text-xs font-mono font-black text-violet-600">{{ item.purchase_price }} cr</p>
                                <p class="text-[8px] text-gray-400 uppercase font-bold">Investimento</p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.role-P { color: #FFD700 !important; font-weight: 900; }
.role-D { color: #006400 !important; font-weight: 900; }
.role-C { color: #1e40af !important; font-weight: 900; }
.role-A { color: #dc2626 !important; font-weight: 900; }
</style>