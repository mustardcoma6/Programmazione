<script setup>
import { ref } from 'vue';
import NavLink from '@/Components/NavLink.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import { Link, usePage } from '@inertiajs/vue3';

const showingNavigationDropdown = ref(false);

// Recuperiamo i dati dell'utente e della lega per i permessi
const user = usePage().props.auth.user;
const leagues = usePage().props.leagues || [];
const isAdmin = leagues.length > 0 && leagues[0].admin_id === user.id;
</script>

<template>
    <div>
        <div class="min-h-screen bg-gray-100">
            <nav class="bg-white border-b border-gray-100 shadow-sm">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between h-16">
                        <div class="flex">
                            <!-- Logo -->
                            <div class="shrink-0 flex items-center">
                                <Link :href="route('dashboard')" class="text-2xl font-black tracking-tighter transition hover:opacity-80">
                                    <span class="text-blue-600">FANTA</span><span class="text-gray-900">gest</span>
                                </Link>
                            </div>

                            <!-- LINK MENU PRINCIPALE -->
                            <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                                <NavLink :href="route('dashboard')" :active="route().current('dashboard')">
                                    Home
                                </NavLink>
                                <NavLink :href="route('teams.index')" :active="route().current('teams.index')">
                                    Squadre
                                </NavLink>
                                <NavLink :href="route('roster.index')" :active="route().current('roster.index')">
                                    Rosa
                                </NavLink>
                                
                                <!-- TENDINA CALCIOMERCATO (PER TUTTI) -->
                                <div class="hidden sm:flex sm:items-center">
                                    <Dropdown align="left" width="48">
                                        <template #trigger>
                                            <button class="inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:outline-none transition duration-150 ease-in-out h-full uppercase font-bold" :class="{'border-blue-500 text-gray-900': route().current('market.*')}">
                                                Calciomercato
                                                <svg class="ms-2 -me-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                                            </button>
                                        </template>
                                        <template #content>
                                            <DropdownLink :href="route('market.auctions')"> Aste e Scambi </DropdownLink>
                                            <DropdownLink :href="route('market.history')"> Cronologia Movimenti </DropdownLink>
                                        </template>
                                    </Dropdown>
                                </div>

                                <NavLink :href="route('players.index')" :active="route().current('players.index')">
                                    Svincolati
                                </NavLink>

                                <!-- TENDINA GESTIONE (SOLO ADMIN) -->
                                <div v-if="isAdmin" class="hidden sm:flex sm:items-center ms-4">
                                    <Dropdown align="right" width="48">
                                        <template #trigger>
                                            <button class="inline-flex items-center px-3 py-2 text-sm font-bold text-red-600 bg-red-50 rounded-lg hover:bg-red-100 transition uppercase border border-red-100">
                                                ⚙️ Gestione
                                                <svg class="ms-2 -me-0.5 h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                                            </button>
                                        </template>
                                        <template #content>
                                            <DropdownLink :href="route('market.sessions')"> Nuova Sessione </DropdownLink>
                                        </template>
                                    </Dropdown>
                                </div>
                            </div>
                        </div>

                        <!-- MENU UTENTE -->
                        <div class="hidden sm:flex sm:items-center sm:ms-6">
                            <Dropdown align="right" width="48">
                                <template #trigger>
                                    <button type="button" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-bold rounded-md text-gray-500 bg-white hover:text-gray-700 transition uppercase">
                                        {{ user.name }} ▼
                                    </button>
                                </template>
                                <template #content>
                                    <DropdownLink :href="route('profile.edit')"> Profilo </DropdownLink>
                                    <DropdownLink :href="route('logout')" method="post" as="button"> Esci </DropdownLink>
                                </template>
                            </Dropdown>
                        </div>
                    </div>
                </div>
            </nav>

            <main>
                <slot />
            </main>
        </div>
    </div>
</template>