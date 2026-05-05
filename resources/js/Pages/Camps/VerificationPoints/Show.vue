<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import QrCodeBlock from '@/Components/QrCodeBlock.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { Camp, PageProps } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import {
    ArrowLeft,
    CheckCircle2,
    ClipboardCheck,
    QrCode,
    Search,
    UserCheck,
    Users,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

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
    last_verified_at?: string | null;
    last_verified_at_label?: string | null;
    operation_url: string;
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
        verification_code?: string | null;
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
    verification?: {
        id: number;
        method: string;
        method_label: string;
        verified_at?: string | null;
        verified_at_label?: string | null;
    } | null;
}

interface RecentEntry {
    id: number;
    method: string;
    method_label: string;
    verified_at?: string | null;
    verified_at_label?: string | null;
    user: {
        id: number;
        name: string;
        email: string;
        phone?: string | null;
        role_name?: string | null;
        team_name?: string | null;
        room_name?: string | null;
        sex_label?: string | null;
    };
    verified_by_name?: string | null;
}

type VerificationMode = 'code' | 'pin' | 'manual';
type ParticipantTab = 'pending' | 'verified' | 'all';

const props = defineProps<{
    camp: Camp;
    point: VerificationPoint;
    participants: Participant[];
    recentEntries: RecentEntry[];
    summary: {
        linked_people: number;
        verified_people: number;
        pending_people: number;
    };
}>();

const page = usePage<PageProps>();
const participantSearch = ref('');
const participantTab = ref<ParticipantTab>('pending');
const verificationMode = ref<VerificationMode>('code');
const manualSubmittingId = ref<number | null>(null);

const verifyForm = useForm({
    method: 'code',
    pin: '',
    verification_code: '',
    camp_payment_id: '',
});

const activeModeButtonClass = (mode: VerificationMode) =>
    verificationMode.value === mode
        ? 'bg-brand-700 text-white shadow-sm'
        : 'bg-app-surface text-app-text hover:bg-app-muted';

const filteredParticipants = computed(() => {
    const search = participantSearch.value.trim().toLowerCase();

    return props.participants.filter((participant) => {
        if (participantTab.value === 'pending' && participant.verification) {
            return false;
        }

        if (participantTab.value === 'verified' && !participant.verification) {
            return false;
        }

        if (!search) {
            return true;
        }

        const haystack = [
            participant.user.name,
            participant.user.email,
            participant.user.phone ?? '',
            participant.team?.name ?? '',
            participant.room?.name ?? '',
            participant.user.pin ?? '',
            participant.user.verification_code ?? '',
        ]
            .join(' ')
            .toLowerCase();

        return haystack.includes(search);
    });
});

const setVerificationMode = (mode: VerificationMode) => {
    verificationMode.value = mode;
    verifyForm.method = mode;
    verifyForm.clearErrors();
};

const submitVerification = () => {
    verifyForm.method = verificationMode.value;

    verifyForm.post(route('camps.verification-points.verifications.store', [props.camp.id, props.point.id]), {
        preserveScroll: true,
        onSuccess: () => {
            verifyForm.reset();
            verifyForm.method = verificationMode.value;
        },
    });
};

const verifyParticipantManually = (participant: Participant) => {
    manualSubmittingId.value = participant.id;
    verifyForm.method = 'manual';
    verifyForm.camp_payment_id = String(participant.id);

    verifyForm.post(route('camps.verification-points.verifications.store', [props.camp.id, props.point.id]), {
        preserveScroll: true,
        onFinish: () => {
            manualSubmittingId.value = null;
            verifyForm.camp_payment_id = '';
            verifyForm.method = verificationMode.value;
        },
    });
};
</script>

