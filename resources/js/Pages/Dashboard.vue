<script setup lang="ts">
import BarcodeBlock from '@/Components/BarcodeBlock.vue';
import ParticipantAccessCard from '@/Components/ParticipantAccessCard.vue';
import QrCodeBlock from '@/Components/QrCodeBlock.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    CalendarDays,
    ClipboardCheck,
    DoorOpen,
    MapPin,
    ShieldCheck,
    Wallet,
} from 'lucide-vue-next';
import { computed } from 'vue';

interface DashboardParticipant {
    user: {
        id: number;
        name: string;
        email: string;
        phone?: string | null;
        photo_url?: string | null;
        pin?: string | null;
        verification_code?: string | null;
        sex?: string | null;
        sex_label?: string | null;
        role?: {
            id: number;
            key: string;
            name: string;
        } | null;
    };
    participation?: {
        id: number;
        status: string;
        status_label: string;
        amount: string;
        amount_paid: string;
        amount_open: string;
        installments_count: number;
        paid_installments: number;
        notes?: string | null;
        camp: {
            id: number;
            name: string;
            address?: string | null;
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
    } | null;
    operationStatus: {
        presence: string;
        check_in: string;
    };
}

const props = defineProps<{
    isAdministrator: boolean;
    participant: DashboardParticipant;
}>();

const money = (value: string | number) =>
    new Intl.NumberFormat('pt-PT', {
        style: 'currency',
        currency: 'EUR',
    }).format(Number(value));

const participation = computed(() => props.participant.participation);
const hasParticipation = computed(() => participation.value !== null);

const financeToneClass = computed(() => {
    switch (participation.value?.status) {
        case 'paid':
            return 'bg-emerald-50 text-emerald-700 ring-emerald-100';
        case 'partial':
            return 'bg-sky-50 text-sky-700 ring-sky-100';
        case 'exempted':
            return 'bg-violet-50 text-violet-700 ring-violet-100';
        default:
            return 'bg-amber-50 text-amber-700 ring-amber-100';
    }
});

const financeShortLabel = computed(() => {
    switch (participation.value?.status) {
        case 'paid':
            return 'Pago';
        case 'partial':
            return 'Parcial';
        case 'exempted':
            return 'Liberado';
        default:
            return 'Em aberto';
    }
});

const financeHeadline = computed(() => {
    switch (participation.value?.status) {
        case 'paid':
            return 'Tudo certo com o financeiro.';
        case 'partial':
            return `Faltam ${money(participation.value?.amount_open ?? 0)} para concluir.`;
        case 'exempted':
            return 'A administracao liberou a participacao sem pagamento.';
        default:
            return `Existe ${money(participation.value?.amount_open ?? 0)} em aberto.`;
    }
});
</script>

<template>
    <Head :title="isAdministrator ? 'Dashboard' : 'Meu acampamento'" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-2">
                <h2 class="text-xl font-semibold leading-tight text-app-text">
                    {{ isAdministrator ? 'Dashboard' : 'Meu acampamento' }}
                </h2>
                <p class="text-sm text-app-subtle">
                    Cracha digital, quarto, equipe e situacao financeira do participante.
                </p>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                <section
                    v-if="hasParticipation"
                    class="grid gap-6 xl:grid-cols-[minmax(0,1.2fr)_minmax(0,0.8fr)]"
                >
                    <div class="space-y-6">
                        <ParticipantAccessCard
                            :user="participant.user"
                            :participation="participation"
                        />

                        <section class="grid gap-3 sm:grid-cols-2">
                            <div class="app-card p-4">
                                <div class="flex items-center gap-2 text-sm font-medium text-app-text">
                                    <DoorOpen class="h-4 w-4 text-app-soft" aria-hidden="true" />
                                    Quarto
                                </div>
                                <p class="mt-2 text-lg font-semibold text-app-text">
                                    {{ participation?.room?.name ?? 'Sem quarto definido' }}
                                </p>
                            </div>

                            <div class="app-card p-4">
                                <div class="flex items-center gap-2 text-sm font-medium text-app-text">
                                    <ShieldCheck class="h-4 w-4 text-app-soft" aria-hidden="true" />
                                    Equipe
                                </div>
                                <p class="mt-2 text-lg font-semibold text-app-text">
                                    {{ participation?.team?.name ?? 'Sem equipe definida' }}
                                </p>
                            </div>

