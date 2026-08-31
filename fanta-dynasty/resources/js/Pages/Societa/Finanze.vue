<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { Line } from 'vue-chartjs';
import { 
    Chart as ChartJS, Title, Tooltip, Legend, LineElement, 
    CategoryScale, LinearScale, PointElement, Filler 
} from 'chart.js';

// Registrazione componenti Chart.js
ChartJS.register(Title, Tooltip, Legend, LineElement, CategoryScale, LinearScale, PointElement, Filler);

const props = defineProps({
    leagues: Object,
    myData: Object,
    stats: Object,
    allTeams: Array,
    history: Array,
    message: String
});

const formatEuro = (value) => {
    if (!value) return '0,00';
    return Number(value).toLocaleString('it-IT', { 
        minimumFractionDigits: 2, 
        maximumFractionDigits: 2 
    });
};

const chartData = {
    labels: Array.from({ length: 38 }, (_, i) => `G${i + 1}`),
    datasets: [
        {
            label: 'Valore (€)',
            data: Array.from({ length: 38 }, (_, i) => {
                const matchdayNumber = i + 1;
                const record = props.history?.find(h => h.matchday === matchdayNumber);
                return record ? record.value : null;
            }),
            borderColor: '#6366f1',
            backgroundColor: 'rgba(99, 102, 241, 0.1)',
            borderWidth: 4,
            tension: 0.4,
            fill: true,
            spanGaps: true,
            pointRadius: 4,
            pointBackgroundColor: '#fff',
            pointBorderColor: '#6366f1',
        }
    ]
};

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    scales: {
        y: {
            min: 0,
            max: 570,
            ticks: { color: '#94a3b8', callback: (v) => v + ' €' },
            grid: { color: 'rgba(241, 245, 249, 0.1)' }
        },
        x: {
            ticks: { color: '#94a3b8', maxRotation: 0, autoSkip: true, maxTicksLimit: 12 },
            grid: { display: false }
        }
    },
    plugins: {
        legend: { display: false },
        tooltip: { backgroundColor: '#1e1b4b', padding: 12 }
    }
};
</script>

