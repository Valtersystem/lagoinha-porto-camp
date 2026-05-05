<script setup lang="ts">
withDefaults(
    defineProps<{
        type?: 'button' | 'submit' | 'reset';
    }>(),
    {
        type: 'button',
    },
);
</script>

<template>
    <button
        :type="type"
        class="inline-flex items-center rounded-md border border-app-border bg-app-surface px-4 py-2 text-xs font-semibold uppercase tracking-widest text-app-text shadow-sm transition duration-150 ease-in-out hover:bg-app-muted focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 disabled:opacity-25"
    >
        <slot />
    </button>
</template>
