<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { Camp, PageProps } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import {
    ArrowLeft,
    CalendarDays,
    CheckCircle2,
    ClipboardCheck,
    DoorOpen,
    MapPin,
    ShieldCheck,
    Users,
} from 'lucide-vue-next';
import { computed } from 'vue';

interface VerificationPoint {
    id: number;
    name: string;
    type: string;
    type_label: string;
    notes?: string | null;
    point_code: string;
    is_active: boolean;
    verified_count: number;
    pending_count: number;
    participant_url: string;
}

interface Participant {
    id: number;
    status_label: string;
    user: {
        id: number;
        name: string;
        email: string;
        phone?: string | null;
        pin?: string | null;
        sex_label?: string | null;
        role?: {
            id: number;
            key: string;
            name: string;
        } | null;
    };
    team?: {
        id: number;
        name: string;
    } | null;
    room?: {
        id: number;
        name: string;
    } | null;
}

interface VerificationEntry {
    id: number;
    method_label: string;
    verified_at_label?: string | null;
}

const props = defineProps<{
    camp: Camp;
    point: VerificationPoint;
    participant: Participant | null;
    selfVerification: VerificationEntry | null;
    canSelfVerify: boolean;
    unavailableReason?: string | null;
}>();

const page = usePage<PageProps>();
const form = useForm({});
const formErrors = computed(() => form.errors as Record<string, string | undefined>);
const errorMessage = computed(() => formErrors.value.participant ?? formErrors.value.point);

const actionLabel = computed(() =>
    props.point.type === 'check_in' ? 'Fazer check-in' : 'Marcar presenca',
);

const actionDescription = computed(() =>
    props.point.type === 'check_in'
        ? 'Confirme a sua chegada neste ponto do acampamento.'
        : 'Confirme a sua presenca neste momento do acampamento.',
);

