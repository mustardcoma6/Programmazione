<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, usePage } from '@inertiajs/vue3';
import { onMounted } from 'vue';
import Chart from 'chart.js/auto';

// UNICO BLOCCO PROPS - RISOLVE L'ERRORE DI BUILD
const props = defineProps({
    myData: Object,
    rankingAsset: Array,
    rankingEuro: Array,
    myEuroValue: Number,
    history: Array 
});

const user = usePage().props.auth.user;

onMounted(() => {
    const ctx = document.getElementById('trendChart');
    if (ctx && props.history && props.history.length > 0) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: props.history.map(h => {
                    const date = new Date(h.recorded_at);
                    return date.toLocaleDateString('it-IT', { day: '2-digit', month: '2-digit' });
                }),
                datasets: [{
                    label: 'Valore (€)',
                    data: props.history.map(h => h.value),
                    borderColor: '#4ade80',
                    backgroundColor: 'rgba(74, 222, 128, 0.1)',
                    fill: true,
                    tension: 0.4,
                    borderWidth: 3,
                    pointRadius: 3,
                    pointBackgroundColor: '#4ade80'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false }, ticks: { color: '#64748b', font: { size: 10 } } },
                    y: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#94a3b8', font: { size: 10 } } }
                }
            }
        });
    }
});
</script>

<template>
    <Head title="Finanze" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-black text-xl uppercase tracking-tighter text-gray-800 italic">Analisi Patrimoniale</h2>
        </template>

        <div class="py-12 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6 px-4">
            
            <!-- 1. BANNER VALORE SOCIETÀ STILE BORSA -->
            <div class="bg-gray-900 rounded-[2rem] p-8 shadow-2xl border-b-4 border-green-500 relative overflow-hidden text-center md:text-left">
                <div class="absolute right-0 top-0 w-64 h-full bg-gradient-to-l from-green-500/10 to-transparent pointer-events-none"></div>
                <div class="relative z-10 flex flex-col md:flex-row justify-between items-center gap-6">
                    <div>
                        <p class="text-green-500 text-xs font-black uppercase tracking-[0.3em] mb-2">Valutazione di Mercato Attuale</p>
                        <h3 class="text-white text-4xl font-black uppercase tracking-tighter">{{ myData.team_name }}</h3>
                    </div>
                    <div class="flex items-center gap-4">
                        <span class="text-green-400 animate-pulse text-3xl">▲</span>
                        <p class="text-7xl font-black text-white font-mono leading-none">
                            {{ myEuroValue.toLocaleString('it-IT', { minimumFractionDigits: 2 }) }}
                        </p>
                        <span class="text-5xl font-black text-gray-600">€</span>
                    </div>
                </div>
            </div>

            <!-- 2. GRAFICO -->
            <div class="bg-white rounded-[2rem] p-6 shadow-xl border border-gray-100">
                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-4 ml-2">Trend Valore Societario</p>
                <div class="h-48 w-full">
                    <canvas id="trendChart"></canvas>
                </div>
                <p v-if="!history || history.length === 0" class="text-center text-xs text-gray-400 italic py-4">Nessun dato storico disponibile.</p>
            </div>

            <!-- 3. CLASSIFICHE DOPPIE -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mt-10">
                <div class="bg-white shadow-xl rounded-3xl overflow-hidden border border-gray-200">
                    <div class="bg-gray-800 p-4 text-white uppercase font-black text-xs">💶 Valore in Euro</div>
                    <table class="w-full text-left">
                        <tbody>
                            <tr v-for="(team, index) in rankingEuro" :key="team.id" class="border-b last:border-0" :class="team.user_id === user.id ? 'bg-green-50' : ''">
                                <td class="p-4 text-center font-black text-xs w-16">{{ index + 1 }}°</td>
                                <td class="p-4 text-xs font-black uppercase text-gray-800">{{ team.team_name }}</td>
                                <td class="p-4 text-right font-mono font-black text-blue-600">{{ team.euro_value.toLocaleString('it-IT', { minimumFractionDigits: 2 }) }} €</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="bg-white shadow-xl rounded-3xl overflow-hidden border border-gray-200">
                    <div class="bg-gray-900 p-4 text-white uppercase font-black text-xs">📈 Valore Asset (cr)</div>
                    <table class="w-full text-left">
                        <tbody>
                            <tr v-for="(team, index) in rankingAsset" :key="team.id" class="border-b last:border-0" :class="team.user_id === user.id ? 'bg-blue-50' : ''">
                                <td class="p-4 text-center font-black text-xs w-16">{{ index + 1 }}°</td>
                                <td class="p-4 text-xs font-black uppercase text-gray-800">{{ team.team_name }}</td>
                                <td class="p-4 text-right font-mono font-black text-green-600">{{ team.total_quotation_value }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>