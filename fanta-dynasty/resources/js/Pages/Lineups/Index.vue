<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';

const props = defineProps({ 
    roster: Array, 
    matchday: Number,
    savedLineup: Object 
});

// --- CONFIGURAZIONE MODULI ---
const modules = {
    '4-4-2': { D: 4, C: 4, A: 2 },
    '4-3-3': { D: 4, C: 3, A: 3 },
    '3-4-3': { D: 3, C: 4, A: 3 },
    '3-5-2': { D: 3, C: 5, A: 2 },
    '5-3-2': { D: 5, C: 3, A: 2 },
};

const selectedModule = ref('4-4-2');

// Stato della formazione (Titolari)
const starters = ref({
    P: [null],
    D: Array(4).fill(null),
    C: Array(4).fill(null),
    A: Array(2).fill(null)
});

// Panchina (Lista fino a 7)
const bench = ref([]);

// --- LOGICA SELEZIONE ---
const activeSlot = ref(null); 

const openSelector = (role, index) => {
    activeSlot.value = { role, index };
};

const selectPlayer = (player) => {
    removePlayerFromEverywhere(player.real_player_id);
    starters.value[activeSlot.value.role][activeSlot.value.index] = player.real_player_id;
    activeSlot.value = null;
};

const removePlayerFromEverywhere = (id) => {
    for (let r in starters.value) {
        starters.value[r] = starters.value[r].map(slotId => slotId === id ? null : slotId);
    }
    bench.value = bench.value.filter(benchId => benchId !== id);
};

const toggleBench = (player) => {
    const id = player.real_player_id;
    if (bench.value.includes(id)) {
        bench.value = bench.value.filter(i => i !== id);
    } else {
        if (bench.value.length < 7) {
            removePlayerFromEverywhere(id);
            bench.value.push(id);
        } else {
            alert("Panchina piena!");
        }
    }
};

// --- HELPERS ---
const getPlayerName = (id) => {
    const p = props.roster.find(item => item.real_player_id === id);
    return p ? p.player.name : 'Scegli';
};

const getAvailableForRole = computed(() => {
    if (!activeSlot.value) return [];
    return props.roster.filter(p => p.player.role === activeSlot.value.role);
});

const isSelected = (id) => {
    let allStarters = [].concat(...Object.values(starters.value));
    return allStarters.includes(id) || bench.value.includes(id);
};

watch(selectedModule, (newMod) => {
    starters.value.D = Array(modules[newMod].D).fill(null);
    starters.value.C = Array(modules[newMod].C).fill(null);
    starters.value.A = Array(modules[newMod].A).fill(null);
});

// --- STILE COLORI ---
const getRoleClass = (role) => {
    if (role === 'P') return 'bg-yellow-400 border-yellow-600 text-yellow-950';
    if (role === 'D') return 'bg-green-800 border-green-900 text-white';
    if (role === 'C') return 'bg-blue-600 border-blue-800 text-white';
    if (role === 'A') return 'role-A-bg text-white'; // Rosso
};

const form = useForm({ matchday: props.matchday, module: selectedModule, starters: starters, bench: bench });
const submit = () => {
    const allStartersCount = Object.values(starters.value).flat().filter(id => id !== null).length;
    if (allStartersCount !== 11) return alert("Devi schierare 11 titolari!");
    form.post(route('lineup.store'), { preserveScroll: true });
};
</script>

