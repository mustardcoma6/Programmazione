<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useForm, Head } from '@inertiajs/vue3';

// Inizializziamo il modulo con i campi necessari
const form = useForm({
    name: '',
    initial_budget: 500, // Valore di default
});

// Funzione che "spara" i dati al server quando premiamo il tasto
const submit = () => {
    form.post(route('leagues.store'), {
        onSuccess: () => {
            alert('Lega creata con successo!');
        },
    });
};
</script>

<template>
    <Head title="Crea Lega" />

    <AuthenticatedLayout>
        <!-- Titolo della pagina in alto -->
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Crea la tua nuova Lega
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-8">
                    
                    <!-- Inizio Modulo -->
                    <form @submit.prevent="submit" class="max-w-md space-y-6">
                        
                        <!-- Campo Nome Lega -->
                        <div>
                            <label for="name" class="block font-medium text-sm text-gray-700">Nome della Lega</label>
                            <input 
                                id="name"
                                v-model="form.name" 
                                type="text" 
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" 
                                placeholder="Es: Lega degli Amici"
                                required
                            >
                            <div v-if="form.errors.name" class="text-red-600 text-sm mt-1">{{ form.errors.name }}</div>
                        </div>

                        <!-- Campo Budget -->
                        <div>
                            <label for="budget" class="block font-medium text-sm text-gray-700">Budget Iniziale (Crediti)</label>
                            <input 
                                id="budget"
                                v-model="form.initial_budget" 
                                type="number" 
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" 
                                min="100"
                                max="1000"
                                required
                            >
                            <div v-if="form.errors.initial_budget" class="text-red-600 text-sm mt-1">{{ form.errors.initial_budget }}</div>
                        </div>

                        <!-- Tasto di Invio -->
                        <div class="flex items-center gap-4">
                            <button 
                                type="submit" 
                                class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                :disabled="form.processing"
                            >
                                Salva e Crea Lega
                            </button>
                            
                            <span v-if="form.processing" class="text-sm text-gray-500 italic">Creazione in corso...</span>
                        </div>

                    </form>
                    <!-- Fine Modulo -->

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>