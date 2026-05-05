<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PhotoUploadField from '@/Components/PhotoUploadField.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import type { ManagedUser, Role } from '@/types';
import { Link, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    roles: Role[];
    managedUser?: ManagedUser;
    mode: 'create' | 'edit';
}>();

const form = useForm({
    name: props.managedUser?.name ?? '',
    email: props.managedUser?.email ?? '',
    phone: props.managedUser?.phone ?? '',
    photo: null as File | null,
    remove_photo: false,
    sex: props.managedUser?.sex ?? '',
    role_id: props.managedUser?.role_id ?? props.roles[0]?.id ?? '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    if (props.mode === 'create') {
        form.post(route('users.store'), {
            forceFormData: true,
            onFinish: () => form.reset('password', 'password_confirmation'),
        });

        return;
    }

    form.transform((data) => ({
        ...data,
        _method: 'put',
    })).post(route('users.update', props.managedUser?.id), {
        forceFormData: true,
        onSuccess: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <form class="space-y-6" @submit.prevent="submit">
        <div>
            <InputLabel for="name" value="Nome" />
            <TextInput
                id="name"
                v-model="form.name"
                type="text"
                class="mt-1 block w-full"
                required
                autofocus
                autocomplete="name"
            />
            <InputError class="mt-2" :message="form.errors.name" />
        </div>

        <div>
            <InputLabel for="email" value="Email" />
            <TextInput
                id="email"
                v-model="form.email"
                type="email"
                class="mt-1 block w-full"
                required
                autocomplete="username"
            />
            <InputError class="mt-2" :message="form.errors.email" />
        </div>

        <div>
            <InputLabel for="phone" value="Telefone" />
            <TextInput
                id="phone"
                v-model="form.phone"
                type="tel"
                class="mt-1 block w-full"
                required
                autocomplete="tel"
            />
            <InputError class="mt-2" :message="form.errors.phone" />
        </div>

        <div>
            <PhotoUploadField
                v-model:photo="form.photo"
                v-model:remove-photo="form.remove_photo"
                label="Foto do participante"
                description="Pode carregar uma imagem ou tirar a foto no telemovel para usar no cracha."
                :existing-url="managedUser?.photo_url ?? null"
                existing-label="Foto do participante"
            />
            <InputError class="mt-2" :message="form.errors.photo" />
        </div>

        <div>
            <InputLabel for="sex" value="Sexo" />
            <select
                id="sex"
                v-model="form.sex"
                required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            >
                <option value="" disabled>Selecione o sexo</option>
                <option value="male">Masculino</option>
                <option value="female">Feminino</option>
            </select>
            <InputError class="mt-2" :message="form.errors.sex" />
        </div>

        <div>
            <InputLabel for="role_id" value="Papel" />
            <select
                id="role_id"
                v-model.number="form.role_id"
                required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            >
                <option value="" disabled>Selecione um papel</option>
                <option
                    v-for="role in roles"
                    :key="role.id"
                    :value="role.id"
                >
                    {{ role.name }}
                </option>
            </select>
            <p
                v-if="roles.find((role) => role.id === form.role_id)?.description"
                class="mt-2 text-sm text-gray-500"
            >
                {{ roles.find((role) => role.id === form.role_id)?.description }}
            </p>
            <InputError class="mt-2" :message="form.errors.role_id" />
        </div>

        <div class="grid gap-6 sm:grid-cols-2">
            <div>
                <InputLabel
                    for="password"
                    :value="mode === 'create' ? 'Senha' : 'Nova senha'"
                />
                <TextInput
                    id="password"
                    v-model="form.password"
                    type="password"
                    class="mt-1 block w-full"
                    :required="mode === 'create'"
                    autocomplete="new-password"
                />
                <p
                    v-if="mode === 'edit'"
                    class="mt-2 text-sm text-gray-500"
                >
                    Deixe em branco para manter a senha atual.
                </p>
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div>
                <InputLabel for="password_confirmation" value="Confirmar senha" />
                <TextInput
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    class="mt-1 block w-full"
                    :required="mode === 'create'"
                    autocomplete="new-password"
                />
                <InputError
                    class="mt-2"
                    :message="form.errors.password_confirmation"
                />
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            <Link :href="route('users.index')">
                <SecondaryButton type="button">Cancelar</SecondaryButton>
            </Link>
            <PrimaryButton
                type="submit"
                :class="{ 'opacity-25': form.processing }"
                :disabled="form.processing"
            >
                {{ mode === 'create' ? 'Criar usuario' : 'Salvar alteracoes' }}
            </PrimaryButton>
        </div>
    </form>
</template>
