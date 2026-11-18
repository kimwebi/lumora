<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { ArrowRight, BookOpen, ChevronDown, CodeXml, LifeBuoy } from 'lucide-vue-next';
import Logo from '@/components/brand/Logo.vue';
import ThemeToggle from '@/components/brand/ThemeToggle.vue';
import ToastHost from '@/components/ui/ToastHost.vue';
import DocBlocks from './DocBlocks.vue';
import RichText from './RichText.vue';
import { routes } from '@/lib/routes';
import { useSeo } from '@/composables/useSeo';

const props = defineProps({
    title: { type: String, required: true },
    eyebrow: { type: String, required: true },
    intro: { type: String, required: true },
    sections: { type: Array, required: true },
    updated: { type: String, default: null },
    kind: { type: String, default: 'guide' },
});

useSeo();

const page = usePage();
const supportEmail = computed(() => page.props.support?.email ?? 'hello@lumora.app');
const switchHref = computed(() => (props.kind === 'guide' ? routes.developerGuide : routes.guide));
const switchLabel = computed(() => (props.kind === 'guide' ? 'Developer guide' : 'User guide'));
const KindIcon = computed(() => (props.kind === 'guide' ? BookOpen : CodeXml));

const readingMinutes = computed(() => {
    const words = JSON.stringify(props.sections)
        .replace(/[{}[\]",:]/g, ' ')
        .split(/\s+/)
        .filter(Boolean).length;

    return Math.max(1, Math.round(words / 190));
});

const activeId = ref(props.sections[0]?.id ?? '');
const mobileNav = ref(null);
let ticking = false;

const activeTitle = computed(
    () => props.sections.find((section) => section.id === activeId.value)?.title ?? props.sections[0]?.title ?? '',
);

const spy = () => {
    let current = props.sections[0]?.id ?? '';

    for (const section of props.sections) {
        const element = document.getElementById(section.id);

        if (element && element.getBoundingClientRect().top <= 150) {
            current = section.id;
        }
    }

    activeId.value = current;
    ticking = false;
};

const onScroll = () => {
    if (ticking) {
        return;
    }

    ticking = true;
    window.requestAnimationFrame(spy);
};

const selectSection = (id) => {
    activeId.value = id;

    if (mobileNav.value) {
        mobileNav.value.open = false;
    }
};

onMounted(() => {
    window.addEventListener('scroll', onScroll, { passive: true });
    spy();
});

onBeforeUnmount(() => {
    window.removeEventListener('scroll', onScroll);
});
</script>

<template>
    <div class="ambient min-h-dvh bg-canvas">
        <header class="sticky top-0 z-30 border-b border-line/70 bg-canvas/85 backdrop-blur-xl">
            <div class="mx-auto flex h-16 w-full max-w-6xl items-center justify-between gap-3 px-5 sm:px-8">
                <Logo size="md" />

                <div class="flex items-center gap-1.5 sm:gap-2">
                    <Link
                        :href="switchHref"
                        class="focus-ring hidden h-10 items-center gap-1.5 rounded-xl px-3 text-[12.5px] font-medium text-subtle transition hover:bg-elevated hover:text-ink sm:inline-flex"
                    >
                        {{ switchLabel }}
                        <ArrowRight class="h-3.5 w-3.5" aria-hidden="true" />
                    </Link>

                    <ThemeToggle />

                    <Link
                        :href="routes.home"
                        class="focus-ring inline-flex h-10 items-center rounded-xl px-3 text-[12.5px] font-medium text-subtle transition hover:bg-elevated hover:text-ink"
                    >
                        Home
                    </Link>
                </div>
            </div>
        </header>

        <main id="top" class="mx-auto w-full max-w-6xl px-5 py-10 sm:px-8 lg:py-14">
            <div class="grid gap-8 lg:grid-cols-[15rem_minmax(0,1fr)] lg:gap-14">
                <aside class="min-w-0 lg:sticky lg:top-24 lg:max-h-[calc(100dvh-7rem)] lg:self-start lg:overflow-y-auto lg:pb-4">
                    <details ref="mobileNav" class="group rounded-xl border border-line bg-surface/80 lg:hidden">
                        <summary class="focus-ring flex cursor-pointer list-none items-center justify-between gap-3 rounded-xl px-4 py-3">
                            <span class="min-w-0">
                                <span class="block font-alt text-[9px] font-bold uppercase tracking-[0.18em] text-subtle">On this page</span>
                                <span class="mt-0.5 block truncate text-[12.5px] font-medium text-ink">{{ activeTitle }}</span>
                            </span>
                            <ChevronDown class="h-4 w-4 shrink-0 text-subtle transition group-open:rotate-180" aria-hidden="true" />
                        </summary>

                        <nav class="border-t border-line/60 p-2" aria-label="On this page">
                            <a
                                v-for="section in sections"
                                :key="section.id"
                                :href="`#${section.id}`"
                                class="block rounded-lg px-3 py-2 text-[12.5px] text-subtle transition hover:bg-elevated hover:text-ink"
                                @click="selectSection(section.id)"
                            >
                                {{ section.title }}
                            </a>
                        </nav>
                    </details>

                    <nav class="hidden lg:block" aria-label="On this page">
                        <p class="px-3 font-alt text-[9px] font-bold uppercase tracking-[0.2em] text-subtle">On this page</p>

                        <div class="mt-3 space-y-0.5 border-l border-line/70 pl-0">
                            <a
                                v-for="section in sections"
                                :key="section.id"
                                :href="`#${section.id}`"
                                :aria-current="activeId === section.id ? 'true' : undefined"
                                :class="[
                                    'block border-l-2 py-1.5 pl-3 pr-2 text-[12.5px] leading-snug transition',
                                    activeId === section.id
                                        ? 'border-brand-500 bg-brand-500/6 font-medium text-brand-700 dark:text-brand-300'
                                        : 'border-transparent text-subtle hover:border-brand-300/70 hover:text-ink',
                                ]"
                                @click="selectSection(section.id)"
                            >
                                {{ section.title }}
                            </a>
                        </div>

                        <div class="mt-6 rounded-xl border border-line bg-surface/70 p-3.5">
                            <div class="flex items-center gap-2">
                                <LifeBuoy class="h-3.5 w-3.5 text-brand-600 dark:text-brand-300" aria-hidden="true" />
                                <p class="text-[11.5px] font-semibold text-ink">Need a hand?</p>
                            </div>
                            <a
                                :href="`mailto:${supportEmail}`"
                                class="mt-1.5 block break-all text-[11px] leading-relaxed text-subtle transition hover:text-brand-700 dark:hover:text-brand-300"
                            >
                                {{ supportEmail }}
                            </a>
                        </div>
                    </nav>
                </aside>

                <article class="min-w-0">
                    <div class="max-w-3xl">
                        <p class="inline-flex items-center gap-1.5 font-alt text-[10px] font-bold uppercase tracking-[0.22em] text-brand-700 dark:text-brand-300">
                            <component :is="KindIcon" class="h-3.5 w-3.5" aria-hidden="true" />
                            {{ eyebrow }}
                        </p>

                        <h1 class="mt-3 text-[2rem] leading-[1.1] tracking-[-0.035em] sm:text-[2.5rem]">{{ title }}</h1>
                        <p class="mt-3 text-[14px] leading-relaxed text-subtle">{{ intro }}</p>

                        <p class="mt-4 flex flex-wrap items-center gap-x-2 gap-y-1 text-[11.5px] text-subtle">
                            <span>{{ sections.length }} sections</span>
                            <span class="text-line">·</span>
                            <span>~{{ readingMinutes }} min read</span>
                            <template v-if="updated">
                                <span class="text-line">·</span>
                                <span>Updated {{ updated }}</span>
                            </template>
                        </p>

                        <Link
                            :href="switchHref"
                            class="focus-ring mt-4 inline-flex items-center gap-1.5 text-[12.5px] font-medium text-brand-700 transition hover:text-brand-500 dark:text-brand-300 sm:hidden"
                        >
                            Switch to {{ switchLabel }}
                            <ArrowRight class="h-3.5 w-3.5" aria-hidden="true" />
                        </Link>
                    </div>

                    <div class="mt-9 space-y-9">
                        <section
                            v-for="section in sections"
                            :id="section.id"
                            :key="section.id"
                            class="scroll-mt-24 border-t border-line/60 pt-8 first:border-t-0 first:pt-0"
                        >
                            <h2 class="text-[19px] font-semibold tracking-[-0.02em] sm:text-[20px]">{{ section.title }}</h2>

                            <RichText
                                v-if="section.intro"
                                :text="section.intro"
                                class="mt-2 text-[13.5px] leading-relaxed text-subtle"
                            />

                            <div class="mt-4">
                                <DocBlocks :blocks="section.blocks" />
                            </div>
                        </section>
                    </div>

                    <div class="mt-10 flex flex-col gap-3 rounded-2xl border border-line bg-surface/70 p-5 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-start gap-3">
                            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-brand-500/10 text-brand-600 dark:text-brand-300">
                                <LifeBuoy class="h-4 w-4" aria-hidden="true" />
                            </span>
                            <div>
                                <p class="text-[13px] font-semibold text-ink">Still need help?</p>
                                <p class="mt-0.5 text-[12px] leading-relaxed text-subtle">
                                    Email us at
                                    <a :href="`mailto:${supportEmail}`" class="font-medium text-brand-700 hover:text-brand-500 dark:text-brand-300">
                                        {{ supportEmail }}
                                    </a>
                                    and we will point you in the right direction.
                                </p>
                            </div>
                        </div>

                        <Link
                            :href="switchHref"
                            class="focus-ring inline-flex h-10 shrink-0 items-center justify-center gap-1.5 rounded-xl border border-line bg-surface px-4 text-[12.5px] font-medium text-ink transition hover:border-brand-400/70 hover:text-brand-700 dark:hover:text-brand-200"
                        >
                            {{ switchLabel }}
                            <ArrowRight class="h-3.5 w-3.5" aria-hidden="true" />
                        </Link>
                    </div>

                    <div class="mt-6 flex justify-end">
                        <a href="#top" class="focus-ring rounded-lg px-2 py-1 text-[11.5px] text-subtle transition hover:text-ink">
                            Back to top
                        </a>
                    </div>
                </article>
            </div>
        </main>

        <footer class="border-t border-line/60 px-5 py-8 sm:px-8">
            <div class="mx-auto flex w-full max-w-6xl flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <Logo size="sm" />
                    <p class="text-[11.5px] text-subtle">© {{ new Date().getFullYear() }} Lumora. All rights reserved.</p>
                </div>

                <nav class="flex flex-wrap items-center gap-x-5 gap-y-2 text-[11.5px] text-subtle" aria-label="Footer">
                    <Link :href="routes.guide" class="transition hover:text-ink">User Guide</Link>
                    <Link :href="routes.developerGuide" class="transition hover:text-ink">Developer Guide</Link>
                    <Link :href="routes.terms" class="transition hover:text-ink">Terms</Link>
                    <Link :href="routes.privacy" class="transition hover:text-ink">Privacy</Link>
                </nav>
            </div>
        </footer>

        <ToastHost />
    </div>
</template>
