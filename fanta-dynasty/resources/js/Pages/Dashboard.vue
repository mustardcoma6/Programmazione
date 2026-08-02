<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    leagues: Array, 
    myData: Object, 
    myPlayers: Array, 
    allParticipants: Array, 
    currentLineup: Object,
    isMarketOpen: Boolean
});

// Calcolo anni liberi con protezione per valori NULL (Nuovi utenti)
const freeYears = computed(() => {
    if (!props.myData) return 0;
    const yearsUsed = props.myPlayers ? props.myPlayers.reduce((acc, p) => acc + p.contract_years, 0) : 0;
    return props.myData.years_budget - yearsUsed;
});
</script>

<template>
    <Head title="Home" />
    <AuthenticatedLayout>
        <div class="py-10">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
                
                <!-- SALUTO PRESIDENTE (Sempre visibile) -->
                <div class="px-4 sm:px-0">
                    <h1 class="text-4xl font-black text-gray-900 tracking-tighter italic">Benvenuto Pres!</h1>
                    <p class="text-xs text-blue-600 font-bold uppercase tracking-[0.3em] mt-1">Quadro Generale</p>
                </div>

                <!-- CASO A: L'UTENTE HA UNA LEGA -->
                <div v-if="leagues && leagues.length > 0 && myData" class="space-y-8">
                    
                    <!-- BOX BUDGET E INFO -->
                    <div class="bg-white p-8 shadow-xl rounded-3xl border-l-[12px] border-indigo-600">
                        <div class="flex flex-col lg:flex-row justify-between items-center gap-8">
                            <div class="flex-1 text-center lg:text-left">
                                <h3 class="text-3xl font-black uppercase text-gray-900 leading-none mb-1">{{ myData.team_name }}</h3>
                                <p class="text-gray-400 font-bold text-sm uppercase">Lega: {{ leagues[0].name }}</p>
                                
                                <div class="mt-4 flex items-center justify-center lg:justify-start gap-3">
                                    <div class="px-4 py-1.5 rounded-full text-xs font-black uppercase border" 
                                         :class="isMarketOpen ? 'bg-green-50 text-green-600 border-green-200' : 'bg-red-50 text-red-600 border-red-200'">
                                        Mercato {{ isMarketOpen ? 'Aperto' : 'Chiuso' }}
                                    </div>
                                    <div class="p-2 bg-yellow-50 border border-yellow-200 rounded text-xs font-mono font-bold text-yellow-700">
                                        Codice: {{ leagues[0].invite_code }}
                                    </div>
                                </div>
                            </div>

                            <div class="flex gap-4">
                                <div class="bg-gray-900 p-5 rounded-2xl text-center shadow-lg w-36">
                                    <p class="text-[9px] font-black text-green-400 uppercase tracking-widest">Crediti</p>
                                    <p class="text-3xl font-black text-white font-mono">{{ myData.remaining_budget }}</p>
                                </div>
                                <div class="bg-indigo-600 p-5 rounded-2xl text-center shadow-lg w-36">
                                    <p class="text-[9px] font-black text-indigo-200 uppercase tracking-widest">Anni Liberi</p>
                                    <p class="text-3xl font-black text-white font-mono">{{ freeYears }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TASTI NAVIGAZIONE INTUITIVI -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                        <a :href="route('players.index')" class="group bg-white p-6 rounded-3xl shadow-md border-b-4 border-gray-100 hover:border-blue-500 transition text-center">
                            <span class="text-4xl block mb-2">📋</span>
                            <span class="font-black uppercase text-[10px] text-gray-500">Svincolati</span>
                        </a>
                        <a :href="route('roster.index')" class="group bg-white p-6 rounded-3xl shadow-md border-b-4 border-gray-100 hover:border-indigo-500 transition text-center">
                            <span class="text-4xl block mb-2">👔</span>
                            <span class="font-black uppercase text-[10px] text-gray-500">Rosa</span>
                        </a>
                        <a :href="route('market.auctions')" class="group bg-blue-600 p-6 rounded-3xl shadow-xl border-b-4 border-blue-800 hover:bg-blue-700 transition text-center text-white">
                            <span class="text-4xl block mb-2">🛒</span>
                            <span class="font-black uppercase text-[10px]">Mercato</span>
                        </a>
                        <a :href="route('teams.index')" class="group bg-white p-6 rounded-3xl shadow-md border-b-4 border-gray-100 hover:border-orange-500 transition text-center">
                            <span class="text-4xl block mb-2">🏆</span>
                            <span class="font-black uppercase text-[10px] text-gray-500">Squadre</span>
                        </a>
                    </div>
                </div>

                <!-- CASO B: L'UTENTE È NUOVO (MOLTO FACILE DA VEDERE) -->
                <div v-else class="bg-white p-12 text-center shadow-2xl rounded-3xl border-2 border-dashed border-blue-300">
                    <div class="max-w-md mx-auto">
                        <span class="text-7xl mb-6 block animate-bounce">⚽</span>
                        <h3 class="text-3xl font-black text-gray-900 uppercase">Benvenuto Pres!</h3>
                        <p class="text-gray-500 mt-4 mb-10 text-lg font-medium">Non sei ancora iscritto a nessuna lega. Inizia ora il tuo cammino verso la vittoria.</p>
                        <div class="flex flex-col gap-4">
                            <a :href="route('leagues.create')" class="bg-green-600 text-white py-5 rounded-2xl font-black uppercase tracking-widest shadow-lg hover:bg-green-700 transition">➕ Crea una Nuova Lega</a>
                            <a :href="route('leagues.join')" class="bg-orange-500 text-white py-5 rounded-2xl font-black uppercase tracking-widest shadow-lg hover:bg-orange-600 transition">🤝 Unisciti con Codice</a>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>