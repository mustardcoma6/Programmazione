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

// Limiti Ruoli (3P, 8D, 8C, 6A)
const limits = { P: 3, D: 8, C: 8, A: 6 };

// Calcolo ruoli mancanti
const missingPlayers = computed(() => {
    const counts = { P: 0, D: 0, C: 0, A: 0 };
    if (props.myPlayers) {
        props.myPlayers.forEach(p => { 
            if (p.player && p.player.role) {
                counts[p.player.role] = (counts[p.player.role] || 0) + 1;
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

// Consiglio del Pres basato sui crediti e ruoli mancanti
const strategyAdvice = computed(() => {
    if (!props.myData || totalMissing.value === 0) return "Rosa al completo. Gestisci i campioni per la prossima giornata!";
    
    const budget = props.myData.remaining_budget;
    const avg = totalMissing.value > 0 ? Math.floor(budget / totalMissing.value) : 0;
    
    if (missingPlayers.value.A > 0) {
        return `Ti mancano ${missingPlayers.value.A} attaccanti. Riserva almeno ${avg * 2} cr per ogni punta, o rischierai di restare senza budget per il reparto offensivo.`;
    }
    
    if (avg < 5) {
        return `Attenzione: hai solo ${avg} cr medi per giocatore. Punta su scommesse a basso costo o svincola qualcuno per fare cassa.`;
    }
    
    return `Hai un'ottima media di ${avg} cr per ogni slot libero. Puoi permetterti un top player a centrocampo!`;
});

// Colori Ruoli (P=Giallo Oro, D=Verde Scuro, C=Blu, A=Rosso)
const getRoleClass = (role) => {
    if (role === 'P') return 'role-P';
    if (role === 'D') return 'role-D';
    if (role === 'C') return 'role-C';
    if (role === 'A') return 'role-A';
    return '';
};
</script>

<template>
    <Head title="Home" />
    <AuthenticatedLayout>
        <div class="py-10 px-4">
            <div class="max-w-7xl mx-auto space-y-8">
                
                <!-- SALUTO PRESIDENTE E RANKING -->
                <div class="px-4 sm:px-0 flex flex-col md:flex-row justify-between items-start md:items-end gap-4">
                    <div>
                        <h1 class="text-5xl font-black text-gray-900 tracking-tighter italic">Benvenuto Pres!</h1>
                        <p class="text-xs text-blue-600 font-bold uppercase tracking-[0.3em] mt-1 ml-1">Dashboard Direzionale</p>
                    </div>
                    <!-- RANKING ECONOMICO -->
                    <div v-if="stats" class="bg-gray-900 text-white px-6 py-3 rounded-2xl shadow-xl border-b-4 border-yellow-500 text-center">
                        <p class="text-[9px] font-black uppercase text-yellow-500 tracking-widest">Ranking Crediti</p>
                        <p class="text-2xl font-black">{{ stats.rank }}° <span class="text-xs text-gray-400">/ {{ stats.totalParticipants }}</span></p>
                    </div>
                </div>

                <!-- CASO A: SE SEI IN UNA LEGA -->
                <div v-if="leagues && leagues.length > 0 && myData" class="space-y-8">
                    
                    <!-- BANNER PATRIMONIO -->
                    <div class="bg-white p-8 shadow-xl rounded-3xl border-l-[12px] border-indigo-600">
                        <div class="flex flex-col lg:flex-row justify-between items-center gap-8">
                            <div class="flex-1 text-center lg:text-left">
                                <h3 class="text-4xl font-black uppercase text-gray-900 leading-none mb-2">{{ myData.team_name }}</h3>
                                <p class="text-gray-400 font-bold text-sm uppercase tracking-widest">Lega: {{ leagues[0].name }}</p>
                                <div class="mt-4 flex items-center justify-center lg:justify-start gap-3">
                                    <div class="px-4 py-1.5 rounded-full text-xs font-black uppercase border" :class="isMarketOpen ? 'bg-green-50 text-green-600 border-green-200' : 'bg-red-50 text-red-600 border-red-200'">
                                        Mercato {{ isMarketOpen ? 'Aperto' : 'Chiuso' }}
                                    </div>
                                    <div class="p-2 bg-yellow-50 border border-yellow-200 rounded text-xs font-mono font-bold text-yellow-700">
                                        Codice: {{ leagues[0].invite_code }}
                                    </div>
                                </div>
                            </div>

                            <!-- RISORSE TOTALI -->
                            <div class="flex gap-4">
                                <div class="bg-gray-100 p-6 rounded-2xl text-center w-36 shadow-sm border border-gray-200">
                                    <p class="text-[10px] font-black text-gray-400 uppercase">Crediti</p>
                                    <p class="text-3xl font-black text-gray-900 font-mono">{{ myData.remaining_budget }}</p>
                                </div>
                                <div class="bg-indigo-600 p-6 rounded-2xl text-center w-36 shadow-sm border border-indigo-700">
                                    <p class="text-[10px] font-black text-white uppercase opacity-70">Budget Anni</p>
                                    <p class="text-3xl font-black text-white font-mono">{{ myData.years_budget }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- PANNELLI ANALISI E CONSIGLI -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- TOP PLAYER IN ROSA -->
                        <div class="bg-white p-6 rounded-3xl shadow-lg border border-gray-100 flex flex-col justify-center text-center">
                            <h4 class="text-xs font-black uppercase text-gray-400 mb-2 tracking-widest">💎 Top Player in Rosa</h4>
                            <p class="text-xl font-black text-gray-900 uppercase tracking-tighter">{{ stats?.topPlayer }}</p>
                            <p class="text-3xl font-mono font-black text-green-500">{{ stats?.topPrice }} cr</p>
                        </div>
                        <!-- CONSIGLIO DEL PRES -->
                        <div class="bg-indigo-900 p-6 rounded-3xl shadow-lg text-white border-b-4 border-indigo-500 flex flex-col justify-center">
                            <h4 class="text-xs font-black uppercase text-indigo-400 mb-2 tracking-widest">💡 Consiglio del Pres</h4>
                            <p class="text-sm font-medium italic leading-relaxed">"{{ strategyAdvice }}"</p>
                        </div>
                    </div>

                    <!-- OBIETTIVI DI MERCATO (CASELLE LIBERE PER RUOLO) -->
                    <div class="bg-white p-8 shadow-xl rounded-3xl border border-gray-100">
                        <h4 class="text-xs font-black uppercase text-gray-400 mb-6 tracking-widest text-center">🧩 Caselle libere per completare la rosa</h4>
                        <div class="grid grid-cols-4 gap-4 text-center">
                            <div v-for="(count, role) in missingPlayers" :key="role" class="p-4 rounded-3xl transition border" :class="count === 0 ? 'bg-green-50 border-green-200 opacity-60' : 'bg-gray-50 border-gray-100'">
                                <p class="text-3xl font-black" :class="count === 0 ? 'text-green-600' : 'text-gray-900'">{{ count }}</p>
                                <p class="text-[10px] font-black uppercase tracking-widest" :class="getRoleClass(role)">{{ role }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- FORMAZIONE SCHIERATA (SE PRESENTE) -->
                    <div v-if="currentLineup" class="bg-green-700 p-8 shadow-2xl rounded-3xl border-[6px] border-green-800 text-white relative overflow-hidden">
                        <div class="flex justify-between items-center mb-8 relative z-10">
                            <h3 class="font-black uppercase text-xl">Schieramento Domenicale <span class="text-green-300 ml-2">{{ currentLineup.module }}</span></h3>
                            <a :href="route('lineup.index')" class="bg-green-900/50 hover:bg-green-900 px-4 py-2 rounded-xl text-xs font-bold transition border border-white/10 uppercase">Modifica Campo</a>
                        </div>
                        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4 relative z-10">
                            <div v-for="detail in currentLineup.details" :key="detail.id" class="p-4 rounded-xl bg-white/10 border border-white/10 backdrop-blur-md text-center">
                                <p class="text-[10px] font-black uppercase mb-1" :class="getRoleClass(detail.player?.role)">{{ detail.player?.role }}</p>
                                <p class="text-sm font-black truncate uppercase tracking-tighter">{{ detail.player?.name }}</p>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- CASO B: NUOVO UTENTE SENZA LEGA -->
                <div v-else class="bg-white p-16 text-center shadow-2xl rounded-3xl border-2 border-dashed border-indigo-200">
                    <h3 class="text-3xl font-black text-gray-900 uppercase">Benvenuto Pres!</h3>
                    <p class="text-gray-500 mt-4 mb-10 text-lg">Inizia la tua carriera. Crea una lega o unisciti a una esistente.</p>
                    <div class="flex flex-col md:flex-row gap-4 justify-center">
                        <a :href="route('leagues.create')" class="bg-green-600 text-white px-8 py-4 rounded-xl font-bold uppercase hover:bg-green-700 transition shadow-lg">➕ Crea Lega</a>
                        <a :href="route('leagues.join')" class="bg-orange-500 text-white px-8 py-4 rounded-xl font-bold uppercase hover:bg-orange-600 transition shadow-lg">🤝 Unisciti</a>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.role-P { color: #FFD700 !important; font-weight: 900; } /* Giallo Oro */
.role-D { color: #006400 !important; font-weight: 900; } /* Verde Scuro */
.role-C { color: #1e40af !important; font-weight: 900; } /* Blu */
.role-A { color: #dc2626 !important; font-weight: 900; } /* Rosso */
</style>