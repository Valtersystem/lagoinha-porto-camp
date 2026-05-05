<script setup lang="ts">
import JsBarcode from 'jsbarcode';
import { nextTick, ref, watch } from 'vue';

const props = withDefaults(
    defineProps<{
        value: string;
        height?: number;
        displayValue?: boolean;
    }>(),
    {
        height: 56,
        displayValue: false,
    },
);

const svgElement = ref<SVGSVGElement | null>(null);

const renderBarcode = async () => {
    await nextTick();

    if (!svgElement.value) {
        return;
    }

    if (!props.value.trim()) {
        svgElement.value.innerHTML = '';

        return;
    }

    JsBarcode(svgElement.value, props.value, {
        format: 'CODE128',
        lineColor: '#1f2937',
        background: 'transparent',
        width: 1.6,
        height: props.height,
        margin: 0,
        displayValue: props.displayValue,
    });
};

watch(
    () => [props.value, props.height, props.displayValue] as const,
    () => {
        renderBarcode();
    },
    { immediate: true },
);
</script>

<template>
    <div class="overflow-hidden rounded-lg border border-app-border bg-white px-3 py-4">
        <svg ref="svgElement" class="h-auto w-full" role="img" aria-label="Codigo de barras" />
    </div>
</template>
