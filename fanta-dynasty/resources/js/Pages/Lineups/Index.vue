<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted } from 'vue';

const props = defineProps({ roster: Array, savedLineup: Object, matchday: Number });

const modules = { '4-4-2': { D: 4, C: 4, A: 2 }, '4-3-3': { D: 4, C: 3, A: 3 }, '3-4-3': { D: 3, C: 4, A: 3 }, '3-5-2': { D: 3, C: 5, A: 2 } };
const selectedModule = ref(props.savedLineup?.module || '4-4-2');

const starters = ref({ P: [null], D: Array(4).fill(null), C: Array(4).fill(null), A: Array(2).fill(null) });
const bench = ref([]);

onMounted(() => {
    if (props.savedLineup) {
        props.savedLineup.details.forEach(d => {
            if (d.is_starter && d.position_key) {
                const [r, i] = d.position_key.split('_');
                if (starters.value[r]) starters.value[r][i] = d.real_player_id;
            } else if (!d.is_starter) { bench.value.push(d.real_player_id); }
        });
    }
});

const activeSlot = ref(null);
const openSelector = (role, index) => { activeSlot.value = { role, index }; };

const selectPlayer = (player) => {
    // CORREZIONE: Usiamo player.id che è il valore corretto passato dal controller
    for (let r in starters.value) starters.value[r] = starters.value[r].map(id => id === player.id ? null : id);
    bench.value = bench.value.filter(id => id !== player.id);
    starters.value[activeSlot.value.role][activeSlot.value.index] = player.id;
    activeSlot.value = null;
};

const addToBench = (p) => {
    if (bench.value.length < 7 && !Object.values(starters.value).flat().includes(p.id)) {
        if (!bench.value.includes(p.id)) bench.value.push(p.id);
    }
};

const getPlayer = (id) => props.roster.find(p => p.id === id);

const getAvailableForRole = computed(() => {
    if (!activeSlot.value) return [];
    return props.roster.filter(p => p.role === activeSlot.value.role);
});

const saveLineup = () => {
    const form = useForm({ matchday: props.matchday, module: selectedModule.value, starters: starters.value, bench: bench.value });
    form.post(route('lineup.store'), { preserveScroll: true });
};

// Logica Intesa (Chemistry)
const getLinkColor = (p1, p2) => {
    if (!p1 || !p2) return 'border-white/10';
    const sameTeam = p1.real_team === p2.real_team;
    const sameNation = p1.nationality === p2.nationality;
    if (sameTeam && sameNation) return 'border-green-500 shadow-[0_0_10px_rgba(34,197,94,0.5)]';
    if (sameTeam || sameNation) return 'border-yellow-400 shadow-[0_0_10px_rgba(250,204,21,0.5)]';
    return 'border-red-500/30';
};
</script>

