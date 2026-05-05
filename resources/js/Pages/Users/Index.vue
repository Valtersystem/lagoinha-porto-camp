<script setup lang="ts">
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { ManagedUser, Role } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedUsers {
    data: ManagedUser[];
    links: PaginationLink[];
    from: number | null;
    to: number | null;
    total: number;
}

const props = defineProps<{
    users: PaginatedUsers;
    roles: Role[];
    filters: {
        search: string;
        role: string;
    };
}>();

const page = usePage();
const search = ref(props.filters.search ?? '');
const selectedRole = ref(props.filters.role ?? '');

const applyFilters = () => {
    router.get(
        route('users.index'),
        {
            search: search.value,
            role: selectedRole.value,
        },
        {
            preserveState: true,
            replace: true,
        },
    );
};

const clearFilters = () => {
    search.value = '';
    selectedRole.value = '';
    applyFilters();
};

const removeUser = (user: ManagedUser) => {
    if (user.is_current_user) {
        return;
    }

    if (! window.confirm(`Remover ${user.name}?`)) {
        return;
    }

    router.delete(route('users.destroy', user.id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Usuarios" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">
                        Usuarios
                    </h2>
                    <p class="mt-1 text-sm text-gray-500">
                        Gestao de administradores, monitores, lideres e participantes.
                    </p>
                </div>

                <Link :href="route('users.create')">
                    <PrimaryButton type="button">Novo usuario</PrimaryButton>
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div
                    v-if="page.props.flash?.status"
                    class="rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800"
                    role="status"
                >
                    {{ page.props.flash.status }}
                </div>

                <form
                    class="grid gap-3 bg-white p-4 shadow-sm sm:rounded-lg md:grid-cols-[1fr_220px_auto]"
                    @submit.prevent="applyFilters"
                >
                    <label class="block">
                        <span class="sr-only">Buscar usuario</span>
                        <TextInput
                            v-model="search"
                            type="search"
                            class="block w-full"
                            placeholder="Buscar por nome, email ou telefone"
                        />
                    </label>

                    <label class="block">
                        <span class="sr-only">Filtrar por papel</span>
                        <select
                            v-model="selectedRole"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">Todos os papeis</option>
                            <option
                                v-for="role in roles"
                                :key="role.id"
                                :value="role.key"
                            >
                                {{ role.name }}
                            </option>
                        </select>
                    </label>

                    <div class="flex gap-2">
                        <PrimaryButton type="submit">Filtrar</PrimaryButton>
                        <SecondaryButton type="button" @click="clearFilters">
                            Limpar
                        </SecondaryButton>
                    </div>
                </form>

                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th
                                        scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                    >
                                        Nome
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                    >
                                        Papel
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                    >
                                        Sexo
                                    </th>
                                    <th
                                        scope="col"
                                        class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500"
                                    >
                                        Contato
                                    </th>
                                    <th scope="col" class="relative px-6 py-3">
                                        <span class="sr-only">Acoes</span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                <tr v-if="users.data.length === 0">
                                    <td
                                        colspan="5"
                                        class="px-6 py-10 text-center text-sm text-gray-500"
                                    >
                                        Nenhum usuario encontrado.
                                    </td>
                                </tr>
                                <tr
                                    v-for="user in users.data"
                                    :key="user.id"
                                    class="align-top"
                                >
                                    <td class="px-6 py-4">
                                        <Link
                                            :href="route('users.show', user.id)"
                                            class="font-medium text-gray-900 hover:text-brand-800"
                                        >
                                            {{ user.name }}
                                        </Link>
                                        <div
                                            v-if="user.is_current_user"
                                            class="mt-1 text-xs text-gray-500"
                                        >
                                            Seu usuario
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        {{ user.role?.name ?? 'Sem papel' }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        {{ user.sex_label ?? 'Nao definido' }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        <div>{{ user.email }}</div>
                                        <div class="mt-1 text-xs text-gray-500">
                                            {{ user.phone ?? 'Sem telefone' }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-right text-sm">
                                        <div class="flex justify-end gap-3">
                                            <Link
                                                :href="route('users.show', user.id)"
                                                class="font-medium text-brand-700 hover:text-brand-900 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2"
                                            >
                                                Ver
                                            </Link>
                                            <Link
                                                :href="route('users.edit', user.id)"
                                                class="font-medium text-indigo-600 hover:text-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                                            >
                                                Editar
                                            </Link>
                                            <button
                                                type="button"
                                                class="font-medium text-red-600 hover:text-red-900 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:text-gray-400"
                                                :disabled="user.is_current_user"
                                                @click="removeUser(user)"
                                            >
                                                Remover
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div
                        class="flex flex-col gap-3 border-t border-gray-200 px-6 py-4 text-sm text-gray-600 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <span>
                            Mostrando {{ users.from ?? 0 }} a {{ users.to ?? 0 }}
                            de {{ users.total }} usuarios
                        </span>

                        <nav
                            class="flex flex-wrap gap-2"
                            aria-label="Paginacao de usuarios"
                        >
                            <component
                                :is="link.url ? Link : 'span'"
                                v-for="link in users.links"
                                :key="link.label"
                                :href="link.url || undefined"
                                class="rounded-md border px-3 py-1"
                                :class="[
                                    link.active
                                        ? 'border-gray-900 bg-gray-900 text-white'
                                        : 'border-gray-300 text-gray-700',
                                    !link.url ? 'cursor-not-allowed opacity-50' : 'hover:bg-gray-50',
                                ]"
                                v-html="link.label"
                            />
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
