<script setup>
import { ref } from 'vue';
import NavLink from '@/Components/NavLink.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import { Link, usePage } from '@inertiajs/vue3';

const user = usePage().props.auth.user;

// PROTEZIONE TOTALE: Controlliamo se la lega esiste prima di fare calcoli
const leagues = usePage().props.leagues || [];
const hasLeague = leagues.length > 0;
const isAdmin = hasLeague && leagues[0] ? leagues[0].admin_id === user.id : false;
</script>

<template>
    <div class="min-h-screen bg-gray-100">
        <nav class="bg-white border-b border-gray-100 shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <div class="shrink-0 flex items-center">
                            <Link :href="route('dashboard')" class="text-2xl font-black text-blue-600 italic">FANTAgest</Link>
                        </div>
                        <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                            <NavLink :href="route('dashboard')" :active="route().current('dashboard')">Home</NavLink>
                            
                            <!-- I tasti compaiono solo se l'utente ha una lega -->
                            <template v-if="hasLeague">
                                <NavLink :href="route('teams.index')" :active="route().current('teams.index')">Squadre</NavLink>
                                <NavLink :href="route('societa.index')" :active="route().current('societa.index')">Società</NavLink>
                                <NavLink :href="route('roster.index')" :active="route().current('roster.index')">Rosa</NavLink>
                                <NavLink :href="route('market.auctions')" :active="route().current('market.auctions')">Calciomercato</NavLink>
                            </template>

                            <NavLink :href="route('players.index')" :active="route().current('players.index')">Svincolati</NavLink>

                            <!-- TASTO GESTIONE (Solo Admin) -->
                            <div v-if="isAdmin" class="hidden sm:flex sm:items-center ms-4">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <button class="text-sm font-bold text-red-600 bg-red-50 px-3 py-1 rounded-lg uppercase">⚙️ Gestione</button>
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
                    <div class="hidden sm:flex sm:items-center sm:ms-6">
                        <Dropdown align="right" width="48">
                            <template #trigger>
                                <button class="uppercase font-bold text-sm text-gray-500">{{ user.name }} ▼</button>
                            </template>
                            <template #content>
                                <DropdownLink :href="route('profile.edit')">Profilo</DropdownLink>
                                <DropdownLink :href="route('logout')" method="post" as="button">Esci</DropdownLink>
                            </template>
                        </Dropdown>
                    </div>
                </div>
            </div>
        </nav>
        <main><slot /></main>
    </div>
</template>