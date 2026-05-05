<script setup lang="ts">
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { Camera, Trash2, Upload } from 'lucide-vue-next';
import { computed, onBeforeUnmount, ref, watch } from 'vue';

const photo = defineModel<File | null>('photo', { default: null });
const removePhoto = defineModel<boolean>('removePhoto', { default: false });

const props = withDefaults(
    defineProps<{
        id?: string;
        label: string;
        description?: string;
        existingUrl?: string | null;
        existingLabel?: string;
    }>(),
    {
        id: 'photo',
        description: '',
        existingUrl: null,
        existingLabel: 'Foto atual',
    },
);

const fileInput = ref<HTMLInputElement | null>(null);
const objectUrl = ref<string | null>(null);

const previewUrl = computed(() => objectUrl.value ?? (removePhoto.value ? null : props.existingUrl));
const hasImage = computed(() => Boolean(previewUrl.value));

const openPicker = () => {
    fileInput.value?.click();
};

const handleChange = (event: Event) => {
    const target = event.target as HTMLInputElement;

    photo.value = target.files?.[0] ?? null;
};

const clearImage = () => {
    photo.value = null;
    removePhoto.value = Boolean(props.existingUrl);

    if (fileInput.value) {
        fileInput.value.value = '';
    }
};

watch(
    () => photo.value,
    (value) => {
        if (objectUrl.value) {
            URL.revokeObjectURL(objectUrl.value);
            objectUrl.value = null;
        }

        if (value instanceof File) {
            objectUrl.value = URL.createObjectURL(value);
            removePhoto.value = false;
        }
    },
);

onBeforeUnmount(() => {
    if (objectUrl.value) {
        URL.revokeObjectURL(objectUrl.value);
    }
});
</script>

<template>
    <div class="rounded-xl border border-app-border bg-app-muted/30 p-4">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
            <div class="flex shrink-0 justify-center">
                <div
                    class="flex h-28 w-28 items-center justify-center overflow-hidden rounded-2xl border border-app-border bg-app-surface"
                >
                    <img
                        v-if="hasImage"
                        :src="previewUrl ?? undefined"
                        :alt="existingLabel"
                        class="h-full w-full object-cover"
                    />
                    <div v-else class="flex flex-col items-center gap-2 text-app-soft">
                        <Camera class="h-8 w-8" aria-hidden="true" />
                        <span class="text-xs font-medium">Sem foto</span>
                    </div>
                </div>
            </div>

            <div class="min-w-0 flex-1">
                <label :for="id" class="block text-sm font-medium text-app-text">
                    {{ label }}
                </label>
                <p v-if="description" class="mt-1 text-sm text-app-subtle">
                    {{ description }}
                </p>

                <div class="mt-4 flex flex-wrap gap-2">
                    <SecondaryButton type="button" @click="openPicker">
                        <Upload class="mr-2 h-4 w-4" aria-hidden="true" />
                        Escolher foto
                    </SecondaryButton>
                    <SecondaryButton type="button" @click="openPicker">
                        <Camera class="mr-2 h-4 w-4" aria-hidden="true" />
                        Camera do telemovel
                    </SecondaryButton>
                    <SecondaryButton
                        v-if="hasImage"
                        type="button"
                        @click="clearImage"
                    >
                        <Trash2 class="mr-2 h-4 w-4" aria-hidden="true" />
                        Remover
                    </SecondaryButton>
                </div>

                <input
                    :id="id"
                    ref="fileInput"
                    type="file"
                    accept="image/*"
                    capture="user"
                    class="sr-only"
                    @change="handleChange"
                />

                <p class="mt-3 text-xs text-app-soft">
                    No celular, o navegador pode abrir direto a camera frontal. Tambem pode usar a galeria.
                </p>
            </div>
        </div>
    </div>
</template>
