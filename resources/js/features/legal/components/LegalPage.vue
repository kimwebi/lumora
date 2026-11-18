<script setup>
import { Link } from '@inertiajs/vue3';
import Logo from '@/components/brand/Logo.vue';
import ThemeToggle from '@/components/brand/ThemeToggle.vue';
import ToastHost from '@/components/ui/ToastHost.vue';
import { routes } from '@/lib/routes';
import { useSeo } from '@/composables/useSeo';

useSeo();

defineProps({
    title: { type: String, required: true },
    updated: { type: String, required: true },
    sections: { type: Array, required: true },
});
</script>

<template>
    <div class="ambient min-h-dvh bg-canvas">
        <header class="border-b border-line/70 bg-canvas/80 backdrop-blur-xl">
            <div class="mx-auto flex h-16 w-full max-w-4xl items-center justify-between px-5 sm:px-8">
                <Logo size="md" />

                <div class="flex items-center gap-2">
                    <ThemeToggle />
                    <Link
                        :href="routes.home"
                        class="focus-ring inline-flex h-10 items-center rounded-xl px-3 text-[13px] font-medium text-subtle transition hover:bg-elevated hover:text-ink"
                    >
                        Back to home
                    </Link>
                </div>
            </div>
        </header>

        <main class="mx-auto w-full max-w-4xl px-5 py-12 sm:px-8 lg:py-16">
            <p class="font-alt text-[10px] font-bold uppercase tracking-[0.22em] text-brand-700 dark:text-brand-300">
                Legal
            </p>
            <h1 class="mt-3 text-[2.1rem] leading-[1.1] tracking-[-0.03em]">{{ title }}</h1>
            <p class="mt-2 text-[12.5px] text-subtle">Last updated {{ updated }}</p>

            <div class="mt-9 space-y-8">
                <section v-for="section in sections" :key="section.heading">
                    <h2 class="text-[16px] font-semibold">{{ section.heading }}</h2>
                    <div class="mt-2 space-y-2.5">
                        <p
                            v-for="paragraph in section.body"
                            :key="paragraph"
                            class="text-[13.5px] leading-relaxed text-subtle"
                        >
                            {{ paragraph }}
                        </p>
                    </div>
                </section>
            </div>
        </main>

        <footer class="border-t border-line/60 px-5 py-6 sm:px-8">
            <div class="mx-auto flex w-full max-w-4xl flex-col items-center justify-between gap-2 text-[11.5px] text-subtle sm:flex-row">
                <p>© {{ new Date().getFullYear() }} Lumora. All rights reserved.</p>
                <div class="flex items-center gap-3">
                    <Link :href="routes.guide" class="transition hover:text-ink">User Guide</Link>
                    <span class="text-line">·</span>
                    <Link href="/terms" class="transition hover:text-ink">Terms</Link>
                    <span class="text-line">·</span>
                    <Link href="/privacy" class="transition hover:text-ink">Privacy</Link>
                </div>
            </div>
        </footer>

        <ToastHost />
    </div>
</template>
