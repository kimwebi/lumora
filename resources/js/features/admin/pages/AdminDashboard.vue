<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Activity,
    ArrowUpRight,
    CircleDollarSign,
    Coins,
    Database,
    FileText,
    RefreshCw,
    ShieldCheck,
    Sparkles,
    TriangleAlert,
    Users,
} from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import PageHeader from '@/components/layout/PageHeader.vue';
import Card from '@/components/ui/Card.vue';
import Button from '@/components/ui/Button.vue';
import Badge from '@/components/ui/Badge.vue';
import StatCard from '@/components/ui/StatCard.vue';
import Avatar from '@/components/brand/Avatar.vue';
import TrendChart from '@/components/charts/TrendChart.vue';
import { formatCredits, formatDate, formatDateTime, formatNumber, formatUsd } from '@/lib/format';
import { routes } from '@/lib/routes';
import { useToast } from '@/composables/useToast';

const props = defineProps({
    metrics: { type: Object, required: true },
    providerReady: { type: Boolean, default: true },
});

const toast = useToast();

const range = ref(props.metrics.range_days ?? 14);
const reconciling = ref(false);
const revenueMetric = ref('charge');

const totals = computed(() => props.metrics.totals ?? {});
const series = computed(() => props.metrics.series ?? []);
const perUser = computed(() => props.metrics.per_user ?? []);
const reconciliation = computed(() => props.metrics.reconciliation ?? {});

const ranges = [
    { value: 7, label: '7d' },
    { value: 14, label: '14d' },
    { value: 30, label: '30d' },
    { value: 90, label: '90d' },
];

const setRange = (value) => {
    if (value === range.value) return;

    range.value = value;
    router.get(routes.admin, { days: value }, { preserveState: true, preserveScroll: true, replace: true });
};

const reconcile = () => {
    router.post(routes.adminReconcile, {}, {
        preserveScroll: true,
        onStart: () => {
            reconciling.value = true;
        },
        onFinish: () => {
            reconciling.value = false;
        },
        onError: () => toast.error('Reconciliation failed. Check provider credentials.', 'Sync error'),
    });
};

const chartPoints = computed(() =>
    series.value.map((point) => ({
        ...point,
        charge: point.charge_micros / 1_000_000,
        provider: point.provider_micros / 1_000_000,
        profit: point.profit_micros / 1_000_000,
    })),
);

const maxUserCharge = computed(() => Math.max(1, ...perUser.value.map((user) => user.charge_micros)));

const successRate = computed(() => Number(totals.value.success_rate ?? 0));

const variance = computed(() => Number(reconciliation.value.variance_micros ?? 0));

const varianceLabel = computed(() => {
    if (variance.value === 0) return 'Fully reconciled';
    if (variance.value > 0) return 'Provider usage higher than settled';

    return 'Settled higher than provider usage';
});

const lastSync = computed(() => reconciliation.value.last_synced_at);

const lastSyncLabel = computed(() => (lastSync.value ? formatDateTime(lastSync.value) : 'Never synced'));
</script>

