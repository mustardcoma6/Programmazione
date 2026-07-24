<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    leagues: Array, 
    myData: Object, 
    myPlayers: Array, 
    allParticipants: Array, 
    currentLineup: Object
});

const yearsUsed = computed(() => {
    return props.myPlayers ? props.myPlayers.reduce((acc, p) => acc + p.contract_years, 0) : 0;
});

const isMarketActive = () => {
    if (!props.leagues || props.leagues.length === 0 || !props.leagues[0]?.market_start_at || !props.leagues[0]?.market_end_at) return false;
    const now = new Date();
    const start = new Date(props.leagues[0].market_start_at);
    const end = new Date(props.leagues[0].market_end_at);
    return now >= start && now <= end;
};
</script>

<template>
    <Head title="Home" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">La mia Scrivania</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <!-- CASO A: L'UTENTE È ISCRITTO A UNA LEGA -->
                <div v-if="leagues && leagues.length > 0" class="space-y-6">
                    
                    <!-- BOX RIEPILOGO SOCIETÀ -->
                    <div class="bg-white p-6 shadow rounded-xl border-l-8 border-indigo-600">
                        <div class="flex flex-col md:flex-row justify-between items-start gap-6">
                            <div>
                                <h3 class="text-3xl font-black uppercase text-gray-900">{{ myData?.team_name }}</h3>
                                <p class="text-gray-500 font-bold italic">Lega: {{ leagues[0].name }}</p>
                                
                                <!-- *** IL CODICE INVITO È TORNATO QUI *** -->
                                <div class="mt-4 inline-block bg-yellow-100 border border-yellow-300 p-3 rounded-lg shadow-sm">
                                    <span class="text-[10px] text-yellow-800 uppercase font-black block">Codice per invitare amici:</span>
                                    <span class="text-2xl font-mono font-bold tracking-widest text-yellow-900 uppercase">
                                        {{ leagues[0].invite_code }}
                                    </span>
                                </div>

                                <div class="mt-4 flex items-center gap-2">
                                    <span class="w-3 h-3 rounded-full" :class="isMarketActive() ? 'bg-green-500 animate-pulse' : 'bg-red-500'"></span>
                                    <span class="text-xs font-bold uppercase" :class="isMarketActive() ? 'text-green-600' : 'text-red-600'">
                                        Mercato {{ isMarketActive() ? 'Aperto' : 'Chiuso' }}
                                    </span>
                                </div>
                            </div>

                            <!-- DASHBOARD BUDGET -->
                            <div class="flex gap-4">
                                <div class="bg-green-50 p-4 rounded-xl text-center border border-green-200 w-32 shadow-sm">
                                    <p class="text-[10px] font-bold text-green-700 uppercase">Crediti</p>
                                    <p class="text-3xl font-black text-green-600 font-mono">{{ myData?.remaining_budget }}</p>
                                </div>
                                <div class="bg-blue-50 p-4 rounded-xl text-center border border-blue-200 w-32 shadow-sm">
                                    <p class="text-[10px] font-bold text-blue-700 uppercase">Anni Rosa</p>
                                    <p class="text-3xl font-black text-blue-600 font-mono">{{ yearsUsed }}/{{ myData?.years_budget }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- MENU NAVIGAZIONE -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <a :href="route('lineup.index')" class="bg-indigo-600 text-white p-4 rounded-xl shadow-md text-center hover:bg-indigo-700 transition">
                            <p class="text-xl">⚽</p>
                            <p class="text-[10px] font-black uppercase">Campo</p>
                        </a>
                        <a :href="route('roster.index')" class="bg-white text-gray-700 p-4 rounded-xl shadow-md text-center border border-gray-200 hover:bg-gray-50 transition">
                            <p class="text-xl">📋</p>
                            <p class="text-[10px] font-black uppercase">Gestione Rosa</p>
                        </a>
                        <a :href="route('market.auctions')" class="bg-blue-600 text-white p-4 rounded-xl shadow-md text-center hover:bg-blue-700 transition">
                            <p class="text-xl">🛒</p>
                            <p class="text-[10px] font-black uppercase">Mercato</p>
                        </a>
                        <a :href="route('teams.index')" class="bg-white text-gray-700 p-4 rounded-xl shadow-md text-center border border-gray-200 hover:bg-gray-50 transition">
                            <p class="text-xl">🏆</p>
                            <p class="text-[10px] font-black uppercase">Squadre</p>
                        </a>
                    </div>

                    <!-- FORMAZIONE SCHIERATA (IL CAMPO) -->
                    <div v-if="currentLineup" class="bg-green-700 p-8 shadow-2xl rounded-2xl border-4 border-green-800 text-white relative overflow-hidden">
                        <h3 class="font-black uppercase mb-6 flex justify-between items-center relative z-10">
                            <span>Schieramento Domenicale</span>
                            <span class="text-xs bg-green-900/50 px-3 py-1 rounded-full border border-white/20">{{ currentLineup.module }}</span>
                        </h3>

                        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4 relative z-10">
                            <div v-for="detail in currentLineup.details" :key="detail.id" 
                                 class="p-3 rounded-lg bg-white/10 border border-white/10 backdrop-blur-sm text-center">
                                <p class="text-[10px] font-black text-green-300 uppercase">{{ detail.player.role }}</p>
                                <p class="text-sm font-bold truncate uppercase tracking-tighter">{{ detail.player.name }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CASO B: L'UTENTE NON HA ANCORA UNA LEGA (Benvenuto) -->
                <div v-else class="bg-white p-12 text-center shadow-xl rounded-2xl border-2 border-dashed border-blue-200">
                    <div class="max-w-md mx-auto">
                        <span class="text-6xl mb-4 block">🏟️</span>
                        <h3 class="text-2xl font-black text-gray-900 uppercase">Benvenuto su Fanta-Dynasty!</h3>
                        <p class="text-gray-500 mt-2 mb-8">Non sei ancora iscritto a nessuna lega. Crea la tua lega o unisciti a una esistente.</p>
                        
                        <div class="flex flex-col gap-4">
                            <a :href="route('leagues.create')" class="bg-green-600 text-white py-4 rounded-xl font-black uppercase tracking-widest hover:bg-green-700 transition">
                                ➕ Crea una Nuova Lega
                            </a>
                            <a :href="route('leagues.join')" class="bg-orange-500 text-white py-4 rounded-xl font-black uppercase tracking-widest hover:bg-orange-600 transition">
                                🤝 Unisciti con Codice
                            </a>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>