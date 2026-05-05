<script setup lang="ts">
import ParticipantBadgePreview from '@/Components/ParticipantBadgePreview.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { ManagedUser, PageProps } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    ArrowLeft,
    CalendarDays,
    ClipboardCheck,
    DoorOpen,
    Mail,
    Phone,
    UserCheck,
    Users,
} from 'lucide-vue-next';
import { computed } from 'vue';

interface UserParticipation {
    id: number;
    status: string;
    status_label: string;
    installments_count: number;
    paid_installments: number;
    notes?: string | null;
    camp: {
        id: number;
        name: string;
        is_active: boolean;
        date_range_label?: string | null;
    };
    room?: {
        id: number;
        name: string;
    } | null;
    team?: {
        id: number;
        name: string;
    } | null;
    lot?: {
        id: number;
        name: string;
    } | null;
}

const props = defineProps<{
    managedUser: ManagedUser;
    currentParticipation?: UserParticipation | null;
    participations: UserParticipation[];
    operationStatus: {
        presence: string;
        check_in: string;
        photo: string;
    };
}>();

const page = usePage<PageProps>();

const initials = computed(() =>
    props.managedUser.name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase())
        .join(''),
);

const backHref = computed(() =>
    page.props.auth.can.manageUsers ? route('users.index') : route('dashboard'),
);

const backLabel = computed(() =>
    page.props.auth.can.manageUsers ? 'Usuarios' : 'Painel',
);

const currentRoomLabel = computed(
    () => props.currentParticipation?.room?.name ?? 'Sem quarto definido',
);

const currentCampLabel = computed(
    () => props.currentParticipation?.camp.name ?? 'Sem acampamento vinculado',
);

const currentTeamLabel = computed(
    () => props.currentParticipation?.team?.name ?? 'Sem equipe definida',
);

const canOpenCamp = computed(() => page.props.auth.can.manageCamps);
</script>

