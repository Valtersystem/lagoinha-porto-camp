<script setup lang="ts">
import BarcodeBlock from '@/Components/BarcodeBlock.vue';
import QrCodeBlock from '@/Components/QrCodeBlock.vue';
import { DoorOpen, IdCard, Phone, ShieldCheck, UserRound } from 'lucide-vue-next';

defineProps<{
    user: {
        name: string;
        phone?: string | null;
        photo_url?: string | null;
        pin?: string | null;
        verification_code?: string | null;
        sex_label?: string | null;
        role?: {
            name: string;
        } | null;
    };
    participation?: {
        camp?: {
            name: string;
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
    <div class="grid gap-5 xl:grid-cols-2">
        <article class="overflow-hidden rounded-[28px] border border-app-border bg-app-surface shadow-card">
            <div class="bg-[#23262c] px-5 pb-6 pt-5 text-white">
                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-white/70">
                    Lagoinha Porto Camp
                </p>
                <p class="mt-2 text-sm text-white/80">
                    {{ participation?.camp?.name ?? 'Participante do acampamento' }}
                </p>

                <div class="mt-6 flex justify-center">
                    <div class="h-32 w-32 overflow-hidden rounded-full border-4 border-white/15 bg-white/10">
                        <img
                            v-if="user.photo_url"
                            :src="user.photo_url"
                            :alt="`Foto de ${user.name}`"
                            class="h-full w-full object-cover"
                        />
                        <div
                            v-else
                            class="flex h-full w-full items-center justify-center text-4xl font-semibold text-white/75"
                        >
                            {{ user.name.slice(0, 1).toUpperCase() }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-4 px-5 pb-5 pt-5 text-center">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-app-soft">
                        {{ user.role?.name ?? 'Participante' }}
                    </p>
                    <h3 class="mt-2 text-2xl font-semibold text-app-text">
                        {{ user.name }}
                    </h3>
                    <p class="mt-2 text-sm text-app-subtle">
                        PIN {{ user.pin ?? '----' }}
                    </p>
                </div>

                <div class="grid gap-2 text-left sm:grid-cols-2">
                    <div class="rounded-2xl border border-app-border bg-app-muted/50 px-3 py-3">
                        <p class="text-xs uppercase tracking-[0.18em] text-app-soft">Quarto</p>
                        <p class="mt-1 text-sm font-medium text-app-text">
                            {{ participation?.room?.name ?? 'Sem quarto' }}
                        </p>
                    </div>
                    <div class="rounded-2xl border border-app-border bg-app-muted/50 px-3 py-3">
                        <p class="text-xs uppercase tracking-[0.18em] text-app-soft">Sexo</p>
                        <p class="mt-1 text-sm font-medium text-app-text">
                            {{ user.sex_label ?? 'Nao definido' }}
                        </p>
                    </div>
                </div>
            </div>
        </article>

        <article class="overflow-hidden rounded-[28px] border border-app-border bg-app-surface shadow-card">
            <div class="bg-[#23262c] px-5 py-5 text-white">
                <h3 class="text-lg font-semibold">
                    Identificacao e verificacao
                </h3>
                <p class="mt-2 text-sm text-white/75">
                    Dados operacionais para rececao, presenca, check-in e alojamento.
                </p>
            </div>

            <div class="space-y-4 px-5 pb-5 pt-5">
                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="rounded-2xl border border-app-border bg-app-muted/40 p-4">
                        <div class="flex items-center gap-2 text-sm font-medium text-app-text">
                            <Phone class="h-4 w-4 text-app-soft" aria-hidden="true" />
                            Telefone
                        </div>
                        <p class="mt-2 text-sm text-app-subtle">
                            {{ user.phone ?? 'Sem telefone' }}
                        </p>
                    </div>
                    <div class="rounded-2xl border border-app-border bg-app-muted/40 p-4">
                        <div class="flex items-center gap-2 text-sm font-medium text-app-text">
                            <DoorOpen class="h-4 w-4 text-app-soft" aria-hidden="true" />
                            Quarto
                        </div>
                        <p class="mt-2 text-sm text-app-subtle">
                            {{ participation?.room?.name ?? 'Sem quarto' }}
                        </p>
                    </div>
                    <div class="rounded-2xl border border-app-border bg-app-muted/40 p-4">
                        <div class="flex items-center gap-2 text-sm font-medium text-app-text">
                            <UserRound class="h-4 w-4 text-app-soft" aria-hidden="true" />
                            Sexo
                        </div>
                        <p class="mt-2 text-sm text-app-subtle">
                            {{ user.sex_label ?? 'Nao definido' }}
                        </p>
                    </div>
                    <div class="rounded-2xl border border-app-border bg-app-muted/40 p-4">
                        <div class="flex items-center gap-2 text-sm font-medium text-app-text">
                            <ShieldCheck class="h-4 w-4 text-app-soft" aria-hidden="true" />
                            Equipe
                        </div>
                        <p class="mt-2 text-sm text-app-subtle">
                            {{ participation?.team?.name ?? 'Sem equipe' }}
                        </p>
                    </div>
                </div>

                <div class="grid gap-4 lg:grid-cols-[180px_1fr]">
                    <div class="rounded-2xl border border-app-border bg-app-muted/40 p-4">
                        <div class="flex items-center gap-2 text-sm font-medium text-app-text">
                            <IdCard class="h-4 w-4 text-app-soft" aria-hidden="true" />
                            QR do participante
                        </div>
                        <div class="mt-4 flex justify-center">
                            <QrCodeBlock
                                :value="user.verification_code ?? ''"
                                :size="140"
                                :alt="`QR de ${user.name}`"
                            />
                        </div>
                    </div>

                    <div class="rounded-2xl border border-app-border bg-app-muted/40 p-4">
                        <p class="text-sm font-medium text-app-text">
                            Codigo de barras
                        </p>
                        <div class="mt-4">
                            <BarcodeBlock
                                :value="user.verification_code ?? ''"
                                :height="64"
                            />
                        </div>
                        <p class="mt-3 break-all font-mono text-xs text-app-soft">
                            {{ user.verification_code ?? 'Sem codigo' }}
                        </p>
                    </div>
                </div>
            </div>
        </article>
    </div>
</template>
