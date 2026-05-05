<script setup lang="ts">
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div
        class="app-shell flex min-h-screen flex-col items-center px-4 pt-8 sm:justify-center sm:pt-0"
    >
        <div>
            <Link href="/">
                <span
                    class="flex h-20 w-20 items-center justify-center rounded-app-xl bg-brand-700 text-white shadow-panel"
                    aria-hidden="true"
                >
                    <ApplicationLogo class="h-12 w-12" />
                </span>
            </Link>
        </div>

        <div
            class="mt-6 w-full overflow-hidden app-card px-6 py-5 sm:max-w-md"
        >
            <slot />
        </div>
    </div>
</template>
