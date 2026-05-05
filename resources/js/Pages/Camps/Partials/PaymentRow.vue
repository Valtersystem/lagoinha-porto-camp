<script setup lang="ts">
import DangerButton from '@/Components/DangerButton.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import type { CampLot, CampPayment, StatusOption } from '@/types';
import { Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps<{
    campId: number;
    campAmount: string;
    payment: CampPayment;
    lots: CampLot[];
    statusOptions: StatusOption[];
}>();

const form = useForm({
    status: props.payment.status,
    camp_lot_id: props.payment.camp_lot_id ?? '',
    installments_count: props.payment.installments_count,
    paid_installments: props.payment.paid_installments,
    amount_paid: props.payment.amount_paid,
    notes: props.payment.notes ?? '',
});

const isEditing = ref(false);

const money = (value: string | number) =>
    new Intl.NumberFormat('pt-PT', {
        style: 'currency',
        currency: 'EUR',
    }).format(Number(value));

const parseMoney = (value: string | number) => {
    const normalized = String(value).replace(',', '.').trim();
    const parsed = Number(normalized);

    return Number.isFinite(parsed) ? parsed : 0;
};

const formatMoneyInput = (value: number) => value.toFixed(2);

const statusClass = (status: string) => {
    if (status === 'paid') {
        return 'bg-green-50 text-green-700 ring-green-200';
    }

    if (status === 'partial') {
        return 'bg-blue-50 text-blue-700 ring-blue-200';
    }

    if (status === 'exempted') {
        return 'bg-amber-50 text-amber-700 ring-amber-200';
    }

    return 'bg-slate-50 text-slate-700 ring-slate-200';
};

const currentLotName = computed(() => props.payment.lot?.name ?? 'Valor base');

const selectedLot = computed(() =>
    props.lots.find((lot) => lot.id === Number(form.camp_lot_id)),
);

const selectedTotalAmount = computed(() =>
    selectedLot.value ? parseMoney(selectedLot.value.amount) : parseMoney(props.campAmount),
);

const selectedTotalAmountLabel = computed(() => money(selectedTotalAmount.value));

const previewPaidInstallments = computed(() => {
    if (form.status === 'paid') {
        return Number(form.installments_count);
    }

    if (form.status === 'pending' || form.status === 'exempted') {
        return 0;
    }

    return Math.min(Number(form.paid_installments) || 0, Number(form.installments_count) || 0);
});

const previewAmountPaid = computed(() => {
    if (form.status === 'paid') {
        return selectedTotalAmount.value;
    }

    if (form.status === 'pending' || form.status === 'exempted') {
        return 0;
    }

    return Math.min(parseMoney(form.amount_paid), selectedTotalAmount.value);
});

const previewOutstandingAmount = computed(() =>
    Math.max(selectedTotalAmount.value - previewAmountPaid.value, 0),
);

const selectedStatusLabel = computed(
    () =>
        props.statusOptions.find((status) => status.value === form.status)?.label ??
        props.payment.status_label,
);

const statusHelpText = computed(() => {
    if (form.status === 'partial') {
        return 'Informe o que ja foi recebido. Se escrever um valor ou parcelas, o sistema guarda como parcial.';
    }

    if (form.status === 'paid') {
        return 'Ao guardar como pago, o sistema marca o valor total e todas as parcelas como concluidas.';
    }

    if (form.status === 'exempted') {
        return 'Liberado deixa a pessoa sem obrigacao de pagamento e regista essa decisao do admin.';
    }

    return 'Pendente significa que ainda nao entrou valor. Se comecar a informar valor ou parcelas, o sistema muda para parcial.';
});

const paymentProgress = computed(
    () => `${props.payment.paid_installments}/${props.payment.installments_count} parcelas`,
);

const financialFieldsDisabled = computed(
    () => form.status === 'paid' || form.status === 'exempted',
);

const resetForm = () => {
    form.status = props.payment.status;
    form.camp_lot_id = props.payment.camp_lot_id ?? '';
    form.installments_count = props.payment.installments_count;
    form.paid_installments = props.payment.paid_installments;
    form.amount_paid = props.payment.amount_paid;
    form.notes = props.payment.notes ?? '';
    form.clearErrors();
};

const startEditing = () => {
    resetForm();
    isEditing.value = true;
};

const cancelEditing = () => {
    resetForm();
    isEditing.value = false;
};

const submit = () => {
    form.patch(route('camps.payments.update', [props.campId, props.payment.id]), {
        preserveScroll: true,
        onSuccess: () => {
            isEditing.value = false;
        },
    });
};

const unlinkUser = () => {
    if (!window.confirm(`Remover ${props.payment.user.name} deste acampamento?`)) {
        return;
    }

    router.delete(route('camps.payments.destroy', [props.campId, props.payment.id]), {
        preserveScroll: true,
    });
};

watch(
    () => form.status,
    (status) => {
        if (status === 'pending' || status === 'exempted') {
            form.paid_installments = 0;
            form.amount_paid = '0.00';
        }

        if (status === 'paid') {
            form.paid_installments = Number(form.installments_count) || 1;
            form.amount_paid = formatMoneyInput(selectedTotalAmount.value);
        }
    },
);

watch(
    () => form.installments_count,
    (value) => {
        const installmentsCount = Math.max(Number(value) || 1, 1);

        if (form.status === 'paid') {
            form.paid_installments = installmentsCount;
        } else if (Number(form.paid_installments) > installmentsCount) {
            form.paid_installments = installmentsCount;
        }
    },
);

watch(
    () => selectedTotalAmount.value,
    (value) => {
        if (form.status === 'paid') {
            form.amount_paid = formatMoneyInput(value);
            return;
        }

        if (parseMoney(form.amount_paid) > value) {
            form.amount_paid = formatMoneyInput(value);
        }
    },
);

watch(
    () => [form.amount_paid, form.paid_installments] as const,
    ([amountPaid, paidInstallments]) => {
        const hasProgress = parseMoney(amountPaid) > 0 || Number(paidInstallments) > 0;

        if (form.status === 'pending' && hasProgress) {
            form.status = 'partial';
        }
    },
);
</script>

<template>
    <article class="border-b border-app-border">
        <div class="grid gap-4 px-4 py-5 xl:grid-cols-[minmax(240px,1fr)_minmax(520px,2fr)_auto]">
            <div>
                <Link
                    :href="route('users.show', payment.user.id)"
                    class="font-medium text-app-text hover:text-brand-800"
                >
                    {{ payment.user.name }}
                </Link>
                <div class="mt-1 text-sm text-app-subtle">
                    {{ payment.user.phone ?? payment.user.email }}
                </div>
                <div
                    v-if="payment.user.phone"
                    class="mt-1 text-xs text-app-soft"
                >
                    {{ payment.user.email }}
                </div>
                <div class="mt-1 text-xs text-app-soft">
                    {{ payment.user.role?.name ?? 'Sem papel' }}
                </div>
            </div>

            <dl class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-lg border border-app-border bg-app-muted/50 px-3 py-2">
                    <dt class="text-xs font-medium uppercase tracking-wider text-app-soft">
                        Estado
                    </dt>
                    <dd class="mt-2">
                        <span
                            class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset"
                            :class="statusClass(payment.status)"
                        >
                            {{ payment.status_label }}
                        </span>
                    </dd>
                </div>

                <div class="rounded-lg border border-app-border bg-app-muted/50 px-3 py-2">
                    <dt class="text-xs font-medium uppercase tracking-wider text-app-soft">
                        Valor
                    </dt>
                    <dd class="mt-1 text-sm font-semibold text-app-text">
                        {{ money(payment.amount) }}
                    </dd>
                    <dd class="mt-1 text-xs text-app-soft">
                        {{ currentLotName }}
                    </dd>
                </div>

                <div class="rounded-lg border border-app-border bg-app-muted/50 px-3 py-2">
                    <dt class="text-xs font-medium uppercase tracking-wider text-app-soft">
                        Recebido
                    </dt>
                    <dd class="mt-1 text-sm font-semibold text-success">
                        {{ money(payment.amount_paid) }}
                    </dd>
                    <dd class="mt-1 text-xs text-app-soft">
                        Falta {{ money(Math.max(Number(payment.amount) - Number(payment.amount_paid), 0)) }}
                    </dd>
                </div>

                <div class="rounded-lg border border-app-border bg-app-muted/50 px-3 py-2">
                    <dt class="text-xs font-medium uppercase tracking-wider text-app-soft">
                        Parcelas
                    </dt>
                    <dd class="mt-1 text-sm font-semibold text-app-text">
                        {{ paymentProgress }}
                    </dd>
                    <dd class="mt-1 truncate text-xs text-app-soft">
                        {{ payment.notes || 'Sem observacao' }}
                    </dd>
                </div>
            </dl>

            <div class="flex flex-wrap items-start justify-end gap-2">
                <SecondaryButton
                    v-if="!isEditing"
                    type="button"
                    @click="startEditing"
                >
                    Editar pagamento
                </SecondaryButton>
                <DangerButton
                    v-if="!isEditing"
                    type="button"
                    @click="unlinkUser"
                >
                    Remover
                </DangerButton>
            </div>
        </div>

        <form
            v-if="isEditing"
            class="border-t border-app-border bg-app-muted/35 px-4 py-5"
            @submit.prevent="submit"
        >
            <div class="mb-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h4 class="text-sm font-semibold text-app-text">
                        Editar pagamento de {{ payment.user.name }}
                    </h4>
                    <p class="mt-1 text-sm text-app-subtle">
                        Resultado ao guardar: {{ selectedStatusLabel }}
                    </p>
                </div>
                <span class="text-sm text-app-soft">
                    Valor aplicado: {{ selectedTotalAmountLabel }}
                </span>
            </div>

            <div class="grid gap-3 sm:grid-cols-3 xl:grid-cols-4">
                <div class="rounded-lg border border-app-border bg-app-surface px-4 py-3">
                    <dt class="text-sm text-app-soft">Estado final</dt>
                    <dd class="mt-1 text-lg font-semibold text-app-text">
                        {{ selectedStatusLabel }}
                    </dd>
                </div>
                <div class="rounded-lg border border-app-border bg-app-surface px-4 py-3">
                    <dt class="text-sm text-app-soft">Recebido</dt>
                    <dd class="mt-1 text-lg font-semibold text-success">
                        {{ money(previewAmountPaid) }}
                    </dd>
                </div>
                <div class="rounded-lg border border-app-border bg-app-surface px-4 py-3">
                    <dt class="text-sm text-app-soft">Em aberto</dt>
                    <dd class="mt-1 text-lg font-semibold text-warning">
                        {{ money(previewOutstandingAmount) }}
                    </dd>
                </div>
                <div class="rounded-lg border border-app-border bg-app-surface px-4 py-3">
                    <dt class="text-sm text-app-soft">Parcelas</dt>
                    <dd class="mt-1 text-lg font-semibold text-app-text">
                        {{ previewPaidInstallments }}/{{ form.installments_count }}
                    </dd>
                </div>
            </div>

            <div class="mt-5 grid gap-4 lg:grid-cols-2 xl:grid-cols-4">
                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-app-text">
                        Estado do pagamento
                    </span>
                    <select
                        v-model="form.status"
                        class="block w-full rounded-md border-app-border text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                    >
                        <option
                            v-for="status in statusOptions"
                            :key="status.value"
                            :value="status.value"
                        >
                            {{ status.label }}
                        </option>
                    </select>
                    <InputError class="mt-2" :message="form.errors.status" />
                </label>

                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-app-text">
                        Lote / valor aplicado
                    </span>
                    <select
                        v-model="form.camp_lot_id"
                        class="block w-full rounded-md border-app-border text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                    >
                        <option value="">Valor base</option>
                        <option
                            v-for="lot in lots"
                            :key="lot.id"
                            :value="lot.id"
                        >
                            {{ lot.name }} - {{ money(lot.amount) }}
                        </option>
                    </select>
                    <InputError class="mt-2" :message="form.errors.camp_lot_id" />
                </label>

                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-app-text">
                        Total de parcelas
                    </span>
                    <input
                        v-model.number="form.installments_count"
                        type="number"
                        min="1"
                        max="24"
                        class="block w-full rounded-md border-app-border text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                    />
                    <InputError
                        class="mt-2"
                        :message="form.errors.installments_count"
                    />
                </label>

                <label class="block">
                    <span class="mb-1 block text-sm font-medium text-app-text">
                        Parcelas ja pagas
                    </span>
                    <input
                        v-model.number="form.paid_installments"
                        type="number"
                        min="0"
                        :max="form.installments_count"
                        :disabled="financialFieldsDisabled"
                        class="block w-full rounded-md border-app-border text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 disabled:cursor-not-allowed disabled:bg-app-muted/70"
                    />
                    <InputError
                        class="mt-2"
                        :message="form.errors.paid_installments"
                    />
                </label>

                <label class="block lg:col-span-2 xl:col-span-1">
                    <span class="mb-1 block text-sm font-medium text-app-text">
                        Valor ja recebido
                    </span>
                    <input
                        v-model="form.amount_paid"
                        type="text"
                        inputmode="decimal"
                        :disabled="financialFieldsDisabled"
                        class="block w-full rounded-md border-app-border text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 disabled:cursor-not-allowed disabled:bg-app-muted/70"
                        placeholder="0,00"
                    />
                    <InputError class="mt-2" :message="form.errors.amount_paid" />
                </label>

                <label class="block lg:col-span-2 xl:col-span-3">
                    <span class="mb-1 block text-sm font-medium text-app-text">
                        Observacao
                    </span>
                    <input
                        v-model="form.notes"
                        type="text"
                        class="block w-full rounded-md border-app-border text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500"
                        placeholder="Ex.: primeira parcela em dinheiro"
                    />
                    <InputError class="mt-2" :message="form.errors.notes" />
                </label>
            </div>

            <p class="mt-3 text-sm text-app-soft">
                {{ statusHelpText }}
            </p>

            <div class="mt-5 flex flex-wrap justify-end gap-2">
                <SecondaryButton type="button" @click="cancelEditing">
                    Cancelar
                </SecondaryButton>
                <PrimaryButton
                    type="submit"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Salvar pagamento
                </PrimaryButton>
            </div>
        </form>
    </article>
</template>
