<script setup lang="ts">
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { Camp } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    ArrowLeft,
    CalendarDays,
    CheckCircle2,
    ClipboardCheck,
    DoorOpen,
    Euro,
    Layers3,
    MapPin,
    Settings,
    Users,
} from 'lucide-vue-next';
import { computed } from 'vue';
import type { Component } from 'vue';

const props = defineProps<{
    camp: Camp;
    financialSummary: {
        total_due: string;
        received: string;
        open: string;
    };
    verificationSummary: {
        points_total: number;
        presence_points: number;
        check_in_points: number;
        entries_total: number;
    };
}>();

const page = usePage();

const formatMoney = (value: string) =>
    new Intl.NumberFormat('pt-PT', {
        style: 'currency',
        currency: 'EUR',
    }).format(Number(value));

const linkedUsers = computed(() => props.camp.payments_total ?? 0);
const pendingFinancial = computed(
    () => (props.camp.payments_pending ?? 0) + (props.camp.payments_partial ?? 0),
);
const resolvedFinancial = computed(
    () => (props.camp.payments_paid ?? 0) + (props.camp.payments_exempted ?? 0),
);

const adminAreas = computed<
    {
        title: string;
        value: string;
        subtitle: string;
        href?: string;
        action: string;
        icon: Component;
        available: boolean;
    }[]
>(() => [
    {
        title: 'Pessoas do acampamento',
        value: String(linkedUsers.value),
        subtitle: 'Usuarios vinculados, lotes e parcelas.',
        href: route('camps.payments.index', props.camp.id),
        action: 'Gerir pessoas',
        icon: Users,
        available: true,
    },
    {
        title: 'Equipes',
        value: String(props.camp.teams_total ?? 0),
        subtitle: 'Grupos, lideres e membros do acampamento.',
        href: route('camps.teams.index', props.camp.id),
        action: 'Gerir equipes',
        icon: Users,
        available: true,
    },
    {
        title: 'Financeiro',
        value: formatMoney(props.financialSummary.open),
        subtitle: 'Valor em aberto neste acampamento.',
        href: route('camps.payments.index', props.camp.id),
        action: 'Ver financeiro',
        icon: Euro,
        available: true,
    },
    {
        title: 'Presenca e check-in',
        value: String(props.verificationSummary.points_total),
        subtitle: `${props.verificationSummary.entries_total} verificacoes registadas neste acampamento.`,
        href: route('camps.verification-points.index', props.camp.id),
        action: 'Abrir operacao',
        icon: ClipboardCheck,
        available: true,
    },
    {
        title: 'Quartos',
        value: String(props.camp.rooms_total ?? 0),
        subtitle: 'Distribuicao de hospedagem por pessoa.',
        href: route('camps.rooms.index', props.camp.id),
        action: 'Organizar quartos',
        icon: DoorOpen,
        available: true,
    },
]);
</script>