<template>
    <Head :title="`${managedUser.name} - Perfil do usuario`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <Link
                        :href="backHref"
                        class="inline-flex items-center gap-1 rounded-md text-sm font-medium text-app-subtle hover:text-brand-800 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2"
                    >
                        <ArrowLeft class="h-4 w-4" aria-hidden="true" />
                        {{ backLabel }}
                    </Link>
                    <h2 class="mt-1 text-xl font-semibold leading-tight text-app-text">
                        Perfil do usuario
                    </h2>
                    <p class="mt-1 text-sm text-app-subtle">
                        {{ managedUser.name }} - {{ managedUser.role?.name ?? 'Sem papel definido' }}
                    </p>
                </div>

                <Link
                    v-if="page.props.auth.can.manageUsers"
                    :href="route('users.edit', managedUser.id)"
                >
                    <SecondaryButton type="button">Editar usuario</SecondaryButton>
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <section class="grid gap-6 lg:grid-cols-[320px_1fr]">
                    <div class="app-card p-6">
                        <div class="flex flex-col items-center text-center">
                            <div
                                class="flex h-28 w-28 items-center justify-center overflow-hidden rounded-full bg-brand-100 text-3xl font-semibold text-brand-800"
                            >
                                <img
                                    v-if="managedUser.photo_url"
                                    :src="managedUser.photo_url"
                                    :alt="`Foto de ${managedUser.name}`"
                                    class="h-full w-full object-cover"
                                />
                                <template v-else>
                                    {{ initials }}
                                </template>
                            </div>
                            <h3 class="mt-4 text-xl font-semibold text-app-text">
                                {{ managedUser.name }}
                            </h3>
                            <p class="mt-1 text-sm text-app-subtle">
                                {{ managedUser.role?.name ?? 'Sem papel definido' }}
                            </p>
                            <span
                                class="mt-3 rounded-full bg-app-muted px-3 py-1 text-xs font-medium text-app-subtle"
                            >
                                {{ operationStatus.photo }}
                            </span>
                        </div>

                        <dl class="mt-6 space-y-4">
                            <div>
                                <dt class="text-xs font-medium uppercase tracking-wider text-app-soft">
                                    Email
                                </dt>
                                <dd class="mt-1 flex items-center gap-2 text-sm text-app-text">
                                    <Mail class="h-4 w-4 text-app-soft" aria-hidden="true" />
                                    <span>{{ managedUser.email }}</span>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium uppercase tracking-wider text-app-soft">
                                    Telefone
                                </dt>
                                <dd class="mt-1 flex items-center gap-2 text-sm text-app-text">
                                    <Phone class="h-4 w-4 text-app-soft" aria-hidden="true" />
                                    <span>{{ managedUser.phone ?? 'Sem telefone' }}</span>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-xs font-medium uppercase tracking-wider text-app-soft">
                                    Sexo
                                </dt>
                                <dd class="mt-1 text-sm text-app-text">
                                    {{ managedUser.sex_label ?? 'Nao definido' }}
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <div class="space-y-6">
                        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                            <div class="app-card p-4">
                                <dt class="text-sm text-app-soft">Acampamento atual</dt>
                                <dd class="mt-2 text-base font-semibold text-app-text">
                                    {{ currentCampLabel }}
                                </dd>
                                <p
                                    v-if="currentParticipation?.camp.date_range_label"
                                    class="mt-1 text-sm text-app-subtle"
                                >
                                    {{ currentParticipation.camp.date_range_label }}
                                </p>
                            </div>

                            <div class="app-card p-4">
                                <dt class="text-sm text-app-soft">Quarto</dt>
                                <dd class="mt-2 text-base font-semibold text-app-text">
                                    {{ currentRoomLabel }}
                                </dd>
                            </div>

                            <div class="app-card p-4">
                                <dt class="text-sm text-app-soft">Equipe</dt>
                                <dd class="mt-2 text-base font-semibold text-app-text">
                                    {{ currentTeamLabel }}
                                </dd>
                            </div>

                            <div class="app-card p-4">
                                <dt class="text-sm text-app-soft">Financeiro</dt>
                                <dd class="mt-2 text-base font-semibold text-app-text">
                                    {{ currentParticipation?.status_label ?? 'Sem vinculacao' }}
                                </dd>
                                <p
                                    v-if="currentParticipation"
                                    class="mt-1 text-sm text-app-subtle"
                                >
                                    {{ currentParticipation.paid_installments }}/{{ currentParticipation.installments_count }} parcelas
                                </p>
                            </div>
                        </div>

                        <section class="app-card p-5">
                            <div>
                                <h3 class="text-lg font-semibold text-app-text">
                                    Situacao operacional
                                </h3>
                                <p class="mt-1 text-sm text-app-subtle">
                                    Ultimos registos de presenca e check-in para a participacao atual.
                                </p>
                            </div>

                            <div class="mt-4 grid gap-3 md:grid-cols-3">
                                <div class="rounded-lg border border-app-border bg-app-muted/60 p-4">
                                    <dt class="flex items-center gap-2 text-sm text-app-soft">
                                        <Users class="h-4 w-4" aria-hidden="true" />
                                        Presenca
                                    </dt>
                                    <dd class="mt-2 text-lg font-semibold text-app-text">
                                        {{ operationStatus.presence }}
                                    </dd>
                                </div>
                                <div class="rounded-lg border border-app-border bg-app-muted/60 p-4">
                                    <dt class="flex items-center gap-2 text-sm text-app-soft">
                                        <UserCheck class="h-4 w-4" aria-hidden="true" />
                                        Check-in
                                    </dt>
                                    <dd class="mt-2 text-lg font-semibold text-app-text">
                                        {{ operationStatus.check_in }}
                                    </dd>
                                </div>
                                <div class="rounded-lg border border-app-border bg-app-muted/60 p-4">
                                    <dt class="flex items-center gap-2 text-sm text-app-soft">
                                        <ClipboardCheck class="h-4 w-4" aria-hidden="true" />
                                        Papel
                                    </dt>
                                    <dd class="mt-2 text-lg font-semibold text-app-text">
                                        {{ managedUser.role?.name ?? 'Sem papel definido' }}
                                    </dd>
                                </div>
                            </div>
                        </section>

                        <section class="app-card p-5">
                            <div>
                                <h3 class="text-lg font-semibold text-app-text">
                                    Cracha do participante
                                </h3>
                                <p class="mt-1 text-sm text-app-subtle">
                                    Preview mobile-first com frente e verso para uso no evento.
                                </p>
                            </div>

                            <div class="mt-4">
                                <ParticipantBadgePreview
                                    :user="managedUser"
                                    :participation="currentParticipation"
                                />
                            </div>
                        </section>
                    </div>
                </section>

                <section class="app-card overflow-hidden">
                    <div class="border-b border-app-border px-5 py-4">
                        <h3 class="text-lg font-semibold text-app-text">
                            Participacao em acampamentos
                        </h3>
                        <p class="mt-1 text-sm text-app-subtle">
                            Quarto, equipe e situacao financeira por acampamento.
                        </p>
                    </div>

                    <div class="divide-y divide-app-border">
                        <div
                            v-if="participations.length === 0"
                            class="px-5 py-10 text-center text-sm text-app-subtle"
                        >
                            Este usuario ainda nao esta vinculado a nenhum acampamento.
                        </div>

                        <article
                            v-for="participation in participations"
                            :key="participation.id"
                            class="grid gap-4 px-5 py-5 lg:grid-cols-[1.2fr_0.9fr_0.9fr_0.9fr]"
                        >
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <component
                                        :is="canOpenCamp ? Link : 'span'"
                                        :href="canOpenCamp ? route('camps.show', participation.camp.id) : undefined"
                                        class="font-semibold text-app-text"
                                        :class="canOpenCamp ? 'hover:text-brand-800' : ''"
                                    >
                                        {{ participation.camp.name }}
                                    </component>
                                    <span
                                        v-if="participation.camp.is_active"
                                        class="rounded-full bg-brand-100 px-2.5 py-1 text-xs font-medium text-brand-800"
                                    >
                                        Ativo
                                    </span>
                                </div>
                                <p class="mt-1 flex items-center gap-2 text-sm text-app-subtle">
                                    <CalendarDays class="h-4 w-4" aria-hidden="true" />
                                    {{ participation.camp.date_range_label ?? 'Data nao definida' }}
                                </p>
                            </div>

                            <div>
                                <dt class="text-xs font-medium uppercase tracking-wider text-app-soft">
                                    Quarto
                                </dt>
                                <dd class="mt-2 flex items-center gap-2 text-sm text-app-text">
                                    <DoorOpen class="h-4 w-4 text-app-soft" aria-hidden="true" />
                                    <span>{{ participation.room?.name ?? 'Sem quarto' }}</span>
                                </dd>
                            </div>

                            <div>
                                <dt class="text-xs font-medium uppercase tracking-wider text-app-soft">
                                    Equipe
                                </dt>
                                <dd class="mt-2 text-sm text-app-text">
                                    {{ participation.team?.name ?? 'Sem equipe' }}
                                </dd>
                                <p class="mt-1 text-xs text-app-soft">
                                    {{ participation.lot?.name ?? 'Valor base' }}
                                </p>
                            </div>

                            <div>
                                <dt class="text-xs font-medium uppercase tracking-wider text-app-soft">
                                    Financeiro
                                </dt>
                                <dd class="mt-2 text-sm font-semibold text-app-text">
                                    {{ participation.status_label }}
                                </dd>
                                <p class="mt-1 text-xs text-app-soft">
                                    {{ participation.paid_installments }}/{{ participation.installments_count }} parcelas
                                </p>
                                <p
                                    v-if="participation.notes"
                                    class="mt-2 text-xs text-app-soft"
                                >
                                    {{ participation.notes }}
                                </p>
                            </div>
                        </article>
                    </div>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
