<script setup>
import { computed, ref, watch } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    ArrowRight,
    CircleDollarSign,
    Coins,
    Images,
    Plug,
    Plus,
    Sparkles,
    TrendingUp,
    TriangleAlert,
} from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import PageHeader from '@/components/layout/PageHeader.vue';
import StatCard from '@/components/ui/StatCard.vue';
import Card from '@/components/ui/Card.vue';
import Button from '@/components/ui/Button.vue';
import Badge from '@/components/ui/Badge.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import GenerationCard from '@/components/brand/GenerationCard.vue';
import GenerationDetailModal from '@/components/brand/GenerationDetailModal.vue';
import SmartImage from '@/components/brand/SmartImage.vue';
import StatusBadge from '@/components/brand/StatusBadge.vue';
import { formatCredits, formatNumber, formatRelative, formatUsd } from '@/lib/format';
import { routes } from '@/lib/routes';
import { useGenerationPolling } from '@/composables/useGenerationPolling';
import { useToast } from '@/composables/useToast';

const props = defineProps({
    stats: { type: Object, required: true },
    recent: { type: Array, default: () => [] },
    active: { type: Object, default: null },
    providerReady: { type: Boolean, default: true },
});

const page = usePage();
const toast = useToast();

const user = computed(() => page.props.auth?.user);

const greeting = computed(() => {
    const hour = new Date().getHours();

    if (hour < 12) return 'Good morning';
    if (hour < 18) return 'Good afternoon';

    return 'Good evening';
});

const firstName = computed(() => (user.value?.name ?? 'creator').split(' ')[0]);

const current = ref(props.active);
const selected = ref(null);

watch(
    () => props.active,
    (value) => {
        current.value = value;
    },
);

const { start, stop } = useGenerationPolling(5000);

const trackActive = (generation) => {
    if (!generation) return;

    if (generation.is_terminal ?? ['succeeded', 'failed'].includes(generation.status)) {
        return;
    }

    start(generation.uuid, async (payload) => {
        current.value = payload.generation;

        if (payload.generation.is_terminal) {
            stop();

            toast.success(
                payload.generation.status === 'succeeded'
                    ? 'Your image is ready in My Creations.'
                    : 'That render failed. Credits were returned.',
                payload.generation.status === 'succeeded' ? 'Render complete' : 'Render failed',
            );

            router.reload({ only: ['stats', 'recent', 'active', 'notifications'], preserveScroll: true });
        }
    });
};

watch(
    () => current.value?.uuid,
    (uuid) => {
        if (uuid && !props.active?.is_terminal) {
            trackActive(current.value);
        }
    },
    { immediate: true },
);

const openGeneration = (generation) => {
    selected.value = generation;
};

const closeGeneration = () => {
    selected.value = null;
};

const onDeleted = () => {
    selected.value = null;
    router.reload({ only: ['stats', 'recent', 'active'], preserveScroll: true });
};

const imageCount = computed(() => props.stats?.succeeded ?? 0);
</script>

