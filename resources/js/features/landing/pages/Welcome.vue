<script setup>
import { computed, ref } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    ArrowRight,
    Check,
    ChevronDown,
    Coins,
    Gauge,
    ImagePlus,
    Layers,
    ShieldCheck,
    Sparkles,
    Wand2,
    Zap,
} from 'lucide-vue-next';
import Logo from '@/components/brand/Logo.vue';
import ThemeToggle from '@/components/brand/ThemeToggle.vue';
import Button from '@/components/ui/Button.vue';
import Badge from '@/components/ui/Badge.vue';
import SmartImage from '@/components/brand/SmartImage.vue';
import ToastHost from '@/components/ui/ToastHost.vue';
import { formatNumber, formatUsd } from '@/lib/format';
import { routes } from '@/lib/routes';
import { useSeo } from '@/composables/useSeo';

const props = defineProps({
    showcase: { type: Array, default: () => [] },
    packages: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({}) },
    isAuthenticated: { type: Boolean, default: false },
    canRegister: { type: Boolean, default: true },
});

useSeo();

const page = usePage();
const brand = computed(() => page.props.brand ?? { name: 'Lumora' });

const primaryHref = computed(() => (props.isAuthenticated ? routes.dashboard : routes.register));

const features = [
    {
        title: 'Prompt to gallery in seconds',
        description: 'A focused studio with prompt presets, reference images and provider-native sizes.',
        icon: Wand2,
    },
    {
        title: 'Transparent per-render pricing',
        description: 'See provider cost, our 25% markup and your total before a single credit is held.',
        icon: Coins,
    },
    {
        title: 'Live render progress',
        description: 'Webhook-driven updates with polling fallback, so you always know what is happening.',
        icon: Gauge,
    },
    {
        title: 'Every creation archived',
        description: 'Search, filter and download past work with the true cost of each render attached.',
        icon: Layers,
    },
    {
        title: 'Failed renders are free',
        description: 'If the model cannot deliver, your held estimate is refunded automatically.',
        icon: ShieldCheck,
    },
    {
        title: 'References that guide the model',
        description: 'Attach up to two image URLs to steer composition, palette and texture.',
        icon: ImagePlus,
    },
];

const steps = [
    {
        number: '01',
        title: 'Describe it',
        description: 'Write a prompt, pick a canvas and quality, and optionally add reference URLs.',
    },
    {
        number: '02',
        title: 'See the price',
        description: 'Lumora shows the provider cost and the exact total — no surprises after the fact.',
    },
    {
        number: '03',
        title: 'Generate and keep',
        description: 'Track progress live, then download or revisit the render from your gallery forever.',
    },
];

const faqs = [
    {
        question: 'How does pricing work?',
        answer:
            'Every render is priced at the provider\'s cost plus a transparent 25% markup. You see the estimate before you generate, and the final charge is based on the provider\'s reported usage. Failed renders are always refunded in full.',
    },
    {
        question: 'What is a Lumora credit?',
        answer:
            'One credit equals $0.01. Credits are prepaid and never expire. New accounts start with complimentary credits so you can try the studio before buying a pack.',
    },
    {
        question: 'Which image sizes are supported?',
        answer:
            'Lumora exposes the provider\'s native canvas sizes — square, portrait, landscape, tall and wide — from 512px drafts up to 1792px wide renders, in standard or HD quality.',
    },
    {
        question: 'Can I use the images commercially?',
        answer:
            'Yes. You keep the rights to the images you generate, subject to the provider\'s acceptable use policy. Lumora never claims ownership of your prompts or renders.',
    },
    {
        question: 'What happens if a render fails?',
        answer:
            'Your held estimate is returned to your wallet automatically, usually within seconds. You are never charged for an image that was not delivered.',
    },
];

const openFaq = ref(0);

const toggleFaq = (index) => {
    openFaq.value = openFaq.value === index ? -1 : index;
};

const showcaseImages = computed(() => props.showcase.slice(0, 6));