                            <div class="app-card p-4">
                                <div class="flex items-center gap-2 text-sm font-medium text-app-text">
                                    <ClipboardCheck class="h-4 w-4 text-app-soft" aria-hidden="true" />
                                    Presenca
                                </div>
                                <p class="mt-2 text-sm font-semibold text-app-text">
                                    {{ participant.operationStatus.presence }}
                                </p>
                            </div>

                            <div class="app-card p-4">
                                <div class="flex items-center gap-2 text-sm font-medium text-app-text">
                                    <CalendarDays class="h-4 w-4 text-app-soft" aria-hidden="true" />
                                    Check-in
                                </div>
                                <p class="mt-2 text-sm font-semibold text-app-text">
                                    {{ participant.operationStatus.check_in }}
                                </p>
                            </div>
                        </section>

                        <section class="app-card p-5">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h3 class="text-lg font-semibold text-app-text">
                                        Informacoes do acampamento
                                    </h3>
                                    <p class="mt-1 text-sm text-app-subtle">
                                        O que voce precisa ter a mao durante o evento.
                                    </p>
                                </div>

                                <Link :href="route('profile.edit')">
                                    <SecondaryButton type="button">Perfil</SecondaryButton>
                                </Link>
                            </div>

                            <dl class="mt-5 grid gap-4 sm:grid-cols-2">
                                <div>
                                    <dt class="text-xs font-medium uppercase tracking-wider text-app-soft">
                                        Acampamento
                                    </dt>
                                    <dd class="mt-2 text-base font-semibold text-app-text">
                                        {{ participation?.camp.name }}
                                    </dd>
                                </div>

                                <div>
                                    <dt class="text-xs font-medium uppercase tracking-wider text-app-soft">
                                        Periodo
                                    </dt>
                                    <dd class="mt-2 text-base font-semibold text-app-text">
                                        {{ participation?.camp.date_range_label ?? 'Sem data definida' }}
                                    </dd>
                                </div>

                                <div class="sm:col-span-2">
                                    <dt class="flex items-center gap-2 text-xs font-medium uppercase tracking-wider text-app-soft">
                                        <MapPin class="h-4 w-4" aria-hidden="true" />
                                        Endereco
                                    </dt>
                                    <dd class="mt-2 text-base font-semibold text-app-text">
                                        {{ participation?.camp.address ?? 'Endereco ainda nao informado' }}
                                    </dd>
                                </div>
                            </dl>
                        </section>
                    </div>

                    <div class="space-y-6">
                        <section class="app-card p-5">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h3 class="text-lg font-semibold text-app-text">
                                        Financeiro
                                    </h3>
                                    <p class="mt-1 text-sm text-app-subtle">
                                        Situacao atual da sua participacao.
                                    </p>
                                </div>

                                <span
                                    class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold ring-1"
                                    :class="financeToneClass"
                                >
                                    {{ financeShortLabel }}
                                </span>
                            </div>

                            <p class="mt-5 text-lg font-semibold text-app-text">
                                {{ financeHeadline }}
                            </p>

                            <div class="mt-5 grid gap-3 sm:grid-cols-2">
                                <div class="rounded-lg border border-app-border bg-app-muted/50 p-4">
                                    <p class="text-sm text-app-soft">Total</p>
                                    <p class="mt-2 text-lg font-semibold text-app-text">
                                        {{ money(participation?.amount ?? 0) }}
                                    </p>
                                </div>

                                <div class="rounded-lg border border-app-border bg-app-muted/50 p-4">
                                    <p class="text-sm text-app-soft">Recebido</p>
                                    <p class="mt-2 text-lg font-semibold text-app-text">
                                        {{ money(participation?.amount_paid ?? 0) }}
                                    </p>
                                </div>

                                <div class="rounded-lg border border-app-border bg-app-muted/50 p-4">
                                    <p class="text-sm text-app-soft">Em aberto</p>
                                    <p class="mt-2 text-lg font-semibold text-app-text">
                                        {{ money(participation?.amount_open ?? 0) }}
                                    </p>
                                </div>