<template>
    <Head title="Campo FUT" />
    <AuthenticatedLayout>
        <div class="py-6 max-w-6xl mx-auto px-4 space-y-6">
            <div class="flex gap-2 overflow-x-auto bg-white p-2 rounded-2xl shadow-sm">
                <button v-for="(v, m) in modules" :key="m" @click="selectedModule = m" class="px-4 py-2 rounded-xl text-xs font-black transition" :class="selectedModule === m ? 'bg-blue-600 text-white' : 'bg-gray-50 text-gray-400'">{{ m }}</button>
            </div>

            <!-- CAMPO STILE FIFA -->
            <div class="relative bg-green-700 rounded-[3rem] shadow-2xl border-[12px] border-green-900 aspect-[3/4] md:aspect-[4/3] flex flex-col justify-around p-4 overflow-hidden">
                <div class="absolute inset-0 opacity-20 bg-[url('https://www.transparenttextures.com/patterns/graphy.png')]"></div>
                
                <div v-for="role in ['A', 'C', 'D', 'P']" :key="role" class="flex justify-around items-center z-10">
                    <div v-for="(slot, i) in starters[role]" :key="i" @click="openSelector(role, i)" 
                         class="w-16 h-20 md:w-24 md:h-32 rounded-xl border-t-4 flex flex-col items-center justify-center cursor-pointer transition shadow-2xl bg-gray-900/90 backdrop-blur-md"
                         :class="slot ? getLinkColor(getPlayer(slot), getPlayer(slot)) : 'border-white/20'">
                        <div v-if="slot" class="text-center">
                            <p class="text-[8px] font-black text-blue-400 uppercase">{{ getPlayer(slot).real_team }}</p>
                            <p class="text-[10px] font-black text-white uppercase">{{ getPlayer(slot).name }}</p>
                            <p class="text-[7px] text-gray-500 font-bold uppercase">{{ getPlayer(slot).nationality }}</p>
                        </div>
                        <span v-else class="text-white/10 text-3xl font-light">+</span>
                    </div>
                </div>
            </div>

            <!-- PANCHINA -->
            <div class="bg-gray-900 p-4 rounded-3xl shadow-xl border-b-8 border-orange-600">
                <p class="text-white text-[10px] font-black uppercase mb-3 text-center tracking-widest">Panchina Riserve</p>
                <div class="flex gap-3 overflow-x-auto pb-2">
                    <div v-for="id in bench" :key="id" @click="bench = bench.filter(b => b !== id)" class="min-w-[100px] p-2 bg-gray-800 rounded-xl border border-white/10 text-center cursor-pointer">
                        <p class="text-white font-black uppercase text-[10px]">{{ getPlayer(id)?.name }}</p>
                    </div>
                </div>
            </div>

            <!-- LISTA ROSA -->
            <div class="grid grid-cols-2 md:grid-cols-5 gap-3 bg-white p-6 rounded-3xl shadow-lg border">
                <div v-for="p in roster" :key="p.id" @click="addToBench(p)"
                     class="p-3 rounded-2xl border-2 cursor-pointer transition text-center"
                     :class="Object.values(starters).flat().includes(p.id) || bench.includes(p.id) ? 'bg-gray-100 border-gray-200 opacity-40' : 'bg-gray-50 border-gray-100 hover:border-blue-500'">
                    <p class="text-[9px] font-black text-blue-600">{{ p.role }}</p>
                    <p class="text-xs font-black uppercase">{{ p.name }}</p>
                    <p class="text-[8px] text-gray-400 uppercase">{{ p.nationality }}</p>
                </div>
            </div>

            <button @click="saveLineup" class="w-full bg-green-600 text-white py-5 rounded-[2rem] font-black uppercase tracking-widest shadow-2xl hover:bg-green-700 transition transform active:scale-95">Salva Formazione 11 + 7</button>
        </div>

        <!-- MODALE -->
        <div v-if="activeSlot" class="fixed inset-0 z-[100] bg-black/80 flex items-center justify-center p-4 backdrop-blur-sm">
            <div class="bg-white w-full max-w-md rounded-[2.5rem] overflow-hidden shadow-2xl">
                <div class="p-5 bg-gray-900 text-white flex justify-between items-center"><span class="font-black uppercase tracking-widest">Scegli {{ activeSlot.role }}</span><button @click="activeSlot = null" class="text-2xl">✕</button></div>
                <div class="p-4 max-h-[60vh] overflow-y-auto space-y-2">
                    <div v-for="p in getAvailableForRole" :key="p.id" @click="selectPlayer(p)" class="p-4 border-2 rounded-2xl flex justify-between items-center hover:border-blue-600 cursor-pointer transition">
                        <div><p class="font-black uppercase text-sm text-gray-800">{{ p.name }}</p><p class="text-[10px] text-gray-400 font-bold uppercase">{{ p.real_team }}</p></div>
                        <span class="text-xs font-black text-gray-400 uppercase">{{ p.nationality }}</span>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>