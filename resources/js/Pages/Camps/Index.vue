<script setup lang="ts">
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { Camp } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { CalendarDays, DoorOpen, Euro, Layers3, MapPin, Settings, Users } from 'lucide-vue-next';

defineProps<{
    camps: Camp[];
}>();

const page = usePage();

const formatMoney = (value: string) =>
    new Intl.NumberFormat('pt-PT', {
        style: 'currency',
        currency: 'EUR',
    }).format(Number(value));

const pendingFinancialCount = (camp: Camp) =>
    (camp.payments_pending ?? 0) + (camp.payments_partial ?? 0);

const resolvedFinancialCount = (camp: Camp) =>
    (camp.payments_paid ?? 0) + (camp.payments_exempted ?? 0);

const removeCamp = (camp: Camp) => {
    if (!window.confirm(`Remover ${camp.name}?`)) {
        return;
    }

    router.delete(route('camps.destroy', camp.id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Acampamentos" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-app-text">
                        Acampamentos
                    </h2>
                    <p class="mt-1 text-sm text-app-subtle">
                        Escolha um acampamento para organizar pessoas, operacao e financeiro.
                    </p>
                </div>

                <Link :href="route('camps.create')">
                    <PrimaryButton type="button">
                        <CalendarDays class="mr-2 h-4 w-4" aria-hidden="true" />
                        Novo acampamento
                    </PrimaryButton>
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

                <div v-if="camps.length === 0" class="app-card p-8 text-center">
                    <h3 class="text-lg font-semibold text-app-text">
                        Nenhum acampamento cadastrado
                    </h3>
                    <p class="mt-2 text-sm text-app-subtle">
                        Nenhum acampamento cadastrado ainda.
                    </p>
                    <Link :href="route('camps.create')" class="mt-5 inline-flex">
                        <PrimaryButton type="button">Criar acampamento</PrimaryButton>
                    </Link>
                </div>

                <div class="grid gap-4 xl:grid-cols-2">
                    <article
                        v-for="camp in camps"
                        :key="camp.id"
                        class="app-card overflow-hidden"
                    >
                        <div class="p-5">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span
                                            class="rounded-full px-3 py-1 text-xs font-medium"
                                            :class="
                                                camp.is_active
                                                    ? 'bg-brand-100 text-brand-800'
                                                    : 'bg-app-muted text-app-subtle'
                                            "
                                        >
                                            {{ camp.is_active ? 'Ativo' : 'Inativo' }}
                                        </span>
                                        <span class="inline-flex items-center gap-1 rounded-full bg-app-muted px-3 py-1 text-xs font-medium text-app-subtle">
                                            <CalendarDays class="h-3.5 w-3.5" aria-hidden="true" />
                                            {{ camp.date_range_label ?? camp.starts_on_label }}
                                        </span>
                                    </div>

                                    <h3 class="mt-4 text-xl font-semibold text-app-text">
                                        {{ camp.name }}
                                    </h3>
                                    <p
                                        v-if="camp.description"
                                        class="mt-2 max-w-2xl text-sm leading-5 text-app-subtle"
                                    >
                                        {{ camp.description }}
                                    </p>
                                    <p class="mt-2 flex items-center gap-1.5 text-sm text-app-subtle">
                                        <MapPin class="h-4 w-4 shrink-0" aria-hidden="true" />
                                        <span>{{ camp.address ?? 'Endereco nao definido' }}</span>
                                    </p>
                                    <p class="mt-1 text-sm text-app-subtle">
                                        Valor base {{ formatMoney(camp.amount) }}
                                    </p>
                                </div>

                                <Link :href="route('camps.show', camp.id)">
                                    <PrimaryButton type="button">
                                        <DoorOpen class="mr-2 h-4 w-4" aria-hidden="true" />
                                        Entrar
                                    </PrimaryButton>
                                </Link>
                            </div>

                            <dl class="mt-5 grid grid-cols-2 gap-3 text-sm lg:grid-cols-4">
                                <div class="rounded-lg border border-app-border bg-app-muted/60 p-3">
                                    <dt class="flex items-center gap-1.5 text-app-soft">
                                        <Users class="h-4 w-4" aria-hidden="true" />
                                        Pessoas
                                    </dt>
                                    <dd class="mt-2 text-xl font-semibold text-app-text">
                                        {{ camp.payments_total ?? 0 }}
                                    </dd>
                                </div>
                                <div class="rounded-lg border border-app-border bg-app-muted/60 p-3">
                                    <dt class="flex items-center gap-1.5 text-app-soft">
                                        <Euro class="h-4 w-4" aria-hidden="true" />
                                        Pendencias
                                    </dt>
                                    <dd class="mt-2 text-xl font-semibold text-warning">
                                        {{ pendingFinancialCount(camp) }}
                                    </dd>
                                </div>
                                <div class="rounded-lg border border-app-border bg-app-muted/60 p-3">
                                    <dt class="flex items-center gap-1.5 text-app-soft">
                                        <Euro class="h-4 w-4" aria-hidden="true" />
                                        Resolvidos
                                    </dt>
                                    <dd class="mt-2 text-xl font-semibold text-success">
                                        {{ resolvedFinancialCount(camp) }}
                                    </dd>
                                </div>
                                <div class="rounded-lg border border-app-border bg-app-muted/60 p-3">
                                    <dt class="flex items-center gap-1.5 text-app-soft">
                                        <Layers3 class="h-4 w-4" aria-hidden="true" />
                                        Lotes
                                    </dt>
                                    <dd class="mt-2 text-xl font-semibold text-app-text">
                                        {{ camp.lots.length }}
                                    </dd>
                                </div>
                            </dl>

                            <div class="mt-5 flex flex-wrap gap-2">
                                <span
                                    v-for="lot in camp.lots"
                                    :key="lot.id"
                                    class="rounded-md bg-app-muted px-3 py-1 text-sm text-app-subtle"
                                >
                                    {{ lot.name }} - {{ formatMoney(lot.amount) }}
                                </span>
                            </div>

                            <div class="mt-5 flex flex-wrap justify-end gap-3 border-t border-app-border pt-5">
                                <Link :href="route('camps.edit', camp.id)">
                                    <SecondaryButton type="button">
                                        <Settings class="mr-2 h-4 w-4" aria-hidden="true" />
                                        Editar
                                    </SecondaryButton>
                                </Link>
                                <button
                                    type="button"
                                    class="text-sm font-medium text-danger hover:text-danger/80 focus:outline-none focus:ring-2 focus:ring-danger focus:ring-offset-2"
                                    @click="removeCamp(camp)"
                                >
                                    Remover
                                </button>
                            </div>
                        </div>
                    </article>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
