<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CampForm from '@/Pages/Camps/Partials/CampForm.vue';
import type { Camp } from '@/types';
import { Head } from '@inertiajs/vue3';

defineProps<{
    camp: Camp;
}>();
</script>

<template>
    <Head :title="`Editar ${camp.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-app-text">
                Editar acampamento
            </h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <div class="app-card p-6">
                    <CampForm :camp="camp" mode="edit" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
