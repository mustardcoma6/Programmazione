<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';

defineProps({
    myData: Object,
    ranking: Array,
    myEuroValue: Number 
});

const user = usePage().props.auth.user;
</script>

<template>
    <Head title="Finanze" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-black text-xl uppercase tracking-tighter text-gray-800 italic">Analisi Patrimoniale</h2>
        </template>

        <div class="py-12 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8 px-4">
            
            <!-- BANNER VALORE SOCIETÀ STILE BORSA -->
            <div class="bg-gray-900 rounded-[2rem] p-8 shadow-2xl border-b-4 border-green-500 relative overflow-hidden text-center md:text-left">
                <div class="absolute right-0 top-0 w-64 h-full bg-gradient-to-l from-green-500/10 to-transparent pointer-events-none"></div>
                
                <div class="relative z-10 flex flex-col md:flex-row justify-between items-center gap-6">
                    <div>
                        <p class="text-green-500 text-xs font-black uppercase tracking-[0.3em] mb-2">Valutazione di Mercato</p>
                        <h3 class="text-white text-4xl font-black uppercase tracking-tighter">{{ myData.team_name }}</h3>
                    </div>
                    
                    <div class="flex items-center gap-4">
                        <span class="text-green-400 animate-pulse text-3xl">▲</span>
                        <!-- Visualizzazione EURO esatta -->
                        <p class="text-7xl font-black text-white font-mono leading-none">
                            {{ myEuroValue.toLocaleString('it-IT', { minimumFractionDigits: 2 }) }}
                        </p>
                        <span class="text-5xl font-black text-gray-600">€</span>
                    </div>
                </div>
            </div>

            <!-- CLASSIFICA VALORE QUOTAZIONI -->
            <div class="bg-white shadow-xl rounded-3xl overflow-hidden border border-gray-100">
                <div class="bg-gray-100 p-4 border-b">
                    <h3 class="font-black uppercase tracking-widest text-[10px] text-gray-500 text-center md:text-left">Ranking Asset Calciatori (cr)</h3>
                </div>

                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-50 text-[10px] font-black uppercase text-gray-400 border-b">
                            <th class="p-4 w-20 text-center">Pos</th>
                            <th class="p-4">Club / Presidente</th>
                            <th class="p-4 text-right">Valore cr</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(team, index) in ranking" :key="team.id" 
                            class="border-b last:border-0 transition"
                            :class="team.user_id === user.id ? 'bg-blue-50' : 'hover:bg-gray-50'">
                            
                            <td class="p-4 text-center">
                                <span class="w-8 h-8 rounded-xl flex items-center justify-center font-black text-sm"
                                      :class="index === 0 ? 'bg-yellow-400 text-yellow-900' : 'bg-gray-200 text-gray-400'">
                                    {{ index + 1 }}
                                </span>
                            </td>

                            <td class="p-4">
                                <div class="flex flex-col">
                                    <span class="font-black uppercase text-sm" :class="team.user_id === user.id ? 'text-blue-700' : 'text-gray-800'">
                                        {{ team.team_name }}
                                    </span>
                                    <span class="text-[9px] text-gray-400 uppercase font-bold">{{ team.user.name }}</span>
                                </div>
                            </td>

                            <td class="p-4 text-right font-mono font-black text-xl text-gray-700">
                                {{ team.total_quotation_value }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </div>
    </AuthenticatedLayout>
</template>