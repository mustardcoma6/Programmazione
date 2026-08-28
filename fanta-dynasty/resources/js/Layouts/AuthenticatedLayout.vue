<script setup>
import { ref, computed } from 'vue';
import NavLink from '@/Components/NavLink.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Link, usePage } from '@inertiajs/vue3';

const showingNavigationDropdown = ref(false);
const openSocieta = ref(false);
const openLega = ref(false);
const openMercato = ref(false);

const user = usePage().props.auth.user;
const leagues = computed(() => usePage().props.leagues || []);
const hasLeague = computed(() => leagues.value.length > 0);
const isAdmin = computed(() => hasLeague.value && leagues.value[0]?.admin_id === user.id);
</script>

<template>
    <div class="min-h-screen bg-gray-100 font-sans">
        <nav class="bg-white border-b border-gray-200 shadow-sm sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <Link :href="route('dashboard')" class="text-2xl font-black text-blue-600 italic mr-8">FANTAgest</Link>
                        <div class="hidden sm:flex sm:items-center sm:space-x-10 h-full">
                            <NavLink :href="route('dashboard')" :active="route().current('dashboard')">HOME</NavLink>
                            <template v-if="hasLeague">
                                <Dropdown align="left" width="48">
                                    <template #trigger><button class="text-sm font-bold uppercase text-gray-500 h-16">Società ▼</button></template>
                                    <template #content>
                                        <DropdownLink :href="route('roster.index')">La mia Rosa</DropdownLink>
                                        <DropdownLink :href="route('roster.lineup')">Formazione</DropdownLink>
                                        <DropdownLink :href="route('roster.finances')">Finanze</DropdownLink>
                                        <DropdownLink :href="route('roster.primavera')">Primavera</DropdownLink>
                                    </template>
                                </Dropdown>
                                <Dropdown align="left" width="48">
                                    <template #trigger><button class="text-sm font-bold uppercase text-gray-500 h-16">Lega ▼</button></template>
                                    <template #content>
                                        <DropdownLink :href="route('societa.index')">Anagrafe</DropdownLink>
                                        <DropdownLink :href="route('teams.index')">Rose Avversarie</DropdownLink>
                                        <DropdownLink :href="route('league.ranking')">Ranking</DropdownLink>
                                        <DropdownLink :href="route('players.index')">Svincolati</DropdownLink>
                                        <DropdownLink :href="route('league.trophies')">Sala Trofei</DropdownLink>
                                    </template>
                                </Dropdown>
                                <Dropdown align="left" width="48">
                                    <template #trigger><button class="text-sm font-bold uppercase text-gray-500 h-16">Calciomercato ▼</button></template>
                                    <template #content>
                                        <DropdownLink :href="route('market.auctions')">Aste e Scambi</DropdownLink>
                                        <DropdownLink :href="route('market.history')">Cronologia</DropdownLink>
                                    </template>
                                </Dropdown>
                            </template>
                            <NavLink :href="route('history.index')" :active="route().current('history.index')">STORIA</NavLink>
                        </div>
                    </div>
                    <div class="flex items-center space-x-6">
                        <div v-if="isAdmin" class="hidden sm:flex items-center">
                            <Dropdown align="right" width="48">
                                <template #trigger><button class="bg-red-50 text-red-600 px-4 py-2 rounded-xl text-[10px] font-black uppercase">⚙️ Gestione</button></template>
                                <template #content>
                                    <DropdownLink :href="route('market.sessions')">Nuova Sessione</DropdownLink>
                                    <DropdownLink :href="route('admin.rosters')">Gestione Rose</DropdownLink>
                                    <DropdownLink :href="route('admin.credits')">Gestione Budget</DropdownLink>
                                    <DropdownLink :href="route('admin.primavera')">Gestione Primavera</DropdownLink>
                                    <DropdownLink :href="route('admin.players')">Gestione Listone</DropdownLink>
                                    <DropdownLink :href="route('admin.finances')">Gestione Finanze</DropdownLink>
                                    <DropdownLink :href="route('admin.campionato.edit')">🏆 Gestione Campionato</DropdownLink>
                                </template>
                            </Dropdown>
                        </div>
                        <div class="hidden sm:flex sm:items-center"><Dropdown align="right" width="48"><template #trigger><button class="uppercase font-bold text-xs text-gray-500">{{ user.name }} ▼</button></template><template #content><DropdownLink :href="route('profile.edit')">Profilo</DropdownLink><DropdownLink :href="route('logout')" method="post" as="button">Esci</DropdownLink></template></Dropdown></div>
                        <div class="-me-2 flex items-center sm:hidden"><button @click="showingNavigationDropdown = !showingNavigationDropdown" class="p-2 rounded-md text-gray-400"><svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24"><path :class="{'hidden': showingNavigationDropdown, 'inline-flex': !showingNavigationDropdown }" d="M4 6h16M4 12h16M4 18h16" /><path :class="{'hidden': !showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }" d="M6 18L18 6M6 6l18 18" /></svg></button></div>
                    </div>
                </div>
            </div>
            <!-- MOBILE MENU (Corretto e Completo) -->
            <div :class="{'block': showingNavigationDropdown, 'hidden': !showingNavigationDropdown}" class="sm:hidden bg-white border-t border-gray-100 shadow-2xl overflow-y-auto max-h-[90vh]">
                <div class="pt-2 pb-3 space-y-1">
                    <ResponsiveNavLink :href="route('dashboard')" :active="route().current('dashboard')">HOME</ResponsiveNavLink>
                    
                    <template v-if="hasLeague">
                        <!-- TENDINA SOCIETÀ -->
                        <button @click="openSocieta = !openSocieta" class="w-full text-left pl-3 pr-4 py-2 font-bold text-gray-600 uppercase flex justify-between items-center">
                            SOCIETÀ 
                            <svg class="h-4 w-4 transform transition-transform" :class="{'rotate-180': openSocieta}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M19 9l-7 7-7-7" /></svg>
                        </button>
                        <div v-show="openSocieta" class="bg-gray-50 pl-4">
                            <ResponsiveNavLink :href="route('roster.index')">La mia Rosa</ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('roster.lineup')">Formazione</ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('roster.finances')">Finanze</ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('roster.primavera')">Primavera</ResponsiveNavLink>
                        </div>

                        <!-- TENDINA LEGA -->
                        <button @click="openLega = !openLega" class="w-full text-left pl-3 pr-4 py-2 font-bold text-gray-600 uppercase flex justify-between items-center">
                            LEGA 
                            <svg class="h-4 w-4 transform transition-transform" :class="{'rotate-180': openLega}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M19 9l-7 7-7-7" /></svg>
                        </button>
                        <div v-show="openLega" class="bg-gray-50 pl-4">
                            <ResponsiveNavLink :href="route('societa.index')">Anagrafe</ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('teams.index')">Rose Avversarie</ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('league.ranking')">Ranking</ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('players.index')">Svincolati</ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('league.trophies')">Sala Trofei</ResponsiveNavLink>
                        </div>

                        <!-- TENDINA MERCATO -->
                        <button @click="openMercato = !openMercato" class="w-full text-left pl-3 pr-4 py-2 font-bold text-gray-600 uppercase flex justify-between items-center">
                            CALCIOMERCATO 
                            <svg class="h-4 w-4 transform transition-transform" :class="{'rotate-180': openMercato}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M19 9l-7 7-7-7" /></svg>
                        </button>
                        <div v-show="openMercato" class="bg-gray-50 pl-4">
                            <ResponsiveNavLink :href="route('market.auctions')">Aste e Scambi</ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('market.history')">Cronologia</ResponsiveNavLink>
                        </div>
                    </template>

                    <ResponsiveNavLink :href="route('history.index')" :active="route().current('history.index')">STORIA</ResponsiveNavLink>

                    <!-- SEZIONE ADMIN (Solo se l'utente è Admin) -->
                    <div v-if="isAdmin" class="border-t border-red-100 mt-4 pt-4">
                        <div class="pl-3 pr-4 py-2 text-xs font-black text-red-500 uppercase">Gestione Lega</div>
                        <ResponsiveNavLink :href="route('market.sessions')">Nuova Sessione</ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('admin.rosters')">Gestione Rose</ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('admin.credits')">Gestione Budget</ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('admin.finances')">Gestione Finanze</ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('admin.campionato.edit')" :active="route().current('admin.campionato.edit')">
                        🏆 Gestione Campionato
                    </ResponsiveNavLink>
                    </div>

                    <!-- SEZIONE UTENTE (Profilo ed Esci) -->
                    <div class="border-t border-gray-200 mt-4 pt-4 pb-1">
                        <div class="pl-3 pr-4 py-2 text-xs font-black text-gray-400 uppercase">{{ user.name }}</div>
                        <ResponsiveNavLink :href="route('profile.edit')">Profilo</ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('logout')" method="post" as="button">Esci</ResponsiveNavLink>
                    </div>
                </div>
            </div>
        </nav>
        <main><slot /></main>
    </div>
</template>