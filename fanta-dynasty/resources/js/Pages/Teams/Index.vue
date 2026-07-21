<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    teams: Array,
    leagueName: String
});
</script>

<template>
    <Head title="Squadre" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Squadre - {{ leagueName }}</h2>
        </template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                <div v-for="team in teams" :key="team.id" class="bg-white shadow rounded-xl overflow-hidden border">
                    <div class="bg-blue-600 p-4 text-white flex justify-between">
                        <h3 class="font-bold uppercase">{{ team.team_name }}</h3>
                        <span class="font-mono">{{ team.remaining_budget }} cr</span>
                    </div>
                    <div class="p-4 grid grid-cols-2 md:grid-cols-4 gap-2">
                        <div v-for="item in team.roster" :key="item.id" class="text-xs p-2 bg-gray-50 border rounded flex justify-between">
                            <span><b>{{ item.player.role }}</b> {{ item.player.name }}</span>
                            <span class="text-gray-400">{{ item.purchase_price }}</span>
                        </div>
                        <div v-if="team.roster.length === 0" class="col-span-full text-center text-gray-400 italic">Rosa vuota</div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>