const submit = () => {
    form.post(route('camps.verification-points.scan.store', [props.camp.id, props.point.id]), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head :title="`${point.name} - confirmar`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-2">
                <Link
                    :href="route('dashboard')"
                    class="inline-flex items-center gap-1 rounded-md text-sm font-medium text-app-subtle hover:text-brand-800 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2"
                >
                    <ArrowLeft class="h-4 w-4" aria-hidden="true" />
                    Meu acampamento
                </Link>
                <h2 class="text-xl font-semibold leading-tight text-app-text">
                    Confirmar {{ point.type === 'check_in' ? 'check-in' : 'presenca' }}
                </h2>
                <p class="text-sm text-app-subtle">
                    {{ camp.name }} - {{ point.name }}
                </p>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8">
                <div
                    v-if="page.props.flash?.status"
                    class="rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800"
                    role="status"
                >
                    {{ page.props.flash.status }}
                </div>

                <div
                    v-if="errorMessage"
                    class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
                    role="alert"
                >
                    {{ errorMessage }}
                </div>

                <section class="app-card overflow-hidden">
                    <div
                        class="px-5 py-6 sm:px-6"
                        style="
                            background:
                                radial-gradient(circle at top right, rgba(251, 191, 36, 0.18), transparent 26%),
                                radial-gradient(circle at bottom left, rgba(16, 185, 129, 0.16), transparent 42%),
                                linear-gradient(135deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.96));
                        "
                    >
                        <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded-full bg-brand-100 px-3 py-1 text-xs font-medium text-brand-800">
                                        {{ point.type_label }}
                                    </span>
                                    <span
                                        class="rounded-full px-3 py-1 text-xs font-medium"
                                        :class="point.is_active ? 'bg-green-100 text-green-800' : 'bg-app-muted text-app-soft'"
                                    >
                                        {{ point.is_active ? 'Ativo' : 'Inativo' }}
                                    </span>
                                </div>

                                <h3 class="mt-4 text-3xl font-semibold text-app-text">
                                    {{ point.name }}
                                </h3>
                                <p class="mt-2 max-w-2xl text-sm text-app-subtle">
                                    {{ point.notes || actionDescription }}
                                </p>

                                <dl class="mt-5 grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <dt class="flex items-center gap-2 text-xs font-medium uppercase tracking-wider text-app-soft">
                                            <CalendarDays class="h-4 w-4" aria-hidden="true" />
                                            Periodo
                                        </dt>
                                        <dd class="mt-2 text-base font-semibold text-app-text">
                                            {{ camp.date_range_label ?? 'Sem data definida' }}
                                        </dd>
                                    </div>

                                    <div>
                                        <dt class="flex items-center gap-2 text-xs font-medium uppercase tracking-wider text-app-soft">
                                            <MapPin class="h-4 w-4" aria-hidden="true" />
                                            Endereco
                                        </dt>
                                        <dd class="mt-2 text-base font-semibold text-app-text">
                                            {{ camp.address ?? 'Endereco ainda nao informado' }}
                                        </dd>
                                    </div>
                                </dl>
                            </div>

                            <div class="w-full max-w-sm rounded-2xl border border-white/70 bg-white/90 p-4 shadow-sm">
                                <div class="flex items-start gap-3">
                                    <span
                                        class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-brand-700 text-white shadow-card"
                                        aria-hidden="true"
                                    >
                                        <ClipboardCheck class="h-6 w-6" />
                                    </span>

                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-app-soft">
                                            Codigo do ponto
                                        </p>
                                        <p class="mt-1 break-all text-lg font-semibold text-app-text">
                                            {{ point.point_code }}
                                        </p>
                                    </div>
                                </div>

                                <p class="mt-4 text-sm text-app-subtle">
                                    {{ selfVerification
                                        ? `Ja existe um registo neste ponto em ${selfVerification.verified_at_label}.`
                                        : 'Ainda nao existe registo seu neste ponto.' }}
                                </p>

                                <form class="mt-5" @submit.prevent="submit">
                                    <PrimaryButton
                                        type="submit"
                                        class="w-full justify-center"
                                        :class="{ 'opacity-25': form.processing }"
                                        :disabled="form.processing || !canSelfVerify"
                                    >
                                        <CheckCircle2 class="mr-2 h-4 w-4" aria-hidden="true" />
                                        {{ selfVerification ? 'Atualizar horario' : actionLabel }}
                                    </PrimaryButton>
                                </form>

                                <p
                                    v-if="!canSelfVerify"
                                    class="mt-3 text-sm text-app-subtle"
                                >
                                    {{ unavailableReason ?? 'Este ponto nao esta disponivel para confirmacao agora.' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                <section
                    v-if="participant"
                    class="grid gap-4 md:grid-cols-2 xl:grid-cols-4"
                >
                    <div class="app-card p-4">
                        <div class="flex items-center gap-2 text-sm font-medium text-app-text">
                            <Users class="h-4 w-4 text-app-soft" aria-hidden="true" />
                            Participante
                        </div>
                        <p class="mt-2 text-lg font-semibold text-app-text">
                            {{ participant.user.name }}
                        </p>
                        <p class="mt-1 text-sm text-app-subtle">
                            {{ participant.user.phone ?? participant.user.email }}
                        </p>
                    </div>

                    <div class="app-card p-4">
                        <div class="flex items-center gap-2 text-sm font-medium text-app-text">
                            <DoorOpen class="h-4 w-4 text-app-soft" aria-hidden="true" />
                            Quarto
                        </div>
                        <p class="mt-2 text-lg font-semibold text-app-text">
                            {{ participant.room?.name ?? 'Sem quarto definido' }}
                        </p>
                        <p class="mt-1 text-sm text-app-subtle">
                            {{ participant.user.sex_label ?? 'Sexo nao definido' }}
                        </p>
                    </div>

                    <div class="app-card p-4">
                        <div class="flex items-center gap-2 text-sm font-medium text-app-text">
                            <ShieldCheck class="h-4 w-4 text-app-soft" aria-hidden="true" />
                            Equipe
                        </div>
                        <p class="mt-2 text-lg font-semibold text-app-text">
                            {{ participant.team?.name ?? 'Sem equipe definida' }}
                        </p>
                        <p class="mt-1 text-sm text-app-subtle">
                            {{ participant.user.role?.name ?? 'Participante' }}
                        </p>
                    </div>

                    <div class="app-card p-4">
                        <div class="flex items-center gap-2 text-sm font-medium text-app-text">
                            <ClipboardCheck class="h-4 w-4 text-app-soft" aria-hidden="true" />
                            Estado
                        </div>
                        <p class="mt-2 text-lg font-semibold text-app-text">
                            {{ participant.status_label }}
                        </p>
                        <p class="mt-1 text-sm text-app-subtle">
                            PIN {{ participant.user.pin ?? '----' }}
                        </p>
                    </div>
                </section>

                <InputError class="px-1" :message="errorMessage" />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
