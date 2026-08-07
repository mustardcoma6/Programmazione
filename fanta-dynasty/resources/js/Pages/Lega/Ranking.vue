<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';

defineProps({
    ranking: Array,
    lastUpdate: String
});

const user = usePage().props.auth.user;

// Verifica se la riga appartiene all'utente loggato
const isMe = (name) => {
    return name.toUpperCase() === user.name.toUpperCase();
};
</script>

<template>
    <Head title="Ranking Presidenti" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-black text-xl uppercase tracking-tighter text-gray-800">Ranking Ufficiale Presidenti</h2>
        </template>

        <div class="py-12 max-w-4xl mx-auto px-4">
            <div class="bg-white shadow-2xl rounded-3xl overflow-hidden border border-gray-100">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-900 text-white text-[10px] font-black uppercase tracking-widest">
                            <th class="p-6">Pos</th>
                            <th class="p-6 text-center">Trend</th>
                            <th class="p-6">Presidente</th>
                            <th class="p-6 text-right">Punti</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(pres, index) in ranking" :key="index" 
                            class="border-b last:border-0 transition-all duration-300"
                            :class="isMe(pres.name) ? 'bg-indigo-600 text-white shadow-inner scale-[1.02] z-10 relative' : 'hover:bg-blue-50/50 text-gray-800'">
                            
                            <!-- POSIZIONE -->
                            <td class="p-6">
                                <span class="w-8 h-8 rounded-full flex items-center justify-center font-black text-sm" 
                                      :class="isMe(pres.name) ? 'bg-white text-indigo-600' : (index < 3 ? 'bg-yellow-400 text-yellow-900' : 'bg-gray-100 text-gray-500')">
                                    {{ index + 1 }}
                                </span>
                            </td>

                            <!-- TREND (Frecce) -->
                            <td class="p-6 text-center">
                                <span v-if="pres.trend === 'up'" class="text-green-500 text-xl font-bold" :class="{'text-white': isMe(pres.name)}">▲</span>
                                <span v-else-if="pres.trend === 'down'" class="text-red-500 text-xl font-bold" :class="{'text-white': isMe(pres.name)}">▼</span>
                                <span v-else class="text-gray-300 font-bold" :class="{'text-white/50': isMe(pres.name)}">—</span>
                            </td>

                            <!-- NOME PRESIDENTE -->
                            <td class="p-6">
                                <span class="font-black uppercase text-sm tracking-tight" :class="{'text-white': isMe(pres.name)}">
                                    {{ pres.name }}
                                    <span v-if="isMe(pres.name)" class="ml-2 text-[8px] bg-white/20 px-2 py-0.5 rounded-full">TU</span>
                                </span>
                            </td>

                            <!-- PUNTI -->
                            <td class="p-6 text-right">
                                <span class="font-mono font-black text-2xl" :class="isMe(pres.name) ? 'text-white' : 'text-gray-900'">
                                    {{ pres.points }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- DATA ULTIMO AGGIORNAMENTO -->
            <div class="mt-8 text-center">
                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">
                    Ultimo aggiornamento dati: <span class="text-gray-600">{{ lastUpdate }}</span>
                </p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>