<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    leagues: Object,
    myData: Object,
    myPlayers: Array,
    currentLineup: Object,
    allParticipants: Array,
    isMarketOpen: Boolean,
    stats: Object,
    classifica: Array 
});

// Funzione formattazione Euro
const formatEuro = (val) => {
    return new Intl.NumberFormat('it-IT', { style: 'currency', currency: 'EUR', maximumFractionDigits: 0 }).format(val);
};

// Calcolo valore Euro Top Player uguale a La Mia Rosa (0.26 x Quotazione)
const topPlayerEuroValue = computed(() => {
    if (!props.myPlayers || props.myPlayers.length === 0) return '0,00 €';
    const sorted = [...props.myPlayers].sort((a, b) => (b.purchase_price || 0) - (a.purchase_price || 0));
    const top = sorted[0];
    if (!top || !top.player) return '0,00 €';
    const q = Number(top.player.quotation ?? top.player.initial_value ?? 0);
    return (0.26 * q).toLocaleString('it-IT', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' €';
});
</script>

<template>
    <Head title="Dashboard" />
    <AuthenticatedLayout>
        <div class="py-10 px-4">
            <div class="max-w-7xl mx-auto space-y-8">
                
                <!-- 1. INTESTAZIONE: SALUTO E RANKING -->
                <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-6 px-2">
                    <div>
                        <h1 class="text-6xl font-black text-gray-900 tracking-tighter italic">Benvenuto Pres!</h1>
                        <p class="text-xs text-blue-600 font-bold uppercase tracking-[0.4em] mt-2 ml-1">Dashboard Direzionale</p>
                    </div>

                    <div v-if="myData && stats" class="flex gap-4">
                        <div class="bg-indigo-600 text-white px-6 py-3 rounded-3xl shadow-xl border-b-4 border-indigo-900 text-center min-w-[120px]">
                            <p class="text-[10px] font-black uppercase text-indigo-200 tracking-widest">Ranking</p>
                            <p class="text-3xl font-black">{{ stats.generalRank }}°</p>
                        </div>
                        <div class="bg-gray-900 text-white px-6 py-3 rounded-3xl shadow-xl border-b-4 border-yellow-500 text-center min-w-[120px]">
                            <p class="text-[10px] font-black uppercase text-yellow-500 tracking-widest">Ranking Crediti</p>
                            <p class="text-3xl font-black">{{ stats.rank }}°</p>
                        </div>
                    </div>
                </div>

                <!-- CASO A: L'UTENTE HA UNA LEGA -->
                <div v-if="myData" class="space-y-8">
                    
                    <!-- 2. BANNER PATRIMONIO -->
                    <div class="bg-white p-8 shadow-xl rounded-[2.5rem] border-l-[16px] border-indigo-600">
                        <div class="flex flex-col lg:flex-row justify-between items-center gap-8">
                            <div class="flex-1 text-center lg:text-left">
                                <h3 class="text-4xl font-black uppercase text-gray-900 tracking-tight mb-2">{{ myData.team_name }}</h3>
                                <p class="text-gray-400 font-bold text-sm uppercase italic">Lega: {{ leagues[0]?.name }}</p>
                                
                                <div class="mt-4 flex flex-wrap justify-center lg:justify-start gap-3">
                                    <div class="px-4 py-1.5 rounded-full text-[10px] font-black uppercase border bg-green-50 text-green-600 border-green-200">
                                        Lega Attiva
                                    </div>
                                    <div class="px-4 py-1.5 bg-yellow-50 border border-yellow-200 rounded-full text-[10px] font-mono font-bold text-yellow-700 uppercase">
                                        Codice Invito: {{ leagues[0]?.invite_code }}
                                    </div>
                                </div>
                            </div>

                            <div class="flex gap-4 font-mono font-black">
                                <div class="bg-gray-100 p-6 rounded-3xl text-center w-32 border border-gray-200">
                                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Crediti</p>
                                    <p class="text-3xl text-gray-900">{{ myData.remaining_budget }}</p>
                                </div>
                                <div class="bg-indigo-600 p-6 rounded-3xl text-center w-32 shadow-lg shadow-indigo-200 text-white border-b-4 border-indigo-800">
                                    <p class="text-[10px] font-black text-indigo-200 uppercase tracking-widest">Anni</p>
                                    <p class="text-3xl">{{ myData.years_budget }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 3. GRIGLIA A DUE COLONNE -->
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
                        
                        <!-- COLONNA SINISTRA (Grande) -->
                        <div class="lg:col-span-2 space-y-6">
                            
                            <!-- PRIMA RIGA: VALORE E PROBABILITÀ -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="bg-indigo-950 p-8 rounded-[2rem] shadow-xl border-b-8 border-indigo-800 text-center relative overflow-hidden">
                                    <h4 class="text-[10px] font-black text-indigo-300 uppercase tracking-widest mb-2">Valore Societario</h4>
                                    <p class="text-5xl font-black text-white font-mono italic">
                                        {{ formatEuro(stats?.valore_societario) }}<span class="text-xl ml-1 text-indigo-400">€</span>
                                    </p>
                                    <p class="text-[9px] text-indigo-400 mt-2 uppercase font-bold tracking-tighter">Stima prezzo di vendita</p>
                                </div>

                                <div class="bg-white p-8 rounded-[2rem] shadow-lg border border-gray-100 flex flex-col justify-center text-center">
                                    <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Probabilità Titolo</h4>
                                    <p class="text-5xl font-black text-gray-900">{{ stats?.probabilita_vittoria }}%</p>
                                    <div class="w-24 h-1.5 bg-gray-100 rounded-full mx-auto mt-3 overflow-hidden">
                                        <div class="bg-green-500 h-full transition-all duration-1000" :style="{ width: stats?.probabilita_vittoria + '%' }"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- SECONDA RIGA: TOP PLAYER -->
                            <div>
                                <div class="bg-white p-8 rounded-[2rem] shadow-lg border border-gray-100 text-center">
                                    <h4 class="text-[10px] font-black uppercase text-blue-400 mb-2 tracking-widest">💎 Top Player in Rosa</h4>
                                    <p class="text-2xl font-black text-gray-900 uppercase tracking-tighter">{{ stats?.topPlayer || 'Nessuno' }}</p>
                                    <div class="mt-2 flex items-center justify-center gap-3">
                                        <span class="text-3xl font-mono font-black text-green-500">
                                            {{ stats?.topPrice || 0 }} <span class="text-xs uppercase">cr</span>
                                        </span>
                                        <span class="text-gray-300 font-bold">|</span>
                                        <span class="text-xl font-mono font-black text-emerald-600 bg-emerald-50 px-3 py-1 rounded-xl border border-emerald-200">
                                            {{ stats?.topEuro || '0,00 €' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- COLONNA DESTRA (CLASSIFICA) -->
                        <div class="lg:col-span-1">
                            <div v-if="classifica && classifica.length > 0" class="bg-white rounded-[2rem] shadow-xl border border-gray-100 overflow-hidden sticky top-24">
                                <div class="bg-blue-600 px-5 py-4 text-center">
                                    <h3 class="font-black text-white uppercase italic text-xs tracking-widest">Classifica Campionato</h3>
                                </div>
                                <table class="w-full text-left">
                                    <thead>
                                        <tr class="bg-gray-50 text-[9px] uppercase font-black text-gray-400 border-b">
                                            <th class="pl-4 py-3">#</th>
                                            <th class="py-3">Squadra</th>
                                            <th class="py-3 text-center">PT</th>
                                            <th class="pr-4 py-3 text-center">TOT</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <tr v-for="(squadra, index) in classifica" :key="squadra.id" 
                                            :class="{'bg-blue-50': squadra.user_id === myData?.user_id}"
                                            class="hover:bg-gray-50 transition">
                                            <td class="pl-4 py-3 text-[10px] font-black text-gray-300 italic">#{{ index + 1 }}</td>
                                            <td class="py-3">
                                                <div class="font-bold text-gray-700 text-[11px] truncate max-w-[110px] uppercase tracking-tighter">{{ squadra.team_name }}</div>
                                            </td>
                                            <td class="py-3 text-center font-black text-gray-900 text-sm">{{ squadra.league_points }}</td>
                                            <td class="pr-4 py-3 text-center font-black text-blue-600 text-[11px]">{{ squadra.total_points }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CASO B: UTENTE SENZA LEGA (Tasti ripristinati) -->
                <div v-else class="bg-white p-20 text-center shadow-2xl rounded-[3rem] border-2 border-dashed border-indigo-200">
                    <span class="text-8xl mb-8 block">🏟️</span>
                    <h3 class="text-4xl font-black text-gray-900 uppercase tracking-tighter mb-4">Benvenuto Pres!</h3>
                    <p class="text-gray-500 mb-12 text-xl max-w-lg mx-auto">Non sei ancora iscritto a nessuna lega. Entra nel vivo del calcio che conta.</p>
                    <div class="flex flex-col md:flex-row gap-6 justify-center">
                        <Link :href="route('leagues.create')" class="bg-green-600 text-white px-10 py-5 rounded-[1.5rem] font-black uppercase tracking-widest hover:bg-green-700 shadow-xl transition active:scale-95">➕ Crea Lega</Link>
                        <Link :href="route('leagues.join')" class="bg-orange-500 text-white px-10 py-5 rounded-[1.5rem] font-black uppercase tracking-widest hover:bg-orange-600 shadow-xl transition active:scale-95">🤝 Unisciti</Link>
                    </div>
                </div>

                <div class="text-[10px] text-gray-400 p-4">
                 DEBUG ORARIO: {{ new Date().toLocaleString() }}
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>