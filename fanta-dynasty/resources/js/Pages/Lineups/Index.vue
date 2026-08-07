<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';

const props = defineProps({ 
    roster: Array, 
    matchday: Number 
});

// Moduli disponibili
const modules = {
    '4-4-2': { D: 4, C: 4, A: 2 },
    '4-3-3': { D: 4, C: 3, A: 3 },
    '3-4-3': { D: 3, C: 4, A: 3 },
    '3-5-2': { D: 3, C: 5, A: 2 },
    '5-3-2': { D: 5, C: 3, A: 2 }
};

const selectedModule = ref('4-4-2');

// Inizializzazione campo
const starters = ref({
    P: [null],
    D: Array(4).fill(null),
    C: Array(4).fill(null),
    A: Array(2).fill(null)
});

// Gestione reset al cambio modulo
watch(selectedModule, (newVal) => {
    starters.value.D = Array(modules[newVal].D).fill(null);
    starters.value.C = Array(modules[newVal].C).fill(null);
    starters.value.A = Array(modules[newVal].A).fill(null);
});

const activeSlot = ref(null);

const openSelector = (role, index) => {
    activeSlot.value = { role, index };
};

const selectPlayer = (player) => {
    // Rimuovi se già presente in altri slot
    for (let r in starters.value) {
        starters.value[r] = starters.value[r].map(id => id === player.id ? null : id);
    }
    // Assegna
    starters.value[activeSlot.value.role][activeSlot.value.index] = player.id;
    activeSlot.value = null;
};

const getPlayerName = (id) => {
    if (!id) return 'SCEGLI';
    const p = props.roster.find(item => item.id === id);
    return p ? p.name : 'SCEGLI';
};

const getAvailableForRole = computed(() => {
    if (!activeSlot.value) return [];
    return props.roster.filter(p => p.role === activeSlot.value.role);
});

const getRoleClass = (role) => {
    if (role === 'P') return 'bg-yellow-400 border-yellow-600 text-yellow-950';
    if (role === 'D') return 'bg-green-800 border-green-950 text-white';
    if (role === 'C') return 'bg-blue-600 border-blue-800 text-white';
    if (role === 'A') return 'bg-red-600 border-red-800 text-white';
};

const form = useForm({
    matchday: props.matchday,
    module: selectedModule,
    starters: starters
});

const submit = () => {
    const totalFilled = Object.values(starters.value).flat().filter(id => id !== null).length;
    if (totalFilled < 11) return alert("Completa l'11 titolare!");
    form.post(route('lineup.store'));
};
</script>

<template>
    <Head title="Campo" />
    <AuthenticatedLayout>
        <template #header><h2 class="font-black text-xl uppercase italic">Area Schieramento</h2></template>

        <div class="py-6 max-w-5xl mx-auto px-4 space-y-6">
            <!-- SELETTORE MODULO -->
            <div class="bg-white p-4 shadow rounded-2xl flex items-center justify-between overflow-x-auto gap-4">
                <span class="font-black text-[10px] uppercase text-gray-400">Modulo:</span>
                <div class="flex gap-2">
                    <button v-for="(v, m) in modules" :key="m" @click="selectedModule = m" 
                            class="px-3 py-1.5 rounded-xl text-xs font-black transition"
                            :class="selectedModule === m ? 'bg-indigo-600 text-white shadow-lg' : 'bg-gray-100 text-gray-400'">
                        {{ m }}
                    </button>
                </div>
            </div>

            <!-- CAMPO -->
            <div class="relative bg-green-600 rounded-[2.5rem] shadow-2xl border-[10px] border-green-800 aspect-[3/4] md:aspect-[4/3] flex flex-col justify-around p-4">
                <!-- Linee -->
                <div class="absolute inset-0 border-2 border-white/20 m-4 rounded-2xl pointer-events-none"></div>
                <div class="absolute top-1/2 left-0 w-full h-0.5 bg-white/20"></div>
                
                <!-- Ruoli -->
                <div v-for="role in ['A', 'C', 'D', 'P']" :key="role" class="flex justify-around items-center z-10">
                    <div v-for="(slot, i) in starters[role]" :key="i" @click="openSelector(role, i)" 
                         class="w-14 h-14 md:w-20 md:h-20 rounded-full border-4 flex flex-col items-center justify-center cursor-pointer shadow-xl transition active:scale-90"
                         :class="slot ? getRoleClass(role) : 'bg-white/10 border-white/30 backdrop-blur-sm'">
                        <span class="text-[8px] font-black opacity-40 uppercase">{{ role }}</span>
                        <span class="text-[9px] font-black uppercase text-center leading-tight px-1 truncate w-full">{{ getPlayerName(slot) }}</span>
                    </div>
                </div>
            </div>

            <button @click="submit" class="w-full bg-green-600 text-white py-5 rounded-3xl font-black uppercase tracking-widest shadow-xl hover:bg-green-700">
                Salva Formazione
            </button>

            <!-- SELETTORE MODALE -->
            <div v-if="activeSlot" class="fixed inset-0 z-[100] bg-black/70 backdrop-blur-sm flex items-end md:items-center justify-center p-4">
                <div class="bg-white w-full max-w-md rounded-[2rem] overflow-hidden shadow-2xl">
                    <div class="p-5 bg-gray-900 text-white flex justify-between items-center">
                        <h4 class="font-black uppercase text-sm">Seleziona {{ activeSlot.role }}</h4>
                        <button @click="activeSlot = null" class="text-2xl">✕</button>
                    </div>
                    <div class="max-h-[50vh] overflow-y-auto p-4 space-y-2">
                        <div v-for="p in getAvailableForRole" :key="p.id" @click="selectPlayer(p)"
                             class="p-4 rounded-2xl border-2 border-gray-100 flex justify-between items-center hover:border-indigo-500 cursor-pointer transition">
                            <span class="font-black uppercase text-sm">{{ p.name }}</span>
                            <span v-if="p.is_primavera" class="text-[8px] bg-violet-600 text-white px-2 py-0.5 rounded-full font-bold">VIVAIO</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>