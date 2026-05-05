<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import UserForm from '@/Pages/Users/Partials/UserForm.vue';
import type { Role } from '@/types';
import { Head } from '@inertiajs/vue3';

defineProps<{
    roles: Role[];
}>();
</script>

<template>
    <Head title="Novo usuario" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Novo usuario
            </h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <UserForm :roles="roles" mode="create" />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
