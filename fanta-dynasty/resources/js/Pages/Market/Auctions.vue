<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref, computed, reactive } from 'vue';

const props = defineProps({
    league: Object, isMarketOpen: Boolean, currentSession: Object,
    myData: Object, myRoster: Array, activeAuctions: Array, availablePlayers: Array,
    frozenCredits: Number
});

const user = usePage().props.auth.user;

// --- LOGICA BUDGET REALE ---
const netBudget = computed(() => {
    const total = props.myData?.remaining_budget || 0;
    const frozen = props.frozenCredits || 0;
    return total - frozen;
});

// --- SMISTAMENTO ASTE ---
const myAuctions = computed(() => (props.activeAuctions || []).filter(auc => auc.user_id === user.id));
const otherAuctions = computed(() => (props.activeAuctions || []).filter(auc => auc.user_id !== user.id));

// --- TIMER E REFRESH AUTOMATICO ---
const timeNow = ref(new Date());
let interval, refreshInterval;
onMounted(() => { 
    interval = setInterval(() => { timeNow.value = new Date(); }, 1000); 
    refreshInterval = setInterval(() => { router.reload({ only: ['activeAuctions', 'myData', 'myRoster', 'availablePlayers', 'frozenCredits'], preserveScroll: true, preserveState: true }); }, 5000); 
});
onUnmounted(() => { clearInterval(interval); clearInterval(refreshInterval); });

const getTimer = (date) => {
    const diff = new Date(date) - timeNow.value;
    if (diff <= 0) return "00:00:00";
    const h = Math.floor(diff / 3600000);
    const m = Math.floor((diff % 3600000) / 60000);
    const s = Math.floor((diff % 60000) / 1000);
    return `${h.toString().padStart(2,'0')}:${m.toString().padStart(2,'0')}:${s.toString().padStart(2,'0')}`;
};

// --- LOGICA RICERCA E FILTRI ---
const searchQuery = ref('');
const roleFilter = ref('');
const teamFilter = ref(''); 

const availableTeams = computed(() => {
    const teams = props.availablePlayers.map(p => p.real_team);
    return [...new Set(teams)].sort();
});

const filteredPlayers = computed(() => {
    return (props.availablePlayers || []).filter(p => {
        const nameMatch = p.name.toLowerCase().includes(searchQuery.value.toLowerCase());
        const roleMatch = roleFilter.value ? p.role === roleFilter.value : true;
        const teamMatch = teamFilter.value ? p.real_team === teamFilter.value : true;
        return nameMatch && roleMatch && teamMatch;
    });
});

// --- AZIONI ---
const inputs = reactive({ prices: {}, autobids: {} });
const bidForm = useForm({ player_id: null, league_id: props.league?.id, price: null, max_autobid: null });

const inviaOfferta = (targetId, currentBid, isAuto = false) => {
    const minBid = (currentBid || 0) + 1;
    bidForm.player_id = targetId;
    if (isAuto) {
        const val = inputs.autobids[targetId];
        if (!val || val < minBid) return alert(`L'autobid deve essere almeno ${minBid} cr!`);
        bidForm.price = null;
        bidForm.max_autobid = val;
    } else {
        const val = inputs.prices[targetId];
        if (!val || val < minBid) return alert(`Devi offrire almeno ${minBid} cr!`);
        bidForm.price = val;
        bidForm.max_autobid = null;
    }
    bidForm.post(route('players.buy'), { 
        preserveScroll: true, 
        onSuccess: () => { inputs.prices[targetId] = null; inputs.autobids[targetId] = null; } 
    });
};

const releaseForm = useForm({ roster_id: null });
const svincola = (item) => { 
    // Calcolo rimborsi per il messaggio
    const baseValue = item.release_clause > 0 ? item.release_clause : item.purchase_price;
    const creditRefund = Math.ceil(baseValue / 2);
    const yearsRefund = Math.floor(item.contract_years / 2);
    const yearsLost = item.contract_years - yearsRefund;

    const message = `SVINCOLO DI: ${item.player.name.toUpperCase()}\n\n` +
                    `Recupererai:\n` +
                    `💰 ${creditRefund} Crediti\n` +
                    `⏳ ${yearsRefund} Anni Contratto\n\n` +
                    `ATTENZIONE: Perderai ${yearsLost} anni dal budget totale.\n` +
                    `Confermi l'azione irreversibile?`;
    
    if (confirm(message)) { 
        releaseForm.roster_id = item.id; 
        releaseForm.post(route('players.release'), { preserveScroll: true }); 
    } 
};

