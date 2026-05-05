<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { Camp, User } from '@/types';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ArrowLeft, Columns3, GripVertical, List, Plus, Trash2, Users } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface TeamParticipant {
    id: number;
    camp_team_id?: number | null;
    status_label: string;
    user: User;
}

interface CampTeam {
    id: number;
    name: string;
    leader_camp_payment_id?: number | null;
    leader?: TeamParticipant | null;
    notes?: string | null;
    sort_order?: number;
    members_count: number;
    members: TeamParticipant[];
}

const props = defineProps<{
    camp: Camp;
    teams: CampTeam[];
    unassignedPayments: TeamParticipant[];
    leaderOptions: TeamParticipant[];
    summary: {
        linked_people: number;
        assigned_people: number;
        unassigned_people: number;
        teams: number;
        leaders: number;
    };
}>();

const page = usePage();
const draggedPaymentId = ref<number | null>(null);
const dragOverTarget = ref<string | null>(null);
const assignmentError = ref<string | null>(null);
const editingTeamId = ref<number | null>(null);
const viewMode = ref<'compact' | 'board'>('compact');

const createForm = useForm({
    name: '',
    leader_camp_payment_id: '',
    notes: '',
});

const editForm = useForm({
    name: '',
    leader_camp_payment_id: '',
    notes: '',
});

const assignmentLabel = computed(
    () => `${props.summary.assigned_people}/${props.summary.linked_people}`,
);

const targetKey = (teamId: number | null) =>
    teamId === null ? 'unassigned' : `team-${teamId}`;

const leaderName = (team: CampTeam) => team.leader?.user.name ?? 'Sem lider';

const participantSubtitle = (participant: TeamParticipant) =>
    [
        participant.user.role?.name,
        participant.user.sex_label,
        participant.user.phone,
    ]
        .filter(Boolean)
        .join(' - ') || participant.status_label;

const startDrag = (paymentId: number) => {
    draggedPaymentId.value = paymentId;
    assignmentError.value = null;
};

const clearDrag = () => {
    draggedPaymentId.value = null;
    dragOverTarget.value = null;
};

const assignParticipant = (paymentId: number, teamId: number | null) => {
    assignmentError.value = null;

    router.patch(
        route('camps.teams.assignments.update', props.camp.id),
        {
            camp_payment_id: paymentId,
            camp_team_id: teamId,
        },
        {
            preserveScroll: true,
            onError: (errors) => {
                assignmentError.value =
                    errors.camp_team_id ??
                    errors.camp_payment_id ??
                    'Nao foi possivel mover esta pessoa.';
            },
            onFinish: clearDrag,
        },
    );
};

const dropOn = (teamId: number | null) => {
    if (draggedPaymentId.value === null) {
        return;
    }

    assignParticipant(draggedPaymentId.value, teamId);
};

const changeTeam = (paymentId: number, event: Event) => {
    const value = (event.target as HTMLSelectElement).value;

    assignParticipant(paymentId, value === '' ? null : Number(value));
};

const createTeam = () => {
    createForm.post(route('camps.teams.store', props.camp.id), {
        preserveScroll: true,
        onSuccess: () => createForm.reset(),
    });
};

const startEditingTeam = (team: CampTeam) => {
    editingTeamId.value = team.id;
    editForm.name = team.name;
    editForm.leader_camp_payment_id = team.leader_camp_payment_id
        ? String(team.leader_camp_payment_id)
        : '';
    editForm.notes = team.notes ?? '';
    editForm.clearErrors();
};

const cancelEditingTeam = () => {
    editingTeamId.value = null;
    editForm.clearErrors();
};

const updateTeam = (team: CampTeam) => {
    editForm.patch(route('camps.teams.update', [props.camp.id, team.id]), {
        preserveScroll: true,
        onSuccess: () => {
            editingTeamId.value = null;
        },
    });
};