<template>
    <Head title="Overview" />

    <AppLayout>
        <div class="space-y-7">
            <PageHeader
                :eyebrow="`${greeting}, ${firstName}`"
                title="Your studio at a glance"
                description="Every render, credit and insight in one calm place."
            >
                <template #actions>
                    <Button :href="routes.generate" size="md">
                        <template #icon><Plus class="h-4 w-4" /></template>
                        Create image
                    </Button>
                    <Button :href="routes.billing" variant="secondary" size="md">
                        <template #icon><Coins class="h-4 w-4" /></template>
                        Buy credits
                    </Button>
                </template>
            </PageHeader>

            <div
                v-if="!providerReady"
                class="flex flex-col gap-3 rounded-2xl border border-pending/30 bg-pending/8 p-4 sm:flex-row sm:items-center sm:justify-between"
                role="status"
            >
                <div class="flex items-start gap-3">
                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-pending/15 text-amber-600 dark:text-amber-300">
                        <Plug class="h-4 w-4" />
                    </span>
                    <div>
                        <p class="text-[13.5px] font-semibold text-ink">Your image provider is not connected yet</p>
                        <p class="mt-1 text-[12.5px] leading-relaxed text-subtle">
                            Add your Pixelforge API key to <code class="rounded bg-elevated px-1 py-0.5 font-mono text-[11px]">.env</code>
                            to enable live rendering. The studio, wallet and gallery are ready.
                        </p>
                    </div>
                </div>
                <Button :href="routes.settings" variant="secondary" size="sm">Open settings</Button>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard
                    label="Credit balance"
                    :value="`${formatCredits(stats.balance_micros)} credits`"
                    :hint="`${formatUsd(stats.balance_micros, 2)} available`"
                    :icon="Coins"
                    tone="brand"
                />
                <StatCard
                    label="Total generations"
                    :value="formatNumber(stats.total_generations)"
                    :hint="`${formatNumber(stats.processing)} in progress`"
                    :icon="Sparkles"
                    tone="cyan"
                />
                <StatCard
                    label="Images created"
                    :value="formatNumber(imageCount)"
                    :hint="`${formatNumber(stats.failed)} failed · refunded`"
                    :icon="Images"
                    tone="success"
                />
                <StatCard
                    label="Total spent"
                    :value="formatUsd(stats.spent_micros, 2)"
                    hint="Actual charges, after settlement"
                    :icon="CircleDollarSign"
                    tone="info"
                />
            </div>

            <div class="grid gap-5 xl:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)]">
                <Card padding="none" class="overflow-hidden">
                    <div class="flex items-center justify-between gap-3 border-b border-line/70 px-5 py-4">
                        <div>
                            <h2 class="text-[15px] font-semibold">Recent creations</h2>
                            <p class="mt-0.5 text-[12px] text-subtle">Your latest renders, newest first.</p>
                        </div>

                        <Link
                            :href="routes.creations"
                            class="flex items-center gap-1 font-alt text-[10.5px] font-bold uppercase tracking-[0.14em] text-brand-700 transition hover:text-brand-500 dark:text-brand-300"
                        >
                            View all
                            <ArrowRight class="h-3.5 w-3.5" />
                        </Link>
                    </div>

                    <div v-if="recent.length" class="grid gap-3 p-3 sm:grid-cols-2 sm:p-4">
                        <GenerationCard
                            v-for="generation in recent.slice(0, 4)"
                            :key="generation.uuid"
                            :generation="generation"
                            @open="openGeneration"
                        />
                    </div>

                    <div v-else class="p-4">
                        <EmptyState
                            title="Your gallery is waiting"
                            description="Describe anything you can imagine — your first render takes a few seconds."
                        >
                            <template #icon><Sparkles class="h-6 w-6" /></template>
                            <template #action>
                                <Button :href="routes.generate">Create your first image</Button>
                            </template>
                        </EmptyState>
                    </div>
                </Card>

                <div class="space-y-5">
                    <Card v-if="current" padding="none" class="overflow-hidden">
                        <div class="flex items-center justify-between gap-2 border-b border-line/70 px-4 py-3">
                            <p class="font-alt text-[9.5px] font-bold uppercase tracking-[0.2em] text-subtle">Live render</p>
                            <StatusBadge :status="current.status" size="sm" />
                        </div>

                        <SmartImage
                            :src="current.image_url"
                            :alt="current.prompt"
                            :processing="!current.is_terminal"
                            ratio="aspect-[16/10]"
                        />

                        <div class="space-y-3 p-4">
                            <p class="clamp-2 text-[13px] font-medium leading-snug text-ink">{{ current.prompt }}</p>

                            <div class="flex items-center justify-between gap-2 text-[11.5px] text-subtle">
                                <span class="tabular">{{ current.size }} · {{ current.quality }}</span>
                                <span class="tabular uppercase tracking-wide">{{ current.status }}</span>
                            </div>

                            <div v-if="!current.is_terminal" class="relative h-1 overflow-hidden rounded-full bg-elevated">
                                <div class="absolute inset-y-0 left-0 w-1/3 animate-shimmer rounded-full" />
                            </div>

                            <Button v-if="current.is_terminal" :href="routes.creations" variant="secondary" block size="sm">
                                View in gallery
                            </Button>
                        </div>
                    </Card>

                    <Card v-else padding="none">
                        <div class="border-b border-line/70 px-4 py-3">
                            <p class="font-alt text-[9.5px] font-bold uppercase tracking-[0.2em] text-subtle">Quick start</p>
                        </div>

                        <div class="space-y-2 p-3">
                            <Link
                                :href="routes.generate"
                                class="focus-ring group flex items-start gap-3 rounded-xl border border-transparent p-2.5 transition hover:border-brand-400/30 hover:bg-brand-500/5"
                            >
                                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-brand-500/10 text-brand-600 ring-1 ring-inset ring-brand-500/15 dark:text-brand-300">
                                    <Sparkles class="h-4 w-4" />
                                </span>
                                <span class="min-w-0">
                                    <span class="block text-[13px] font-semibold text-ink">Generate an image</span>
                                    <span class="block text-[11.5px] leading-snug text-subtle">Prompt, size, quality and live pricing.</span>
                                </span>
                            </Link>

                            <Link
                                :href="routes.creations"
                                class="focus-ring group flex items-start gap-3 rounded-xl border border-transparent p-2.5 transition hover:border-brand-400/30 hover:bg-brand-500/5"
                            >
                                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-info/10 text-sky-600 ring-1 ring-inset ring-info/20 dark:text-sky-300">
                                    <Images class="h-4 w-4" />
                                </span>
                                <span class="min-w-0">
                                    <span class="block text-[13px] font-semibold text-ink">Browse creations</span>
                                    <span class="block text-[11.5px] leading-snug text-subtle">Filter, search and download past work.</span>
                                </span>
                            </Link>

                            <Link
                                :href="routes.billing"
                                class="focus-ring group flex items-start gap-3 rounded-xl border border-transparent p-2.5 transition hover:border-brand-400/30 hover:bg-brand-500/5"
                            >
                                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-success/10 text-emerald-600 ring-1 ring-inset ring-success/20 dark:text-emerald-300">
                                    <TrendingUp class="h-4 w-4" />
                                </span>
                                <span class="min-w-0">
                                    <span class="block text-[13px] font-semibold text-ink">Review spend</span>
                                    <span class="block text-[11.5px] leading-snug text-subtle">Credits, ledger and purchase history.</span>
                                </span>
                            </Link>
                        </div>
                    </Card>

                    <div
                        v-if="!providerReady"
                        class="flex items-start gap-2.5 rounded-2xl border border-line bg-muted/50 p-3.5"
                    >
                        <TriangleAlert class="mt-0.5 h-4 w-4 shrink-0 text-pending" />
                        <p class="text-[11.5px] leading-relaxed text-subtle">
                            Estimates follow Pixelforge’s documented token model until the provider reports real usage.
                        </p>
                    </div>
                </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-3">
                <div class="rounded-2xl border border-line bg-surface p-4">
                    <div class="flex items-center gap-2">
                        <Badge tone="brand" size="sm">Transparent</Badge>
                        <p class="text-[12.5px] font-semibold text-ink">Cost + 25%</p>
                    </div>
                    <p class="mt-2 text-[11.5px] leading-relaxed text-subtle">
                        You always see the provider cost and our markup before generating.
                    </p>
                </div>

                <div class="rounded-2xl border border-line bg-surface p-4">
                    <div class="flex items-center gap-2">
                        <Badge tone="success" size="sm">Guaranteed</Badge>
                        <p class="text-[12.5px] font-semibold text-ink">Failures are free</p>
                    </div>
                    <p class="mt-2 text-[11.5px] leading-relaxed text-subtle">
                        If a render fails, your estimated charge is refunded automatically.
                    </p>
                </div>

                <div class="rounded-2xl border border-line bg-surface p-4">
                    <div class="flex items-center gap-2">
                        <Badge tone="info" size="sm">Accurate</Badge>
                        <p class="text-[12.5px] font-semibold text-ink">Actual usage</p>
                    </div>
                    <p class="mt-2 text-[11.5px] leading-relaxed text-subtle">
                        Final charges come from the provider's reported token usage, not estimates.
                    </p>
                </div>
            </div>
        </div>

        <GenerationDetailModal
            :generation="selected"
            @close="closeGeneration"
            @deleted="onDeleted"
        />
    </AppLayout>
</template>
