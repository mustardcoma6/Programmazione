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
    isMarketOpen: Boolean, 
    stats: Object
});

// Limiti Ruoli Dynasty
const limits = { P: 3, D: 8, C: 8, A: 6 };

// Calcolo ruoli mancanti (Logica robusta)
const missingPlayers = computed(() => {
    const counts = { P: 0, D: 0, C: 0, A: 0 };
    if (props.myPlayers) {
        props.myPlayers.forEach(p => { 
            if (p.player && p.player.role) {
                counts[p.player.role]++;
            }
        });
    }
    return {
        P: Math.max(0, limits.P - counts.P),
        D: Math.max(0, limits.D - counts.D),
        C: Math.max(0, limits.C - counts.C),
        A: Math.max(0, limits.A - counts.A)
    };
});

const totalMissing = computed(() => Object.values(missingPlayers.value).reduce((a, b) => a + b, 0));

// Consiglio del Pres
const strategyAdvice = computed(() => {
    if (!props.myData || totalMissing.value === 0) return "Rosa al completo. Pensa solo alla formazione!";
    const budget = props.myData.remaining_budget;
    const avg = totalMissing.value > 0 ? Math.floor(budget / totalMissing.value) : 0;
    if (missingPlayers.value.A > 0) return `Ti mancano ${missingPlayers.value.A} punte. Riserva almeno ${avg * 2} cr per ogni attaccante!`;
    return `Hai circa ${avg} cr per ogni slot libero. Gestiscili con intelligenza.`;
});

// Stile Slot Liberi
const getSlotStyle = (role) => {
    switch(role) {
        case 'P': return 'bg-yellow-50 border-yellow-400 text-yellow-700';
        case 'D': return 'bg-green-50 border-green-800 text-green-900';
        case 'C': return 'bg-blue-50 border-blue-600 text-blue-800';
        case 'A': return 'bg-red-50 border-red-600 text-red-800';
        default: return 'bg-gray-50 border-gray-200 text-gray-500';
    }
};

// Colori Ruoli in Campo
const getRoleClass = (role) => {
    if (role === 'P') return 'role-P';
    if (role === 'D') return 'role-D';
    if (role === 'C') return 'role-C';
    if (role === 'A') return 'role-A';
    return '';
};
</script>

