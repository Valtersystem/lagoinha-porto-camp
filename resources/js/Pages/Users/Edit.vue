<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import UserForm from '@/Pages/Users/Partials/UserForm.vue';
import type { ManagedUser, Role } from '@/types';
import { Head } from '@inertiajs/vue3';

defineProps<{
    managedUser: ManagedUser;
    roles: Role[];
}>();
</script>

<template>
    <Head :title="`Editar ${managedUser.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Editar usuario
            </h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                <div class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <UserForm
                        :roles="roles"
                        :managed-user="managedUser"
                        mode="edit"
                    />
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
