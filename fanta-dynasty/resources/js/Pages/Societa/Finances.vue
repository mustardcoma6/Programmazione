<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';

defineProps({
    myData: Object,
    rankingAsset: Array,
    rankingEuro: Array,
    myEuroValue: Number 
});

const user = usePage().props.auth.user;
</script>

<template>
    <Head title="Finanze" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-black text-xl uppercase tracking-tighter text-gray-800 italic">Analisi Patrimoniale</h2>
        </template>

        <div class="py-12 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-10 px-4">
            
            <!-- 1. BANNER VALORE SOCIETÀ STILE BORSA -->
            <div class="bg-gray-900 rounded-[2rem] p-8 shadow-2xl border-b-4 border-green-500 relative overflow-hidden text-center md:text-left">
                <div class="absolute right-0 top-0 w-64 h-full bg-gradient-to-l from-green-500/10 to-transparent pointer-events-none"></div>
                <div class="relative z-10 flex flex-col md:flex-row justify-between items-center gap-6">
                    <div>
                        <p class="text-green-500 text-xs font-black uppercase tracking-[0.3em] mb-2">Valutazione di Mercato Attuale</p>
                        <h3 class="text-white text-4xl font-black uppercase tracking-tighter">{{ myData.team_name }}</h3>
                    </div>
                    <div class="flex items-center gap-4">
                        <span class="text-green-400 animate-pulse text-3xl">▲</span>
                        <p class="text-7xl font-black text-white font-mono leading-none">
                            {{ myEuroValue.toLocaleString('it-IT', { minimumFractionDigits: 2 }) }}
                        </p>
                        <span class="text-5xl font-black text-gray-600">€</span>
                    </div>
                </div>
            </div>

            <!-- 2. CLASSIFICHE DOPPIE -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                
                <!-- RANKING EURO -->
                <div class="bg-white shadow-xl rounded-3xl overflow-hidden border border-gray-200 h-fit">
                    <div class="bg-gray-800 p-4 text-white">
                        <h3 class="font-black uppercase tracking-widest text-xs flex items-center gap-2">
                            <span class="text-lg">💶</span> Ranking Valore in Euro
                        </h3>
                    </div>
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-gray-50 text-[10px] font-black uppercase text-gray-400 border-b">
                                <th class="p-4 w-16 text-center">Pos</th>
                                <th class="p-4">Club</th>
                                <th class="p-4 text-right">Valore €</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(team, index) in rankingEuro" :key="team.id" class="border-b last:border-0" :class="team.user_id === user.id ? 'bg-green-50' : ''">
                                <td class="p-4 text-center font-black text-xs">{{ index + 1 }}°</td>
                                <td class="p-4 text-xs font-black uppercase">{{ team.team_name }}</td>
                                <td class="p-4 text-right font-mono font-black text-blue-600">{{ team.euro_value.toLocaleString('it-IT', { minimumFractionDigits: 2 }) }} €</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- RANKING ASSET (CREDITI) -->
                <div class="bg-white shadow-xl rounded-3xl overflow-hidden border border-gray-200 h-fit">
                    <div class="bg-gray-900 p-4 text-white">
                        <h3 class="font-black uppercase tracking-widest text-xs flex items-center gap-2">
                            <span class="text-lg">📈</span> Ranking Asset Calciatori
                        </h3>
                    </div>
                    <table class="w-full text-left">
                        <thead>
                            <tr class="bg-gray-50 text-[10px] font-black uppercase text-gray-400 border-b">
                                <th class="p-4 w-16 text-center">Pos</th>
                                <th class="p-4">Club</th>
                                <th class="p-4 text-right">Valore cr</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(team, index) in rankingAsset" :key="team.id" class="border-b last:border-0" :class="team.user_id === user.id ? 'bg-blue-50' : ''">
                                <td class="p-4 text-center font-black text-xs">{{ index + 1 }}°</td>
                                <td class="p-4 text-xs font-black uppercase">{{ team.team_name }}</td>
                                <td class="p-4 text-right font-mono font-black text-green-600">{{ team.total_quotation_value }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>