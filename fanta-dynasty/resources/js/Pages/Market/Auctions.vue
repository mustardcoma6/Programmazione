<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref, computed, reactive } from 'vue';

const props = defineProps({
    league: Object, isMarketOpen: Boolean, currentSession: Object,
    myData: Object, myRoster: Array, activeAuctions: Array, availablePlayers: Array
});

// Timer
const timeNow = ref(new Date());
let interval;
onMounted(() => { interval = setInterval(() => { timeNow.value = new Date(); }, 1000); });
onUnmounted(() => clearInterval(interval));

const getTimer = (date) => {
    const diff = new Date(date) - timeNow.value;
    if (diff <= 0) return "00:00:00";
    const h = Math.floor(diff / 3600000);
    const m = Math.floor((diff % 3600000) / 60000);
    const s = Math.floor((diff % 60000) / 1000);
    return `${h.toString().padStart(2,'0')}:${m.toString().padStart(2,'0')}:${s.toString().padStart(2,'0')}`;
};

const canCallNewPlayers = computed(() => {
    if (!props.currentSession || !props.isMarketOpen) return false;
    const diffInMinutes = (new Date(props.currentSession.end_at) - timeNow.value) / 60000;
    return diffInMinutes >= 90;
});

// --- SOLUZIONE BUG REATTIVITÀ ---
// Creiamo un contenitore per tutti gli input scritti dagli utenti
const inputs = reactive({
    prices: {}, // Qui salviamo i prezzi manuali
    autobids: {} // Qui salviamo gli autobid
});

const bidForm = useForm({ player_id: null, league_id: props.league.id, price: null, max_autobid: null });

const inviaOfferta = (targetId, isAuto = false) => {
    bidForm.player_id = targetId;
    
    if (isAuto) {
        const val = inputs.autobids[targetId];
        if (!val) return alert("Inserisci offerta massima!");
        bidForm.price = null;
        bidForm.max_autobid = val;
    } else {
        const val = inputs.prices[targetId];
        if (!val) return alert("Inserisci un prezzo!");
        bidForm.price = val;
        bidForm.max_autobid = null;
    }

    bidForm.post(route('players.buy'), { 
        preserveScroll: true, 
        onSuccess: () => {
            inputs.prices[targetId] = null;
            inputs.autobids[targetId] = null;
        } 
    });
};

const releaseForm = useForm({ roster_id: null });
const svincola = (item) => {
    if (confirm(`Svincolare ${item.player.name}?`)) {
        releaseForm.roster_id = item.id;
        releaseForm.post(route('players.release'), { preserveScroll: true });
    }
};
</script>

