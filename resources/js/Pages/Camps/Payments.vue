<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PaymentRow from '@/Pages/Camps/Partials/PaymentRow.vue';
import type { Camp, CampPayment, StatusOption, User } from '@/types';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedPayments {
    data: CampPayment[];
    links: PaginationLink[];
    from: number | null;
    to: number | null;
    total: number;
}

const props = defineProps<{
    camp: Camp;
    payments: PaginatedPayments;
    filters: {
        search: string;
        status: string;
    };
    statusOptions: StatusOption[];
    availableUsers: Pick<User, 'id' | 'name' | 'email' | 'phone' | 'sex_label'>[];
    summary: {
        total: number;
        pending: number;
        partial: number;
        paid: number;
        exempted: number;
    };
}>();

const page = usePage();
const search = ref(props.filters.search ?? '');
const selectedStatus = ref(props.filters.status ?? '');
const linkForm = useForm({
    user_id: '',
    camp_lot_id: props.camp.lots[0]?.id ?? '',
    installments_count: 1,
    notes: '',
});

const applyFilters = () => {
    router.get(
        route('camps.payments.index', props.camp.id),
        {
            search: search.value,
            status: selectedStatus.value,
        },
        {
            preserveState: true,
            replace: true,
        },
    );
};

const clearFilters = () => {
    search.value = '';
    selectedStatus.value = '';
    applyFilters();
};

const linkUser = () => {
    linkForm.post(route('camps.payments.store', props.camp.id), {
        preserveScroll: true,
        onSuccess: () => {
            linkForm.reset('user_id', 'notes');
            linkForm.installments_count = 1;
            linkForm.camp_lot_id = props.camp.lots[0]?.id ?? '';
        },
    });
};
</script>

