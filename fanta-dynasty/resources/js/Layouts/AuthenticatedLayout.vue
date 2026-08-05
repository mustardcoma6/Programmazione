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
const openGestione = ref(false);

const user = usePage().props.auth.user;

// --- PROTEZIONE SENIOR: Optional Chaining (?.) ---
// Se leagues non esiste, il sito NON diventa bianco ma semplicemente non mostra i tasti
const leagues = computed(() => usePage().props.leagues || []);
const hasLeague = computed(() => leagues.value.length > 0);
const isAdmin = computed(() => {
    return hasLeague.value && leagues.value[0]?.admin_id === user.id;
});
</script>

<template>
    <div class="min-h-screen bg-gray-100">
        <nav class="bg-white border-b border-gray-100 shadow-sm sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <div class="shrink-0 flex items-center">
                            <Link :href="route('dashboard')" class="text-2xl font-black text-blue-600 italic tracking-tighter">
                                FANTAgest
                            </Link>
                        </div>

                        <!-- MENU DESKTOP -->
                        <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                            <NavLink :href="route('dashboard')" :active="route().current('dashboard')">Home</NavLink>
                            
                            <template v-if="hasLeague">
                                <!-- SOCIETÀ -->
                                <div class="hidden sm:flex sm:items-center">
                                    <Dropdown align="left" width="48">
                                        <template #trigger>
                                            <button class="inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-gray-500 hover:text-gray-700 transition uppercase font-bold">
                                                Società <svg class="ms-1 h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path d="M19 9l-7 7-7-7" /></svg>
                                            </button>
                                        </template>
                                        <template #content>
                                            <DropdownLink :href="route('roster.index')"> La mia Rosa </DropdownLink>
                                            <DropdownLink :href="route('teams.index')"> Rose Avversarie </DropdownLink>
                                            <DropdownLink :href="route('roster.lineup')"> Formazione </DropdownLink>
                                            <DropdownLink :href="route('roster.finances')"> Finanze </DropdownLink>
                                        </template>
                                    </Dropdown>
                                </div>

                                <!-- LEGA -->
                                <div class="hidden sm:flex sm:items-center">
                                    <Dropdown align="left" width="48">
                                        <template #trigger>
                                            <button class="inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-gray-500 hover:text-gray-700 transition uppercase font-bold">
                                                Lega <svg class="ms-1 h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path d="M19 9l-7 7-7-7" /></svg>
                                            </button>
                                        </template>
                                        <template #content>
                                            <DropdownLink :href="route('societa.index')"> Anagrafe </DropdownLink>
                                            <DropdownLink :href="route('league.ranking')"> Ranking </DropdownLink>
                                            <DropdownLink :href="route('league.trophies')"> Sala Trofei </DropdownLink>
                                        </template>
                                    </Dropdown>
                                </div>

                                <!-- MERCATO -->
                                <div class="hidden sm:flex sm:items-center">
                                    <Dropdown align="left" width="48">
                                        <template #trigger>
                                            <button class="inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-gray-500 hover:text-gray-700 transition uppercase font-bold">
                                                Mercato <svg class="ms-1 h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path d="M19 9l-7 7-7-7" /></svg>
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

                            <!-- GESTIONE ADMIN -->
                            <div v-if="isAdmin" class="hidden sm:flex sm:items-center ms-4">
                                <Dropdown align="right" width="48">
                                    <template #trigger><button class="text-sm font-bold text-red-600 bg-red-50 px-3 py-1 rounded-lg uppercase">⚙️ Gestione</button></template>
                                    <template #content>
                                        <DropdownLink :href="route('market.sessions')"> Nuova Sessione </DropdownLink>
                                        <DropdownLink :href="route('admin.rosters')"> Gestione Rose </DropdownLink>
                                        <DropdownLink :href="route('admin.credits')"> Gestione Budget </DropdownLink>
                                        <DropdownLink :href="route('admin.players')"> Gestione Listone </DropdownLink>
                                        <DropdownLink :href="route('admin.finances')"> Gestione Finanze </DropdownLink>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>
                    </div>

                    <div class="-me-2 flex items-center sm:hidden">
                        <button @click="showingNavigationDropdown = !showingNavigationDropdown" class="p-2 rounded-md text-gray-400 hover:bg-gray-100 transition">
                            <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24"><path :class="{'hidden': showingNavigationDropdown, 'inline-flex': !showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /><path :class="{'hidden': !showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l18 18" /></svg>
                        </button>
                    </div>

                    <div class="hidden sm:flex sm:items-center sm:ms-6">
                        <Dropdown align="right" width="48">
                            <template #trigger><button class="uppercase font-bold text-xs text-gray-500">{{ user.name }} ▼</button></template>
                            <template #content><DropdownLink :href="route('profile.edit')">Profilo</DropdownLink><DropdownLink :href="route('logout')" method="post" as="button">Esci</DropdownLink></template>
                        </Dropdown>
                    </div>
                </div>
            </div>

            <!-- MENU MOBILE -->
            <div :class="{'block': showingNavigationDropdown, 'hidden': !showingNavigationDropdown}" class="sm:hidden bg-white border-t border-gray-100 shadow-2xl overflow-y-auto max-h-[90vh]">
                <div class="pt-2 pb-3 space-y-1">
                    <ResponsiveNavLink :href="route('dashboard')" :active="route().current('dashboard')">Home</ResponsiveNavLink>
                    <template v-if="hasLeague">
                        <button @click="openSocieta = !openSocieta" class="w-full text-left pl-3 pr-4 py-2 font-bold text-gray-600 uppercase">Società</button>
                        <div v-show="openSocieta" class="bg-gray-50"><ResponsiveNavLink :href="route('roster.index')">La mia Rosa</ResponsiveNavLink></div>
                        <button @click="openLega = !openLega" class="w-full text-left pl-3 pr-4 py-2 font-bold text-gray-600 uppercase">Lega</button>
                        <div v-show="openLega" class="bg-gray-50"><ResponsiveNavLink :href="route('societa.index')">Anagrafe</ResponsiveNavLink></div>
                    </template>
                    <ResponsiveNavLink :href="route('players.index')">Svincolati</ResponsiveNavLink>
                    <div v-if="isAdmin">
                        <button @click="openGestione = !openGestione" class="w-full text-left pl-3 pr-4 py-2 font-bold text-red-600 uppercase">Gestione</button>
                        <div v-show="openGestione" class="bg-red-50"><ResponsiveNavLink :href="route('admin.rosters')">Gestione Rose</ResponsiveNavLink></div>
                    </div>
                </div>
            </div>
        </nav>
        <main><slot /></main>
    </div>
</template>