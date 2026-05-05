<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PhotoUploadField from '@/Components/PhotoUploadField.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';

defineProps<{
    mustVerifyEmail?: Boolean;
    status?: String;
}>();

const user = usePage().props.auth.user;

if (!user) {
    throw new Error('Authenticated user is required to edit the profile.');
}

const form = useForm({
    name: user.name,
    email: user.email,
    photo: null as File | null,
    remove_photo: false,
});
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900">
                Dados do perfil
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                Atualize o seu nome, email e foto para usar no acampamento.
            </p>
        </header>

        <form
            @submit.prevent="
                form.transform((data) => ({ ...data, _method: 'patch' })).post(route('profile.update'), {
                    forceFormData: true,
                })
            "
            class="mt-6 space-y-6"
        >
            <div>
                <InputLabel for="name" value="Name" />

                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.name"
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
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div>
                <PhotoUploadField
                    v-model:photo="form.photo"
                    v-model:remove-photo="form.remove_photo"
                    label="Foto"
                    description="No telemovel pode abrir a camera frontal ou escolher uma imagem da galeria."
                    :existing-url="user.photo_url ?? null"
                    existing-label="Foto do perfil"
                />
                <InputError class="mt-2" :message="form.errors.photo" />
            </div>

            <div v-if="mustVerifyEmail && user.email_verified_at === null">
                <p class="mt-2 text-sm text-gray-800">
                    O seu email ainda nao esta verificado.
                    <Link
                        :href="route('verification.send')"
                        method="post"
                        as="button"
                        class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    >
                        Reenviar email de verificacao.
                    </Link>
                </p>

                <div
                    v-show="status === 'verification-link-sent'"
                    class="mt-2 text-sm font-medium text-green-600"
                >
                    Um novo link de verificacao foi enviado para o seu email.
                </div>
            </div>

            <div class="flex items-center gap-4">
                <PrimaryButton type="submit" :disabled="form.processing">Guardar</PrimaryButton>

                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <p
                        v-if="form.recentlySuccessful"
                        class="text-sm text-gray-600"
                    >
                        Guardado.
                    </p>
                </Transition>
            </div>
        </form>
    </section>
</template>