<template>
    <Head :title="`Pessoas e financeiro - ${camp.name}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-app-text">
                        Pessoas e financeiro
                    </h2>
                    <p class="mt-1 text-sm text-app-subtle">
                        {{ camp.name }} - usuarios vinculados, lotes e parcelas.
                    </p>
                </div>

                <Link :href="route('camps.show', camp.id)">
                    <SecondaryButton type="button">Voltar a central</SecondaryButton>
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

                <dl class="grid gap-3 sm:grid-cols-5">
                    <div class="app-card p-4">
                        <dt class="text-sm text-app-soft">Pessoas</dt>
                        <dd class="mt-1 text-2xl font-semibold text-app-text">
                            {{ summary.total }}
                        </dd>
                    </div>
                    <div class="app-card p-4">
                        <dt class="text-sm text-app-soft">Pendentes</dt>
                        <dd class="mt-1 text-2xl font-semibold text-app-text">
                            {{ summary.pending }}
                        </dd>
                    </div>
                    <div class="app-card p-4">
                        <dt class="text-sm text-app-soft">Parciais</dt>
                        <dd class="mt-1 text-2xl font-semibold text-info">
                            {{ summary.partial }}
                        </dd>
                    </div>
                    <div class="app-card p-4">
                        <dt class="text-sm text-app-soft">Pagos</dt>
                        <dd class="mt-1 text-2xl font-semibold text-success">
                            {{ summary.paid }}
                        </dd>
                    </div>
                    <div class="app-card p-4">
                        <dt class="text-sm text-app-soft">Liberados</dt>
                        <dd class="mt-1 text-2xl font-semibold text-warning">
                            {{ summary.exempted }}
                        </dd>
                    </div>
                </dl>

                <section class="app-card p-5">
                    <div class="flex flex-col gap-1">
                        <h3 class="text-lg font-semibold text-app-text">
                            Adicionar pessoa ao acampamento
                        </h3>
                        <p class="text-sm text-app-subtle">
                            Ao vincular uma pessoa, o sistema cria a obrigacao financeira dela.
                        </p>
                    </div>

                    <form
                        class="mt-4 grid gap-4 lg:grid-cols-[minmax(240px,1fr)_190px_150px_minmax(220px,1fr)_auto]"
                        @submit.prevent="linkUser"
                    >
                        <label class="block">
                            <span class="mb-1 block text-sm font-medium text-app-text">
                                Pessoa
                            </span>
                            <select
                                v-model="linkForm.user_id"
                                required
                                class="block w-full rounded-md border-app-border shadow-sm focus:border-brand-500 focus:ring-brand-500"
                            >
                                <option value="" disabled>
                                    Selecione uma pessoa
                                </option>
                                <option
                                    v-for="user in availableUsers"
                                    :key="user.id"
                                    :value="user.id"
                                >
                                    {{ user.name }} - {{ user.sex_label ?? 'Sexo nao definido' }} - {{ user.phone ?? user.email }}
                                </option>
                            </select>
                            <InputError class="mt-2" :message="linkForm.errors.user_id" />
                        </label>

                        <label class="block">
                            <span class="mb-1 block text-sm font-medium text-app-text">
                                Lote inicial
                            </span>
                            <select
                                v-model="linkForm.camp_lot_id"
                                class="block w-full rounded-md border-app-border shadow-sm focus:border-brand-500 focus:ring-brand-500"
                            >
                                <option value="">Valor base</option>
                                <option
                                    v-for="lot in camp.lots"
                                    :key="lot.id"
                                    :value="lot.id"
                                >
                                    {{ lot.name }}
                                </option>
                            </select>
                            <InputError class="mt-2" :message="linkForm.errors.camp_lot_id" />
                        </label>

                        <label class="block">
                            <span class="mb-1 block text-sm font-medium text-app-text">
                                Parcelas
                            </span>
                            <input
                                v-model="linkForm.installments_count"
                                type="number"
                                min="1"
                                max="24"
                                required
                                class="block w-full rounded-md border-app-border shadow-sm focus:border-brand-500 focus:ring-brand-500"
                            />
                            <InputError
                                class="mt-2"
                                :message="linkForm.errors.installments_count"
                            />
                        </label>

                        <label class="block">
                            <span class="mb-1 block text-sm font-medium text-app-text">
                                Observacao inicial
                            </span>
                            <input
                                v-model="linkForm.notes"
                                type="text"
                                class="block w-full rounded-md border-app-border shadow-sm focus:border-brand-500 focus:ring-brand-500"
                                placeholder="Ex.: pagar em duas vezes"
                            />
                            <InputError class="mt-2" :message="linkForm.errors.notes" />
                        </label>

                        <div class="flex items-end">
                            <PrimaryButton
                                :class="{ 'opacity-25': linkForm.processing }"
                                :disabled="linkForm.processing || availableUsers.length === 0"
                            >
                                Vincular
                            </PrimaryButton>
                        </div>
                    </form>
                </section>

                <section class="app-card p-5">
                    <div class="flex flex-col gap-1">
                        <h3 class="text-lg font-semibold text-app-text">
                            Buscar e filtrar
                        </h3>
                        <p class="text-sm text-app-subtle">
                            Encontre rapidamente uma pessoa vinculada a este acampamento.
                        </p>
                    </div>

                    <form
                        class="mt-4 grid gap-4 md:grid-cols-[1fr_220px_auto]"
                        @submit.prevent="applyFilters"
                    >
                        <label class="block">
                            <span class="mb-1 block text-sm font-medium text-app-text">
                                Nome, email ou telefone
                            </span>
                            <TextInput
                                v-model="search"
                                type="search"
                                class="block w-full"
                                placeholder="Buscar por nome, email ou telefone"
                            />
                        </label>

                        <label class="block">
                            <span class="mb-1 block text-sm font-medium text-app-text">
                                Estado
                            </span>
                            <select
                                v-model="selectedStatus"
                                class="block w-full rounded-md border-app-border shadow-sm focus:border-brand-500 focus:ring-brand-500"
                            >
                                <option value="">Todos os estados</option>
                                <option
                                    v-for="status in statusOptions"
                                    :key="status.value"
                                    :value="status.value"
                                >
                                    {{ status.label }}
                                </option>
                            </select>
                        </label>

                        <div class="flex items-end gap-2">
                            <PrimaryButton type="submit">Filtrar</PrimaryButton>
                            <SecondaryButton type="button" @click="clearFilters">
                                Limpar
                            </SecondaryButton>
                        </div>
                    </form>
                </section>

                <div class="overflow-hidden app-card">
                    <div class="border-b border-app-border bg-app-muted px-4 py-4">
                        <h3 class="text-lg font-semibold text-app-text">
                            Pessoas vinculadas
                        </h3>
                        <p class="mt-1 text-sm text-app-subtle">
                            Use o resumo para conferir a situacao. Na edicao, o formulario
                            mostra o resultado final antes de guardar e muda para parcial
                            quando voce regista valor recebido.
                        </p>
                    </div>

                    <div
                        v-if="payments.data.length === 0"
                        class="p-8 text-center text-sm text-app-subtle"
                    >
                        Nenhum usuario vinculado a este acampamento.
                    </div>

                    <PaymentRow
                        v-for="payment in payments.data"
                        :key="payment.id"
                        :camp-id="camp.id"
                        :camp-amount="camp.amount"
                        :payment="payment"
                        :lots="camp.lots"
                        :status-options="statusOptions"
                    />

                    <div
                        class="flex flex-col gap-3 border-t border-app-border px-6 py-4 text-sm text-app-subtle sm:flex-row sm:items-center sm:justify-between"
                    >
                        <span>
                            Mostrando {{ payments.from ?? 0 }} a {{ payments.to ?? 0 }}
                            de {{ payments.total }} vinculos
                        </span>

                        <nav
                            class="flex flex-wrap gap-2"
                            aria-label="Paginacao de pessoas vinculadas"
                        >
                            <component
                                :is="link.url ? Link : 'span'"
                                v-for="link in payments.links"
                                :key="link.label"
                                :href="link.url || undefined"
                                class="rounded-md border px-3 py-1"
                                :class="[
                                    link.active
                                        ? 'border-brand-700 bg-brand-700 text-white'
                                        : 'border-app-border text-app-subtle',
                                    !link.url ? 'cursor-not-allowed opacity-50' : 'hover:bg-app-muted',
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
