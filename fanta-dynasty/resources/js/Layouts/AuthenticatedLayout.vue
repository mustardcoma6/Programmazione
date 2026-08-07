<script setup>
import { ref, computed } from 'vue';
import NavLink from '@/Components/NavLink.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Link, usePage } from '@inertiajs/vue3';

const showingNavigationDropdown = ref(false);

// Stati per l'apertura delle tendine su Mobile
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
            <!-- BARRA SUPERIORE -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    
                    <!-- LATO SINISTRO: LOGO + NAVIGAZIONE DESKTOP -->
                    <div class="flex items-center">
                        <div class="shrink-0 flex items-center mr-12">
                            <Link :href="route('dashboard')" class="text-2xl font-black text-blue-600 italic tracking-tighter">
                                FANTA<span class="text-gray-900 italic">gest</span>
                            </Link>
                        </div>

                        <!-- MENU DESKTOP (Spaziatura 12) -->
                        <div class="hidden sm:flex sm:items-center sm:space-x-12 h-full">
                            <!-- 1. HOME -->
                            <NavLink :href="route('dashboard')" :active="route().current('dashboard')" class="h-16 flex items-center font-black">
                                HOME
                            </NavLink>
                            
                            <template v-if="hasLeague">
                                <!-- 2. SOCIETÀ -->
                                <Dropdown align="left" width="48">
                                    <template #trigger>
                                        <button class="inline-flex items-center px-2 pt-1 text-sm font-bold uppercase text-gray-500 hover:text-gray-700 transition h-16">
                                            Società <svg class="ms-1 h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" /></svg>
                                        </button>
                                    </template>
                                    <template #content>
                                        <DropdownLink :href="route('roster.index')">La mia Rosa</DropdownLink>
                                        <DropdownLink :href="route('teams.index')">Rose Avversarie</DropdownLink>
                                        <DropdownLink :href="route('roster.lineup')">Formazione</DropdownLink>
                                        <DropdownLink :href="route('roster.finances')">Finanze</DropdownLink>
                                        <DropdownLink :href="route('roster.primavera')">Primavera</DropdownLink>
                                    </template>
                                </Dropdown>

                                <!-- 3. CALCIOMERCATO -->
                                <Dropdown align="left" width="48">
                                    <template #trigger>
                                        <button class="inline-flex items-center px-2 pt-1 text-sm font-bold uppercase text-gray-500 hover:text-gray-700 transition h-16">
                                            Calciomercato <svg class="ms-1 h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" /></svg>
                                        </button>
                                    </template>
                                    <template #content>
                                        <DropdownLink :href="route('market.auctions')">Aste e Scambi</DropdownLink>
                                        <DropdownLink :href="route('market.history')">Cronologia</DropdownLink>
                                    </template>
                                </Dropdown>
                            </template>

                            <!-- 4. STORIA -->
                            <NavLink :href="route('history.index')" :active="route().current('history.index')" class="h-16 flex items-center font-black">
                                STORIA
                            </NavLink>

                            <!-- 5. LEGA -->
                            <template v-if="hasLeague">
                                <Dropdown align="left" width="48">
                                    <template #trigger>
                                        <button class="inline-flex items-center px-2 pt-1 text-sm font-bold uppercase text-gray-500 hover:text-gray-700 transition h-16">
                                            Lega <svg class="ms-1 h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" /></svg>
                                        </button>
                                    </template>
                                    <template #content>
                                        <DropdownLink :href="route('societa.index')">Anagrafe Lega</DropdownLink>
                                        <DropdownLink :href="route('league.ranking')">Ranking</DropdownLink>
                                        <DropdownLink :href="route('league.trophies')">Sala Trofei</DropdownLink>
                                        <DropdownLink :href="route('players.index')">Svincolati</DropdownLink>
                                    </template>
                                </Dropdown>
                            </template>
                        </div>
                    </div>

                    <!-- LATO DESTRO: GESTIONE + PROFILO -->
                    <div class="flex items-center space-x-6">
                        <div v-if="isAdmin" class="hidden sm:flex items-center">
                            <Dropdown align="right" width="48">
                                <template #trigger>
                                    <button class="bg-red-50 text-red-600 border border-red-100 px-4 py-2 rounded-xl text-[10px] font-black uppercase hover:bg-red-600 hover:text-white transition shadow-sm">
                                        ⚙️ Gestione
                                    </button>
                                </template>
                                <template #content>
                                    <DropdownLink :href="route('market.sessions')">Nuova Sessione</DropdownLink>
                                    <DropdownLink :href="route('admin.rosters')">Gestione Rose</DropdownLink>
                                    <DropdownLink :href="route('admin.credits')">Gestione Budget</DropdownLink>
                                    <DropdownLink :href="route('admin.primavera')">Gestione Primavera</DropdownLink>
                                    <DropdownLink :href="route('admin.players')">Gestione Listone</DropdownLink>
                                    <DropdownLink :href="route('admin.finances')">Gestione Finanze</DropdownLink>
                                </template>
                            </Dropdown>
                        </div>

                        <div class="hidden sm:flex sm:items-center">
                            <Dropdown align="right" width="48">
                                <template #trigger>
                                    <button class="flex items-center text-sm font-bold text-gray-500 hover:text-gray-700 transition uppercase">
                                        {{ user.name }} <svg class="ms-1 h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" /></svg>
                                    </button>
                                </template>
                                <template #content>
                                    <DropdownLink :href="route('profile.edit')">Profilo</DropdownLink>
                                    <DropdownLink :href="route('logout')" method="post" as="button">Esci</DropdownLink>
                                </template>
                            </Dropdown>
                        </div>

                        <!-- BURGER MOBILE -->
                        <div class="-me-2 flex items-center sm:hidden">
                            <button @click="showingNavigationDropdown = !showingNavigationDropdown" class="p-2 rounded-md text-gray-400 hover:bg-gray-100 transition">
                                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                    <path :class="{'hidden': showingNavigationDropdown, 'inline-flex': !showingNavigationDropdown }" d="M4 6h16M4 12h16M4 18h16" />
                                    <path :class="{'hidden': !showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }" d="M6 18L18 6M6 6l18 18" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PANNELLO MOBILE RESPONSIVE -->
            <div :class="{'block': showingNavigationDropdown, 'hidden': !showingNavigationDropdown}" class="sm:hidden bg-white border-t border-gray-100 shadow-2xl overflow-y-auto max-h-[90vh]">
                <div class="pt-2 pb-3 space-y-1">
                    <ResponsiveNavLink :href="route('dashboard')" :active="route().current('dashboard')">HOME</ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('history.index')" :active="route().current('history.index')">STORIA</ResponsiveNavLink>
                    
                    <template v-if="hasLeague">
                        <button @click="openSocieta = !openSocieta" class="w-full text-left pl-3 pr-4 py-2 font-bold text-gray-600 uppercase flex justify-between items-center">
                            Società <svg class="h-4 w-4 transition-transform" :class="{'rotate-180': openSocieta}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M19 9l-7 7-7-7" /></svg>
                        </button>
                        <div v-show="openSocieta" class="bg-gray-50">
                            <ResponsiveNavLink :href="route('roster.index')">La mia Rosa</ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('roster.lineup')">Formazione</ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('roster.finances')">Finanze</ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('roster.primavera')">Primavera</ResponsiveNavLink>
                        </div>

                        <button @click="openLega = !openLega" class="w-full text-left pl-3 pr-4 py-2 font-bold text-gray-600 uppercase flex justify-between items-center border-t border-gray-50">
                            Lega <svg class="h-4 w-4 transition-transform" :class="{'rotate-180': openLega}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M19 9l-7 7-7-7" /></svg>
                        </button>
                        <div v-show="openLega" class="bg-gray-50">
                            <ResponsiveNavLink :href="route('societa.index')">Anagrafe Lega</ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('league.ranking')">Ranking</ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('league.trophies')">Sala Trofei</ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('players.index')">Svincolati</ResponsiveNavLink>
                        </div>

                        <button @click="openMercato = !openMercato" class="w-full text-left pl-3 pr-4 py-2 font-bold text-gray-600 uppercase flex justify-between items-center border-t border-gray-50">
                            Calciomercato <svg class="h-4 w-4 transition-transform" :class="{'rotate-180': openMercato}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path d="M19 9l-7 7-7-7" /></svg>
                        </button>
                        <div v-show="openMercato" class="bg-gray-50">
                            <ResponsiveNavLink :href="route('market.auctions')">Aste e Scambi</ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('market.history')">Cronologia</ResponsiveNavLink>
                        </div>
                    </template>
                </div>

                <div class="pt-4 pb-1 border-t border-gray-200 bg-gray-50">
                    <div class="px-4 flex justify-between items-center"><div class="font-black text-blue-600 uppercase">{{ user.name }}</div></div>
                    <div class="mt-3 space-y-1"><ResponsiveNavLink :href="route('logout')" method="post" as="button" class="text-red-500 font-bold">Esci</ResponsiveNavLink></div>
                </div>
            </div>
        </nav>

        <main><slot /></main>
    </div>
</template>