const canCallNewPlayers = computed(() => {
    if (!props.currentSession || !props.isMarketOpen) return false;
    const diffInMinutes = (new Date(props.currentSession.end_at) - timeNow.value) / 60000;
    return diffInMinutes >= 90;
});
</script>

<template>
    <Head title="Calciomercato" />
    <AuthenticatedLayout>
        <!-- TIMER GENERALE -->
        <div class="py-4 bg-gray-800 text-white shadow text-center border-b border-gray-700">
            <div v-if="currentSession && isMarketOpen">
                <p class="text-[10px] uppercase font-bold text-gray-400 tracking-widest">Chiusura Mercato:</p>
                <p class="text-4xl font-mono font-black text-green-400">{{ getTimer(currentSession.end_at) }}</p>
            </div>
            <div v-else><p class="text-xl font-bold text-red-500 uppercase italic">🛑 Mercato Chiuso</p></div>
        </div>

        <div class="py-10 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            
            <!-- BANNER BUDGET -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white p-4 shadow rounded-xl border-l-4 border-gray-300 text-center">
                    <p class="text-[10px] font-black text-gray-400 uppercase">Budget Totale</p>
                    <p class="text-2xl font-mono font-bold text-gray-700">{{ myData?.remaining_budget || 0 }} cr</p>
                </div>
                <div class="bg-white p-4 shadow rounded-xl border-l-4 border-orange-400 text-center">
                    <p class="text-[10px] font-black text-orange-400 uppercase">Impegnati in Asta</p>
                    <p class="text-2xl font-mono font-bold text-orange-600">- {{ frozenCredits || 0 }} cr</p>
                </div>
                <div class="bg-white p-4 shadow rounded-xl border-l-8 border-green-500 text-center">
                    <p class="text-[10px] font-black text-green-600 uppercase">Disponibilità Reale</p>
                    <p class="text-3xl font-mono font-black text-green-600">{{ netBudget }} cr</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                <!-- 1. LA TUA ROSA (TASTO SVINCOLA AGGIORNATO) -->
                <div class="lg:col-span-1 bg-white p-4 shadow rounded-xl border h-fit">
                    <h3 class="font-black uppercase text-xs mb-4 border-b pb-2">La tua Rosa</h3>
                    <div class="space-y-1 max-h-[500px] overflow-y-auto">
                        <div v-for="item in myRoster" :key="item.id" class="p-2 bg-gray-50 border rounded text-[11px] flex justify-between items-center group transition-all">
                            <span><b :class="'role-' + item.player?.role" class="mr-1">{{ item.player?.role }}</b> {{ item.player?.name }}</span>
                            
                            <!-- NUOVO TASTO SVINCOLA -->
                            <button 
                                v-if="isMarketOpen" 
                                @click="svincola(item)" 
                                class="bg-red-50 text-red-500 border border-red-100 px-1.5 py-0.5 rounded text-[8px] font-black uppercase opacity-0 group-hover:opacity-100 hover:bg-red-500 hover:text-white transition-all shadow-sm"
                            >
                                Svincola
                            </button>
                        </div>
                    </div>
                </div>

                <!-- 2. ASTE LIVE -->
                <div class="lg:col-span-2 space-y-8">
                    <div v-if="myAuctions.length > 0" class="space-y-4">
                        <h3 class="font-black text-green-600 uppercase text-xs border-b pb-2">✅ LE MIE ASTE</h3>
                        <div v-for="auc in myAuctions" :key="auc.id" class="bg-green-50 p-6 shadow rounded-xl border-2 border-green-400">
                            <div class="flex justify-between items-start">
                                <div>
                                    <span :class="'role-' + auc.player?.role" class="text-[10px] font-black uppercase bg-white px-2 py-0.5 rounded border border-gray-200">{{ auc.player?.role }}</span>
                                    <h4 class="text-xl font-black uppercase text-gray-800 mt-1">{{ auc.player?.name }}</h4>
                                    <p class="text-[10px] text-red-500 font-bold uppercase mt-1">⏱ Scade tra: {{ getTimer(auc.expires_at) }}</p>
                                </div>
                                <div class="text-right"><p class="text-3xl font-black text-green-600 font-mono">{{ auc.current_bid }} cr</p></div>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <h3 class="font-black text-orange-600 uppercase text-xs border-b pb-2">🔥 ALTRE ASTE</h3>
                        <div v-if="otherAuctions.length === 0 && myAuctions.length === 0" class="text-center py-10 bg-white rounded-xl border-2 border-dashed text-gray-400 text-xs font-bold uppercase">Nessuna asta attiva.</div>
                        <div v-for="auc in otherAuctions" :key="auc.id" class="bg-white p-6 shadow-xl rounded-xl border-2 border-orange-400">
                            <div class="flex justify-between items-start">
                                <div>
                                    <span :class="'role-' + auc.player?.role" class="text-[10px] font-black uppercase bg-white px-2 py-0.5 rounded border border-gray-200">{{ auc.player?.role }}</span>
                                    <h4 class="text-xl font-black uppercase text-gray-800 mt-1">{{ auc.player?.name }}</h4>
                                    <p class="text-xs text-gray-500 italic">Leader: {{ auc.user?.name }}</p>
                                    <p class="text-[10px] text-red-500 font-bold uppercase mt-1">⏱ Scade: {{ getTimer(auc.expires_at) }}</p>
                                </div>
                                <div class="text-right"><p class="text-3xl font-black text-orange-500 font-mono">{{ auc.current_bid }} cr</p></div>
                            </div>
                            <div class="mt-4 grid grid-cols-2 gap-4 border-t pt-4">
                                <div class="flex flex-col gap-1"><span class="text-[9px] font-bold text-gray-400 uppercase italic">Rilancio (Min: {{ auc.current_bid + 1 }})</span><div class="flex gap-1"><input type="number" v-model="inputs.prices[auc.real_player_id]" class="w-full rounded border-gray-300 text-xs" :placeholder="auc.current_bid + 1"><button @click="inviaOfferta(auc.real_player_id, auc.current_bid)" class="bg-blue-600 text-white px-2 py-1 rounded font-bold text-[10px] uppercase">Vai</button></div></div>
                                <div class="flex flex-col gap-1"><span class="text-[9px] font-bold text-orange-500 uppercase italic">Offerta Max</span><div class="flex gap-1"><input type="number" v-model="inputs.autobids[auc.real_player_id]" class="w-full rounded border-orange-200 text-xs" placeholder="Max"><button @click="inviaOfferta(auc.real_player_id, auc.current_bid, true)" class="bg-orange-500 text-white px-2 py-1 rounded font-bold text-[10px] uppercase">Auto</button></div></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. CHIAMA GIOCATORE -->
                <div class="lg:col-span-1 bg-white p-4 shadow rounded-xl border-t-4 h-fit" :class="canCallNewPlayers ? 'border-green-600' : 'border-red-600'">
                    <h3 class="font-black uppercase text-xs mb-4 border-b pb-2 text-gray-600">Chiama Giocatore</h3>
                    <div v-if="isMarketOpen && canCallNewPlayers" class="space-y-4">
                        <div class="space-y-2">
                            <input v-model="searchQuery" type="text" placeholder="Cerca nome..." class="w-full p-2 text-xs border-gray-300 rounded-lg bg-gray-50">
                            <div class="grid grid-cols-2 gap-2">
                                <select v-model="roleFilter" class="w-full p-2 text-[10px] border-gray-300 rounded-lg bg-gray-50 uppercase font-bold">
                                    <option value="">Ruoli</option><option value="P">P</option><option value="D">D</option><option value="C">C</option><option value="A">A</option>
                                </select>
                                <select v-model="teamFilter" class="w-full p-2 text-[10px] border-gray-300 rounded-lg bg-gray-50 font-bold">
                                    <option value="">Squadre</option><option v-for="team in availableTeams" :key="team" :value="team">{{ team }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="space-y-1 max-h-[400px] overflow-y-auto pr-1">
                            <div v-for="p in filteredPlayers" :key="p.id" class="flex justify-between items-center p-2 border-b text-[10px] hover:bg-gray-50 transition group">
                                <span class="font-bold uppercase tracking-tighter"><b :class="'role-' + p.role" class="mr-1">{{ p.role }}</b> {{ p.name }}</span>
                                <div class="flex gap-1">
                                    <input type="number" v-model="inputs.prices[p.id]" class="w-10 p-0.5 text-[10px] border-gray-300 rounded" placeholder="1">
                                    <button @click="inviaOfferta(p.id, 0)" class="bg-green-600 text-white px-1.5 py-1 rounded font-black text-[9px] hover:bg-green-700">VAI</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="text-center py-10 text-gray-400 text-[10px] font-bold uppercase italic">Azione bloccata</div>
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