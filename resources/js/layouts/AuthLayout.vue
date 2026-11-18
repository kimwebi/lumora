<script setup>
import { Link } from '@inertiajs/vue3';
import { ArrowLeft, Sparkles } from 'lucide-vue-next';
import Logo from '@/components/brand/Logo.vue';
import ThemeToggle from '@/components/brand/ThemeToggle.vue';
import { useSeo } from '@/composables/useSeo';

useSeo();

defineProps({
    title: { type: String, required: true },
    description: { type: String, default: '' },
    backHref: { type: String, default: null },
    backLabel: { type: String, default: 'Back' },
});
</script>

<template>
    <div class="ambient relative min-h-dvh overflow-hidden bg-canvas">
        <div class="pointer-events-none absolute inset-0 dot-grid opacity-[0.35]" aria-hidden="true" />

        <header class="relative z-10 mx-auto flex w-full max-w-6xl items-center justify-between px-5 py-5 sm:px-8">
            <Logo size="md" show-tagline />

            <div class="flex items-center gap-2">
                <Link
                    v-if="backHref"
                    :href="backHref"
                    class="focus-ring hidden h-10 items-center gap-1.5 rounded-xl px-3 text-[13px] font-medium text-subtle transition hover:bg-elevated hover:text-ink sm:inline-flex"
                >
                    <ArrowLeft class="h-3.5 w-3.5" />
                    {{ backLabel }}
                </Link>
                <ThemeToggle />
            </div>
        </header>

        <main class="relative z-10 mx-auto grid w-full max-w-6xl items-center gap-10 px-5 pb-16 pt-4 sm:px-8 lg:grid-cols-[1.05fr_minmax(0,28rem)] lg:gap-16 lg:pb-24">
            <section class="hidden lg:block animate-rise">
                <p class="inline-flex items-center gap-2 rounded-full border border-brand-500/25 bg-brand-500/8 px-3 py-1.5 font-alt text-[10.5px] font-bold uppercase tracking-[0.22em] text-brand-700 dark:text-brand-200">
                    <Sparkles class="h-3 w-3" />
                    Lumora Studio
                </p>

                <p class="mt-6 max-w-xl text-[2.6rem] leading-[1.08] tracking-[-0.03em]">
                    Your imagination,
                    <span class="text-gradient">beautifully rendered.</span>
                </p>

                <p class="mt-5 max-w-md text-[15px] leading-relaxed text-subtle">
                    A calm, focused studio for AI image generation — transparent pricing, live render
                    progress and every creation neatly archived.
                </p>

                <dl class="mt-9 grid max-w-md grid-cols-3 gap-3">
                    <div class="rounded-2xl border border-line bg-surface/70 p-3.5 backdrop-blur">
                        <dt class="font-alt text-[9.5px] font-bold uppercase tracking-[0.18em] text-subtle">Pricing</dt>
                        <dd class="mt-1.5 text-[15px] font-semibold text-ink">Cost + 25%</dd>
                    </div>
                    <div class="rounded-2xl border border-line bg-surface/70 p-3.5 backdrop-blur">
                        <dt class="font-alt text-[9.5px] font-bold uppercase tracking-[0.18em] text-subtle">Failures</dt>
                        <dd class="mt-1.5 text-[15px] font-semibold text-ink">Always free</dd>
                    </div>
                    <div class="rounded-2xl border border-line bg-surface/70 p-3.5 backdrop-blur">
                        <dt class="font-alt text-[9.5px] font-bold uppercase tracking-[0.18em] text-subtle">History</dt>
                        <dd class="mt-1.5 text-[15px] font-semibold text-ink">Archived</dd>
                    </div>
                </dl>
            </section>

            <section class="w-full animate-rise">
                <div class="rounded-3xl border border-line bg-surface/95 p-6 shadow-lift backdrop-blur-xl sm:p-8">
                    <h1 class="text-[26px] leading-tight tracking-[-0.02em]">{{ title }}</h1>
                    <p v-if="description" class="mt-2 text-[13.5px] leading-relaxed text-subtle">{{ description }}</p>

                    <div class="mt-7">
                        <slot />
                    </div>
                </div>

                <div class="mt-5 text-center">
                    <slot name="footer" />
                </div>
            </section>
        </main>
    </div>
</template>