<template>
    <Head title="Finance Terminal" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center w-full">
                <h2 class="font-black text-xl text-gray-800 uppercase italic tracking-tighter">Financial Terminal</h2>
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Market Open</span>
                </div>
            </div>
        </template>

        <div class="py-12 px-4 max-w-6xl mx-auto space-y-6">
            <!-- 1. MARKET CAP -->
            <div class="bg-indigo-950 p-10 rounded-[3.5rem] shadow-2xl relative border-b-8 border-indigo-800 text-center overflow-hidden">
                <div class="absolute top-0 left-0 w-full h-full opacity-5 pointer-events-none italic font-black text-white text-9xl -rotate-12 translate-y-12 uppercase">Analysis</div>
                <h4 class="relative z-10 text-[10px] font-black text-indigo-300 uppercase tracking-[0.4em] mb-4">Estimated Market Value</h4>
                
                <div class="relative z-10 flex flex-col items-center justify-center">
                    <div class="flex items-center gap-6">
                        <p class="text-7xl md:text-8xl font-black text-white font-mono tracking-tighter">
                            {{ formatEuro(stats?.valore_societario) }}<span class="text-3xl text-indigo-400 ml-2">€</span>
                        </p>

                        <div v-if="stats?.trend?.dir !== 'stable'" 
                             :class="stats?.trend?.dir === 'up' ? 'text-green-400' : 'text-red-500'"
                             class="flex flex-col items-center bg-white/5 p-4 rounded-3xl border border-white/10 backdrop-blur-sm">
                            <span class="text-xl font-black font-mono">{{ stats?.trend?.perc }}%</span>
                        </div>
                    </div>
                </div>

                <div class="relative z-10 mt-8 inline-flex gap-4">
                    <div class="px-4 py-1.5 bg-green-500/20 border border-green-500/50 rounded-full">
                        <span class="text-green-400 text-[10px] font-black uppercase tracking-widest">Base: 130,00€</span>
                    </div>
                    <div class="px-4 py-1.5 bg-blue-500/20 border border-blue-500/50 rounded-full text-blue-400 text-[10px] font-black uppercase tracking-widest">
                        Target: 570,00€
                    </div>
                </div>
            </div>

            <!-- 2. INDICI -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-white p-8 rounded-[2.5rem] shadow-xl border border-gray-100">
                    <div class="flex justify-between items-start mb-4">
                        <h4 class="text-[10px] font-black text-gray-400 uppercase tracking-widest italic">Asset Quality Index</h4>
                        <span class="text-xs font-black text-gray-900">{{ stats?.asset_quality_perc || 0 }}%</span>
                    </div>
                    <div class="w-full bg-gray-100 h-3 rounded-full overflow-hidden">
                        <div class="bg-indigo-600 h-full transition-all duration-1000" 
                             :style="{ width: (stats?.asset_quality_perc || 0) + '%' }">
                        </div>
                    </div>
                    <p class="text-[9px] text-gray-400 mt-4 uppercase leading-tight font-bold">Potenza rosa rispetto ai migliori 25 del listone</p>
                </div>

                <div class="bg-white p-8 rounded-[2.5rem] shadow-xl border border-gray-100">
                    <div class="flex justify-between items-start mb-4">
                        <h4 class="text-[10px] font-black text-blue-500 uppercase tracking-widest italic">Winning Efficiency</h4>
                        <span class="text-xs font-black text-blue-600">{{ stats?.winning_efficiency_perc || 0 }}%</span>
                    </div>
                    <div class="w-full bg-blue-50 h-3 rounded-full overflow-hidden">
                        <div class="bg-blue-500 h-full transition-all duration-1000" 
                             :style="{ width: (stats?.winning_efficiency_perc || 0) + '%' }">
                        </div>
                    </div>
                    <p class="text-[9px] text-gray-400 mt-4 uppercase leading-tight font-bold">Capacità realizzativa rispetto al leader della lega</p>
                </div>
            </div>

            <!-- 3. GRAFICO E CLASSIFICA -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="lg:col-span-2 bg-white p-8 rounded-[3rem] shadow-xl border border-gray-100">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="font-black text-gray-800 uppercase italic text-sm">Valuation Trend</h3>
                        <span class="text-[9px] font-black text-indigo-500 bg-indigo-50 px-3 py-1 rounded-full uppercase">Live Update</span>
                    </div>
                    <div class="h-[320px]">
                        <Line :data="chartData" :options="chartOptions" />
                    </div>
                </div>

                <div class="bg-indigo-900 rounded-[3rem] shadow-2xl overflow-hidden flex flex-col border-b-8 border-indigo-950">
                    <div class="p-6 bg-indigo-950/50 border-b border-indigo-800 text-center">
                        <h3 class="text-white font-black uppercase italic text-xs tracking-widest">League Market Cap</h3>
                    </div>
                    <div class="overflow-y-auto flex-1 max-h-[320px]">
                        <table class="w-full text-left">
                            <tbody class="divide-y divide-indigo-800/30">
                                <tr v-for="(team, index) in allTeams" :key="index" 
                                    :class="{'bg-white/10': team.user_id === myData?.user_id}"
                                    class="hover:bg-white/5 transition">
                                    <td class="p-4 text-[10px] font-black text-indigo-400 italic">#{{ index + 1 }}</td>
                                    <td class="p-4">
                                        <div class="text-white font-bold text-xs uppercase truncate max-w-[120px]">
                                            {{ team.team_name }}
                                        </div>
                                    </td>
                                    <td class="p-4 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <div v-if="team.trend?.dir !== 'stable'" 
                                                 :class="team.trend?.dir === 'up' ? 'text-green-400' : 'text-red-500'" 
                                                 class="text-[10px] font-black flex items-center">
                                                <span v-if="team.trend?.dir === 'up'">▲</span>
                                                <span v-else>▼</span>
                                                {{ team.trend?.perc }}%
                                            </div>
                                            <div class="text-white font-mono font-black text-xs">
                                                {{ formatEuro(team.valore) }}€
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="p-4 bg-indigo-950/80 text-center text-[8px] text-indigo-400 font-bold uppercase tracking-[0.2em]">
                        Global Valuation Ranking
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.bg-indigo-950 { background: linear-gradient(145deg, #0f172a 0%, #1e1b4b 100%); }
</style>