<template>
    <Head title="Admin Dashboard" />

    <AppLayout container="max-w-[1500px]">
        <div class="space-y-6">
            <PageHeader
                eyebrow="Administration"
                title="Admin dashboard"
                description="Revenue, provider cost, profit and platform health at a glance."
            >
                <template #actions>
                    <div class="flex items-center gap-1 rounded-xl border border-line bg-surface p-1">
                        <button
                            v-for="option in ranges"
                            :key="option.value"
                            type="button"
                            :class="[
                                'focus-ring rounded-lg px-2.5 py-1.5 font-alt text-[11px] font-semibold transition',
                                range === option.value
                                    ? 'bg-brand-500/12 text-brand-700 ring-1 ring-inset ring-brand-500/20 dark:text-brand-200'
                                    : 'text-subtle hover:bg-elevated hover:text-ink',
                            ]"
                            @click="setRange(option.value)"
                        >
                            {{ option.label }}
                        </button>
                    </div>

                    <Link
                        :href="routes.adminReports"
                        class="focus-ring inline-flex h-10 items-center justify-center gap-2 rounded-xl border border-line bg-surface px-3.5 text-[12.5px] font-semibold text-ink transition hover:border-brand-400/70 hover:text-brand-700 dark:hover:text-brand-200"
                    >
                        <FileText class="h-4 w-4" />
                        Operator report
                    </Link>

                    <Button variant="secondary" size="md" :loading="reconciling" @click="reconcile">
                        <template #icon><RefreshCw class="h-4 w-4" /></template>
                        Reconcile usage
                    </Button>
                </template>
            </PageHeader>

            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard
                    label="Customer charges"
                    :value="formatUsd(totals.charge_micros)"
                    :hint="`${formatNumber(totals.succeeded)} successful renders`"
                    :icon="CircleDollarSign"
                    tone="brand"
                />
                <StatCard
                    label="Provider cost"
                    :value="formatUsd(totals.provider_micros)"
                    hint="Reported by Pixelforge usage"
                    :icon="Database"
                    tone="cyan"
                />
                <StatCard
                    label="Gross profit"
                    :value="formatUsd(totals.profit_micros)"
                    :hint="`${formatNumber(successRate)}% success rate`"
                    :icon="ArrowUpRight"
                    tone="success"
                />
                <StatCard
                    label="Active creators"
                    :value="formatNumber(totals.users)"
                    :hint="`${formatNumber(totals.active_users)} have generated images`"
                    :icon="Users"
                    tone="info"
                />
            </div>

            <div class="grid gap-5 xl:grid-cols-[minmax(0,1.65fr)_minmax(0,1fr)]">
                <Card padding="none">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line/70 px-5 py-4">
                        <div>
                            <h2 class="text-[15px] font-semibold">Revenue vs provider cost</h2>
                            <p class="mt-0.5 text-[12px] text-subtle">Settled generations per day, in USD.</p>
                        </div>

                        <div class="flex items-center gap-1 rounded-lg border border-line p-1">
                            <button
                                v-for="option in [
                                    { key: 'charge', label: 'Revenue' },
                                    { key: 'provider', label: 'Cost' },
                                    { key: 'profit', label: 'Profit' },
                                ]"
                                :key="option.key"
                                type="button"
                                :class="[
                                    'rounded-md px-2 py-1 font-alt text-[10.5px] font-semibold uppercase tracking-wide transition',
                                    revenueMetric === option.key
                                        ? 'bg-brand-500/12 text-brand-700 dark:text-brand-200'
                                        : 'text-subtle hover:text-ink',
                                ]"
                                @click="revenueMetric = option.key"
                            >
                                {{ option.label }}
                            </button>
                        </div>
                    </div>

                    <div class="p-4 sm:p-5">
                        <div class="mb-3 flex items-baseline gap-3">
                            <p class="tabular text-[24px] font-semibold leading-none text-ink">
                                {{ formatUsd(totals.charge_micros) }}
                            </p>
                            <span class="flex items-center gap-1 font-alt text-[10.5px] font-semibold uppercase tracking-wide text-emerald-600 dark:text-emerald-400">
                                <ArrowUpRight class="h-3 w-3" />
                                {{ formatUsd(totals.profit_micros) }} profit
                            </span>
                        </div>

                        <TrendChart
                            :points="chartPoints"
                            :value-key="revenueMetric"
                            :secondary-key="revenueMetric === 'charge' ? 'provider' : null"
                            :format-value="(value) => formatUsd(Math.round(value * 1_000_000))"
                            :height="200"
                        />

                        <div class="mt-3 flex items-center gap-4 text-[10.5px] text-subtle">
                            <span class="flex items-center gap-1.5">
                                <span class="h-2 w-2 rounded-full bg-brand-500" />
                                {{ revenueMetric === 'charge' ? 'Revenue' : revenueMetric === 'provider' ? 'Provider cost' : 'Profit' }}
                            </span>
                            <span v-if="revenueMetric === 'charge'" class="flex items-center gap-1.5">
                                <span class="h-0.5 w-3 rounded-full bg-accent" />
                                Provider cost
                            </span>
                        </div>
                    </div>
                </Card>

                <div class="space-y-5">
                    <Card padding="none">
                        <div class="flex items-center justify-between border-b border-line/70 px-5 py-4">
                            <h2 class="text-[15px] font-semibold">Provider reconciliation</h2>
                            <Badge :tone="variance === 0 ? 'success' : 'pending'" size="sm">
                                {{ variance === 0 ? 'Balanced' : 'Check' }}
                            </Badge>
                        </div>

                        <div class="space-y-3 p-5">
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-[12px] text-subtle">Settled provider cost</span>
                                <span class="tabular text-[12.5px] font-semibold text-ink">
                                    {{ formatUsd(reconciliation.generation_micros, 2) }}
                                </span>
                            </div>

                            <div class="flex items-center justify-between gap-3">
                                <span class="text-[12px] text-subtle">Usage records total</span>
                                <span class="tabular text-[12.5px] font-semibold text-ink">
                                    {{ formatUsd(reconciliation.usage_micros, 2) }}
                                </span>
                            </div>

                            <div class="hairline" />

                            <div class="flex items-center justify-between gap-3">
                                <span class="text-[12px] font-medium text-ink">Variance</span>
                                <span
                                    :class="[
                                        'tabular text-[13px] font-semibold',
                                        variance === 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-pending',
                                    ]"
                                >
                                    {{ variance > 0 ? '+' : '' }}{{ formatUsd(variance, 4) }}
                                </span>
                            </div>

                            <p class="flex items-start gap-1.5 text-[11px] leading-relaxed text-subtle">
                                <TriangleAlert v-if="variance !== 0" class="mt-0.5 h-3 w-3 shrink-0 text-pending" />
                                <Activity v-else class="mt-0.5 h-3 w-3 shrink-0 text-success" />
                                {{ varianceLabel }}
                            </p>

                            <div class="grid grid-cols-2 gap-2 rounded-xl border border-line bg-muted/40 p-3">
                                <div>
                                    <p class="font-alt text-[9px] font-bold uppercase tracking-[0.14em] text-subtle">Records</p>
                                    <p class="tabular mt-1 text-[12.5px] font-semibold text-ink">
                                        {{ formatNumber(reconciliation.usage_records) }}
                                    </p>
                                </div>
                                <div>
                                    <p class="font-alt text-[9px] font-bold uppercase tracking-[0.14em] text-subtle">Linked</p>
                                    <p class="tabular mt-1 text-[12.5px] font-semibold text-ink">
                                        {{ formatNumber(reconciliation.linked_records) }}
                                    </p>
                                </div>
                            </div>

                            <p class="text-[10.5px] text-subtle">Last synced {{ lastSyncLabel }}</p>

                            <p v-if="!providerReady" class="rounded-lg border border-line bg-muted/50 px-2.5 py-2 text-[10.5px] leading-relaxed text-subtle">
                                Provider credentials missing — reconciliation is limited to local records.
                            </p>
                        </div>
                    </Card>

                    <Card padding="none">
                        <div class="border-b border-line/70 px-5 py-4">
                            <h2 class="text-[15px] font-semibold">Render health</h2>
                        </div>

                        <div class="space-y-3 p-5">
                            <div>
                                <div class="flex items-center justify-between text-[11.5px]">
                                    <span class="text-subtle">Success</span>
                                    <span class="tabular font-semibold text-ink">{{ formatNumber(totals.succeeded) }}</span>
                                </div>
                                <div class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-elevated">
                                    <div class="h-full rounded-full bg-success" :style="{ width: `${successRate}%` }" />
                                </div>
                            </div>

                            <div class="grid grid-cols-3 gap-2 pt-1">
                                <div class="rounded-lg border border-line p-2.5">
                                    <p class="font-alt text-[9px] font-bold uppercase tracking-[0.12em] text-subtle">Failed</p>
                                    <p class="tabular mt-1 text-[13px] font-semibold text-danger">{{ formatNumber(totals.failed) }}</p>
                                </div>
                                <div class="rounded-lg border border-line p-2.5">
                                    <p class="font-alt text-[9px] font-bold uppercase tracking-[0.12em] text-subtle">Active</p>
                                    <p class="tabular mt-1 text-[13px] font-semibold text-brand-700 dark:text-brand-300">
                                        {{ formatNumber(totals.processing) }}
                                    </p>
                                </div>
                                <div class="rounded-lg border border-line p-2.5">
                                    <p class="font-alt text-[9px] font-bold uppercase tracking-[0.12em] text-subtle">Total</p>
                                    <p class="tabular mt-1 text-[13px] font-semibold text-ink">{{ formatNumber(totals.generations) }}</p>
                                </div>
                            </div>
                        </div>
                    </Card>
                </div>
            </div>

            <Card padding="none">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line/70 px-5 py-4">
                    <div>
                        <h2 class="text-[15px] font-semibold">Top spenders</h2>
                        <p class="mt-0.5 text-[12px] text-subtle">Customer charges and margin by creator.</p>
                    </div>

                    <Badge tone="neutral" size="sm">
                        <ShieldCheck class="mr-1 h-3 w-3" />
                        Admin only
                    </Badge>
                </div>

                <div v-if="perUser.length">
                    <div class="divide-y divide-line/60 lg:hidden">
                        <article v-for="creator in perUser" :key="creator.id" class="p-4 sm:p-5">
                            <div class="flex items-start gap-2.5">
                                <Avatar :name="creator.name" size="xs" />

                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-[12.5px] font-medium text-ink">{{ creator.name }}</p>
                                    <p class="truncate text-[10.5px] text-subtle">{{ creator.email }}</p>
                                </div>

                                <span class="tabular shrink-0 rounded-full border border-line bg-muted/40 px-2 py-0.5 text-[10px] font-medium text-subtle">
                                    {{ formatNumber(creator.generations) }} renders
                                </span>
                            </div>

                            <dl class="mt-3 grid grid-cols-3 gap-2 rounded-xl border border-line bg-muted/30 p-3">
                                <div>
                                    <dt class="font-alt text-[8.5px] font-bold uppercase tracking-[0.12em] text-subtle">Charged</dt>
                                    <dd class="tabular mt-1 text-[12px] font-medium text-ink">{{ formatUsd(creator.charge_micros, 3) }}</dd>
                                </div>
                                <div>
                                    <dt class="font-alt text-[8.5px] font-bold uppercase tracking-[0.12em] text-subtle">Provider</dt>
                                    <dd class="tabular mt-1 text-[12px] text-subtle">{{ formatUsd(creator.provider_micros, 3) }}</dd>
                                </div>
                                <div>
                                    <dt class="font-alt text-[8.5px] font-bold uppercase tracking-[0.12em] text-subtle">Profit</dt>
                                    <dd class="tabular mt-1 text-[12px] font-semibold text-emerald-600 dark:text-emerald-400">
                                        {{ formatUsd(creator.profit_micros, 3) }}
                                    </dd>
                                </div>
                            </dl>

                            <div class="mt-3 flex items-center gap-2">
                                <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-elevated">
                                    <div
                                        class="h-full rounded-full bg-brand-500"
                                        :style="{ width: `${Math.max(4, (creator.charge_micros / maxUserCharge) * 100)}%` }"
                                    />
                                </div>
                                <span class="tabular text-[10.5px] text-subtle">
                                    {{ formatCredits(creator.charge_micros) }} credits
                                </span>
                            </div>
                        </article>
                    </div>

                    <div class="hidden overflow-x-auto lg:block">
                        <table class="w-full min-w-[640px] text-left">
                            <thead>
                                <tr class="border-b border-line/60">
                                    <th class="px-5 py-2.5 font-alt text-[9.5px] font-bold uppercase tracking-[0.16em] text-subtle">Creator</th>
                                    <th class="px-3 py-2.5 text-right font-alt text-[9.5px] font-bold uppercase tracking-[0.16em] text-subtle">Renders</th>
                                    <th class="px-3 py-2.5 text-right font-alt text-[9.5px] font-bold uppercase tracking-[0.16em] text-subtle">Charged</th>
                                    <th class="px-3 py-2.5 text-right font-alt text-[9.5px] font-bold uppercase tracking-[0.16em] text-subtle">Provider</th>
                                    <th class="px-3 py-2.5 text-right font-alt text-[9.5px] font-bold uppercase tracking-[0.16em] text-subtle">Profit</th>
                                    <th class="px-5 py-2.5 font-alt text-[9.5px] font-bold uppercase tracking-[0.16em] text-subtle">Share</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-line/50">
                                <tr v-for="creator in perUser" :key="creator.id" class="transition hover:bg-elevated/40">
                                    <td class="px-5 py-3">
                                        <div class="flex items-center gap-2.5">
                                            <Avatar :name="creator.name" size="xs" />
                                            <div class="min-w-0">
                                                <p class="truncate text-[12.5px] font-medium text-ink">{{ creator.name }}</p>
                                                <p class="truncate text-[10.5px] text-subtle">{{ creator.email }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="tabular px-3 py-3 text-right text-[12px] text-ink">{{ formatNumber(creator.generations) }}</td>
                                    <td class="tabular px-3 py-3 text-right text-[12px] text-ink">{{ formatUsd(creator.charge_micros, 3) }}</td>
                                    <td class="tabular px-3 py-3 text-right text-[12px] text-subtle">{{ formatUsd(creator.provider_micros, 3) }}</td>
                                    <td class="tabular px-3 py-3 text-right text-[12px] font-semibold text-emerald-600 dark:text-emerald-400">
                                        {{ formatUsd(creator.profit_micros, 3) }}
                                    </td>
                                    <td class="px-5 py-3">
                                        <div class="flex items-center gap-2">
                                            <div class="h-1.5 w-24 overflow-hidden rounded-full bg-elevated">
                                                <div
                                                    class="h-full rounded-full bg-brand-500"
                                                    :style="{ width: `${Math.max(4, (creator.charge_micros / maxUserCharge) * 100)}%` }"
                                                />
                                            </div>
                                            <span class="tabular text-[10.5px] text-subtle">
                                                {{ formatCredits(creator.charge_micros) }} credits
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div v-else class="px-5 py-10 text-center">
                    <Sparkles class="mx-auto h-5 w-5 text-subtle/60" />
                    <p class="mt-2 text-[12.5px] font-medium text-ink">No settled activity yet</p>
                    <p class="mt-1 text-[11.5px] text-subtle">Spending data appears once generations settle.</p>
                </div>
            </Card>

            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-xl border border-line bg-surface p-3.5">
                    <p class="font-alt text-[9.5px] font-bold uppercase tracking-[0.14em] text-subtle">Range</p>
                    <p class="mt-1.5 text-[13px] font-semibold text-ink">Last {{ metrics.range_days ?? range }} days</p>
                </div>
                <div class="rounded-xl border border-line bg-surface p-3.5">
                    <p class="font-alt text-[9.5px] font-bold uppercase tracking-[0.14em] text-subtle">Today</p>
                    <p class="mt-1.5 text-[13px] font-semibold text-ink">{{ formatDate(new Date().toISOString()) }}</p>
                </div>
                <div class="rounded-xl border border-line bg-surface p-3.5">
                    <p class="font-alt text-[9.5px] font-bold uppercase tracking-[0.14em] text-subtle">Avg. margin</p>
                    <p class="mt-1.5 text-[13px] font-semibold text-ink">
                        {{
                            totals.charge_micros > 0
                                ? `${Math.round((totals.profit_micros / totals.charge_micros) * 100)}%`
                                : '—'
                        }}
                    </p>
                </div>
                <div class="rounded-xl border border-line bg-surface p-3.5">
                    <p class="font-alt text-[9.5px] font-bold uppercase tracking-[0.14em] text-subtle">Coins in circulation</p>
                    <p class="mt-1.5 text-[13px] font-semibold text-ink">
                        <Coins class="mr-1 inline h-3 w-3 text-brand-500" />
                        {{ formatCredits(perUser.reduce((sum, user) => sum + user.balance_micros, 0)) }} credits
                    </p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