<template>
    <Head :title="`${camp.name} - Central do acampamento`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="flex items-center gap-2 text-sm text-app-subtle">
                        <Link
                            :href="route('camps.index')"
                            class="inline-flex items-center gap-1 rounded-md font-medium hover:text-brand-800 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2"
                        >
                            <ArrowLeft class="h-4 w-4" aria-hidden="true" />
                            Acampamentos
                        </Link>
                    </div>
                    <h2 class="mt-1 text-xl font-semibold leading-tight text-app-text">
                        {{ camp.name }}
                    </h2>
                    <p class="mt-1 text-sm text-app-subtle">
                        Central de organizacao do admin
                    </p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <Link :href="route('camps.edit', camp.id)">
                        <SecondaryButton type="button">
                            <Settings class="mr-2 h-4 w-4" aria-hidden="true" />
                            Configurar
                        </SecondaryButton>
                    </Link>
                    <Link :href="route('camps.payments.index', camp.id)">
                        <PrimaryButton type="button">
                            <Users class="mr-2 h-4 w-4" aria-hidden="true" />
                            Pessoas
                        </PrimaryButton>
                    </Link>
                    <Link :href="route('camps.teams.index', camp.id)">
                        <SecondaryButton type="button">
                            <Users class="mr-2 h-4 w-4" aria-hidden="true" />
                            Equipes
                        </SecondaryButton>
                    </Link>
                    <Link :href="route('camps.rooms.index', camp.id)">
                        <SecondaryButton type="button">
                            <DoorOpen class="mr-2 h-4 w-4" aria-hidden="true" />
                            Quartos
                        </SecondaryButton>
                    </Link>
                    <Link :href="route('camps.verification-points.index', camp.id)">
                        <SecondaryButton type="button">
                            <ClipboardCheck class="mr-2 h-4 w-4" aria-hidden="true" />
                            Operacao
                        </SecondaryButton>
                    </Link>
                </div>
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

                <section class="app-card overflow-hidden">
                    <div class="grid gap-0 lg:grid-cols-[1.15fr_0.85fr]">
                        <div class="border-b border-app-border p-6 lg:border-b-0 lg:border-r">
                            <div class="flex flex-wrap items-center gap-2">
                                <span
                                    class="inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-medium"
                                    :class="
                                        camp.is_active
                                            ? 'bg-brand-100 text-brand-800'
                                            : 'bg-app-muted text-app-subtle'
                                    "
                                >
                                    <CheckCircle2
                                        v-if="camp.is_active"
                                        class="h-3.5 w-3.5"
                                        aria-hidden="true"
                                    />
                                    {{ camp.is_active ? 'Ativo' : 'Inativo' }}
                                </span>
                                <span class="inline-flex items-center gap-1 rounded-full bg-app-muted px-3 py-1 text-xs font-medium text-app-subtle">
                                    <CalendarDays class="h-3.5 w-3.5" aria-hidden="true" />
                                    {{ camp.date_range_label ?? camp.starts_on_label }}
                                </span>
                                <span class="inline-flex items-center gap-1 rounded-full bg-app-muted px-3 py-1 text-xs font-medium text-app-subtle">
                                    <Layers3 class="h-3.5 w-3.5" aria-hidden="true" />
                                    {{ camp.lots.length }} lote{{ camp.lots.length === 1 ? '' : 's' }}
                                </span>
                            </div>

                            <h3 class="mt-5 text-2xl font-semibold tracking-normal text-app-text">
                                Ecossistema do acampamento
                            </h3>
                            <p class="mt-2 max-w-2xl text-sm leading-6 text-app-subtle">
                                {{ camp.description || 'Acesso rapido as frentes que o administrador precisa acompanhar antes e durante o evento.' }}
                            </p>
                            <p class="mt-3 flex items-center gap-1.5 text-sm text-app-subtle">
                                <MapPin class="h-4 w-4 shrink-0" aria-hidden="true" />
                                <span>{{ camp.address ?? 'Endereco nao definido' }}</span>
                            </p>

                            <div class="mt-6 grid gap-3 sm:grid-cols-3">
                                <div class="rounded-lg border border-app-border bg-app-muted/60 p-4">
                                    <dt class="text-sm text-app-soft">Pessoas vinculadas</dt>
                                    <dd class="mt-1 text-2xl font-semibold text-app-text">
                                        {{ linkedUsers }}
                                    </dd>
                                </div>
                                <div class="rounded-lg border border-app-border bg-app-muted/60 p-4">
                                    <dt class="text-sm text-app-soft">Pendencias</dt>
                                    <dd class="mt-1 text-2xl font-semibold text-warning">
                                        {{ pendingFinancial }}
                                    </dd>
                                </div>
                                <div class="rounded-lg border border-app-border bg-app-muted/60 p-4">
                                    <dt class="text-sm text-app-soft">Resolvidos</dt>
                                    <dd class="mt-1 text-2xl font-semibold text-success">
                                        {{ resolvedFinancial }}
                                    </dd>
                                </div>
                            </div>
                        </div>

                        <div class="p-6">
                            <h3 class="text-sm font-semibold uppercase tracking-wider text-app-soft">
                                Financeiro
                            </h3>
                            <dl class="mt-4 space-y-4">
                                <div class="flex items-center justify-between gap-4">
                                    <dt class="text-sm text-app-subtle">A receber</dt>
                                    <dd class="text-lg font-semibold text-app-text">
                                        {{ formatMoney(financialSummary.total_due) }}
                                    </dd>
                                </div>
                                <div class="flex items-center justify-between gap-4">
                                    <dt class="text-sm text-app-subtle">Recebido</dt>
                                    <dd class="text-lg font-semibold text-success">
                                        {{ formatMoney(financialSummary.received) }}
                                    </dd>
                                </div>
                                <div class="flex items-center justify-between gap-4 border-t border-app-border pt-4">
                                    <dt class="text-sm font-medium text-app-text">Em aberto</dt>
                                    <dd class="text-2xl font-semibold text-warning">
                                        {{ formatMoney(financialSummary.open) }}
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </section>

                <section>
                    <div class="mb-4 flex items-center justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-semibold text-app-text">
                                Areas do admin
                            </h3>
                            <p class="mt-1 text-sm text-app-subtle">
                                Organizacao do acampamento em um so lugar.
                            </p>
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        <component
                            :is="area.href ? Link : 'article'"
                            v-for="area in adminAreas"
                            :key="area.title"
                            :href="area.href"
                            class="app-card group block p-5 transition"
                            :class="
                                area.available
                                    ? 'hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-panel'
                                    : 'opacity-70'
                            "
                            :aria-disabled="area.available ? undefined : true"
                        >
                            <div class="flex items-start justify-between gap-4">
                                <span
                                    class="flex h-11 w-11 items-center justify-center rounded-lg"
                                    :class="
                                        area.available
                                            ? 'bg-brand-100 text-brand-800'
                                            : 'bg-app-muted text-app-soft'
                                    "
                                    aria-hidden="true"
                                >
                                    <component :is="area.icon" class="h-5 w-5" />
                                </span>
                                <span
                                    class="rounded-full px-2.5 py-1 text-xs font-medium"
                                    :class="
                                        area.available
                                            ? 'bg-brand-50 text-brand-800'
                                            : 'bg-app-muted text-app-soft'
                                    "
                                >
                                    {{ area.action }}
                                </span>
                            </div>

                            <h4 class="mt-5 text-base font-semibold text-app-text">
                                {{ area.title }}
                            </h4>
                            <p class="mt-2 text-3xl font-semibold text-app-text">
                                {{ area.value }}
                            </p>
                            <p class="mt-2 text-sm leading-5 text-app-subtle">
                                {{ area.subtitle }}
                            </p>
                        </component>
                    </div>
                </section>

                <section class="grid gap-4 lg:grid-cols-[1fr_360px]">
                    <div class="app-card p-5">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <h3 class="text-lg font-semibold text-app-text">
                                    Lotes do acampamento
                                </h3>
                                <p class="mt-1 text-sm text-app-subtle">
                                    Valores configurados para vinculo de participantes.
                                </p>
                            </div>
                            <Link :href="route('camps.edit', camp.id)">
                                <SecondaryButton type="button">Editar</SecondaryButton>
                            </Link>
                        </div>

                        <div class="mt-5 grid gap-3 sm:grid-cols-2">
                            <div
                                v-for="lot in camp.lots"
                                :key="lot.id"
                                class="rounded-lg border border-app-border bg-app-muted/60 px-4 py-3"
                            >
                                <p class="text-sm font-medium text-app-text">
                                    {{ lot.name }}
                                </p>
                                <p class="mt-1 text-sm text-app-subtle">
                                    {{ formatMoney(lot.amount) }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="app-card p-5">
                        <h3 class="text-lg font-semibold text-app-text">
                            Proximo passo
                        </h3>
                        <p class="mt-2 text-sm leading-6 text-app-subtle">
                            Comece vinculando os usuarios deste acampamento. Cada vinculo
                            ja nasce com obrigacao financeira, parcelas e lote.
                        </p>
                        <Link
                            :href="route('camps.payments.index', camp.id)"
                            class="mt-5 inline-flex"
                        >
                            <PrimaryButton type="button">Abrir pessoas</PrimaryButton>
                        </Link>
                    </div>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
