<script setup lang="ts">
import Checkbox from '@/Components/Checkbox.vue';
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import QrCodeBlock from '@/Components/QrCodeBlock.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { Camp, PageProps } from '@/types';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import {
    ArrowLeft,
    ClipboardCheck,
    Pencil,
    QrCode,
    Trash2,
} from 'lucide-vue-next';
import { ref } from 'vue';

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

interface TypeOption {
    value: string;
    label: string;
}

const props = defineProps<{
    camp: Camp;
    points: VerificationPoint[];
    summary: {
        linked_people: number;
        points_total: number;
        presence_points: number;
        check_in_points: number;
        verified_entries: number;
    };
    typeOptions: TypeOption[];
}>();

const page = usePage<PageProps>();
const backHref = page.props.auth.can.manageCamps
    ? route('camps.show', props.camp.id)
    : route('dashboard');
const backLabel = page.props.auth.can.manageCamps
    ? 'Central do acampamento'
    : 'Painel';
const editingPointId = ref<number | null>(null);

const createForm = useForm({
    name: '',
    type: props.typeOptions[0]?.value ?? 'presence',
    notes: '',
    is_active: true,
});

const editForm = useForm({
    name: '',
    type: props.typeOptions[0]?.value ?? 'presence',
    notes: '',
    is_active: true,
});

const createPoint = () => {
    createForm.post(route('camps.verification-points.store', props.camp.id), {
        preserveScroll: true,
        onSuccess: () => {
            createForm.reset();
            createForm.type = props.typeOptions[0]?.value ?? 'presence';
            createForm.is_active = true;
        },
    });
};

const startEditing = (point: VerificationPoint) => {
    editingPointId.value = point.id;
    editForm.name = point.name;
    editForm.type = point.type;
    editForm.notes = point.notes ?? '';
    editForm.is_active = point.is_active;
    editForm.clearErrors();
};

const cancelEditing = () => {
    editingPointId.value = null;
    editForm.reset();
};

const updatePoint = (pointId: number) => {
    editForm.patch(route('camps.verification-points.update', [props.camp.id, pointId]), {
        preserveScroll: true,
        onSuccess: () => {
            cancelEditing();
        },
    });
};