<template>
    <Head title="Calciomercato" />
    <AuthenticatedLayout>
        <div class="py-6 bg-gray-800 text-white shadow text-center">
            <div v-if="currentSession && isMarketOpen">
                <p class="text-xs uppercase font-bold text-gray-400">Il mercato chiude tra:</p>
                <p class="text-5xl font-mono font-black text-green-400">{{ getTimer(currentSession.end_at) }}</p>
            </div>
            <div v-else><p class="text-xl font-bold text-red-500 uppercase italic">🛑 Mercato Chiuso</p></div>
        </div>

        <div class="py-12 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                
                <!-- ROSA -->
                <div class="lg:col-span-1 bg-white p-4 shadow rounded-xl border-t-4 border-blue-600">
                    <h3 class="font-black uppercase text-sm mb-4 flex justify-between">
                        <span>Rosa</span>
                        <span class="text-green-600 font-mono">{{ myData.remaining_budget }} cr</span>
                    </h3>
                    <div class="space-y-1 max-h-[500px] overflow-y-auto">
                        <div v-for="item in myRoster" :key="item.id" class="p-2 bg-gray-50 border rounded text-[11px] flex justify-between items-center group">
                            <span><b class="text-blue-600 mr-1">{{ item.player.role }}</b> {{ item.player.name }}</span>
                            <button v-if="isMarketOpen" @click="svincola(item)" class="text-red-400 hover:text-red-600 font-bold opacity-0 group-hover:opacity-100 transition">✕</button>
                        </div>
                    </div>
                </div>

                <!-- ASTE ATTIVE -->
                <div class="lg:col-span-2 space-y-4">
                    <h3 class="font-black text-orange-600 uppercase flex items-center gap-2">🔥 Aste in corso</h3>
                    <div v-for="auc in activeAuctions" :key="auc.id" class="bg-white p-6 shadow rounded-xl border-2 border-orange-400">
                        <div class="flex justify-between items-start">
                            <div>
                                <span class="text-[10px] font-black uppercase bg-orange-100 text-orange-700 px-2 rounded">{{ auc.player.role }}</span>
                                <h4 class="text-xl font-black uppercase text-gray-800">{{ auc.player.name }}</h4>
                                <p class="text-xs text-gray-500">Leader: <span class="font-bold text-blue-600">{{ auc.user.name }}</span></p>
                            </div>
                            <div class="text-right">
                                <p class="text-3xl font-black text-orange-500 font-mono">{{ auc.current_bid }} cr</p>
                                <p class="text-[10px] font-bold text-red-500 uppercase mt-1">⏱ {{ getTimer(auc.expires_at) }}</p>
                            </div>
                        </div>

                        <div v-if="isMarketOpen" class="mt-6 grid grid-cols-2 gap-4 border-t pt-4">
                            <div class="flex flex-col gap-1">
                                <span class="text-[9px] font-bold text-gray-400 uppercase">Rilancio Manuale</span>
                                <div class="flex gap-1">
                                    <input type="number" v-model="inputs.prices[auc.real_player_id]" class="w-full rounded border-gray-300 text-xs" :placeholder="auc.current_bid + 1">
                                    <button @click="inviaOfferta(auc.real_player_id)" class="bg-blue-600 text-white px-2 py-1 rounded font-bold text-[10px] uppercase">Vai</button>
                                </div>
                            </div>
                            <div class="flex flex-col gap-1">
                                <span class="text-[9px] font-bold text-orange-500 uppercase">Offerta Massima (Auto)</span>
                                <div class="flex gap-1">
                                    <input type="number" v-model="inputs.autobids[auc.real_player_id]" class="w-full rounded border-orange-200 text-xs" placeholder="Max">
                                    <button @click="inviaOfferta(auc.real_player_id, true)" class="bg-orange-500 text-white px-2 py-1 rounded font-bold text-[10px] uppercase">Attiva</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-if="activeAuctions.length === 0" class="text-center py-10 bg-white rounded-xl border-2 border-dashed text-gray-400 text-xs font-bold uppercase">Nessuna asta attiva.</div>
                </div>

                <!-- CHIAMA GIOCATORE -->
                <div class="lg:col-span-1 bg-white p-4 shadow rounded-xl border-t-4" :class="canCallNewPlayers ? 'border-green-600' : 'border-red-600'">
                    <h3 class="font-black uppercase text-sm mb-4 border-b">Chiama Giocatore</h3>
                    <div v-if="isMarketOpen && !canCallNewPlayers" class="bg-red-50 p-4 rounded text-red-600 text-[10px] font-bold uppercase mb-4 text-center border border-red-100">
                        🛑 Chiamate bloccate.
                    </div>
                    <div v-if="isMarketOpen && canCallNewPlayers" class="space-y-1 max-h-[500px] overflow-y-auto">
                        <div v-for="p in availablePlayers" :key="p.id" class="flex justify-between items-center p-2 border-b text-[10px]">
                            <span class="font-bold uppercase">{{ p.name }}</span>
                            <div class="flex gap-1">
                                <input type="number" v-model="inputs.prices[p.id]" class="w-10 p-0.5 text-[10px] border-gray-300 rounded" placeholder="1">
                                <button @click="inviaOfferta(p.id)" class="bg-green-600 text-white px-1.5 py-1 rounded font-black text-[9px]">CHIAMA</button>
                            </div>
                        </div>
                    </div>
                    <div v-else-if="!isMarketOpen" class="text-center py-10 text-gray-400 text-[10px] font-bold italic">Mercato Chiuso</div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>