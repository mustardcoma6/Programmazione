<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage, useForm } from '@inertiajs/vue3';

defineProps({ league: Object, participants: Array });

const user = usePage().props.auth.user;
const isAdminView = (league) => league.admin_id === user.id;

// Logica per espellere
const kickParticipant = (p) => {
    if (confirm(`⚠️ ATTENZIONE PRES!\n\nVuoi espellere la squadra "${p.team_name}"?\nTutti i suoi calciatori verranno svincolati e la partecipazione annullata. Azione irreversibile.`)) {
        useForm({}).delete(route('admin.participant.kick', p.id), { preserveScroll: true });
    }
};
</script>

<template>
    <Head title="Società" />
    <AuthenticatedLayout>
        <template #header><h2 class="font-black text-xl uppercase tracking-tighter">Anagrafe Societaria</h2></template>
        
        <div class="py-12 max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded-2xl overflow-hidden border border-gray-200">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-gray-100 text-[10px] font-black uppercase text-gray-500 border-b">
                            <th class="p-4">Società</th>
                            <th class="p-4">Presidente / Contatto</th>
                            <th class="p-4 text-center">Crediti</th>
                            <th class="p-4 text-center">Anni</th>
                            <th v-if="isAdminView(league)" class="p-4 text-right">Amministrazione</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="p in participants" :key="p.id" class="border-b last:border-0 hover:bg-gray-50 transition">
                            <td class="p-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-indigo-600 rounded-lg flex items-center justify-center text-white font-black">
                                        {{ p.team_name.substring(0, 2).toUpperCase() }}
                                    </div>
                                    <span class="font-black uppercase text-sm text-gray-800">{{ p.team_name }}</span>
                                </div>
                            </td>
                            <td class="p-4">
                                <p class="text-sm font-bold text-gray-700 uppercase">{{ p.user.name }}</p>
                                <p v-if="isAdminView(league)" class="text-[10px] text-blue-500 font-mono lowercase">{{ p.user.email }}</p>
                            </td>
                            <td class="p-4 text-center font-mono font-black text-green-600">{{ p.remaining_budget }}</td>
                            <td class="p-4 text-center font-mono font-black text-blue-600">{{ p.years_budget - p.years_used }}</td>
                            
                            <!-- TASTO ESPULSIONE (Solo per Admin e non su se stesso) -->
                            <td v-if="isAdminView(league)" class="p-4 text-right">
                                <button v-if="p.user_id !== user.id" @click="kickParticipant(p)" class="text-red-500 hover:text-red-700 font-black text-[10px] uppercase border border-red-100 px-3 py-1 rounded-lg hover:bg-red-50 transition">
                                    Espelle
                                </button>
                                <span v-else class="text-[9px] font-black text-gray-300 uppercase italic">Sei tu</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>