const removePoint = (point: VerificationPoint) => {
    if (!window.confirm(`Remover o ponto "${point.name}"?`)) {
        return;
    }

    router.delete(route('camps.verification-points.destroy', [props.camp.id, point.id]), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head :title="`Presenca e check-in - ${camp.name}`" />

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
                        Presenca e check-in
                    </h2>
                    <p class="mt-1 text-sm text-app-subtle">
                        {{ camp.name }} - pontos de verificacao para o dia do evento.
                    </p>
                </div>

                <Link v-if="page.props.auth.can.managePayments" :href="route('camps.payments.index', camp.id)">
                    <SecondaryButton type="button">Pessoas do acampamento</SecondaryButton>
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

                <dl class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
                    <div class="app-card p-4">
                        <dt class="text-sm text-app-soft">Pessoas vinculadas</dt>
                        <dd class="mt-1 text-2xl font-semibold text-app-text">
                            {{ summary.linked_people }}
                        </dd>
                    </div>
                    <div class="app-card p-4">
                        <dt class="text-sm text-app-soft">Pontos</dt>
                        <dd class="mt-1 text-2xl font-semibold text-app-text">
                            {{ summary.points_total }}
                        </dd>
                    </div>
                    <div class="app-card p-4">
                        <dt class="text-sm text-app-soft">Presenca</dt>
                        <dd class="mt-1 text-2xl font-semibold text-app-text">
                            {{ summary.presence_points }}
                        </dd>
                    </div>
                    <div class="app-card p-4">
                        <dt class="text-sm text-app-soft">Check-in</dt>
                        <dd class="mt-1 text-2xl font-semibold text-info">
                            {{ summary.check_in_points }}
                        </dd>
                    </div>
                    <div class="app-card p-4">
                        <dt class="text-sm text-app-soft">Verificacoes</dt>
                        <dd class="mt-1 text-2xl font-semibold text-success">
                            {{ summary.verified_entries }}
                        </dd>
                    </div>
                </dl>

                <section class="app-card p-5">
                    <div class="flex flex-col gap-1">
                        <h3 class="text-lg font-semibold text-app-text">
                            Novo ponto operacional
                        </h3>
                        <p class="text-sm text-app-subtle">
                            Crie os momentos de contagem e registo que o acampamento precisa.
                        </p>
                    </div>

                    <form
                        class="mt-4 grid gap-4 lg:grid-cols-[minmax(220px,1fr)_180px_minmax(220px,1fr)_auto]"
                        @submit.prevent="createPoint"
                    >
                        <label class="block">
                            <span class="mb-1 block text-sm font-medium text-app-text">
                                Nome do ponto
                            </span>
                            <input
                                v-model="createForm.name"
                                type="text"
                                class="block w-full rounded-md border-app-border shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                placeholder="Ex.: Almoco de sabado"
                            />
                            <InputError class="mt-2" :message="createForm.errors.name" />
                        </label>

                        <label class="block">
                            <span class="mb-1 block text-sm font-medium text-app-text">
                                Tipo
                            </span>
                            <select
                                v-model="createForm.type"
                                class="block w-full rounded-md border-app-border shadow-sm focus:border-brand-500 focus:ring-brand-500"
                            >
                                <option
                                    v-for="option in typeOptions"
                                    :key="option.value"
                                    :value="option.value"
                                >
                                    {{ option.label }}
                                </option>
                            </select>
                            <InputError class="mt-2" :message="createForm.errors.type" />
                        </label>

                        <label class="block">
                            <span class="mb-1 block text-sm font-medium text-app-text">
                                Observacao
                            </span>
                            <input
                                v-model="createForm.notes"
                                type="text"
                                class="block w-full rounded-md border-app-border shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                placeholder="Ex.: contagem antes do culto"
                            />
                            <InputError class="mt-2" :message="createForm.errors.notes" />
                        </label>

                        <div class="flex flex-col justify-end gap-3">
                            <label class="inline-flex items-center gap-2 text-sm text-app-subtle">
                                <Checkbox v-model:checked="createForm.is_active" />
                                Ponto ativo
                            </label>
                            <PrimaryButton
                                type="submit"
                                :class="{ 'opacity-25': createForm.processing }"
                                :disabled="createForm.processing"
                            >
                                Criar ponto
                            </PrimaryButton>
                        </div>
                    </form>
                </section>

                <section class="space-y-4">
                    <div>
                        <h3 class="text-lg font-semibold text-app-text">
                            Pontos configurados
                        </h3>
                        <p class="mt-1 text-sm text-app-subtle">
                            Abra o ponto para operar por PIN, codigo, QR ou marcacao manual.
                        </p>
                    </div>

                    <div
                        v-if="points.length === 0"
                        class="app-card px-5 py-10 text-center text-sm text-app-subtle"
                    >
                        Ainda nao existem pontos de verificacao neste acampamento.
                    </div>

                    <article
                        v-for="point in points"
                        :key="point.id"
                        class="app-card overflow-hidden"
                    >
                        <div
                            v-if="editingPointId !== point.id"
                            class="grid gap-5 p-5 lg:grid-cols-[1.15fr_0.85fr]"
                        >
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span
                                        class="rounded-full bg-brand-100 px-3 py-1 text-xs font-medium text-brand-800"
                                    >
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

                                <div class="mt-4 flex flex-wrap items-start justify-between gap-4">
                                    <div>
                                        <h4 class="text-lg font-semibold text-app-text">
                                            {{ point.name }}
                                        </h4>
                                        <p class="mt-1 text-sm text-app-subtle">
                                            {{ point.notes || 'Sem observacao operacional.' }}
                                        </p>
                                    </div>

                                    <div class="flex flex-wrap gap-2">
                                        <Link :href="route('camps.verification-points.show', [camp.id, point.id])">
                                            <PrimaryButton type="button">
                                                <ClipboardCheck class="mr-2 h-4 w-4" aria-hidden="true" />
                                                Abrir ponto
                                            </PrimaryButton>
                                        </Link>
                                        <SecondaryButton type="button" @click="startEditing(point)">
                                            <Pencil class="mr-2 h-4 w-4" aria-hidden="true" />
                                            Editar
                                        </SecondaryButton>
                                        <DangerButton type="button" @click="removePoint(point)">
                                            <Trash2 class="mr-2 h-4 w-4" aria-hidden="true" />
                                            Remover
                                        </DangerButton>
                                    </div>
                                </div>

                                <dl class="mt-5 grid gap-3 sm:grid-cols-3">
                                    <div class="rounded-lg border border-app-border bg-app-muted/60 p-4">
                                        <dt class="text-sm text-app-soft">Verificados</dt>
                                        <dd class="mt-1 text-2xl font-semibold text-success">
                                            {{ point.verified_count }}
                                        </dd>
                                    </div>
                                    <div class="rounded-lg border border-app-border bg-app-muted/60 p-4">
                                        <dt class="text-sm text-app-soft">Pendentes</dt>
                                        <dd class="mt-1 text-2xl font-semibold text-warning">
                                            {{ point.pending_count }}
                                        </dd>
                                    </div>
                                    <div class="rounded-lg border border-app-border bg-app-muted/60 p-4">
                                        <dt class="text-sm text-app-soft">Ultimo registo</dt>
                                        <dd class="mt-1 text-sm font-semibold text-app-text">
                                            {{ point.last_verified_at_label ?? 'Sem registos' }}
                                        </dd>
                                    </div>
                                </dl>
                            </div>

                            <div class="rounded-xl border border-app-border bg-app-muted/50 p-5">
                                <div class="flex items-center gap-2 text-sm font-medium text-app-text">
                                    <QrCode class="h-4 w-4 text-app-soft" aria-hidden="true" />
                                    QR do ponto
                                </div>
                                <p class="mt-1 text-xs text-app-soft">
                                    Codigo {{ point.point_code }}
                                </p>

                                <div class="mt-4 flex flex-col items-center gap-3 sm:flex-row sm:items-start">
                                    <QrCodeBlock
                                        :value="point.operation_url"
                                        :size="108"
                                        :alt="`QR do ponto ${point.name}`"
                                    />
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-medium text-app-text">
                                            {{ point.point_code }}
                                        </p>
                                        <p class="mt-2 text-sm text-app-subtle">
                                            Abertura rapida do ponto operacional e identificacao para o acampamento.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <form
                            v-else
                            class="grid gap-4 border-t border-app-border bg-app-muted/20 p-5 lg:grid-cols-[minmax(220px,1fr)_180px_minmax(220px,1fr)_auto]"
                            @submit.prevent="updatePoint(point.id)"
                        >
                            <label class="block">
                                <span class="mb-1 block text-sm font-medium text-app-text">
                                    Nome do ponto
                                </span>
                                <input
                                    v-model="editForm.name"
                                    type="text"
                                    class="block w-full rounded-md border-app-border shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                />
                                <InputError class="mt-2" :message="editForm.errors.name" />
                            </label>

                            <label class="block">
                                <span class="mb-1 block text-sm font-medium text-app-text">
                                    Tipo
                                </span>
                                <select
                                    v-model="editForm.type"
                                    class="block w-full rounded-md border-app-border shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                >
                                    <option
                                        v-for="option in typeOptions"
                                        :key="option.value"
                                        :value="option.value"
                                    >
                                        {{ option.label }}
                                    </option>
                                </select>
                                <InputError class="mt-2" :message="editForm.errors.type" />
                            </label>

                            <label class="block">
                                <span class="mb-1 block text-sm font-medium text-app-text">
                                    Observacao
                                </span>
                                <input
                                    v-model="editForm.notes"
                                    type="text"
                                    class="block w-full rounded-md border-app-border shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                />
                                <InputError class="mt-2" :message="editForm.errors.notes" />
                            </label>

                            <div class="flex flex-col justify-end gap-3">
                                <label class="inline-flex items-center gap-2 text-sm text-app-subtle">
                                    <Checkbox v-model:checked="editForm.is_active" />
                                    Ponto ativo
                                </label>
                                <div class="flex gap-2">
                                    <PrimaryButton
                                        type="submit"
                                        :class="{ 'opacity-25': editForm.processing }"
                                        :disabled="editForm.processing"
                                    >
                                        Guardar
                                    </PrimaryButton>
                                    <SecondaryButton type="button" @click="cancelEditing">
                                        Cancelar
                                    </SecondaryButton>
                                </div>
                            </div>
                        </form>
                    </article>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
