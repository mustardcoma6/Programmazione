<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref } from 'vue';

const props = defineProps({
    league: Object, currentSession: Object, isMarketTime: Boolean,
    myData: Object, myRoster: Array, availablePlayers: Array, activeAuctions: Array
});

// Timer Mercato Centrale
const timeNow = ref(new Date());
let timerInterval;
onMounted(() => { timerInterval = setInterval(() => { timeNow.value = new Date(); }, 1000); });
onUnmounted(() => clearInterval(timerInterval));

const getCountdown = (targetDate) => {
    const diff = new Date(targetDate) - timeNow.value;
    if (diff <= 0) return "00:00:00";
    const h = Math.floor(diff / 3600000);
    const m = Math.floor((diff % 3600000) / 60000);
    const s = Math.floor((diff % 60000) / 1000);
    return `${h.toString().padStart(2,'0')}:${m.toString().padStart(2,'0')}:${s.toString().padStart(2,'0')}`;
};

// Form per Nuova Sessione
const sessionForm = useForm({ league_id: props.league.id, start_at: '', end_at: '' });
const createSession = () => sessionForm.post(route('market.sessions.store'), { onSuccess: () => sessionForm.reset() });

// Chiusura Forzata
const stopMarket = () => {
    if(confirm("ATTENZIONE: Questo chiuderà il mercato e CANCELLERÀ tutte le aste in corso. Procedere?")) {
        useForm({}).post(route('market.close-all', props.league.id));
    }
};

// Offerte
const bidForm = useForm({ player_id: null, league_id: props.league.id, price: null });
const faiOfferta = (p) => {
    bidForm.player_id = p.id;
    bidForm.price = p.temp_price;
    bidForm.post(route('players.buy'), { preserveScroll: true });
};
</script>

<template>
    <Head title="Mercato Hub" />
    <AuthenticatedLayout>
        <div class="py-6 bg-gray-900 text-white shadow-inner">
            <div class="max-w-7xl mx-auto px-4 text-center">
                <!-- TIMER CENTRALE -->
                <div v-if="currentSession">
                    <p class="text-xs uppercase font-black tracking-widest text-gray-400">
                        {{ isMarketTime ? 'Il mercato chiude tra:' : 'Il mercato apre tra:' }}
                    </p>
                    <p class="text-6xl font-mono font-black text-green-400">
                        {{ getCountdown(isMarketTime ? currentSession.end_at : currentSession.start_at) }}
                    </p>
                    <p class="text-xs text-gray-500 mt-1 uppercase font-bold">
                        {{ isMarketTime ? 'Fine Sessione:' : 'Inizio Sessione:' }} 
                        {{ new Date(isMarketTime ? currentSession.end_at : currentSession.start_at).toLocaleString() }}
                    </p>
                </div>
                <div v-else>
                    <p class="text-xl font-bold text-red-500 uppercase">Nessuna sessione programmata</p>
                </div>
            </div>
        </div>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <!-- CONTROLLI ADMIN -->
                <div v-if="league.admin_id === $page.props.auth.user.id" class="bg-white p-6 shadow rounded-xl border-2 border-dashed border-gray-300">
                    <h3 class="font-black uppercase mb-4 text-sm text-gray-500">Pannello Presidente</h3>
                    <div class="flex flex-wrap gap-4 items-end">
                        <div class="flex-1 min-w-[200px]">
                            <label class="block text-[10px] font-black uppercase">Inizio Nuova Sessione</label>
                            <input type="datetime-local" v-model="sessionForm.start_at" class="w-full border-gray-300 rounded-lg text-sm">
                        </div>
                        <div class="flex-1 min-w-[200px]">
                            <label class="block text-[10px] font-black uppercase">Fine Nuova Sessione</label>
                            <input type="datetime-local" v-model="sessionForm.end_at" class="w-full border-gray-300 rounded-lg text-sm">
                        </div>
                        <button @click="createSession" class="bg-blue-600 text-white px-6 py-2 rounded-lg font-black uppercase text-xs hover:bg-blue-700">Programma</button>
                        <button @click="stopMarket" class="bg-red-600 text-white px-6 py-2 rounded-lg font-black uppercase text-xs hover:bg-red-700">STOP & ANNULLA TUTTO</button>
                    </div>
                </div>

                <!-- GRIGLIA MERCATO -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- ASTE ATTIVE -->
                    <div class="lg:col-span-2 space-y-4">
                        <h3 class="font-black text-orange-500 uppercase">🔥 Aste in Corso</h3>
                        <div v-for="auc in activeAuctions" :key="auc.id" class="bg-white p-6 shadow-xl rounded-2xl border-l-8 border-orange-500 flex justify-between">
                            <div>
                                <h4 class="text-2xl font-black uppercase">{{ auc.player.name }}</h4>
                                <p class="text-xs text-gray-500 italic">Leader: {{ auc.user.name }}</p>
                                <p class="mt-2 text-sm font-bold text-red-600">⏱ Fine asta: {{ getCountdown(auc.expires_at) }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-4xl font-black text-orange-500 font-mono">{{ auc.current_bid }} cr</p>
                                <div v-if="isMarketTime" class="mt-2 flex gap-1">
                                    <input type="number" v-model="auc.temp_bid" class="w-16 p-1 border-gray-300 rounded" :placeholder="auc.current_bid + 1">
                                    <button @click="faiOfferta(auc)" class="bg-orange-500 text-white px-2 py-1 rounded font-bold uppercase text-[10px]">Rilancia</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- LISTA PER CHIAMA -->
                    <div class="bg-white p-4 shadow rounded-xl">
                        <h3 class="font-black uppercase mb-4 border-b">Chiama Giocatore</h3>
                        <div v-if="isMarketTime" class="space-y-1 max-h-[500px] overflow-y-auto">
                            <div v-for="p in availablePlayers" :key="p.id" class="flex justify-between items-center p-2 border-b text-sm">
                                <span class="font-bold uppercase text-xs">{{ p.name }}</span>
                                <div class="flex gap-1">
                                    <input type="number" v-model="p.temp_price" class="w-12 p-1 text-[10px] border-gray-300 rounded" placeholder="1">
                                    <button @click="faiOfferta(p)" class="bg-green-600 text-white px-2 py-1 rounded text-[10px] font-black uppercase">Chiama</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>