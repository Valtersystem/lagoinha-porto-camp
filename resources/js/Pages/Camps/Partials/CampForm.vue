<script setup lang="ts">
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import type { Camp } from '@/types';
import { Link, useForm } from '@inertiajs/vue3';

interface LotForm {
    name: string;
    amount: string;
}

const props = defineProps<{
    camp?: Camp;
    mode: 'create' | 'edit';
}>();

const form = useForm({
    name: props.camp?.name ?? '',
    description: props.camp?.description ?? '',
    address: props.camp?.address ?? '',
    starts_on: props.camp?.starts_on ?? '',
    ends_on: props.camp?.ends_on ?? '',
    amount: props.camp?.amount ?? '',
    is_active: props.camp?.is_active ?? true,
    lots: props.camp?.lots?.length
        ? props.camp.lots.map((lot) => ({
              name: lot.name,
              amount: lot.amount,
          }))
        : [
              {
                  name: 'Primeiro lote',
                  amount: props.camp?.amount ?? '',
              },
          ],
});

const addLot = () => {
    form.lots.push({
        name: `${form.lots.length + 1}o lote`,
        amount: form.amount,
    });
};

const removeLot = (index: number) => {
    if (form.lots.length === 1) {
        return;
    }

    form.lots.splice(index, 1);
};

const fieldError = (field: string) =>
    (form.errors as Record<string, string | undefined>)[field];

const submit = () => {
    if (props.mode === 'create') {
        form.post(route('camps.store'));

        return;
    }

    form.put(route('camps.update', props.camp?.id));
};
</script>

<template>
    <form class="space-y-6" @submit.prevent="submit">
        <div class="grid gap-6 sm:grid-cols-2">
            <div>
                <InputLabel for="name" value="Nome do acampamento" />
                <TextInput
                    id="name"
                    v-model="form.name"
                    type="text"
                    class="mt-1 block w-full"
                    required
                    autofocus
                />
                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div>
                <InputLabel for="starts_on" value="Data de inicio" />
                <TextInput
                    id="starts_on"
                    v-model="form.starts_on"
                    type="date"
                    class="mt-1 block w-full"
                    required
                />
                <InputError class="mt-2" :message="form.errors.starts_on" />
            </div>
        </div>

        <div class="grid gap-6 sm:grid-cols-2">
            <div>
                <InputLabel for="address" value="Endereco" />
                <TextInput
                    id="address"
                    v-model="form.address"
                    type="text"
                    class="mt-1 block w-full"
                    required
                    autocomplete="street-address"
                    placeholder="Local do acampamento"
                />
                <InputError class="mt-2" :message="form.errors.address" />
            </div>

            <div>
                <InputLabel for="ends_on" value="Data de fim" />
                <TextInput
                    id="ends_on"
                    v-model="form.ends_on"
                    type="date"
                    :min="form.starts_on || undefined"
                    class="mt-1 block w-full"
                    required
                />
                <InputError class="mt-2" :message="form.errors.ends_on" />
            </div>
        </div>

        <div class="grid gap-6 sm:grid-cols-2">
            <div>
                <InputLabel for="amount" value="Valor base" />
                <TextInput
                    id="amount"
                    v-model="form.amount"
                    type="number"
                    min="0"
                    step="0.01"
                    class="mt-1 block w-full"
                    required
                />
                <InputError class="mt-2" :message="form.errors.amount" />
            </div>
        </div>

        <div class="grid gap-6 sm:grid-cols-[1fr_auto]">
            <div>
                <InputLabel for="description" value="Descricao" />
                <textarea
                    id="description"
                    v-model="form.description"
                    rows="4"
                    class="mt-1 block w-full rounded-md border-app-border shadow-sm focus:border-brand-500 focus:ring-brand-500"
                    placeholder="Resumo do acampamento, tema, publico ou observacoes importantes."
                />
                <InputError class="mt-2" :message="form.errors.description" />
            </div>

            <label class="flex items-center gap-3 pt-7">
                <Checkbox v-model:checked="form.is_active" />
                <span class="text-sm text-app-subtle">Acampamento ativo</span>
            </label>
        </div>

        <section class="space-y-4">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h3 class="text-base font-semibold text-app-text">
                        Lotes de pagamento
                    </h3>
                    <p class="text-sm text-app-subtle">
                        Crie valores como primeiro lote, segundo lote e outros.
                    </p>
                </div>

                <SecondaryButton type="button" @click="addLot">
                    Adicionar lote
                </SecondaryButton>
            </div>

            <div
                v-for="(lot, index) in form.lots"
                :key="index"
                class="grid gap-4 rounded-md border border-app-border bg-app-muted p-4 sm:grid-cols-[1fr_160px_auto]"
            >
                <div>
                    <InputLabel :for="`lot-name-${index}`" value="Nome do lote" />
                    <TextInput
                        :id="`lot-name-${index}`"
                        v-model="lot.name"
                        type="text"
                        class="mt-1 block w-full"
                        required
                    />
                    <InputError
                        class="mt-2"
                        :message="fieldError(`lots.${index}.name`)"
                    />
                </div>

                <div>
                    <InputLabel :for="`lot-amount-${index}`" value="Valor" />
                    <TextInput
                        :id="`lot-amount-${index}`"
                        v-model="lot.amount"
                        type="number"
                        min="0"
                        step="0.01"
                        class="mt-1 block w-full"
                        required
                    />
                    <InputError
                        class="mt-2"
                        :message="fieldError(`lots.${index}.amount`)"
                    />
                </div>

                <div class="flex items-end">
                    <SecondaryButton
                        type="button"
                        :disabled="form.lots.length === 1"
                        @click="removeLot(index)"
                    >
                        Remover
                    </SecondaryButton>
                </div>
            </div>

            <InputError class="mt-2" :message="form.errors.lots" />
        </section>

        <div class="flex items-center justify-end gap-3">
            <Link :href="route('camps.index')">
                <SecondaryButton type="button">Cancelar</SecondaryButton>
            </Link>
            <PrimaryButton
                type="submit"
                :class="{ 'opacity-25': form.processing }"
                :disabled="form.processing"
            >
                {{ mode === 'create' ? 'Criar acampamento' : 'Salvar alteracoes' }}
            </PrimaryButton>
        </div>
    </form>
</template>
