<script setup>
import { ref, computed } from 'vue';
import NavLink from '@/Components/NavLink.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue'; // FONDAMENTALE PER MOBILE
import { Link, usePage } from '@inertiajs/vue3';

// Variabile per aprire/chiudere il menu su cellulare
const showingNavigationDropdown = ref(false);

const user = usePage().props.auth.user;
const leagues = computed(() => usePage().props.leagues || []);
const hasLeague = computed(() => leagues.value.length > 0);
const isAdmin = computed(() => hasLeague.value && leagues.value[0].admin_id === user.id);
</script>

<template>
    <div class="min-h-screen bg-gray-100 font-sans">
        <nav class="bg-white border-b border-gray-100 shadow-sm sticky top-0 z-50">
            <!-- Menu Principale -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <!-- Logo -->
                        <div class="shrink-0 flex items-center">
                            <Link :href="route('dashboard')" class="text-2xl font-black text-blue-600 italic tracking-tighter">
                                FANTA<span class="text-gray-900 italic">gest</span>
                            </Link>
                        </div>

                        <!-- LINK DESKTOP (Scompaiono su mobile) -->
                        <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                            <NavLink :href="route('dashboard')" :active="route().current('dashboard')">Home</NavLink>
                            
                            <template v-if="hasLeague">
                                <!-- Tendina Società Desktop -->
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

                                <!-- Tendina Mercato Desktop -->
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

                            <!-- Tendina Gestione Admin Desktop -->
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

                    <!-- BOTTONE HAMBURGER (Solo per Mobile) -->
                    <div class="-me-2 flex items-center sm:hidden">
                        <button @click="showingNavigationDropdown = !showingNavigationDropdown" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 transition duration-150 ease-in-out">
                            <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                <path :class="{'hidden': showingNavigationDropdown, 'inline-flex': !showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                <path :class="{'hidden': !showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l18 18" />
                            </svg>
                        </button>
                    </div>

                    <!-- Menu Profilo Desktop -->
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

            <!-- MENU MOBILE (Il Pannello che compare cliccando il tasto) -->
            <div :class="{'block': showingNavigationDropdown, 'hidden': !showingNavigationDropdown}" class="sm:hidden bg-white border-t border-gray-100 shadow-xl overflow-y-auto max-h-[80vh]">
                <div class="pt-2 pb-3 space-y-1">
                    <ResponsiveNavLink :href="route('dashboard')" :active="route().current('dashboard')">Home</ResponsiveNavLink>
                    
                    <template v-if="hasLeague">
                        <div class="px-4 py-2 text-[10px] font-black text-gray-400 uppercase border-b bg-gray-50">Società</div>
                        <ResponsiveNavLink :href="route('societa.index')">Anagrafe Lega</ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('teams.index')">Rose Avversarie</ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('roster.index')">La mia Rosa</ResponsiveNavLink>

                        <div class="px-4 py-2 text-[10px] font-black text-gray-400 uppercase border-b bg-gray-50 mt-2">Calciomercato</div>
                        <ResponsiveNavLink :href="route('market.auctions')">Aste e Scambi</ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('market.history')">Cronologia Movimenti</ResponsiveNavLink>
                    </template>

                    <ResponsiveNavLink :href="route('players.index')">Svincolati</ResponsiveNavLink>

                    <!-- SEZIONE GESTIONE MOBILE -->
                    <template v-if="isAdmin">
                        <div class="px-4 py-2 text-[10px] font-black text-red-600 uppercase border-b bg-red-50 mt-2">⚙️ Amministrazione</div>
                        <ResponsiveNavLink :href="route('market.sessions')">Nuova Sessione</ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('admin.rosters')">Gestione Rose</ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('admin.credits')">Gestione Budget</ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('admin.players')">Gestione Listone</ResponsiveNavLink>
                    </template>
                </div>

                <!-- PROFILO MOBILE -->
                <div class="pt-4 pb-1 border-t border-gray-200 bg-gray-50">
                    <div class="px-4"><div class="font-bold text-base text-gray-800 uppercase">{{ user.name }}</div></div>
                    <div class="mt-3 space-y-1">
                        <ResponsiveNavLink :href="route('profile.edit')">Il tuo Profilo</ResponsiveNavLink>
                        <ResponsiveNavLink :href="route('logout')" method="post" as="button">Esci dal portale</ResponsiveNavLink>
                    </div>
                </div>
            </div>
        </nav>

        <main><slot /></main>
    </div>
</template>