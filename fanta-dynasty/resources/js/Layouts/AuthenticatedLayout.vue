<script setup>
import { ref } from 'vue';
import NavLink from '@/Components/NavLink.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import { Link, usePage } from '@inertiajs/vue3';

const user = usePage().props.auth.user;
const isAdmin = usePage().props.auth.is_admin;
</script>

<template>
    <div>
        <div class="min-h-screen bg-gray-100">
            <nav class="bg-white border-b border-gray-100 shadow-sm">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between h-16">
                        <div class="flex">
                            <div class="shrink-0 flex items-center">
                                <Link :href="route('dashboard')" class="text-xl font-black text-blue-600 italic">FANTA-DYNASTY</Link>
                            </div>
                            <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                                <NavLink :href="route('dashboard')" :active="route().current('dashboard')">Home</NavLink>
                                <NavLink :href="route('teams.index')" :active="route().current('teams.index')">Squadre</NavLink>
                                <!-- TASTO ROSA -->
                                <NavLink :href="route('roster.index')" :active="route().current('roster.index')">Rosa</NavLink>
                                <NavLink :href="route('market.auctions')" :active="route().current('market.auctions')">Calciomercato</NavLink>
                                <NavLink :href="route('players.index')" :active="route().current('players.index')">Svincolati</NavLink>

                                <div v-if="isAdmin" class="hidden sm:flex sm:items-center ms-4">
                                    <Dropdown align="right" width="48">
                                        <template #trigger>
                                           <button class="inline-flex items-center text-sm font-bold text-red-600 uppercase">⚙️ Gestione</button>
                                        </template>
                                        <template #content>
                                            <DropdownLink :href="route('market.sessions')">Nuova Sessione</DropdownLink>
                                            <DropdownLink :href="route('market.history')">Cronologia</DropdownLink>
                                        </template>
                                    </Dropdown>
                                </div>
                            </div>
                        </div>
                        <div class="hidden sm:flex sm:items-center">
                            <Dropdown align="right" width="48">
                                <template #trigger><button class="font-bold text-gray-500 uppercase text-sm">{{ user.name }} ▼</button></template>
                                <template #content>
    <DropdownLink :href="route('profile.edit')">Profilo</DropdownLink>

    <DropdownLink :href="route('logout')" method="post" as="button">
        Esci
    </DropdownLink>
</template>
                            </Dropdown>
                        </div>
                    </div>
                </div>
            </nav>
            <main><slot /></main>
        </div>
    </div>
</template>