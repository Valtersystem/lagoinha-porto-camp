<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { Camp, User } from '@/types';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ArrowLeft, Columns3, DoorOpen, GripVertical, List, Plus, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface RoomParticipant {
    id: number;
    camp_room_id?: number | null;
    status_label: string;
    user: User;
}

interface CampRoom {
    id: number;
    name: string;
    sex?: string | null;
    sex_label?: string | null;
    capacity: number;
    notes?: string | null;
    sort_order?: number;
    occupants_count: number;
    available_places: number;
    occupants: RoomParticipant[];
}

const props = defineProps<{
    camp: Camp;
    rooms: CampRoom[];
    unassignedPayments: RoomParticipant[];
    summary: {
        linked_people: number;
        assigned_people: number;
        unassigned_people: number;
        rooms: number;
        capacity: number;
    };
}>();

const page = usePage();
const draggedPaymentId = ref<number | null>(null);
const dragOverTarget = ref<string | null>(null);
const assignmentError = ref<string | null>(null);
const editingRoomId = ref<number | null>(null);
const viewMode = ref<'compact' | 'board'>('compact');

const createForm = useForm({
    name: '',
    sex: '',
    capacity: 4,
    notes: '',
});

const editForm = useForm({
    name: '',
    sex: '',
    capacity: 4,
    notes: '',
});

const occupancyLabel = computed(
    () => `${props.summary.assigned_people}/${props.summary.capacity || 0}`,
);

const targetKey = (roomId: number | null) =>
    roomId === null ? 'unassigned' : `room-${roomId}`;

const participantSubtitle = (participant: RoomParticipant) =>
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

const assignParticipant = (paymentId: number, roomId: number | null) => {
    assignmentError.value = null;

    router.patch(
        route('camps.rooms.assignments.update', props.camp.id),
        {
            camp_payment_id: paymentId,
            camp_room_id: roomId,
        },
        {
            preserveScroll: true,
            onError: (errors) => {
                assignmentError.value =
                    errors.camp_room_id ??
                    errors.camp_payment_id ??
                    'Nao foi possivel mover esta pessoa.';
            },
            onFinish: clearDrag,
        },
    );
};

const dropOn = (roomId: number | null) => {
    if (draggedPaymentId.value === null) {
        return;
    }

    assignParticipant(draggedPaymentId.value, roomId);
};

const changeRoom = (paymentId: number, event: Event) => {
    const value = (event.target as HTMLSelectElement).value;

    assignParticipant(paymentId, value === '' ? null : Number(value));
};

const createRoom = () => {
    createForm.post(route('camps.rooms.store', props.camp.id), {
        preserveScroll: true,
        onSuccess: () => {
            createForm.reset();
            createForm.sex = '';
            createForm.capacity = 4;
        },
    });
};

const startEditingRoom = (room: CampRoom) => {
    editingRoomId.value = room.id;
    editForm.name = room.name;
    editForm.sex = room.sex ?? '';
    editForm.capacity = room.capacity;
    editForm.notes = room.notes ?? '';
    editForm.clearErrors();
};

const cancelEditingRoom = () => {
    editingRoomId.value = null;
    editForm.clearErrors();
};

const updateRoom = (room: CampRoom) => {
    editForm.patch(route('camps.rooms.update', [props.camp.id, room.id]), {
        preserveScroll: true,
        onSuccess: () => {
            editingRoomId.value = null;
        },
    });
};

const removeRoom = (room: CampRoom) => {
    if (
        !window.confirm(
            `Remover ${room.name}? As pessoas deste quarto ficarao sem quarto definido.`,
        )
    ) {
        return;
    }

    router.delete(route('camps.rooms.destroy', [props.camp.id, room.id]), {
        preserveScroll: true,
    });
};

const isRoomOptionDisabled = (
    room: CampRoom,
    currentRoomId?: number | null,
    participantSex?: string | null,
) =>
    (room.available_places <= 0 && currentRoomId !== room.id) ||
    (participantSex != null && room.sex !== participantSex);
</script>

