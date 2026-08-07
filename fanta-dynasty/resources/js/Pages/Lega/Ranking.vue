<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';

defineProps({
    ranking: Array,
    lastUpdate: String
});

const user = usePage().props.auth.user;

const isMe = (name) => {
    return name.toUpperCase() === user.name.toUpperCase();
};
</script>

<template>
    <Head title="Ranking Presidenti" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-black text-xl uppercase tracking-tighter text-gray-800 italic">Classifica Generale</h2>
        </template>

        <div class="py-12 max-w-4xl mx-auto px-4">
            <div class="bg-white shadow-2xl rounded-[2rem] overflow-hidden border border-gray-100">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-900 text-white text-[10px] font-black uppercase tracking-[0.2em]">
                            <th class="p-6">Posizione</th>
                            <th class="p-6">Presidente</th>
                            <th class="p-6 text-right">Punti Totali</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(pres, index) in ranking" :key="index" 
                            class="border-b last:border-0 transition-all duration-300"
                            :class="isMe(pres.name) ? 'bg-indigo-600 text-white shadow-lg z-10 relative' : 'hover:bg-gray-50 text-gray-800'">
                            
                            <!-- POSIZIONE -->
                            <td class="p-6 w-24">
                                <div class="w-10 h-10 rounded-2xl flex items-center justify-center font-black text-lg" 
                                      :class="isMe(pres.name) ? 'bg-white text-indigo-600' : (index < 3 ? 'bg-yellow-400 text-yellow-900' : 'bg-gray-100 text-gray-400')">
                                    {{ index + 1 }}
                                </div>
                            </td>

                            <!-- NOME PRESIDENTE -->
                            <td class="p-6">
                                <span class="font-black uppercase text-sm tracking-tight" :class="isMe(pres.name) ? 'text-white' : 'text-gray-900'">
                                    {{ pres.name }}
                                    <span v-if="isMe(pres.name)" class="ml-2 text-[8px] bg-white/20 px-2 py-0.5 rounded-full border border-white/30">IL TUO CLUB</span>
                                </span>
                            </td>

                            <!-- PUNTI -->
                            <td class="p-6 text-right">
                                <span class="font-mono font-black text-3xl" :class="isMe(pres.name) ? 'text-white' : (pres.points > 0 ? 'text-blue-600' : 'text-gray-200')">
                                    {{ pres.points }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- DATA ULTIMO AGGIORNAMENTO -->
            <div class="mt-8 flex flex-col items-center">
                <div class="h-1 w-20 bg-gray-200 rounded-full mb-4"></div>
                <p class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em]">
                    Dati aggiornati al: <span class="text-gray-600">{{ lastUpdate }}</span>
                </p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>