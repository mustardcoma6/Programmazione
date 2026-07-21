<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({ roster: Array, matchday: Number });

const selectedModule = ref('4-4-2');
const starters = ref([]);
const bench = ref([]);

// Funzione per gestire i clic sui giocatori
const handlePlayerClick = (player) => {
    const id = player.real_player_id;

    // 1. Se è già titolare, lo togliamo
    if (starters.value.includes(id)) {
        starters.value = starters.value.filter(i => i !== id);
        return;
    }

    // 2. Se è già in panchina, lo togliamo
    if (bench.value.includes(id)) {
        bench.value = bench.value.filter(i => i !== id);
        return;
    }

    // 3. Se non è da nessuna parte, proviamo a metterlo in campo
    if (starters.value.length < 11) {
        starters.value.push(id);
    } 
    // 4. Se il campo è pieno, proviamo in panchina
    else if (bench.value.length < 7) {
        bench.value.push(id);
    } else {
        alert("Squadra completa!");
    }
};

const form = useForm({
    matchday: props.matchday,
    module: selectedModule,
    starters: [],
    bench: []
});

const submit = () => {
    if (starters.value.length !== 11) {
        alert("Devi scegliere 11 titolari!");
        return;
    }
    form.starters = starters.value;
    form.bench = bench.value;
    form.post(route('lineup.store'));
};
</script>

<template>
    <Head title="Campo" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Schiera Formazione</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                
                <!-- SELETTORE MODULO -->
                <div class="bg-white p-6 shadow rounded-lg flex items-center justify-between">
                    <span class="font-bold text-gray-700">Scegli il Modulo:</span>
                    <select v-model="selectedModule" class="border-gray-300 rounded-lg">
                        <option>4-4-2</option>
                        <option>4-3-3</option>
                        <option>3-4-3</option>
                        <option>3-5-2</option>
                    </select>
                </div>

                <!-- LISTA ROSA -->
                <div class="bg-white p-6 shadow rounded-lg">
                    <h3 class="font-black uppercase mb-4 text-gray-600">Scegli i tuoi campioni</h3>
                    <p class="text-xs text-gray-400 mb-4 italic">Clicca una volta per Titolare, due per Panchina.</p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                        <div v-for="item in roster" :key="item.id" 
                             @click="handlePlayerClick(item)"
                             class="p-3 border-2 rounded-xl cursor-pointer transition-all"
                             :class="{
                                'bg-blue-600 border-blue-800 text-white shadow-lg': starters.includes(item.real_player_id),
                                'bg-orange-400 border-orange-600 text-white shadow-md': bench.includes(item.real_player_id),
                                'bg-gray-50 border-gray-100 hover:border-blue-200': !starters.includes(item.real_player_id) && !bench.includes(item.real_player_id)
                             }">
                            <div class="flex justify-between items-center">
                                <span class="text-[10px] font-black uppercase">{{ item.player.role }}</span>
                                <span v-if="starters.includes(item.real_player_id)" class="text-[10px] bg-blue-800 px-1 rounded">TITOLARE</span>
                                <span v-if="bench.includes(item.real_player_id)" class="text-[10px] bg-orange-700 px-1 rounded">PANCHINA</span>
                            </div>
                            <p class="font-bold uppercase text-sm mt-1">{{ item.player.name }}</p>
                        </div>
                    </div>

                    <div class="mt-8 flex gap-4">
                        <div class="flex-1 bg-blue-50 p-4 rounded-lg text-center border border-blue-200">
                            <p class="text-xs font-bold text-blue-700 uppercase">In Campo</p>
                            <p class="text-2xl font-black text-blue-900">{{ starters.length }} / 11</p>
                        </div>
                        <div class="flex-1 bg-orange-50 p-4 rounded-lg text-center border border-orange-200">
                            <p class="text-xs font-bold text-orange-700 uppercase">In Panchina</p>
                            <p class="text-2xl font-black text-orange-900">{{ bench.length }} / 7</p>
                        </div>
                    </div>

                    <button @click="submit" class="w-full mt-6 bg-green-600 text-white py-4 rounded-xl font-black uppercase tracking-widest hover:bg-green-700 transition shadow-xl">
                        SALVA FORMAZIONE GIORNATA 1
                    </button>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>