<template>
    <Head :title="`Quartos - ${camp.name}`" />

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
                        Quartos
                    </h2>
                    <p class="mt-1 text-sm text-app-subtle">
                        {{ camp.name }} - organize onde cada pessoa vai dormir.
                    </p>
                </div>

                <Link :href="route('camps.payments.index', camp.id)">
                    <SecondaryButton type="button">Pessoas e financeiro</SecondaryButton>
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
                        <dt class="text-sm text-app-soft">Com quarto</dt>
                        <dd class="mt-1 text-2xl font-semibold text-success">
                            {{ summary.assigned_people }}
                        </dd>
                    </div>
                    <div class="app-card p-4">
                        <dt class="text-sm text-app-soft">Sem quarto</dt>
                        <dd class="mt-1 text-2xl font-semibold text-warning">
                            {{ summary.unassigned_people }}
                        </dd>
                    </div>
                    <div class="app-card p-4">
                        <dt class="text-sm text-app-soft">Quartos</dt>
                        <dd class="mt-1 text-2xl font-semibold text-app-text">
                            {{ summary.rooms }}
                        </dd>
                    </div>
                    <div class="app-card p-4">
                        <dt class="text-sm text-app-soft">Lotacao</dt>
                        <dd class="mt-1 text-2xl font-semibold text-app-text">
                            {{ occupancyLabel }}
                        </dd>
                    </div>
                </dl>

                <section class="app-card p-5">
                    <div class="flex flex-col gap-1">
                        <h3 class="text-lg font-semibold text-app-text">
                            Criar quarto
                        </h3>
                        <p class="text-sm text-app-subtle">
                            Defina o nome e a capacidade antes de distribuir as pessoas.
                        </p>
                    </div>

                    <form
                        class="mt-4 grid gap-4 lg:grid-cols-[minmax(220px,1fr)_140px_170px_minmax(260px,1fr)_auto]"
                        @submit.prevent="createRoom"
                    >
                        <label class="block">
                            <span class="mb-1 block text-sm font-medium text-app-text">
                                Nome do quarto
                            </span>
                            <input
                                v-model="createForm.name"
                                type="text"
                                required
                                class="block w-full rounded-md border-app-border shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                placeholder="Ex.: Quarto 01"
                            />
                            <InputError class="mt-2" :message="createForm.errors.name" />
                        </label>

                        <label class="block">
                            <span class="mb-1 block text-sm font-medium text-app-text">
                                Capacidade
                            </span>
                            <input
                                v-model="createForm.capacity"
                                type="number"
                                min="1"
                                max="100"
                                required
                                class="block w-full rounded-md border-app-border shadow-sm focus:border-brand-500 focus:ring-brand-500"
                            />
                            <InputError
                                class="mt-2"
                                :message="createForm.errors.capacity"
                            />
                        </label>

                        <label class="block">
                            <span class="mb-1 block text-sm font-medium text-app-text">
                                Tipo do quarto
                            </span>
                            <select
                                v-model="createForm.sex"
                                required
                                class="block w-full rounded-md border-app-border shadow-sm focus:border-brand-500 focus:ring-brand-500"
                            >
                                <option value="" disabled>Selecione</option>
                                <option value="male">Masculino</option>
                                <option value="female">Feminino</option>
                            </select>
                            <InputError class="mt-2" :message="createForm.errors.sex" />
                        </label>

                        <label class="block">
                            <span class="mb-1 block text-sm font-medium text-app-text">
                                Observacao
                            </span>
                            <input
                                v-model="createForm.notes"
                                type="text"
                                class="block w-full rounded-md border-app-border shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                placeholder="Ex.: beliches, piso, restricoes"
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
                                Distribuicao dos quartos
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
                                        Sem quarto
                                    </span>
                                    <span class="text-sm text-app-subtle">
                                        Pessoas ainda sem hospedagem definida.
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
                                    Todas as pessoas vinculadas ja estao em quartos.
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
                                        :value="participant.camp_room_id ?? ''"
                                        class="block w-full rounded-md border-app-border text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                        @change="changeRoom(participant.id, $event)"
                                    >
                                        <option value="">Sem quarto</option>
                                        <option
                                            v-for="room in rooms"
                                            :key="room.id"
                                            :value="room.id"
                                            :disabled="
                                                isRoomOptionDisabled(
                                                    room,
                                                    participant.camp_room_id,
                                                    participant.user.sex,
                                                )
                                            "
                                        >
                                            {{ room.name }} - {{ room.sex_label }}
                                            ({{ room.occupants_count }}/{{ room.capacity }})
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </details>

                        <details
                            v-for="room in rooms"
                            :key="room.id"
                            class="app-card overflow-hidden"
                        >
                            <summary class="flex cursor-pointer items-center justify-between gap-4 bg-app-muted px-4 py-3">
                                <span class="min-w-0">
                                    <span class="flex items-center gap-2 font-semibold text-app-text">
                                        <DoorOpen class="h-4 w-4 text-brand-700" aria-hidden="true" />
                                        {{ room.name }}
                                    </span>
                                    <span class="mt-1 block truncate text-sm text-app-subtle">
                                        {{ room.sex_label ?? 'Sexo nao definido' }}
                                        <span v-if="room.notes">- {{ room.notes }}</span>
                                    </span>
                                </span>
                                <span
                                    class="shrink-0 rounded-full px-2.5 py-1 text-sm font-semibold"
                                    :class="
                                        room.available_places === 0
                                            ? 'bg-warning/10 text-warning'
                                            : 'bg-brand-100 text-brand-800'
                                    "
                                >
                                    {{ room.occupants_count }}/{{ room.capacity }}
                                </span>
                            </summary>

                            <div class="space-y-4 p-4">
                                <form
                                    v-if="editingRoomId === room.id"
                                    class="grid gap-3 lg:grid-cols-[1fr_120px_150px_1fr_auto]"
                                    @submit.prevent="updateRoom(room)"
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
                                        <span class="mb-1 block text-sm font-medium text-app-text">Capacidade</span>
                                        <input
                                            v-model="editForm.capacity"
                                            type="number"
                                            min="1"
                                            max="100"
                                            required
                                            class="block w-full rounded-md border-app-border text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                        />
                                        <InputError class="mt-2" :message="editForm.errors.capacity" />
                                    </label>
                                    <label class="block">
                                        <span class="mb-1 block text-sm font-medium text-app-text">Tipo</span>
                                        <select
                                            v-model="editForm.sex"
                                            required
                                            class="block w-full rounded-md border-app-border text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                        >
                                            <option value="" disabled>Selecione</option>
                                            <option value="male">Masculino</option>
                                            <option value="female">Feminino</option>
                                        </select>
                                        <InputError class="mt-2" :message="editForm.errors.sex" />
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
                                        <SecondaryButton type="button" @click="cancelEditingRoom">
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
                                    <SecondaryButton type="button" @click="startEditingRoom(room)">
                                        Editar
                                    </SecondaryButton>
                                    <button
                                        type="button"
                                        class="inline-flex items-center rounded-md px-3 py-2 text-sm font-medium text-danger transition hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-danger focus:ring-offset-2"
                                        @click="removeRoom(room)"
                                    >
                                        <Trash2 class="mr-2 h-4 w-4" aria-hidden="true" />
                                        Remover
                                    </button>
                                </div>

                                <div class="divide-y divide-app-border">
                                    <p
                                        v-if="room.occupants.length === 0"
                                        class="py-6 text-center text-sm text-app-subtle"
                                    >
                                        Nenhuma pessoa neste quarto.
                                    </p>

                                    <div
                                        v-for="participant in room.occupants"
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
                                            :value="participant.camp_room_id ?? ''"
                                            class="block w-full rounded-md border-app-border text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                            @change="changeRoom(participant.id, $event)"
                                        >
                                            <option value="">Sem quarto</option>
                                            <option
                                                v-for="optionRoom in rooms"
                                                :key="optionRoom.id"
                                                :value="optionRoom.id"
                                                :disabled="
                                                    isRoomOptionDisabled(
                                                        optionRoom,
                                                        participant.camp_room_id,
                                                        participant.user.sex,
                                                    )
                                                "
                                            >
                                                {{ optionRoom.name }} - {{ optionRoom.sex_label }}
                                                ({{ optionRoom.occupants_count }}/{{ optionRoom.capacity }})
                                            </option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </details>

                        <div
                            v-if="rooms.length === 0"
                            class="app-card p-8 text-center text-sm text-app-subtle"
                        >
                            Crie o primeiro quarto para comecar a distribuicao.
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
                                            Sem quarto
                                        </h4>
                                        <p class="mt-1 text-sm text-app-subtle">
                                            Pessoas ainda sem hospedagem definida.
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
                                    Todas as pessoas vinculadas ja estao em quartos.
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
                                            :value="participant.camp_room_id ?? ''"
                                            class="block w-full rounded-md border-app-border text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                            @change="changeRoom(participant.id, $event)"
                                        >
                                            <option value="">Sem quarto</option>
                                            <option
                                                v-for="room in rooms"
                                                :key="room.id"
                                                :value="room.id"
                                                :disabled="
                                                    isRoomOptionDisabled(
                                                        room,
                                                        participant.camp_room_id,
                                                        participant.user.sex,
                                                    )
                                                "
                                            >
                                                {{ room.name }} - {{ room.sex_label }}
                                                ({{ room.occupants_count }}/{{ room.capacity }})
                                            </option>
                                        </select>
                                    </label>
                                </article>
                            </div>
                        </section>

                        <div class="grid gap-4 lg:grid-cols-2">
                            <article
                                v-for="room in rooms"
                                :key="room.id"
                                class="app-card flex min-h-80 flex-col overflow-hidden"
                                :class="{
                                    'border-brand-400 ring-2 ring-brand-200':
                                        dragOverTarget === targetKey(room.id),
                                }"
                                @dragenter.prevent="dragOverTarget = targetKey(room.id)"
                                @dragover.prevent
                                @dragleave="dragOverTarget = null"
                                @drop.prevent="dropOn(room.id)"
                            >
                                <div class="border-b border-app-border bg-app-muted px-4 py-3">
                                    <form
                                        v-if="editingRoomId === room.id"
                                        class="space-y-3"
                                        @submit.prevent="updateRoom(room)"
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
                                                Capacidade
                                            </span>
                                            <input
                                                v-model="editForm.capacity"
                                                type="number"
                                                min="1"
                                                max="100"
                                                required
                                                class="block w-full rounded-md border-app-border text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                            />
                                            <InputError
                                                class="mt-2"
                                                :message="editForm.errors.capacity"
                                            />
                                        </label>

                                        <label class="block">
                                            <span class="mb-1 block text-sm font-medium text-app-text">
                                                Tipo do quarto
                                            </span>
                                            <select
                                                v-model="editForm.sex"
                                                required
                                                class="block w-full rounded-md border-app-border text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                            >
                                                <option value="" disabled>Selecione</option>
                                                <option value="male">Masculino</option>
                                                <option value="female">Feminino</option>
                                            </select>
                                            <InputError
                                                class="mt-2"
                                                :message="editForm.errors.sex"
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
                                                @click="cancelEditingRoom"
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
                                                    <DoorOpen
                                                        class="h-5 w-5 text-brand-700"
                                                        aria-hidden="true"
                                                    />
                                                    <h4 class="font-semibold text-app-text">
                                                        {{ room.name }}
                                                    </h4>
                                                </div>
                                                <p class="mt-1 text-sm text-app-subtle">
                                                    {{ room.sex_label ?? 'Sexo nao definido' }}
                                                    <span v-if="room.notes">- {{ room.notes }}</span>
                                                </p>
                                            </div>
                                            <span
                                                class="rounded-full px-2.5 py-1 text-sm font-semibold"
                                                :class="
                                                    room.available_places === 0
                                                        ? 'bg-warning/10 text-warning'
                                                        : 'bg-brand-100 text-brand-800'
                                                "
                                            >
                                                {{ room.occupants_count }}/{{ room.capacity }}
                                            </span>
                                        </div>

                                        <div class="mt-4 flex flex-wrap justify-end gap-2">
                                            <SecondaryButton
                                                type="button"
                                                @click="startEditingRoom(room)"
                                            >
                                                Editar
                                            </SecondaryButton>
                                            <button
                                                type="button"
                                                class="inline-flex items-center rounded-md px-3 py-2 text-sm font-medium text-danger transition hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-danger focus:ring-offset-2"
                                                @click="removeRoom(room)"
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
                                        v-if="room.occupants.length === 0"
                                        class="rounded-lg border border-dashed border-app-border px-4 py-6 text-center text-sm text-app-subtle"
                                    >
                                        Arraste pessoas para este quarto.
                                    </p>

                                    <article
                                        v-for="participant in room.occupants"
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
                                                :value="participant.camp_room_id ?? ''"
                                                class="block w-full rounded-md border-app-border text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                                @change="changeRoom(participant.id, $event)"
                                            >
                                                <option value="">Sem quarto</option>
                                                <option
                                                    v-for="optionRoom in rooms"
                                                    :key="optionRoom.id"
                                                    :value="optionRoom.id"
                                                    :disabled="
                                                        isRoomOptionDisabled(
                                                            optionRoom,
                                                            participant.camp_room_id,
                                                            participant.user.sex,
                                                        )
                                                    "
                                                >
                                                    {{ optionRoom.name }}
                                                    - {{ optionRoom.sex_label }}
                                                    ({{ optionRoom.occupants_count }}/{{ optionRoom.capacity }})
                                                </option>
                                            </select>
                                        </label>
                                    </article>
                                </div>
                            </article>

                            <div
                                v-if="rooms.length === 0"
                                class="app-card p-8 text-center text-sm text-app-subtle"
                            >
                                Crie o primeiro quarto para comecar a distribuicao.
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