<template>
    <Head :title="`${point.name} - ${camp.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <Link
                        :href="route('camps.verification-points.index', camp.id)"
                        class="inline-flex items-center gap-1 rounded-md text-sm font-medium text-app-subtle hover:text-brand-800 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2"
                    >
                        <ArrowLeft class="h-4 w-4" aria-hidden="true" />
                        Presenca e check-in
                    </Link>
                    <div class="mt-1 flex flex-wrap items-center gap-2">
                        <h2 class="text-xl font-semibold leading-tight text-app-text">
                            {{ point.name }}
                        </h2>
                        <span class="rounded-full bg-brand-100 px-3 py-1 text-xs font-medium text-brand-800">
                            {{ point.type_label }}
                        </span>
                        <span
                            class="rounded-full px-3 py-1 text-xs font-medium"
                            :class="
                                point.is_active
                                    ? 'bg-green-100 text-green-800'
                                    : 'bg-app-muted text-app-soft'
                            "
                        >
                            {{ point.is_active ? 'Ativo' : 'Inativo' }}
                        </span>
                    </div>
                    <p class="mt-1 text-sm text-app-subtle">
                        {{ point.notes || 'Registo operacional do acampamento.' }}
                    </p>
                </div>

                <div class="flex gap-2">
                    <Link v-if="page.props.auth.can.manageUsers" :href="route('users.index')">
                        <SecondaryButton type="button">
                            <Users class="mr-2 h-4 w-4" aria-hidden="true" />
                            Usuarios
                        </SecondaryButton>
                    </Link>
                    <Link v-if="page.props.auth.can.manageCamps" :href="route('camps.show', camp.id)">
                        <SecondaryButton type="button">Central</SecondaryButton>
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

                <div
                    v-if="!point.is_active"
                    class="rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800"
                >
                    Este ponto esta inativo. Reative-o na pagina anterior para voltar a registar verificacoes.
                </div>

                <section class="grid gap-6 xl:grid-cols-[380px_1fr]">
                    <div class="space-y-6">
                        <div class="app-card p-5">
                            <div class="flex items-center gap-2 text-app-text">
                                <ClipboardCheck class="h-5 w-5 text-app-soft" aria-hidden="true" />
                                <h3 class="text-lg font-semibold">
                                    Registar verificacao
                                </h3>
                            </div>

                            <div class="mt-4 grid grid-cols-3 gap-2 rounded-xl bg-app-muted p-1">
                                <button
                                    type="button"
                                    class="rounded-lg px-3 py-2 text-sm font-medium transition"
                                    :class="activeModeButtonClass('code')"
                                    @click="setVerificationMode('code')"
                                >
                                    Codigo
                                </button>
                                <button
                                    type="button"
                                    class="rounded-lg px-3 py-2 text-sm font-medium transition"
                                    :class="activeModeButtonClass('pin')"
                                    @click="setVerificationMode('pin')"
                                >
                                    PIN
                                </button>
                                <button
                                    type="button"
                                    class="rounded-lg px-3 py-2 text-sm font-medium transition"
                                    :class="activeModeButtonClass('manual')"
                                    @click="setVerificationMode('manual')"
                                >
                                    Manual
                                </button>
                            </div>

                            <form class="mt-5 space-y-4" @submit.prevent="submitVerification">
                                <label v-if="verificationMode === 'code'" class="block">
                                    <span class="mb-1 block text-sm font-medium text-app-text">
                                        Codigo, QR ou codigo de barras
                                    </span>
                                    <input
                                        v-model="verifyForm.verification_code"
                                        type="text"
                                        :disabled="!point.is_active"
                                        autofocus
                                        class="block w-full rounded-md border-app-border shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                        placeholder="Ex.: USR-ABC12345"
                                    />
                                    <InputError class="mt-2" :message="verifyForm.errors.verification_code" />
                                </label>

                                <label v-else-if="verificationMode === 'pin'" class="block">
                                    <span class="mb-1 block text-sm font-medium text-app-text">
                                        PIN da pessoa
                                    </span>
                                    <input
                                        v-model="verifyForm.pin"
                                        type="text"
                                        inputmode="numeric"
                                        maxlength="4"
                                        :disabled="!point.is_active"
                                        class="block w-full rounded-md border-app-border shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                        placeholder="Ex.: 0427"
                                    />
                                    <InputError class="mt-2" :message="verifyForm.errors.pin" />
                                </label>

                                <label v-else class="block">
                                    <span class="mb-1 block text-sm font-medium text-app-text">
                                        Pessoa
                                    </span>
                                    <select
                                        v-model="verifyForm.camp_payment_id"
                                        :disabled="!point.is_active"
                                        class="block w-full rounded-md border-app-border shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                    >
                                        <option value="">Escolha uma pessoa</option>
                                        <option
                                            v-for="participant in participants"
                                            :key="participant.id"
                                            :value="participant.id"
                                        >
                                            {{ participant.user.name }} - {{ participant.user.phone ?? participant.user.email }}
                                        </option>
                                    </select>
                                    <InputError class="mt-2" :message="verifyForm.errors.camp_payment_id" />
                                </label>

                                <PrimaryButton
                                    type="submit"
                                    :class="{ 'opacity-25': verifyForm.processing }"
                                    :disabled="verifyForm.processing || !point.is_active"
                                >
                                    <CheckCircle2 class="mr-2 h-4 w-4" aria-hidden="true" />
                                    Confirmar
                                </PrimaryButton>
                            </form>
                        </div>

                        <div class="app-card p-5">
                            <div class="flex items-center gap-2 text-app-text">
                                <QrCode class="h-5 w-5 text-app-soft" aria-hidden="true" />
                                <h3 class="text-lg font-semibold">
                                    QR do ponto
                                </h3>
                            </div>
                            <p class="mt-1 text-sm text-app-subtle">
                                Codigo {{ point.point_code }}
                            </p>

                            <div class="mt-4 flex flex-col items-center gap-4">
                                <QrCodeBlock
                                    :value="point.operation_url"
                                    :size="180"
                                    :alt="`QR do ponto ${point.name}`"
                                />
                                <p class="text-center text-sm text-app-subtle">
                                    Acesso rapido ao ponto operacional deste momento.
                                </p>
                            </div>
                        </div>

                        <dl class="grid gap-3 sm:grid-cols-3 xl:grid-cols-1">
                            <div class="app-card p-4">
                                <dt class="text-sm text-app-soft">Pessoas</dt>
                                <dd class="mt-1 text-2xl font-semibold text-app-text">
                                    {{ summary.linked_people }}
                                </dd>
                            </div>
                            <div class="app-card p-4">
                                <dt class="text-sm text-app-soft">Verificados</dt>
                                <dd class="mt-1 text-2xl font-semibold text-success">
                                    {{ summary.verified_people }}
                                </dd>
                            </div>
                            <div class="app-card p-4">
                                <dt class="text-sm text-app-soft">Pendentes</dt>
                                <dd class="mt-1 text-2xl font-semibold text-warning">
                                    {{ summary.pending_people }}
                                </dd>
                            </div>
                        </dl>
                    </div>

                    <div class="space-y-6">
                        <section class="app-card overflow-hidden">
                            <div class="border-b border-app-border px-5 py-4">
                                <h3 class="text-lg font-semibold text-app-text">
                                    Ultimas verificacoes
                                </h3>
                                <p class="mt-1 text-sm text-app-subtle">
                                    Nome, hora, telefone, equipe e quarto no momento do registo.
                                </p>
                            </div>

                            <div
                                v-if="recentEntries.length === 0"
                                class="px-5 py-10 text-center text-sm text-app-subtle"
                            >
                                Ainda nao existem verificacoes neste ponto.
                            </div>

                            <div v-else class="divide-y divide-app-border">
                                <article
                                    v-for="entry in recentEntries"
                                    :key="entry.id"
                                    class="grid gap-4 px-5 py-4 lg:grid-cols-[1.2fr_0.8fr_0.8fr_0.8fr]"
                                >
                                    <div>
                                        <Link
                                            :href="route('users.show', entry.user.id)"
                                            class="font-semibold text-app-text hover:text-brand-800"
                                        >
                                            {{ entry.user.name }}
                                        </Link>
                                        <p class="mt-1 text-sm text-app-subtle">
                                            {{ entry.user.phone ?? entry.user.email }}
                                        </p>
                                    </div>

                                    <div>
                                        <dt class="text-xs font-medium uppercase tracking-wider text-app-soft">
                                            Hora
                                        </dt>
                                        <dd class="mt-2 text-sm font-semibold text-app-text">
                                            {{ entry.verified_at_label }}
                                        </dd>
                                        <p class="mt-1 text-xs text-app-soft">
                                            {{ entry.method_label }}
                                        </p>
                                    </div>

                                    <div>
                                        <dt class="text-xs font-medium uppercase tracking-wider text-app-soft">
                                            Equipe
                                        </dt>
                                        <dd class="mt-2 text-sm text-app-text">
                                            {{ entry.user.team_name ?? 'Sem equipe' }}
                                        </dd>
                                        <p class="mt-1 text-xs text-app-soft">
                                            {{ entry.user.role_name ?? 'Sem papel' }}
                                        </p>
                                    </div>

                                    <div>
                                        <dt class="text-xs font-medium uppercase tracking-wider text-app-soft">
                                            Quarto
                                        </dt>
                                        <dd class="mt-2 text-sm text-app-text">
                                            {{ entry.user.room_name ?? 'Sem quarto' }}
                                        </dd>
                                        <p class="mt-1 text-xs text-app-soft">
                                            {{ entry.verified_by_name ?? 'Sem operador' }}
                                        </p>
                                    </div>
                                </article>
                            </div>
                        </section>

                        <section class="app-card overflow-hidden">
                            <div class="border-b border-app-border px-5 py-4">
                                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                                    <div>
                                        <h3 class="text-lg font-semibold text-app-text">
                                            Pessoas deste acampamento
                                        </h3>
                                        <p class="mt-1 text-sm text-app-subtle">
                                            Lista compacta para procura, confirmacao e marcacao manual.
                                        </p>
                                    </div>

                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                                        <div class="grid grid-cols-3 gap-2 rounded-xl bg-app-muted p-1">
                                            <button
                                                type="button"
                                                class="rounded-lg px-3 py-2 text-sm font-medium transition"
                                                :class="participantTab === 'pending' ? 'bg-brand-700 text-white shadow-sm' : 'bg-app-surface text-app-text hover:bg-app-muted'"
                                                @click="participantTab = 'pending'"
                                            >
                                                Pendentes
                                            </button>
                                            <button
                                                type="button"
                                                class="rounded-lg px-3 py-2 text-sm font-medium transition"
                                                :class="participantTab === 'verified' ? 'bg-brand-700 text-white shadow-sm' : 'bg-app-surface text-app-text hover:bg-app-muted'"
                                                @click="participantTab = 'verified'"
                                            >
                                                Verificados
                                            </button>
                                            <button
                                                type="button"
                                                class="rounded-lg px-3 py-2 text-sm font-medium transition"
                                                :class="participantTab === 'all' ? 'bg-brand-700 text-white shadow-sm' : 'bg-app-surface text-app-text hover:bg-app-muted'"
                                                @click="participantTab = 'all'"
                                            >
                                                Todos
                                            </button>
                                        </div>

                                        <label class="relative block min-w-[260px]">
                                            <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-app-soft" aria-hidden="true" />
                                            <input
                                                v-model="participantSearch"
                                                type="text"
                                                class="block w-full rounded-md border-app-border py-2 pl-9 pr-3 shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                                placeholder="Buscar nome, telefone, quarto..."
                                            />
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div
                                v-if="filteredParticipants.length === 0"
                                class="px-5 py-10 text-center text-sm text-app-subtle"
                            >
                                Nenhuma pessoa encontrada com esse filtro.
                            </div>

                            <div v-else class="divide-y divide-app-border">
                                <article
                                    v-for="participant in filteredParticipants"
                                    :key="participant.id"
                                    class="grid gap-4 px-5 py-4 xl:grid-cols-[1.2fr_0.7fr_0.8fr_0.8fr_auto]"
                                >
                                    <div>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <Link
                                                :href="route('users.show', participant.user.id)"
                                                class="font-semibold text-app-text hover:text-brand-800"
                                            >
                                                {{ participant.user.name }}
                                            </Link>
                                            <span
                                                v-if="participant.verification"
                                                class="rounded-full bg-green-100 px-2.5 py-1 text-xs font-medium text-green-800"
                                            >
                                                {{ participant.verification.verified_at_label }}
                                            </span>
                                        </div>
                                        <p class="mt-1 text-sm text-app-subtle">
                                            {{ participant.user.phone ?? participant.user.email }}
                                        </p>
                                        <div class="mt-2 flex flex-wrap gap-2 text-xs text-app-soft">
                                            <span class="rounded-full bg-app-muted px-2.5 py-1">
                                                PIN {{ participant.user.pin ?? '----' }}
                                            </span>
                                            <span class="rounded-full bg-app-muted px-2.5 py-1">
                                                {{ participant.user.role?.name ?? 'Sem papel' }}
                                            </span>
                                            <span class="rounded-full bg-app-muted px-2.5 py-1">
                                                {{ participant.status_label }}
                                            </span>
                                        </div>
                                    </div>

                                    <div>
                                        <dt class="text-xs font-medium uppercase tracking-wider text-app-soft">
                                            Equipe
                                        </dt>
                                        <dd class="mt-2 text-sm text-app-text">
                                            {{ participant.team?.name ?? 'Sem equipe' }}
                                        </dd>
                                    </div>

                                    <div>
                                        <dt class="text-xs font-medium uppercase tracking-wider text-app-soft">
                                            Quarto
                                        </dt>
                                        <dd class="mt-2 text-sm text-app-text">
                                            {{ participant.room?.name ?? 'Sem quarto' }}
                                        </dd>
                                    </div>

                                    <div class="min-w-0">
                                        <dt class="text-xs font-medium uppercase tracking-wider text-app-soft">
                                            Codigo
                                        </dt>
                                        <dd class="mt-2 truncate font-mono text-xs text-app-text">
                                            {{ participant.user.verification_code ?? 'Sem codigo' }}
                                        </dd>
                                    </div>

                                    <div class="flex items-center justify-end">
                                        <PrimaryButton
                                            :class="{ 'opacity-25': manualSubmittingId === participant.id }"
                                            :disabled="manualSubmittingId === participant.id || !point.is_active"
                                            @click="verifyParticipantManually(participant)"
                                        >
                                            <UserCheck class="mr-2 h-4 w-4" aria-hidden="true" />
                                            {{ participant.verification ? 'Atualizar' : 'Marcar' }}
                                        </PrimaryButton>
                                    </div>
                                </article>
                            </div>
                        </section>
                    </div>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