<template>
    <Head title="Dashboard" />
    <AuthenticatedLayout>
        <div class="py-10 px-4">
            <div class="max-w-7xl mx-auto space-y-8">
                
                <!-- INTESTAZIONE: SALUTO E RANKING -->
                <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-6 px-2">
                    <div>
                        <h1 class="text-6xl font-black text-gray-900 tracking-tighter italic">Benvenuto Pres!</h1>
                        <p class="text-xs text-blue-600 font-bold uppercase tracking-[0.4em] mt-2 ml-1">Dashboard Direzionale</p>
                    </div>

                    <div v-if="stats" class="flex gap-4">
                        <div class="bg-indigo-600 text-white px-6 py-3 rounded-3xl shadow-xl border-b-4 border-indigo-900 text-center">
                            <p class="text-[10px] font-black uppercase text-indigo-200 tracking-widest">Ranking Generale</p>
                            <p class="text-3xl font-black">{{ stats.generalRank }}°</p>
                        </div>
                        <div class="bg-gray-900 text-white px-6 py-3 rounded-3xl shadow-xl border-b-4 border-yellow-500 text-center">
                            <p class="text-[10px] font-black uppercase text-yellow-500 tracking-widest">Ranking Crediti</p>
                            <p class="text-3xl font-black">{{ stats.rank }}°</p>
                        </div>
                    </div>
                </div>

                <!-- SEZIONE LEGA -->
                <div v-if="leagues && leagues.length > 0 && myData" class="space-y-8">
                    
                    <!-- BANNER PATRIMONIO -->
                    <div class="bg-white p-8 shadow-xl rounded-[2.5rem] border-l-[16px] border-indigo-600">
                        <div class="flex flex-col lg:flex-row justify-between items-center gap-8">
                            <div class="flex-1 text-center lg:text-left">
                                <h3 class="text-4xl font-black uppercase text-gray-900 tracking-tight mb-2">{{ myData.team_name }}</h3>
                                <p class="text-gray-400 font-bold text-sm uppercase">Lega: {{ leagues[0].name }}</p>
                                <div class="mt-4 flex flex-wrap justify-center lg:justify-start gap-3">
                                    <div class="px-4 py-1.5 rounded-full text-xs font-black uppercase border" :class="isMarketOpen ? 'bg-green-50 text-green-600 border-green-200' : 'bg-red-50 text-red-600 border-red-200'">
                                        Mercato {{ isMarketOpen ? 'Aperto' : 'Chiuso' }}
                                    </div>
                                    <div class="px-4 py-1.5 bg-yellow-50 border border-yellow-200 rounded-full text-[10px] font-mono font-bold text-yellow-700 uppercase">
                                        Codice: {{ leagues[0].invite_code }}
                                    </div>
                                </div>
                            </div>

                            <div class="flex gap-4">
                                <div class="bg-gray-100 p-6 rounded-3xl text-center w-40 border border-gray-200">
                                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Crediti</p>
                                    <p class="text-4xl font-black text-gray-900 font-mono">{{ myData.remaining_budget }}</p>
                                </div>
                                <div class="bg-indigo-600 p-6 rounded-3xl text-center w-40 shadow-lg shadow-indigo-200">
                                    <p class="text-[10px] font-black text-indigo-200 uppercase tracking-widest">Budget Anni</p>
                                    <p class="text-4xl font-black text-white font-mono">{{ myData.years_budget }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ANALISI E CONSIGLI -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-white p-8 rounded-[2rem] shadow-lg border border-gray-100 flex flex-col justify-center text-center">
                            <h4 class="text-xs font-black uppercase text-gray-400 mb-2 tracking-widest">💎 Top Player in Rosa</h4>
                            <p class="text-2xl font-black text-gray-900 uppercase tracking-tighter">{{ stats?.topPlayer }}</p>
                            <p class="text-4xl font-mono font-black text-green-500 mt-2">{{ stats?.topPrice }} cr</p>
                        </div>
                        <div class="bg-indigo-900 p-8 rounded-[2rem] shadow-xl text-white border-b-8 border-indigo-500 flex flex-col justify-center">
                            <h4 class="text-xs font-black uppercase text-indigo-400 mb-3 tracking-widest">💡 Consiglio del Pres</h4>
                            <p class="text-base font-medium italic leading-relaxed text-indigo-50">"{{ strategyAdvice }}"</p>
                        </div>
                    </div>

                    <!-- SLOT LIBERI (RAPPORTO DS) -->
                    <div class="bg-white p-8 shadow-xl rounded-[2.5rem] border border-gray-100">
                        <div class="flex items-center gap-4 mb-8">
                            <span class="text-4xl">📝</span>
                            <div>
                                <h4 class="text-xs font-black uppercase text-red-500 tracking-tighter">Nota del Direttore Sportivo</h4>
                                <p class="text-lg font-black text-gray-800 uppercase tracking-tight">Rosa attualmente incompleta</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                            <div v-for="(count, role) in missingPlayers" :key="role" 
                                 class="p-6 rounded-[2rem] border-2 transition-all shadow-sm flex flex-col items-center"
                                 :class="count === 0 ? 'bg-gray-50 border-gray-100 opacity-40' : getSlotStyle(role)">
                                <p class="text-5xl font-black mb-2">{{ count }}</p>
                                <p class="text-xs font-black uppercase tracking-[0.2em] mb-1">Slot {{ role }}</p>
                                <p class="text-[9px] font-bold uppercase opacity-60">{{ count === 0 ? 'Reparto Chiuso' : 'Liberi' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- CAMPO DA GIOCO -->
                    <div v-if="currentLineup" class="bg-green-700 p-8 shadow-2xl rounded-[3rem] border-[8px] border-green-800 text-white relative overflow-hidden">
                        <div class="absolute inset-0 opacity-10 pointer-events-none">
                            <div class="w-full h-full border-2 border-white rounded-full scale-150 -translate-y-1/2"></div>
                        </div>
                        <div class="flex justify-between items-center mb-10 relative z-10">
                            <h3 class="font-black uppercase text-2xl tracking-tighter">L'11 Titolare <span class="text-green-300 ml-4 font-mono">{{ currentLineup.module }}</span></h3>
                            <a :href="route('lineup.index')" class="bg-green-900/50 hover:bg-green-900 px-6 py-2 rounded-2xl text-[10px] font-black tracking-widest transition border border-white/20 uppercase shadow-lg">Modifica Campo</a>
                        </div>
                        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-6 relative z-10">
                            <div v-for="detail in currentLineup.details" :key="detail.id" class="p-4 rounded-2xl bg-white/10 border border-white/10 backdrop-blur-lg text-center shadow-sm">
                                <p class="text-[10px] font-black uppercase mb-1" :class="getRoleClass(detail.player?.role)">{{ detail.player?.role }}</p>
                                <p class="text-sm font-black truncate uppercase tracking-tighter">{{ detail.player?.name }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CASO NUOVO UTENTE -->
                <div v-else class="bg-white p-20 text-center shadow-2xl rounded-[3rem] border-2 border-dashed border-indigo-200">
                    <span class="text-8xl mb-8 block">🏟️</span>
                    <h3 class="text-4xl font-black text-gray-900 uppercase tracking-tighter mb-4">Benvenuto Pres!</h3>
                    <p class="text-gray-500 mb-12 text-xl max-w-lg mx-auto">Non sei ancora iscritto a nessuna lega. Entra nel vivo del calcio che conta.</p>
                    <div class="flex flex-col md:flex-row gap-6 justify-center">
                        <a :href="route('leagues.create')" class="bg-green-600 text-white px-10 py-5 rounded-[1.5rem] font-black uppercase tracking-widest hover:bg-green-700 shadow-xl transition-transform active:scale-95">➕ Crea Lega</a>
                        <a :href="route('leagues.join')" class="bg-orange-500 text-white px-10 py-5 rounded-[1.5rem] font-black uppercase tracking-widest hover:bg-orange-600 shadow-xl transition-transform active:scale-95">🤝 Unisciti</a>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.role-P { color: #856404 !important; background-color: #fff3cd !important; padding: 2px 8px; border-radius: 6px; font-weight: 900; }
.role-D { color: #155724 !important; background-color: #d4edda !important; padding: 2px 8px; border-radius: 6px; font-weight: 900; }
.role-C { color: #004085 !important; background-color: #cce5ff !important; padding: 2px 8px; border-radius: 6px; font-weight: 900; }
.role-A { color: #721c24 !important; background-color: #f8d7da !important; padding: 2px 8px; border-radius: 6px; font-weight: 900; }
</style>