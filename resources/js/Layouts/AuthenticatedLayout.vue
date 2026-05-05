<script setup lang="ts">
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    CalendarDays,
    Home,
    LogOut,
    UserCircle,
    Users,
} from 'lucide-vue-next';
import { computed } from 'vue';
import type { Component } from 'vue';

interface NavigationItem {
    label: string;
    href: string;
    active: boolean;
    icon: Component;
    show: boolean;
}

const page = usePage();

const user = computed(() => page.props.auth.user);
const can = computed(() => page.props.auth.can);

const navigationItems = computed<NavigationItem[]>(() => [
    {
        label: 'Painel',
        href: route('dashboard'),
        active: route().current('dashboard') ?? false,
        icon: Home,
        show: true,
    },
    {
        label: 'Usuarios',
        href: route('users.index'),
        active: route().current('users.*') ?? false,
        icon: Users,
        show: can.value.manageUsers,
    },
    {
        label: 'Acampamentos',
        href: route('camps.index'),
        active: route().current('camps.*') ?? false,
        icon: CalendarDays,
        show: can.value.manageCamps,
    },
    {
        label: 'Perfil',
        href: route('profile.edit'),
        active: route().current('profile.*') ?? false,
        icon: UserCircle,
        show: true,
    },
]);

const visibleNavigationItems = computed(() =>
    navigationItems.value.filter((item) => item.show),
);

const initials = computed(() => {
    const name = user.value?.name ?? 'Usuario';

    return name
        .split(' ')
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0]?.toUpperCase())
        .join('');
});

const logout = () => {
    router.post(route('logout'));
};
</script>

<template>
    <div class="app-shell">
        <aside
            class="fixed inset-y-0 left-0 z-40 hidden w-72 border-r border-app-border bg-app-elevated/95 px-4 py-5 shadow-panel backdrop-blur lg:flex lg:flex-col"
            aria-label="Navegacao principal"
        >
            <Link
                :href="route('dashboard')"
                class="flex items-center gap-3 rounded-app-lg px-2 py-2 text-app-text"
            >
                <span
                    class="flex h-11 w-11 items-center justify-center rounded-app-lg bg-brand-700 text-white shadow-card"
                    aria-hidden="true"
                >
                    <ApplicationLogo class="h-7 w-7" />
                </span>
                <span>
                    <span class="block text-sm font-semibold leading-5">
                        Lagoinha
                    </span>
                    <span class="block text-xs text-app-soft">Porto Camp</span>
                </span>
            </Link>

            <nav class="mt-8 flex flex-1 flex-col gap-1">
                <Link
                    v-for="item in visibleNavigationItems"
                    :key="item.label"
                    :href="item.href"
                    class="app-nav-item flex items-center gap-3 rounded-app-lg px-3 py-3 text-sm font-medium"
                    :class="{ 'app-nav-item-active': item.active }"
                    :aria-current="item.active ? 'page' : undefined"
                >
                    <component :is="item.icon" class="h-5 w-5" aria-hidden="true" />
                    <span>{{ item.label }}</span>
                </Link>
            </nav>

            <div class="border-t border-app-border pt-4">
                <div class="flex items-center gap-3 rounded-app-lg bg-app-muted px-3 py-3">
                    <span
                        class="flex h-10 w-10 items-center justify-center overflow-hidden rounded-full bg-brand-100 text-sm font-semibold text-brand-800"
                        aria-hidden="true"
                    >
                        <img
                            v-if="user?.photo_url"
                            :src="user.photo_url"
                            :alt="`Foto de ${user.name}`"
                            class="h-full w-full object-cover"
                        />
                        <template v-else>
                            {{ initials }}
                        </template>
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-sm font-medium text-app-text">
                            {{ user?.name }}
                        </span>
                        <span class="block truncate text-xs text-app-soft">
                            {{ user?.role?.name ?? user?.email }}
                        </span>
                    </span>
                </div>

                <button
                    type="button"
                    class="mt-3 flex w-full items-center gap-3 rounded-app-lg px-3 py-3 text-sm font-medium text-app-subtle transition hover:bg-app-muted hover:text-app-text focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2"
                    @click="logout"
                >
                    <LogOut class="h-5 w-5" aria-hidden="true" />
                    <span>Sair</span>
                </button>
            </div>
        </aside>

        <div class="min-h-screen lg:pl-72">
            <header class="sticky top-0 z-30 app-topbar">
                <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
                    <Link :href="route('dashboard')" class="flex items-center gap-3 lg:hidden">
                        <span
                            class="flex h-10 w-10 items-center justify-center rounded-app-lg bg-brand-700 text-white shadow-card"
                            aria-hidden="true"
                        >
                            <ApplicationLogo class="h-6 w-6" />
                        </span>
                        <span>
                            <span class="block text-sm font-semibold leading-5 text-app-text">
                                Porto Camp
                            </span>
                            <span class="block text-xs text-app-soft">PWA</span>
                        </span>
                    </Link>

                    <div class="hidden items-center gap-3 lg:flex">
                        <span
                            class="flex h-9 w-9 items-center justify-center rounded-app-lg bg-brand-50 text-brand-800"
                            aria-hidden="true"
                        >
                            <ApplicationLogo class="h-5 w-5" />
                        </span>
                        <div>
                            <p class="text-sm font-semibold leading-5 text-app-text">
                                Porto Camp
                            </p>
                            <p class="text-xs text-app-soft">
                                Gestao do acampamento
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <div class="hidden text-right sm:block">
                            <div class="text-sm font-medium text-app-text">
                                {{ user?.name }}
                            </div>
                            <div class="text-xs text-app-soft">
                                {{ user?.role?.name ?? user?.email }}
                            </div>
                        </div>
                        <span
                            class="flex h-10 w-10 items-center justify-center overflow-hidden rounded-full bg-brand-100 text-sm font-semibold text-brand-800"
                            aria-hidden="true"
                        >
                            <img
                                v-if="user?.photo_url"
                                :src="user.photo_url"
                                :alt="`Foto de ${user.name}`"
                                class="h-full w-full object-cover"
                            />
                            <template v-else>
                                {{ initials }}
                            </template>
                        </span>
                    </div>
                </div>
            </header>

            <section
                v-if="$slots.header"
                class="border-b border-app-border bg-app-surface/95"
            >
                <div class="mx-auto max-w-7xl px-4 py-5 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </section>

            <main class="pb-28 lg:pb-8">
                <slot />
            </main>
        </div>

        <nav
            class="fixed inset-x-0 bottom-0 z-40 app-bottom-nav px-3 pb-[calc(env(safe-area-inset-bottom)+0.5rem)] pt-2 lg:hidden"
            aria-label="Navegacao inferior"
        >
            <div class="mx-auto grid max-w-md auto-cols-fr grid-flow-col gap-1">
                <Link
                    v-for="item in visibleNavigationItems"
                    :key="item.label"
                    :href="item.href"
                    class="app-nav-item flex min-h-14 flex-col items-center justify-center gap-1 rounded-app-lg px-2 py-2 text-xs font-medium"
                    :class="{ 'app-nav-item-active': item.active }"
                    :aria-current="item.active ? 'page' : undefined"
                >
                    <component :is="item.icon" class="h-5 w-5" aria-hidden="true" />
                    <span class="max-w-full truncate">{{ item.label }}</span>
                </Link>
            </div>
        </nav>
    </div>
</template>
