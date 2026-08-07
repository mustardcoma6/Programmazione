<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted } from 'vue';

const props = defineProps({ roster: Array, savedLineup: Object, matchday: Number });

const modules = {
    '4-4-2': { D: 4, C: 4, A: 2 },
    '4-3-3': { D: 4, C: 3, A: 3 },
    '3-4-3': { D: 3, C: 4, A: 3 },
    '3-5-2': { D: 3, C: 5, A: 2 },
};

const selectedModule = ref(props.savedLineup?.module || '4-4-2');
const starters = ref({ P: [null], D: Array(4).fill(null), C: Array(4).fill(null), A: Array(2).fill(null) });
const bench = ref([]);

// --- 1. RICARICA FORMAZIONE SALVATA ---
onMounted(() => {
    if (props.savedLineup) {
        props.savedLineup.details.forEach(detail => {
            if (detail.is_starter && detail.position_key) {
                const [role, index] = detail.position_key.split('_');
                if (starters.value[role]) starters.value[role][index] = detail.real_player_id;
            } else if (!detail.is_starter) {
                bench.value.push(detail.real_player_id);
            }
        });
    }
});

// --- 2. LOGICA INTESA (CHEMISTRY) ---
const getChemistryColor = (role) => {
    // Prendiamo i giocatori in campo
    const playersInField = props.roster.filter(p => Object.values(starters.value).flat().includes(p.id));
    if (playersInField.length < 2) return 'border-white/20';

    // Per semplicità, l'intesa è calcolata rispetto alla media del reparto
    // In un sistema avanzato useremmo coordinate SVG per le linee
    return 'border-green-500'; 
};

const getPlayer = (id) => props.roster.find(p => p.id === id);

const activeSlot = ref(null);
const openSelector = (role, index) => { activeSlot.value = { role, index }; };

const selectPlayer = (player) => {
    // Rimuovi se già presente
    for (let r in starters.value) starters.value[r] = starters.value[r].map(id => id === player.id ? null : id);
    bench.value = bench.value.filter(id => id !== player.id);
    
    starters.value[activeSlot.value.role][activeSlot.value.index] = player.id;
    activeSlot.value = null;
};

const addToBench = (player) => {
    if (bench.value.length < 7 && !Object.values(starters.value).flat().includes(player.id)) {
        if (!bench.value.includes(player.id)) bench.value.push(player.id);
    }
};

const form = useForm({ matchday: props.matchday, module: selectedModule, starters: starters, bench: bench });
</script>

<template>
    <Head title="Formazione FUT" />
    <AuthenticatedLayout>
        <div class="py-6 max-w-6xl mx-auto px-4 space-y-8">
            
            <!-- MODULO -->
            <div class="flex gap-2 overflow-x-auto pb-2">
                <button v-for="(v, m) in modules" :key="m" @click="selectedModule = m" 
                    class="px-4 py-2 rounded-full text-xs font-black transition"
                    :class="selectedModule === m ? 'bg-blue-600 text-white' : 'bg-white text-gray-400'">
                    {{ m }}
                </button>
            </div>

            <!-- CAMPO STILE FIFA -->
            <div class="relative bg-green-700 rounded-[3rem] shadow-2xl border-[12px] border-green-900 aspect-[3/4] md:aspect-[4/3] flex flex-col justify-around p-4 overflow-hidden">
                <div class="absolute inset-0 opacity-30 bg-[url('https://www.transparenttextures.com/patterns/carbon-fibre.png')]"></div>
                
                <!-- Linee Intesa (Semplificate come bordi luminosi) -->
                <div v-for="role in ['A', 'C', 'D', 'P']" :key="role" class="flex justify-around items-center z-10">
                    <div v-for="(slot, i) in starters[role]" :key="i" @click="openSelector(role, i)" 
                         class="w-16 h-20 md:w-24 md:h-28 rounded-xl border-t-4 flex flex-col items-center justify-center cursor-pointer transition shadow-2xl bg-gray-900/80 backdrop-blur-md relative"
                         :class="slot ? 'border-green-500 shadow-green-500/20' : 'border-white/20'">
                        
                        <div v-if="slot" class="text-center">
                            <p class="text-[8px] font-black text-green-400">{{ getPlayer(slot).real_team }}</p>
                            <p class="text-[10px] font-black text-white uppercase">{{ getPlayer(slot).name }}</p>
                            <p class="text-[7px] text-gray-400">{{ getPlayer(slot).nationality }}</p>
                        </div>
                        <span v-else class="text-white/20 text-2xl">+</span>
                    </div>
                </div>
            </div>

            <!-- PANCHINA -->
            <div class="bg-gray-900 p-6 rounded-3xl shadow-xl">
                <h3 class="text-white font-black uppercase text-xs mb-4 tracking-widest text-center">Panchina Riserve</h3>
                <div class="flex gap-4 overflow-x-auto pb-4">
                    <div v-for="id in bench" :key="id" @click="bench = bench.filter(b => b !== id)" 
                         class="min-w-[100px] p-3 bg-gray-800 rounded-2xl border-b-4 border-orange-500 text-center cursor-pointer">
                        <p class="text-white font-black uppercase text-[10px]">{{ getPlayer(id).name }}</p>
                    </div>
                    <div v-if="bench.length === 0" class="text-gray-600 text-xs italic w-full text-center">Clicca i giocatori sotto per la panchina</div>
                </div>
            </div>

            <!-- LISTONE ROSA -->
            <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
                <div v-for="p in roster" :key="p.id" @click="addToBench(p)"
                     class="p-3 rounded-2xl border-2 cursor-pointer transition text-center"
                     :class="Object.values(starters).flat().includes(p.id) || bench.includes(p.id) ? 'bg-gray-200 border-gray-300 opacity-50' : 'bg-white border-gray-100 hover:border-blue-500'">
                    <p class="text-[10px] font-black">{{ p.role }}</p>
                    <p class="text-xs font-bold uppercase">{{ p.name }}</p>
                </div>
            </div>

            <button @click="form.post(route('lineup.store'))" class="w-full bg-blue-600 text-white py-5 rounded-3xl font-black uppercase tracking-widest shadow-2xl">
                Salva Formazione Definitiva
            </button>
        </div>

        <!-- MODALE SELEZIONE -->
        <div v-if="activeSlot" class="fixed inset-0 z-[100] bg-black/80 flex items-center justify-center p-4 backdrop-blur-sm">
            <div class="bg-white w-full max-w-md rounded-3xl overflow-hidden shadow-2xl">
                <div class="p-4 bg-gray-900 text-white flex justify-between">
                    <span class="font-black uppercase">Seleziona {{ activeSlot.role }}</span>
                    <button @click="activeSlot = null">✕</button>
                </div>
                <div class="p-4 max-h-[60vh] overflow-y-auto space-y-2">
                    <div v-for="p in getAvailableForRole" :key="p.id" @click="selectPlayer(p)" class="p-4 border-2 rounded-2xl flex justify-between items-center hover:border-blue-500 cursor-pointer">
                        <span class="font-black uppercase">{{ p.name }}</span>
                        <span class="text-xs text-gray-400">{{ p.real_team }} - {{ p.nationality }}</span>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>