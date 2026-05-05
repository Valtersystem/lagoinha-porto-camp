<script setup lang="ts">
import QRCode from 'qrcode';
import { ref, watch } from 'vue';

const props = withDefaults(
    defineProps<{
        value: string;
        size?: number;
        alt?: string;
    }>(),
    {
        size: 160,
        alt: 'QR code',
    },
);

const dataUrl = ref('');

watch(
    () => [props.value, props.size] as const,
    async ([value, size]) => {
        if (!value.trim()) {
            dataUrl.value = '';

            return;
        }

        dataUrl.value = await QRCode.toDataURL(value, {
            margin: 1,
            width: size,
            color: {
                dark: '#1f2937',
                light: '#ffffff',
            },
        });
    },
    { immediate: true },
);
</script>

<template>
    <div
        class="inline-flex items-center justify-center rounded-lg border border-app-border bg-white p-3"
        :style="{ width: `${props.size}px`, height: `${props.size}px` }"
    >
        <img
            v-if="dataUrl"
            :src="dataUrl"
            :alt="props.alt"
            class="h-full w-full object-contain"
        />
    </div>
</template>