<template>
    <Head title="Campo Formazione" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-black text-xl uppercase italic">Area Tecnica</h2>
        </template>

        <div class="py-6 max-w-5xl mx-auto px-4 space-y-6">
            
            <!-- SELETTORE MODULO -->
            <div class="bg-white p-4 shadow rounded-2xl flex items-center justify-between overflow-x-auto gap-4">
                <span class="font-black text-[10px] uppercase text-gray-400">Modulo:</span>
                <div class="flex gap-2">
                    <button v-for="(v, m) in modules" :key="m" @click="selectedModule = m" 
                            class="px-4 py-2 rounded-xl text-xs font-black transition border-2"
                            :class="selectedModule === m ? 'bg-indigo-600 border-indigo-700 text-white shadow-lg' : 'bg-gray-50 border-gray-100 text-gray-400'">
                        {{ m }}
                    </button>
                </div>
            </div>

            <!-- CAMPO DA CALCIO -->
            <div class="relative bg-green-600 rounded-[2.5rem] shadow-2xl border-[10px] border-green-800 aspect-[3/4] md:aspect-[4/3] flex flex-col justify-around p-4">
                <div class="absolute inset-0 border-2 border-white/20 m-4 rounded-2xl pointer-events-none"></div>
                <div class="absolute top-1/2 left-0 w-full h-0.5 bg-white/20"></div>
                <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-32 h-32 border-2 border-white/20 rounded-full"></div>

                <!-- ATTACCO -->
                <div class="flex justify-around items-center z-10">
                    <div v-for="(slot, i) in starters.A" :key="i" @click="openSelector('A', i)" 
                         class="slot-circle" :class="slot ? getRoleClass('A') : 'bg-white/10 border-white/30 backdrop-blur-sm'">
                        <span class="text-[8px] font-black opacity-50">A</span>
                        <span class="text-[10px] font-black uppercase text-center leading-none px-1">{{ getPlayerName(slot) }}</span>
                    </div>
                </div>

                <!-- CENTROCAMPO -->
                <div class="flex justify-around items-center z-10">
                    <div v-for="(slot, i) in starters.C" :key="i" @click="openSelector('C', i)" 
                         class="slot-circle" :class="slot ? getRoleClass('C') : 'bg-white/10 border-white/30 backdrop-blur-sm'">
                        <span class="text-[8px] font-black opacity-50">C</span>
                        <span class="text-[10px] font-black uppercase text-center leading-none px-1">{{ getPlayerName(slot) }}</span>
                    </div>
                </div>

                <!-- DIFESA -->
                <div class="flex justify-around items-center z-10">
                    <div v-for="(slot, i) in starters.D" :key="i" @click="openSelector('D', i)" 
                         class="slot-circle" :class="slot ? getRoleClass('D') : 'bg-white/10 border-white/30 backdrop-blur-sm'">
                        <span class="text-[8px] font-black opacity-50">D</span>
                        <span class="text-[10px] font-black uppercase text-center leading-none px-1">{{ getPlayerName(slot) }}</span>
                    </div>
                </div>

                <!-- PORTIERE -->
                <div class="flex justify-center items-center z-10">
                    <div @click="openSelector('P', 0)" 
                         class="slot-circle" :class="starters.P[0] ? getRoleClass('P') : 'bg-white/10 border-white/30 backdrop-blur-sm'">
                        <span class="text-[8px] font-black opacity-50">P</span>
                        <span class="text-[10px] font-black uppercase text-center leading-none px-1">{{ getPlayerName(starters.P[0]) }}</span>
                    </div>
                </div>
            </div>

            <!-- PANCHINA -->
            <div class="bg-white p-6 shadow-xl rounded-3xl border-t-4 border-orange-400">
                <h3 class="font-black text-xs uppercase text-orange-400 mb-4">Panchina ({{ bench.length }}/7)</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                    <div v-for="id in bench" :key="id" @click="removePlayerFromEverywhere(id)" class="p-2 bg-orange-50 border border-orange-200 rounded-xl text-center cursor-pointer hover:bg-red-50 transition">
                        <p class="text-[10px] font-black text-orange-600 uppercase">{{ getPlayerName(id) }}</p>
                    </div>
                    <div v-if="bench.length === 0" class="col-span-full py-2 text-center text-gray-300 text-xs italic">VUOTA</div>
                </div>
            </div>

            <!-- GESTIONE ROSA -->
            <div class="bg-white p-6 shadow rounded-3xl">
                <h3 class="font-black text-xs uppercase text-gray-400 mb-4">Scegli dalla Rosa</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <div v-for="item in roster" :key="item.id" @click="toggleBench(item)"
                         class="p-3 border-2 rounded-2xl cursor-pointer transition flex justify-between items-center"
                         :class="isSelected(item.real_player_id) ? 'bg-gray-100 border-gray-300 opacity-50' : 'bg-gray-50 border-gray-100 hover:border-blue-400'">
                        <span class="text-xs font-black uppercase">{{ item.player.name }}</span>
                        <span v-if="isSelected(item.real_player_id)" class="text-[8px] bg-blue-600 text-white px-2 py-0.5 rounded-full font-black uppercase">Occupato</span>
                        <span v-else class="text-[8px] font-bold text-gray-300 uppercase">Metti in Panchina</span>
                    </div>
                </div>
            </div>

            <button @click="submit" class="w-full bg-green-600 text-white py-5 rounded-3xl font-black uppercase tracking-widest shadow-2xl hover:bg-green-700 transition transform active:scale-95">Invia Formazione</button>

            <!-- MODALE SELETTORE -->
            <div v-if="activeSlot" class="fixed inset-0 z-[100] bg-black/60 backdrop-blur-sm flex items-end md:items-center justify-center p-4">
                <div class="bg-white w-full max-w-md rounded-[2.5rem] overflow-hidden shadow-2xl">
                    <div class="p-5 bg-gray-900 text-white flex justify-between items-center">
                        <h4 class="font-black uppercase text-sm tracking-widest">Ruolo: {{ activeSlot.role }}</h4>
                        <button @click="activeSlot = null" class="text-2xl">✕</button>
                    </div>
                    <div class="max-h-[50vh] overflow-y-auto p-4 space-y-2">
                        <div v-for="p in getAvailableForRole" :key="p.id" @click="selectPlayer(p)"
                             class="p-4 rounded-2xl border-2 border-gray-100 flex justify-between items-center hover:border-indigo-500 cursor-pointer transition">
                            <span class="font-black uppercase text-sm">{{ p.player.name }}</span>
                            <span class="text-[8px] font-black uppercase px-2 py-1 rounded bg-gray-100">{{ p.player.real_team }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.slot-circle {
    @apply w-14 h-14 md:w-20 md:h-20 rounded-full border-4 flex flex-col items-center justify-center cursor-pointer transition shadow-2xl transform active:scale-90 overflow-hidden;
}
.role-A-bg { background-color: #dc2626; border-color: #991b1b; }
</style>