const removeTeam = (team: CampTeam) => {
    if (
        !window.confirm(
            `Remover ${team.name}? As pessoas desta equipe ficarao sem equipe definida.`,
        )
    ) {
        return;
    }

    router.delete(route('camps.teams.destroy', [props.camp.id, team.id]), {
        preserveScroll: true,
    });
};

const isLeader = (team: CampTeam, participant: TeamParticipant) =>
    team.leader_camp_payment_id === participant.id;
</script>

<template>
    <Head :title="`Equipes - ${camp.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <Link
                        :href="route('camps.show', camp.id)"
                        class="inline-flex items-center gap-1 rounded-md text-sm font-medium text-app-subtle hover:text-brand-800 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2"
                    >
                        <ArrowLeft class="h-4 w-4" aria-hidden="true" />
                        Central do acampamento
                    </Link>
                    <h2 class="mt-1 text-xl font-semibold leading-tight text-app-text">
                        Equipes
                    </h2>
                    <p class="mt-1 text-sm text-app-subtle">
                        {{ camp.name }} - organize lideres e membros.
                    </p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <Link :href="route('camps.payments.index', camp.id)">
                        <SecondaryButton type="button">Pessoas</SecondaryButton>
                    </Link>
                    <Link :href="route('camps.rooms.index', camp.id)">
                        <SecondaryButton type="button">Quartos</SecondaryButton>
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
                    v-if="assignmentError"
                    class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"
                    role="alert"
                >
                    {{ assignmentError }}
                </div>

                <dl class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
                    <div class="app-card p-4">
                        <dt class="text-sm text-app-soft">Pessoas</dt>
                        <dd class="mt-1 text-2xl font-semibold text-app-text">
                            {{ summary.linked_people }}
                        </dd>
                    </div>
                    <div class="app-card p-4">
                        <dt class="text-sm text-app-soft">Com equipe</dt>
                        <dd class="mt-1 text-2xl font-semibold text-success">
                            {{ summary.assigned_people }}
                        </dd>
                    </div>
                    <div class="app-card p-4">
                        <dt class="text-sm text-app-soft">Sem equipe</dt>
                        <dd class="mt-1 text-2xl font-semibold text-warning">
                            {{ summary.unassigned_people }}
                        </dd>
                    </div>
                    <div class="app-card p-4">
                        <dt class="text-sm text-app-soft">Equipes</dt>
                        <dd class="mt-1 text-2xl font-semibold text-app-text">
                            {{ summary.teams }}
                        </dd>
                    </div>
                    <div class="app-card p-4">
                        <dt class="text-sm text-app-soft">Distribuicao</dt>
                        <dd class="mt-1 text-2xl font-semibold text-app-text">
                            {{ assignmentLabel }}
                        </dd>
                    </div>
                </dl>

                <section class="app-card p-5">
                    <div class="flex flex-col gap-1">
                        <h3 class="text-lg font-semibold text-app-text">
                            Criar equipe
                        </h3>
                        <p class="text-sm text-app-subtle">
                            Defina a equipe e, se ja souber, escolha um lider vinculado ao acampamento.
                        </p>
                    </div>

                    <form
                        class="mt-4 grid gap-4 lg:grid-cols-[minmax(220px,1fr)_minmax(240px,1fr)_minmax(260px,1fr)_auto]"
                        @submit.prevent="createTeam"
                    >
                        <label class="block">
                            <span class="mb-1 block text-sm font-medium text-app-text">
                                Nome da equipe
                            </span>
                            <input
                                v-model="createForm.name"
                                type="text"
                                required
                                class="block w-full rounded-md border-app-border shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                placeholder="Ex.: Equipe Azul"
                            />
                            <InputError class="mt-2" :message="createForm.errors.name" />
                        </label>

                        <label class="block">
                            <span class="mb-1 block text-sm font-medium text-app-text">
                                Lider
                            </span>
                            <select
                                v-model="createForm.leader_camp_payment_id"
                                class="block w-full rounded-md border-app-border shadow-sm focus:border-brand-500 focus:ring-brand-500"
                            >
                                <option value="">Sem lider definido</option>
                                <option
                                    v-for="leader in leaderOptions"
                                    :key="leader.id"
                                    :value="leader.id"
                                >
                                    {{ leader.user.name }} - {{ leader.user.role?.name ?? leader.user.email }}
                                </option>
                            </select>
                            <InputError
                                class="mt-2"
                                :message="createForm.errors.leader_camp_payment_id"
                            />
                        </label>

                        <label class="block">
                            <span class="mb-1 block text-sm font-medium text-app-text">
                                Observacao
                            </span>
                            <input
                                v-model="createForm.notes"
                                type="text"
                                class="block w-full rounded-md border-app-border shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                placeholder="Ex.: adolescentes, cozinha, apoio"
                            />
                            <InputError class="mt-2" :message="createForm.errors.notes" />
                        </label>

                        <div class="flex items-end">
                            <PrimaryButton
                                :class="{ 'opacity-25': createForm.processing }"
                                :disabled="createForm.processing"
                            >
                                <Plus class="mr-2 h-4 w-4" aria-hidden="true" />
                                Criar
                            </PrimaryButton>
                        </div>
                    </form>
                </section>

                <section class="space-y-4">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <h3 class="text-lg font-semibold text-app-text">
                                Distribuicao das equipes
                            </h3>
                            <p class="mt-1 text-sm text-app-subtle">
                                Use a lista compacta para leitura rapida ou os cartoes para arrastar pessoas.
                            </p>
                        </div>

                        <div class="inline-flex rounded-md border border-app-border bg-app-surface p-1 shadow-sm">
                            <button
                                type="button"
                                class="inline-flex items-center gap-2 rounded px-3 py-2 text-sm font-medium transition"
                                :class="
                                    viewMode === 'compact'
                                        ? 'bg-brand-700 text-white'
                                        : 'text-app-subtle hover:bg-app-muted hover:text-app-text'
                                "
                                @click="viewMode = 'compact'"
                            >
                                <List class="h-4 w-4" aria-hidden="true" />
                                Compacto
                            </button>
                            <button
                                type="button"
                                class="inline-flex items-center gap-2 rounded px-3 py-2 text-sm font-medium transition"
                                :class="
                                    viewMode === 'board'
                                        ? 'bg-brand-700 text-white'
                                        : 'text-app-subtle hover:bg-app-muted hover:text-app-text'
                                "
                                @click="viewMode = 'board'"
                            >
                                <Columns3 class="h-4 w-4" aria-hidden="true" />
                                Arrastar
                            </button>
                        </div>
                    </div>

                    <div v-if="viewMode === 'compact'" class="space-y-3">
                        <details
                            class="app-card overflow-hidden"
                            :open="unassignedPayments.length > 0"
                        >
                            <summary class="flex cursor-pointer items-center justify-between gap-4 bg-app-muted px-4 py-3">
                                <span>
                                    <span class="block font-semibold text-app-text">
                                        Sem equipe
                                    </span>
                                    <span class="text-sm text-app-subtle">
                                        Pessoas ainda sem grupo definido.
                                    </span>
                                </span>
                                <span class="rounded-full bg-warning/10 px-2.5 py-1 text-sm font-semibold text-warning">
                                    {{ unassignedPayments.length }}
                                </span>
                            </summary>

                            <div class="divide-y divide-app-border px-4">
                                <p
                                    v-if="unassignedPayments.length === 0"
                                    class="py-6 text-center text-sm text-app-subtle"
                                >
                                    Todas as pessoas vinculadas ja estao em equipes.
                                </p>

                                <div
                                    v-for="participant in unassignedPayments"
                                    :key="participant.id"
                                    class="grid gap-3 py-3 md:grid-cols-[1fr_260px] md:items-center"
                                >
                                    <div class="min-w-0">
                                        <Link
                                            :href="route('users.show', participant.user.id)"
                                            class="truncate text-sm font-medium text-app-text hover:text-brand-800"
                                        >
                                            {{ participant.user.name }}
                                        </Link>
                                        <p class="truncate text-xs text-app-soft">
                                            {{ participantSubtitle(participant) }}
                                        </p>
                                    </div>

                                    <select
                                        :value="participant.camp_team_id ?? ''"
                                        class="block w-full rounded-md border-app-border text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                        @change="changeTeam(participant.id, $event)"
                                    >
                                        <option value="">Sem equipe</option>
                                        <option
                                            v-for="team in teams"
                                            :key="team.id"
                                            :value="team.id"
                                        >
                                            {{ team.name }} ({{ team.members_count }})
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </details>

                        <details
                            v-for="team in teams"
                            :key="team.id"
                            class="app-card overflow-hidden"
                        >
                            <summary class="flex cursor-pointer items-center justify-between gap-4 bg-app-muted px-4 py-3">
                                <span class="min-w-0">
                                    <span class="flex items-center gap-2 font-semibold text-app-text">
                                        <Users class="h-4 w-4 text-brand-700" aria-hidden="true" />
                                        {{ team.name }}
                                    </span>
                                    <span class="mt-1 block truncate text-sm text-app-subtle">
                                        Lider: {{ leaderName(team) }}
                                        <span v-if="team.notes">- {{ team.notes }}</span>
                                    </span>
                                </span>
                                <span class="shrink-0 rounded-full bg-brand-100 px-2.5 py-1 text-sm font-semibold text-brand-800">
                                    {{ team.members_count }}
                                </span>
                            </summary>

                            <div class="space-y-4 p-4">
                                <form
                                    v-if="editingTeamId === team.id"
                                    class="grid gap-3 lg:grid-cols-[1fr_240px_1fr_auto]"
                                    @submit.prevent="updateTeam(team)"
                                >
                                    <label class="block">
                                        <span class="mb-1 block text-sm font-medium text-app-text">Nome</span>
                                        <input
                                            v-model="editForm.name"
                                            type="text"
                                            required
                                            class="block w-full rounded-md border-app-border text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                        />
                                        <InputError class="mt-2" :message="editForm.errors.name" />
                                    </label>
                                    <label class="block">
                                        <span class="mb-1 block text-sm font-medium text-app-text">Lider</span>
                                        <select
                                            v-model="editForm.leader_camp_payment_id"
                                            class="block w-full rounded-md border-app-border text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                        >
                                            <option value="">Sem lider definido</option>
                                            <option
                                                v-for="leader in leaderOptions"
                                                :key="leader.id"
                                                :value="leader.id"
                                            >
                                                {{ leader.user.name }} - {{ leader.user.role?.name ?? leader.user.email }}
                                            </option>
                                        </select>
                                        <InputError class="mt-2" :message="editForm.errors.leader_camp_payment_id" />
                                    </label>
                                    <label class="block">
                                        <span class="mb-1 block text-sm font-medium text-app-text">Observacao</span>
                                        <input
                                            v-model="editForm.notes"
                                            type="text"
                                            class="block w-full rounded-md border-app-border text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                        />
                                        <InputError class="mt-2" :message="editForm.errors.notes" />
                                    </label>
                                    <div class="flex items-end gap-2">
                                        <SecondaryButton type="button" @click="cancelEditingTeam">
                                            Cancelar
                                        </SecondaryButton>
                                        <PrimaryButton
                                            :class="{ 'opacity-25': editForm.processing }"
                                            :disabled="editForm.processing"
                                        >
                                            Salvar
                                        </PrimaryButton>
                                    </div>
                                </form>

                                <div v-else class="flex flex-wrap justify-end gap-2">
                                    <SecondaryButton type="button" @click="startEditingTeam(team)">
                                        Editar
                                    </SecondaryButton>
                                    <button
                                        type="button"
                                        class="inline-flex items-center rounded-md px-3 py-2 text-sm font-medium text-danger transition hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-danger focus:ring-offset-2"
                                        @click="removeTeam(team)"
                                    >
                                        <Trash2 class="mr-2 h-4 w-4" aria-hidden="true" />
                                        Remover
                                    </button>
                                </div>

                                <div class="divide-y divide-app-border">
                                    <p
                                        v-if="team.members.length === 0"
                                        class="py-6 text-center text-sm text-app-subtle"
                                    >
                                        Nenhuma pessoa nesta equipe.
                                    </p>

                                    <div
                                        v-for="participant in team.members"
                                        :key="participant.id"
                                        class="grid gap-3 py-3 md:grid-cols-[1fr_260px] md:items-center"
                                    >
                                        <div class="min-w-0">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <Link
                                                    :href="route('users.show', participant.user.id)"
                                                    class="truncate text-sm font-medium text-app-text hover:text-brand-800"
                                                >
                                                    {{ participant.user.name }}
                                                </Link>
                                                <span
                                                    v-if="isLeader(team, participant)"
                                                    class="rounded-full bg-brand-100 px-2 py-0.5 text-xs font-medium text-brand-800"
                                                >
                                                    Lider
                                                </span>
                                            </div>
                                            <p class="truncate text-xs text-app-soft">
                                                {{ participantSubtitle(participant) }}
                                            </p>
                                        </div>

                                        <select
                                            :value="participant.camp_team_id ?? ''"
                                            class="block w-full rounded-md border-app-border text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                            @change="changeTeam(participant.id, $event)"
                                        >
                                            <option value="">Sem equipe</option>
                                            <option
                                                v-for="optionTeam in teams"
                                                :key="optionTeam.id"
                                                :value="optionTeam.id"
                                            >
                                                {{ optionTeam.name }} ({{ optionTeam.members_count }})
                                            </option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </details>

                        <div
                            v-if="teams.length === 0"
                            class="app-card p-8 text-center text-sm text-app-subtle"
                        >
                            Crie a primeira equipe para comecar a distribuicao.
                        </div>
                    </div>

                    <div v-else class="grid gap-4 xl:grid-cols-[minmax(280px,340px)_1fr]">
                        <section
                            class="app-card flex min-h-80 flex-col overflow-hidden"
                            :class="{
                                'border-brand-400 ring-2 ring-brand-200':
                                    dragOverTarget === targetKey(null),
                            }"
                            @dragenter.prevent="dragOverTarget = targetKey(null)"
                            @dragover.prevent
                            @dragleave="dragOverTarget = null"
                            @drop.prevent="dropOn(null)"
                        >
                            <div class="border-b border-app-border bg-app-muted px-4 py-3">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <h4 class="font-semibold text-app-text">
                                            Sem equipe
                                        </h4>
                                        <p class="mt-1 text-sm text-app-subtle">
                                            Pessoas ainda sem grupo definido.
                                        </p>
                                    </div>
                                    <span class="rounded-full bg-warning/10 px-2.5 py-1 text-sm font-semibold text-warning">
                                        {{ unassignedPayments.length }}
                                    </span>
                                </div>
                            </div>

                            <div class="flex-1 space-y-3 p-4">
                                <p
                                    v-if="unassignedPayments.length === 0"
                                    class="rounded-lg border border-dashed border-app-border px-4 py-6 text-center text-sm text-app-subtle"
                                >
                                    Todas as pessoas vinculadas ja estao em equipes.
                                </p>

                                <article
                                    v-for="participant in unassignedPayments"
                                    :key="participant.id"
                                    draggable="true"
                                    class="rounded-lg border border-app-border bg-app-surface p-3 shadow-sm"
                                    @dragstart="startDrag(participant.id)"
                                    @dragend="clearDrag"
                                >
                                    <div class="flex items-start gap-3">
                                        <GripVertical
                                            class="mt-0.5 h-4 w-4 shrink-0 text-app-soft"
                                            aria-hidden="true"
                                        />
                                        <div class="min-w-0 flex-1">
                                            <Link
                                                :href="route('users.show', participant.user.id)"
                                                class="truncate text-sm font-medium text-app-text hover:text-brand-800"
                                            >
                                                {{ participant.user.name }}
                                            </Link>
                                            <p class="truncate text-xs text-app-soft">
                                                {{ participantSubtitle(participant) }}
                                            </p>
                                        </div>
                                    </div>

                                    <label class="mt-3 block">
                                        <span class="sr-only">Mover pessoa</span>
                                        <select
                                            :value="participant.camp_team_id ?? ''"
                                            class="block w-full rounded-md border-app-border text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                            @change="changeTeam(participant.id, $event)"
                                        >
                                            <option value="">Sem equipe</option>
                                            <option
                                                v-for="team in teams"
                                                :key="team.id"
                                                :value="team.id"
                                            >
                                                {{ team.name }} ({{ team.members_count }})
                                            </option>
                                        </select>
                                    </label>
                                </article>
                            </div>
                        </section>

                        <div class="grid gap-4 lg:grid-cols-2">
                            <article
                                v-for="team in teams"
                                :key="team.id"
                                class="app-card flex min-h-80 flex-col overflow-hidden"
                                :class="{
                                    'border-brand-400 ring-2 ring-brand-200':
                                        dragOverTarget === targetKey(team.id),
                                }"
                                @dragenter.prevent="dragOverTarget = targetKey(team.id)"
                                @dragover.prevent
                                @dragleave="dragOverTarget = null"
                                @drop.prevent="dropOn(team.id)"
                            >
                                <div class="border-b border-app-border bg-app-muted px-4 py-3">
                                    <form
                                        v-if="editingTeamId === team.id"
                                        class="space-y-3"
                                        @submit.prevent="updateTeam(team)"
                                    >
                                        <label class="block">
                                            <span class="mb-1 block text-sm font-medium text-app-text">
                                                Nome
                                            </span>
                                            <input
                                                v-model="editForm.name"
                                                type="text"
                                                required
                                                class="block w-full rounded-md border-app-border text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                            />
                                            <InputError
                                                class="mt-2"
                                                :message="editForm.errors.name"
                                            />
                                        </label>

                                        <label class="block">
                                            <span class="mb-1 block text-sm font-medium text-app-text">
                                                Lider
                                            </span>
                                            <select
                                                v-model="editForm.leader_camp_payment_id"
                                                class="block w-full rounded-md border-app-border text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                            >
                                                <option value="">Sem lider definido</option>
                                                <option
                                                    v-for="leader in leaderOptions"
                                                    :key="leader.id"
                                                    :value="leader.id"
                                                >
                                                    {{ leader.user.name }} - {{ leader.user.role?.name ?? leader.user.email }}
                                                </option>
                                            </select>
                                            <InputError
                                                class="mt-2"
                                                :message="editForm.errors.leader_camp_payment_id"
                                            />
                                        </label>

                                        <label class="block">
                                            <span class="mb-1 block text-sm font-medium text-app-text">
                                                Observacao
                                            </span>
                                            <input
                                                v-model="editForm.notes"
                                                type="text"
                                                class="block w-full rounded-md border-app-border text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                            />
                                            <InputError
                                                class="mt-2"
                                                :message="editForm.errors.notes"
                                            />
                                        </label>

                                        <div class="flex flex-wrap justify-end gap-2">
                                            <SecondaryButton
                                                type="button"
                                                @click="cancelEditingTeam"
                                            >
                                                Cancelar
                                            </SecondaryButton>
                                            <PrimaryButton
                                                :class="{ 'opacity-25': editForm.processing }"
                                                :disabled="editForm.processing"
                                            >
                                                Salvar
                                            </PrimaryButton>
                                        </div>
                                    </form>

                                    <div v-else>
                                        <div class="flex items-start justify-between gap-3">
                                            <div>
                                                <div class="flex items-center gap-2">
                                                    <Users
                                                        class="h-5 w-5 text-brand-700"
                                                        aria-hidden="true"
                                                    />
                                                    <h4 class="font-semibold text-app-text">
                                                        {{ team.name }}
                                                    </h4>
                                                </div>
                                                <p class="mt-1 text-sm text-app-subtle">
                                                    Lider: {{ leaderName(team) }}
                                                </p>
                                                <p
                                                    v-if="team.notes"
                                                    class="mt-1 text-sm text-app-subtle"
                                                >
                                                    {{ team.notes }}
                                                </p>
                                            </div>
                                            <span class="rounded-full bg-brand-100 px-2.5 py-1 text-sm font-semibold text-brand-800">
                                                {{ team.members_count }}
                                            </span>
                                        </div>

                                        <div class="mt-4 flex flex-wrap justify-end gap-2">
                                            <SecondaryButton
                                                type="button"
                                                @click="startEditingTeam(team)"
                                            >
                                                Editar
                                            </SecondaryButton>
                                            <button
                                                type="button"
                                                class="inline-flex items-center rounded-md px-3 py-2 text-sm font-medium text-danger transition hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-danger focus:ring-offset-2"
                                                @click="removeTeam(team)"
                                            >
                                                <Trash2
                                                    class="mr-2 h-4 w-4"
                                                    aria-hidden="true"
                                                />
                                                Remover
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex-1 space-y-3 p-4">
                                    <p
                                        v-if="team.members.length === 0"
                                        class="rounded-lg border border-dashed border-app-border px-4 py-6 text-center text-sm text-app-subtle"
                                    >
                                        Arraste pessoas para esta equipe.
                                    </p>

                                    <article
                                        v-for="participant in team.members"
                                        :key="participant.id"
                                        draggable="true"
                                        class="rounded-lg border border-app-border bg-app-surface p-3 shadow-sm"
                                        @dragstart="startDrag(participant.id)"
                                        @dragend="clearDrag"
                                    >
                                        <div class="flex items-start gap-3">
                                            <GripVertical
                                                class="mt-0.5 h-4 w-4 shrink-0 text-app-soft"
                                                aria-hidden="true"
                                            />
                                            <div class="min-w-0 flex-1">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <Link
                                                        :href="route('users.show', participant.user.id)"
                                                        class="truncate text-sm font-medium text-app-text hover:text-brand-800"
                                                    >
                                                        {{ participant.user.name }}
                                                    </Link>
                                                    <span
                                                        v-if="isLeader(team, participant)"
                                                        class="rounded-full bg-brand-100 px-2 py-0.5 text-xs font-medium text-brand-800"
                                                    >
                                                        Lider
                                                    </span>
                                                </div>
                                                <p class="truncate text-xs text-app-soft">
                                                    {{ participantSubtitle(participant) }}
                                                </p>
                                            </div>
                                        </div>

                                        <label class="mt-3 block">
                                            <span class="sr-only">Mover pessoa</span>
                                            <select
                                                :value="participant.camp_team_id ?? ''"
                                                class="block w-full rounded-md border-app-border text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                                @change="changeTeam(participant.id, $event)"
                                            >
                                                <option value="">Sem equipe</option>
                                                <option
                                                    v-for="optionTeam in teams"
                                                    :key="optionTeam.id"
                                                    :value="optionTeam.id"
                                                >
                                                    {{ optionTeam.name }} ({{ optionTeam.members_count }})
                                                </option>
                                            </select>
                                        </label>
                                    </article>
                                </div>
                            </article>

                            <div
                                v-if="teams.length === 0"
                                class="app-card p-8 text-center text-sm text-app-subtle"
                            >
                                Crie a primeira equipe para comecar a distribuicao.
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