const placeholderTiles = [
    'from-[#2e1065] via-[#6d28d9] to-[#c4b5fd]',
    'from-[#0f172a] via-[#1e3a8a] to-[#22d3ee]',
    'from-[#3b0764] via-[#7c3aed] to-[#f0abfc]',
    'from-[#052e2b] via-[#0e7490] to-[#a5f3fc]',
    'from-[#1c1917] via-[#4c1d95] to-[#a78bfa]',
    'from-[#0b1120] via-[#312e81] to-[#38bdf8]',
];
</script>

<template>
    <Head title="AI Image Generation" />

    <div class="ambient min-h-dvh bg-canvas">
        <header class="sticky top-0 z-40 border-b border-line/70 bg-canvas/80 backdrop-blur-xl">
            <div class="mx-auto flex h-16 w-full max-w-6xl items-center justify-between px-5 sm:px-8">
                <Logo size="md" />

                <nav class="hidden items-center gap-1 md:flex" aria-label="Main">
                    <a href="#features" class="rounded-lg px-3 py-2 text-[13px] font-medium text-subtle transition hover:bg-elevated hover:text-ink">Features</a>
                    <a href="#showcase" class="rounded-lg px-3 py-2 text-[13px] font-medium text-subtle transition hover:bg-elevated hover:text-ink">Showcase</a>
                    <a href="#pricing" class="rounded-lg px-3 py-2 text-[13px] font-medium text-subtle transition hover:bg-elevated hover:text-ink">Pricing</a>
                    <a href="#faq" class="rounded-lg px-3 py-2 text-[13px] font-medium text-subtle transition hover:bg-elevated hover:text-ink">FAQ</a>
                </nav>

                <div class="flex items-center gap-2">
                    <ThemeToggle />

                    <template v-if="isAuthenticated">
                        <Button :href="routes.dashboard" size="sm">Open studio</Button>
                    </template>
                    <template v-else>
                        <Link
                            :href="routes.login"
                            class="focus-ring hidden h-10 items-center rounded-xl px-3 text-[13px] font-medium text-subtle transition hover:bg-elevated hover:text-ink sm:inline-flex"
                        >
                            Sign in
                        </Link>
                        <Button v-if="canRegister" :href="routes.register" size="sm">
                            Start free
                            <ArrowRight class="ml-1 h-3.5 w-3.5" />
                        </Button>
                    </template>
                </div>
            </div>
        </header>

        <main>
            <section class="relative overflow-hidden">
                <div class="mx-auto grid w-full max-w-6xl gap-12 px-5 pb-20 pt-14 sm:px-8 lg:grid-cols-[1.02fr_1fr] lg:items-center lg:gap-16 lg:pb-28 lg:pt-20">
                    <div class="animate-rise">
                        <p class="inline-flex items-center gap-2 rounded-full border border-brand-500/25 bg-brand-500/8 px-3 py-1.5 font-alt text-[10px] font-bold uppercase tracking-[0.2em] text-brand-700 dark:text-brand-200">
                            <Sparkles class="h-3 w-3" />
                            Powered by Pixelforge
                        </p>

                        <h1 class="mt-6 text-[2.6rem] leading-[1.03] tracking-[-0.035em] sm:text-[3.4rem]">
                            Your imagination,
                            <span class="text-gradient">beautifully rendered.</span>
                        </h1>

                        <p class="mt-5 max-w-xl text-[15.5px] leading-relaxed text-subtle">
                            Lumora is a calm, considered AI image studio. Write a prompt, see the exact price,
                            and watch gallery-quality art appear — with every render archived and every failed
                            attempt refunded.
                        </p>

                        <div class="mt-8 flex flex-wrap items-center gap-3">
                            <Button :href="primaryHref" size="lg">
                                <template #icon><Sparkles class="h-4 w-4" /></template>
                                {{ isAuthenticated ? 'Open your studio' : 'Start creating free' }}
                            </Button>

                            <Button :href="isAuthenticated ? routes.generate : routes.login" variant="secondary" size="lg">
                                {{ isAuthenticated ? 'Generate an image' : 'Sign in' }}
                            </Button>
                        </div>

                        <ul class="mt-7 flex flex-wrap items-center gap-x-5 gap-y-2">
                            <li v-for="item in ['Complimentary starter credits', 'No subscription', 'Failed renders free']" :key="item" class="flex items-center gap-1.5 text-[12px] text-subtle">
                                <Check class="h-3.5 w-3.5 text-success" />
                                {{ item }}
                            </li>
                        </ul>
                    </div>

                    <div class="relative animate-rise">
                        <div class="pointer-events-none absolute -inset-6 rounded-[2rem] bg-brand-500/10 blur-3xl" aria-hidden="true" />

                        <div class="relative grid grid-cols-2 gap-3 sm:gap-4">
                            <div
                                v-for="(image, index) in showcaseImages"
                                :key="image.uuid"
                                :class="[
                                    'overflow-hidden rounded-2xl border border-line bg-surface shadow-card transition-transform duration-500 hover:-translate-y-1',
                                    index % 3 === 1 ? 'translate-y-4 sm:translate-y-6' : '',
                                ]"
                            >
                                <SmartImage :src="image.image_url" :alt="image.prompt" ratio="aspect-[4/5]" />
                            </div>

                            <template v-if="showcaseImages.length === 0">
                                <div
                                    v-for="(tile, index) in placeholderTiles"
                                    :key="index"
                                    :class="[
                                        'relative overflow-hidden rounded-2xl bg-gradient-to-br',
                                        tile,
                                        index % 3 === 1 ? 'translate-y-4 sm:translate-y-6' : '',
                                        index > 3 ? 'hidden sm:block' : '',
                                    ]"
                                >
                                    <div class="absolute inset-0 dot-grid opacity-25" aria-hidden="true" />
                                    <div class="aspect-[4/5]" />
                                    <Sparkles class="absolute bottom-3 right-3 h-4 w-4 text-white/70" />
                                </div>
                            </template>
                        </div>

                        <div class="absolute -bottom-3 left-1/2 flex -translate-x-1/2 items-center gap-2 rounded-full border border-line bg-surface/95 px-3.5 py-2 shadow-lift backdrop-blur">
                            <span class="relative flex h-2 w-2">
                                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-success opacity-60" />
                                <span class="relative inline-flex h-2 w-2 rounded-full bg-success" />
                            </span>
                            <span class="font-alt text-[10px] font-bold uppercase tracking-[0.16em] text-subtle">
                                Rendering in seconds
                            </span>
                        </div>
                    </div>
                </div>

                <div class="border-y border-line/70 bg-surface/60">
                    <div class="mx-auto grid w-full max-w-6xl grid-cols-2 gap-6 px-5 py-7 sm:grid-cols-4 sm:px-8">
                        <div>
                            <p class="tabular text-[22px] font-semibold leading-none text-ink">
                                {{ formatNumber(stats.images ?? 0) }}
                            </p>
                            <p class="mt-1.5 font-alt text-[10px] font-bold uppercase tracking-[0.16em] text-subtle">Images rendered</p>
                        </div>
                        <div>
                            <p class="tabular text-[22px] font-semibold leading-none text-ink">
                                {{ formatNumber(stats.creators ?? 0) }}
                            </p>
                            <p class="mt-1.5 font-alt text-[10px] font-bold uppercase tracking-[0.16em] text-subtle">Creators</p>
                        </div>
                        <div>
                            <p class="tabular text-[22px] font-semibold leading-none text-ink">25%</p>
                            <p class="mt-1.5 font-alt text-[10px] font-bold uppercase tracking-[0.16em] text-subtle">Flat markup</p>
                        </div>
                        <div>
                            <p class="tabular text-[22px] font-semibold leading-none text-ink">$0.00</p>
                            <p class="mt-1.5 font-alt text-[10px] font-bold uppercase tracking-[0.16em] text-subtle">Cost of failures</p>
                        </div>
                    </div>
                </div>
            </section>

            <section id="features" class="mx-auto w-full max-w-6xl scroll-mt-24 px-5 py-20 sm:px-8 lg:py-24">
                <div class="max-w-2xl">
                    <p class="font-alt text-[10px] font-bold uppercase tracking-[0.22em] text-brand-700 dark:text-brand-300">The studio</p>
                    <h2 class="mt-3 text-[1.9rem] leading-[1.12] tracking-[-0.03em] sm:text-[2.3rem]">
                        Everything you need to go from idea to image
                    </h2>
                    <p class="mt-3 text-[14.5px] leading-relaxed text-subtle">
                        No cluttered toolbars, no hidden fees. Just a precise, fast workspace built around your prompts.
                    </p>
                </div>

                <div class="mt-10 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <div
                        v-for="feature in features"
                        :key="feature.title"
                        class="group rounded-2xl border border-line bg-surface p-5 shadow-card transition-all duration-300 hover:-translate-y-0.5 hover:border-brand-400/40 hover:shadow-lift"
                    >
                        <span class="grid h-10 w-10 place-items-center rounded-xl bg-brand-500/10 text-brand-600 ring-1 ring-inset ring-brand-500/15 dark:text-brand-300">
                            <component :is="feature.icon" class="h-[18px] w-[18px]" />
                        </span>

                        <h3 class="mt-4 text-[14.5px] font-semibold">{{ feature.title }}</h3>
                        <p class="mt-1.5 text-[12.5px] leading-relaxed text-subtle">{{ feature.description }}</p>
                    </div>
                </div>
            </section>

            <section id="showcase" class="scroll-mt-24 border-y border-line/70 bg-muted/40 py-20 lg:py-24">
                <div class="mx-auto w-full max-w-6xl px-5 sm:px-8">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                        <div class="max-w-xl">
                            <p class="font-alt text-[10px] font-bold uppercase tracking-[0.22em] text-brand-700 dark:text-brand-300">Showcase</p>
                            <h2 class="mt-3 text-[1.9rem] leading-[1.12] tracking-[-0.03em] sm:text-[2.3rem]">
                                Made in Lumora
                            </h2>
                            <p class="mt-3 text-[14px] leading-relaxed text-subtle">
                                A snapshot of recent renders from the studio — every one archived with its prompt and true cost.
                            </p>
                        </div>

                        <Button :href="primaryHref" variant="secondary" size="sm">
                            Start your own gallery
                            <ArrowRight class="ml-1 h-3.5 w-3.5" />
                        </Button>
                    </div>

                    <div class="mt-8 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-3">
                        <template v-if="showcaseImages.length">
                            <figure
                                v-for="image in showcaseImages"
                                :key="image.uuid"
                                class="group overflow-hidden rounded-2xl border border-line bg-surface shadow-card"
                            >
                                <SmartImage
                                    :src="image.image_url"
                                    :alt="image.prompt"
                                    ratio="aspect-[4/3]"
                                    class="transition-transform duration-700 group-hover:scale-[1.02]"
                                />
                                <figcaption class="clamp-2 p-3 text-[11.5px] leading-snug text-subtle">
                                    {{ image.prompt }}
                                </figcaption>
                            </figure>
                        </template>

                        <template v-else>
                            <div
                                v-for="(tile, index) in placeholderTiles"
                                :key="index"
                                :class="['relative overflow-hidden rounded-2xl bg-gradient-to-br', tile]"
                            >
                                <div class="absolute inset-0 dot-grid opacity-20" aria-hidden="true" />
                                <div class="aspect-[4/3]" />
                            </div>
                        </template>
                    </div>
                </div>
            </section>

            <section class="mx-auto w-full max-w-6xl px-5 py-20 sm:px-8 lg:py-24">
                <div class="grid gap-10 lg:grid-cols-[0.9fr_1.1fr] lg:items-center">
                    <div>
                        <p class="font-alt text-[10px] font-bold uppercase tracking-[0.22em] text-brand-700 dark:text-brand-300">How it works</p>
                        <h2 class="mt-3 text-[1.9rem] leading-[1.12] tracking-[-0.03em] sm:text-[2.3rem]">
                            Three steps to a finished image
                        </h2>
                        <p class="mt-3 text-[14.5px] leading-relaxed text-subtle">
                            The studio handles provider queues, webhooks, wallet settlement and refunds — so you can stay in the creative flow.
                        </p>
                    </div>

                    <ol class="space-y-3">
                        <li
                            v-for="step in steps"
                            :key="step.number"
                            class="flex gap-4 rounded-2xl border border-line bg-surface p-4 shadow-card"
                        >
                            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-brand-500/10 font-alt text-[11px] font-bold text-brand-700 ring-1 ring-inset ring-brand-500/15 dark:text-brand-200">
                                {{ step.number }}
                            </span>
                            <div>
                                <h3 class="text-[14px] font-semibold">{{ step.title }}</h3>
                                <p class="mt-1 text-[12.5px] leading-relaxed text-subtle">{{ step.description }}</p>
                            </div>
                        </li>
                    </ol>
                </div>
            </section>

            <section id="pricing" class="scroll-mt-24 border-y border-line/70 bg-muted/40 py-20 lg:py-24">
                <div class="mx-auto w-full max-w-6xl px-5 sm:px-8">
                    <div class="max-w-2xl">
                        <p class="font-alt text-[10px] font-bold uppercase tracking-[0.22em] text-brand-700 dark:text-brand-300">Pricing</p>
                        <h2 class="mt-3 text-[1.9rem] leading-[1.12] tracking-[-0.03em] sm:text-[2.3rem]">
                            Buy credits once. Spend them on what you love.
                        </h2>
                        <p class="mt-3 text-[14.5px] leading-relaxed text-subtle">
                            One credit is $0.01. Packs never expire, and every render shows its price before you confirm.
                        </p>
                    </div>

                    <div class="mt-9 grid gap-3 sm:grid-cols-3">
                        <div
                            v-for="pack in packages"
                            :key="pack.key"
                            :class="[
                                'relative flex flex-col rounded-2xl border p-5 shadow-card',
                                pack.popular
                                    ? 'border-brand-500/50 bg-surface ring-1 ring-brand-500/20'
                                    : 'border-line bg-surface',
                            ]"
                        >
                            <span
                                v-if="pack.popular"
                                class="absolute -top-2.5 left-4 rounded-full bg-brand-600 px-2.5 py-0.5 font-alt text-[8.5px] font-bold uppercase tracking-[0.16em] text-white"
                            >
                                Most popular
                            </span>

                            <p class="font-alt text-[10px] font-bold uppercase tracking-[0.18em] text-subtle">{{ pack.name }}</p>

                            <p class="mt-3 flex items-baseline gap-1">
                                <span class="tabular text-[30px] font-semibold leading-none text-ink">
                                    {{ formatUsd(pack.price_micros, 0) }}
                                </span>
                                <span class="text-[12px] text-subtle">one-time</span>
                            </p>

                            <p class="mt-1.5 tabular text-[12.5px] font-medium text-brand-700 dark:text-brand-300">
                                {{ formatNumber(pack.credits) }} credits
                            </p>

                            <p class="mt-3 text-[12px] leading-relaxed text-subtle">{{ pack.description }}</p>

                            <ul class="mt-4 space-y-1.5">
                                <li v-for="item in ['Credits never expire', 'Failed renders refunded', 'Commercial usage rights']" :key="item" class="flex items-center gap-1.5 text-[11.5px] text-subtle">
                                    <Check class="h-3 w-3 text-success" />
                                    {{ item }}
                                </li>
                            </ul>

                            <Button
                                :href="isAuthenticated ? routes.billing : primaryHref"
                                :variant="pack.popular ? 'primary' : 'secondary'"
                                block
                                size="md"
                                class="mt-5"
                            >
                                {{ isAuthenticated ? 'Buy credits' : 'Start free' }}
                            </Button>
                        </div>
                    </div>

                    <p class="mt-5 flex items-center justify-center gap-1.5 text-center text-[11.5px] text-subtle">
                        <Zap class="h-3.5 w-3.5 text-brand-500" />
                        New accounts receive complimentary credits — no card required.
                    </p>
                </div>
            </section>

            <section id="faq" class="mx-auto w-full max-w-3xl scroll-mt-24 px-5 py-20 sm:px-8 lg:py-24">
                <div class="text-center">
                    <p class="font-alt text-[10px] font-bold uppercase tracking-[0.22em] text-brand-700 dark:text-brand-300">FAQ</p>
                    <h2 class="mt-3 text-[1.9rem] leading-[1.12] tracking-[-0.03em] sm:text-[2.2rem]">
                        Questions, answered
                    </h2>
                </div>

                <div class="mt-8 divide-y divide-line rounded-2xl border border-line bg-surface">
                    <div v-for="(faq, index) in faqs" :key="faq.question">
                        <button
                            type="button"
                            class="focus-ring flex w-full items-center justify-between gap-4 px-5 py-4 text-left"
                            :aria-expanded="openFaq === index"
                            @click="toggleFaq(index)"
                        >
                            <span class="text-[13.5px] font-semibold text-ink">{{ faq.question }}</span>
                            <ChevronDown
                                :class="[
                                    'h-4 w-4 shrink-0 text-subtle transition-transform duration-200',
                                    openFaq === index ? 'rotate-180' : '',
                                ]"
                            />
                        </button>

                        <div v-if="openFaq === index" class="px-5 pb-4">
                            <p class="text-[12.5px] leading-relaxed text-subtle">{{ faq.answer }}</p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="mx-auto w-full max-w-6xl px-5 pb-20 sm:px-8 lg:pb-24">
                <div class="relative overflow-hidden rounded-3xl border border-brand-500/25 bg-[radial-gradient(60rem_30rem_at_20%_0%,rgba(124,58,237,0.16),transparent_60%)] p-8 text-center sm:p-12">
                    <div class="pointer-events-none absolute inset-0 dot-grid opacity-25" aria-hidden="true" />

                    <div class="relative">
                        <span class="mx-auto grid h-12 w-12 place-items-center rounded-2xl bg-brand-500/12 ring-1 ring-brand-500/20">
                            <Sparkles class="h-5 w-5 text-brand-600 dark:text-brand-300" />
                        </span>

                        <h2 class="mx-auto mt-5 max-w-xl text-[1.8rem] leading-[1.12] tracking-[-0.03em] sm:text-[2.1rem]">
                            The next image you love is one prompt away
                        </h2>

                        <p class="mx-auto mt-3 max-w-md text-[13.5px] leading-relaxed text-subtle">
                            Join {{ brand.name }} and turn the idea you keep describing into something you can hold.
                        </p>

                        <div class="mt-7 flex flex-wrap items-center justify-center gap-2.5">
                            <Button :href="primaryHref" size="lg">
                                {{ isAuthenticated ? 'Open your studio' : 'Create your free studio' }}
                            </Button>
                            <Button :href="isAuthenticated ? routes.creations : routes.login" variant="secondary" size="lg">
                                {{ isAuthenticated ? 'View creations' : 'Sign in' }}
                            </Button>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <footer class="border-t border-line/70 bg-surface/60">
            <div class="mx-auto flex w-full max-w-6xl flex-col gap-6 px-5 py-8 sm:px-8 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <Logo size="md" show-tagline />
                    <p class="mt-2 max-w-sm text-[11.5px] leading-relaxed text-subtle">
                        {{ brand.tagline }} A premium AI image studio with transparent pricing and a creator-first gallery.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-x-6 gap-y-2 text-[12px] text-subtle">
                    <a href="#features" class="transition hover:text-ink">Features</a>
                    <a href="#pricing" class="transition hover:text-ink">Pricing</a>
                    <a href="#faq" class="transition hover:text-ink">FAQ</a>
                    <Link href="/privacy" class="transition hover:text-ink">Privacy</Link>
                    <Link href="/terms" class="transition hover:text-ink">Terms</Link>
                    <Link :href="routes.guide" class="transition hover:text-ink">User Guide</Link>
                </div>
            </div>

            <div class="border-t border-line/60 px-5 py-4 sm:px-8">
                <div class="mx-auto flex w-full max-w-6xl flex-col items-center justify-between gap-2 text-[11px] text-subtle sm:flex-row">
                    <p>© {{ new Date().getFullYear() }} {{ brand.name }}. All rights reserved.</p>
                    <p class="flex items-center gap-1.5">
                        <Badge tone="brand" size="sm">25% flat markup</Badge>
                        <span>Failed renders are always free.</span>
                    </p>
                </div>
            </div>
        </footer>

        <ToastHost />
    </div>
</template>
