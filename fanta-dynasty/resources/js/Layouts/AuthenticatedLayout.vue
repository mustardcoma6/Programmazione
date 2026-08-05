<script setup>
import { ref, computed } from 'vue';
import NavLink from '@/Components/NavLink.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';
import { Link, usePage } from '@inertiajs/vue3';

// Controlli apertura menu mobile
const showingNavigationDropdown = ref(false);

// Controlli apertura tendine interne al mobile
const openSocieta = ref(false);
const openMercato = ref(false);
const openGestione = ref(false);

const user = usePage().props.auth.user;
const leagues = computed(() => usePage().props.leagues || []);
const hasLeague = computed(() => leagues.value.length > 0);
const isAdmin = computed(() => hasLeague.value && leagues.value[0].admin_id === user.id);
</script>

<template>
    <div class="min-h-screen bg-gray-100 font-sans">
        <nav class="bg-white border-b border-gray-100 shadow-sm sticky top-0 z-50">
            <!-- BARRA SUPERIORE -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <!-- Logo -->
                        <div class="shrink-0 flex items-center">
                            <Link :href="route('dashboard')" class="text-2xl font-black text-blue-600 italic tracking-tighter">
                                FANTA<span class="text-gray-900 italic">gest</span>
                            </Link>
                        </div>

                        <!-- MENU DESKTOP (Invariato) -->
                        <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                            <NavLink :href="route('dashboard')" :active="route().current('dashboard')">Home</NavLink>
                            
                            <template v-if="hasLeague">
                                <div class="hidden sm:flex sm:items-center">
                                    <Dropdown align="left" width="48">
                                        <template #trigger>
                                            <button class="inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-bold leading-5 text-gray-500 hover:text-gray-700 transition uppercase">
                                                Società <svg class="ms-1 h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" /></svg>
                                            </button>
                                        </template>
                                        <template #content>
                                            <DropdownLink :href="route('societa.index')"> Anagrafe Lega </DropdownLink>
                                            <DropdownLink :href="route('teams.index')"> Rose Avversarie </DropdownLink>
                                            <DropdownLink :href="route('roster.index')"> La mia Rosa </DropdownLink>
                                        </template>
                                    </Dropdown>
                                </div>

                                <div class="hidden sm:flex sm:items-center">
                                    <Dropdown align="left" width="48">
                                        <template #trigger>
                                            <button class="inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-bold leading-5 text-gray-500 hover:text-gray-700 transition uppercase">
                                                Calciomercato <svg class="ms-1 h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" /></svg>
                                            </button>
                                        </template>
                                        <template #content>
                                            <DropdownLink :href="route('market.auctions')"> Aste e Scambi </DropdownLink>
                                            <DropdownLink :href="route('market.history')"> Cronologia </DropdownLink>
                                        </template>
                                    </Dropdown>
                                </div>
                            </template>

                            <NavLink :href="route('players.index')" :active="route().current('players.index')">Svincolati</NavLink>

                            <div v-if="isAdmin" class="hidden sm:flex sm:items-center ms-4">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <button class="text-sm font-bold text-red-600 bg-red-50 px-3 py-1 rounded-lg uppercase border border-red-100">⚙️ Gestione</button>
                                    </template>
                                    <template #content>
                                        <DropdownLink :href="route('market.sessions')"> Nuova Sessione </DropdownLink>
                                        <DropdownLink :href="route('admin.rosters')"> Gestione Rose </DropdownLink>
                                        <DropdownLink :href="route('admin.credits')"> Gestione Budget </DropdownLink>
                                        <DropdownLink :href="route('admin.players')"> Gestione Listone </DropdownLink>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>
                    </div>

                    <!-- TASTO BURGER MOBILE -->
                    <div class="-me-2 flex items-center sm:hidden">
                        <button @click="showingNavigationDropdown = !showingNavigationDropdown" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 transition">
                            <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                <path :class="{'hidden': showingNavigationDropdown, 'inline-flex': !showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                <path :class="{'hidden': !showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l18 18" />
                            </svg>
                        </button>
                    </div>

                    <!-- Profilo Destra Desktop -->
                    <div class="hidden sm:flex sm:items-center">
                        <Dropdown align="right" width="48">
                            <template #trigger><button class="uppercase font-bold text-xs text-gray-500">{{ user.name }} ▼</button></template>
                            <template #content>
                                <DropdownLink :href="route('profile.edit')">Profilo</DropdownLink>
                                <DropdownLink :href="route('logout')" method="post" as="button">Esci</DropdownLink>
                            </template>
                        </Dropdown>
                    </div>
                </div>
            </div>

            <!-- MENU MOBILE RESPONSIVE -->
            <div :class="{'block': showingNavigationDropdown, 'hidden': !showingNavigationDropdown}" class="sm:hidden bg-white border-t border-gray-100 shadow-2xl overflow-y-auto max-h-[90vh]">
                <div class="pt-2 pb-3 space-y-1">
                    <ResponsiveNavLink :href="route('dashboard')" :active="route().current('dashboard')">Home</ResponsiveNavLink>
                    
                    <template v-if="hasLeague">
                        <!-- TENDINA SOCIETÀ MOBILE -->
                        <div>
                            <button @click="openSocieta = !openSocieta" class="flex items-center justify-between w-full pl-3 pr-4 py-2 border-l-4 border-transparent text-left text-base font-bold text-gray-600 hover:text-gray-800 hover:bg-gray-50 transition uppercase">
                                Società
                                <svg class="h-4 w-4 transition-transform duration-200" :class="{'rotate-180': openSocieta}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                            </button>
                            <div v-show="openSocieta" class="bg-gray-50 py-1">
                                <ResponsiveNavLink :href="route('societa.index')" class="pl-8 text-sm">Anagrafe Lega</ResponsiveNavLink>
                                <ResponsiveNavLink :href="route('teams.index')" class="pl-8 text-sm">Rose Avversarie</ResponsiveNavLink>
                                <ResponsiveNavLink :href="route('roster.index')" class="pl-8 text-sm">La mia Rosa</ResponsiveNavLink>
                            </div>
                        </div>

                        <!-- TENDINA CALCIOMERCATO MOBILE -->
                        <div>
                            <button @click="openMercato = !openMercato" class="flex items-center justify-between w-full pl-3 pr-4 py-2 border-l-4 border-transparent text-left text-base font-bold text-gray-600 hover:text-gray-800 hover:bg-gray-50 transition uppercase">
                                Calciomercato
                                <svg class="h-4 w-4 transition-transform duration-200" :class="{'rotate-180': openMercato}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                            </button>
                            <div v-show="openMercato" class="bg-gray-50 py-1">
                                <ResponsiveNavLink :href="route('market.auctions')" class="pl-8 text-sm">Aste e Scambi</ResponsiveNavLink>
                                <ResponsiveNavLink :href="route('market.history')" class="pl-8 text-sm">Cronologia Movimenti</ResponsiveNavLink>
                            </div>
                        </div>
                    </template>

                    <ResponsiveNavLink :href="route('players.index')">Svincolati</ResponsiveNavLink>

                    <!-- TENDINA GESTIONE MOBILE -->
                    <div v-if="isAdmin">
                        <button @click="openGestione = !openGestione" class="flex items-center justify-between w-full pl-3 pr-4 py-2 border-l-4 border-transparent text-left text-base font-bold text-red-600 hover:bg-red-50 transition uppercase">
                            ⚙️ Gestione
                            <svg class="h-4 w-4 transition-transform duration-200" :class="{'rotate-180': openGestione}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                        </button>
                        <div v-show="openGestione" class="bg-red-50/30 py-1 border-l-4 border-red-200">
                            <ResponsiveNavLink :href="route('market.sessions')" class="pl-8 text-sm">Nuova Sessione</ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('admin.rosters')" class="pl-8 text-sm">Gestione Rose</ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('admin.credits')" class="pl-8 text-sm">Gestione Budget</ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('admin.players')" class="pl-8 text-sm">Gestione Listone</ResponsiveNavLink>
                        </div>
                    </div>
                </div>

                <!-- PROFILO MOBILE -->
                <div class="pt-4 pb-1 border-t border-gray-200 bg-gray-50">
                    <div class="px-4 flex justify-between items-center">
                        <div class="font-black text-blue-600 uppercase">{{ user.name }}</div>
                        <Link :href="route('profile.edit')" class="text-xs text-gray-400 underline uppercase font-bold">Profilo</Link>
                    </div>
                    <div class="mt-3 space-y-1">
                        <ResponsiveNavLink :href="route('logout')" method="post" as="button" class="text-red-500 font-bold">Esci dal portale</ResponsiveNavLink>
                    </div>
                </div>
            </div>
        </nav>

        <main><slot /></main>
    </div>
</template>