                                <div class="rounded-lg border border-app-border bg-app-muted/50 p-4">
                                    <p class="text-sm text-app-soft">Parcelas</p>
                                    <p class="mt-2 text-lg font-semibold text-app-text">
                                        {{ participation?.paid_installments ?? 0 }}/{{ participation?.installments_count ?? 0 }}
                                    </p>
                                </div>
                            </div>

                            <div class="mt-4 space-y-2 text-sm text-app-subtle">
                                <p>
                                    <span class="font-medium text-app-text">Estado:</span>
                                    {{ participation?.status_label }}
                                </p>
                                <p v-if="participation?.lot?.name">
                                    <span class="font-medium text-app-text">Lote:</span>
                                    {{ participation.lot.name }}
                                </p>
                                <p v-if="participation?.notes">
                                    <span class="font-medium text-app-text">Observacao:</span>
                                    {{ participation.notes }}
                                </p>
                            </div>
                        </section>

                        <section class="app-card p-5">
                            <h3 class="text-lg font-semibold text-app-text">
                                QR code
                            </h3>
                            <p class="mt-1 text-sm text-app-subtle">
                                Use este codigo para identificacao e verificacoes.
                            </p>

                            <div class="mt-5 flex justify-center">
                                <QrCodeBlock
                                    :value="participant.user.verification_code ?? ''"
                                    :size="180"
                                    :alt="`QR de ${participant.user.name}`"
                                />
                            </div>

                            <p class="mt-4 break-all text-center font-mono text-xs text-app-soft">
                                {{ participant.user.verification_code ?? 'Sem codigo' }}
                            </p>
                        </section>

                        <section class="app-card p-5">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <h3 class="text-lg font-semibold text-app-text">
                                        Codigo de barras
                                    </h3>
                                    <p class="mt-1 text-sm text-app-subtle">
                                        Alternativa rapida para leitura no acampamento.
                                    </p>
                                </div>

                                <ArrowRight class="h-5 w-5 text-app-soft" aria-hidden="true" />
                            </div>

                            <div class="mt-5">
                                <BarcodeBlock
                                    :value="participant.user.verification_code ?? ''"
                                    :height="72"
                                />
                            </div>
                        </section>
                    </div>
                </section>

                <section
                    v-else
                    class="app-card p-8 text-center"
                >
                    <h3 class="text-lg font-semibold text-app-text">
                        O seu cracha aparece aqui
                    </h3>
                    <p class="mt-2 text-sm text-app-subtle">
                        Assim que voce for vinculado a um acampamento, este painel vai mostrar quarto, equipe, QR code, codigo de barras e a situacao financeira.
                    </p>
                </section>

                <section
                    v-if="isAdministrator"
                    class="space-y-4"
                >
                    <div>
                        <h3 class="text-lg font-semibold text-app-text">
                            Acessos rapidos do admin
                        </h3>
                        <p class="mt-1 text-sm text-app-subtle">
                            Atalhos para continuar a operacao do acampamento.
                        </p>
                    </div>

                    <div class="grid gap-3 md:grid-cols-3">
                        <Link
                            :href="route('users.index')"
                            class="app-card p-5 transition hover:border-brand-300 hover:bg-brand-50/40"
                        >
                            <h4 class="text-base font-semibold text-app-text">
                                Usuarios
                            </h4>
                            <p class="mt-2 text-sm text-app-subtle">
                                Gerir participantes, monitores e lideres.
                            </p>
                        </Link>

                        <Link
                            :href="route('camps.index')"
                            class="app-card p-5 transition hover:border-brand-300 hover:bg-brand-50/40"
                        >
                            <h4 class="text-base font-semibold text-app-text">
                                Acampamentos
                            </h4>
                            <p class="mt-2 text-sm text-app-subtle">
                                Abrir a central, quartos, equipes e financeiro.
                            </p>
                        </Link>

                        <Link
                            :href="route('profile.edit')"
                            class="app-card p-5 transition hover:border-brand-300 hover:bg-brand-50/40"
                        >
                            <h4 class="text-base font-semibold text-app-text">
                                Perfil
                            </h4>
                            <p class="mt-2 text-sm text-app-subtle">
                                Atualizar os seus dados e a foto do cracha.
                            </p>
                        </Link>
                    </div>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
