<script setup lang="ts">
import ApplicationLogo from '@/Components/ApplicationLogo.vue';

defineProps<{
    user: {
        name: string;
        phone?: string | null;
        photo_url?: string | null;
        pin?: string | null;
        sex_label?: string | null;
        role?: {
            name: string;
        } | null;
    };
    participation?: {
        camp?: {
            name: string;
            date_range_label?: string | null;
            is_active?: boolean;
        } | null;
        room?: {
            name: string;
        } | null;
        team?: {
            name: string;
        } | null;
    } | null;
}>();
</script>

<template>
    <article class="app-card overflow-hidden">
        <div
            class="relative overflow-hidden px-5 pb-6 pt-5"
            style="
                background:
                    radial-gradient(circle at top right, rgba(251, 191, 36, 0.18), transparent 28%),
                    radial-gradient(circle at bottom left, rgba(16, 185, 129, 0.18), transparent 40%),
                    linear-gradient(135deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.96));
            "
        >
            <div class="flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-brand-900/60">
                        Porto Camp
                    </p>
                    <p class="mt-2 truncate text-lg font-semibold text-app-text">
                        {{ participation?.camp?.name ?? 'Acesso do participante' }}
                    </p>
                    <p class="mt-1 text-sm text-app-subtle">
                        {{ participation?.camp?.date_range_label ?? 'Aguardando vinculacao' }}
                    </p>
                </div>

                <span
                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full border border-white/70 bg-white/80 text-brand-800 shadow-sm"
                    aria-hidden="true"
                >
                    <ApplicationLogo class="h-6 w-6" />
                </span>
            </div>

            <div class="mt-6 flex items-center gap-4">
                <div
                    class="flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-full border border-white/80 bg-white text-2xl font-semibold text-brand-800 shadow-sm"
                >
                    <img
                        v-if="user.photo_url"
                        :src="user.photo_url"
                        :alt="`Foto de ${user.name}`"
                        class="h-full w-full object-cover"
                    />
                    <template v-else>
                        {{ user.name.slice(0, 1).toUpperCase() }}
                    </template>
                </div>

                <div class="min-w-0 flex-1">
                    <h3 class="truncate text-3xl font-semibold text-app-text">
                        {{ user.name }}
                    </h3>
                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        <span
                            class="inline-flex items-center rounded-full bg-white/85 px-3 py-1 text-xs font-medium text-app-text shadow-sm ring-1 ring-black/5"
                        >
                            {{ user.role?.name ?? 'Participante' }}
                        </span>
                        <span
                            class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium shadow-sm ring-1 ring-black/5"
                            :class="
                                participation?.camp?.is_active
                                    ? 'bg-emerald-50 text-emerald-700'
                                    : 'bg-slate-100 text-slate-600'
                            "
                        >
                            <span
                                class="mr-2 h-2 w-2 rounded-full"
                                :class="
                                    participation?.camp?.is_active
                                        ? 'bg-emerald-500'
                                        : 'bg-slate-400'
                                "
                            />
                            {{ participation?.camp?.is_active ? 'Cracha ativo' : 'Sem acampamento ativo' }}
                        </span>
                    </div>
                    <p class="mt-3 text-sm font-medium text-app-subtle">
                        PIN {{ user.pin ?? '----' }}
                    </p>
                </div>
            </div>

            <div class="mt-6 flex flex-wrap gap-2">
                <span class="inline-flex rounded-full bg-white/85 px-3 py-2 text-xs font-medium text-app-text shadow-sm ring-1 ring-black/5">
                    Quarto: {{ participation?.room?.name ?? 'Sem quarto' }}
                </span>
                <span class="inline-flex rounded-full bg-white/85 px-3 py-2 text-xs font-medium text-app-text shadow-sm ring-1 ring-black/5">
                    Equipe: {{ participation?.team?.name ?? 'Sem equipe' }}
                </span>
                <span class="inline-flex rounded-full bg-white/85 px-3 py-2 text-xs font-medium text-app-text shadow-sm ring-1 ring-black/5">
                    Sexo: {{ user.sex_label ?? 'Nao definido' }}
                </span>
            </div>
        </div>
    </article>
</template>
