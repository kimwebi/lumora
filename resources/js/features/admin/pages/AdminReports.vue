<script setup>
import { computed, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import {
    ArrowUpRight,
    CheckCircle2,
    CircleDollarSign,
    Coins,
    Database,
    FileImage,
    FileSpreadsheet,
    FileText,
    ReceiptText,
    RefreshCw,
    ShieldCheck,
    TriangleAlert,
    Users,
} from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import PageHeader from '@/components/layout/PageHeader.vue';
import Card from '@/components/ui/Card.vue';
import Badge from '@/components/ui/Badge.vue';
import StatCard from '@/components/ui/StatCard.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import Avatar from '@/components/brand/Avatar.vue';
import {
    formatCredits,
    formatDate,
    formatDateTime,
    formatNumber,
    formatUsd,
} from '@/lib/format';
import { adminReportExportUrl, routes } from '@/lib/routes';
import { useToast } from '@/composables/useToast';

const props = defineProps({
    report: { type: Object, required: true },
    providerReady: { type: Boolean, default: true },
});

const toast = useToast();

const range = ref(props.report.range_days ?? 14);
const syncing = ref(false);

const totals = computed(() => props.report.totals ?? {});
const daily = computed(() => props.report.daily ?? []);
const users = computed(() => props.report.users ?? []);
const reconciliation = computed(() => props.report.reconciliation ?? {});

const ranges = [
    { value: 7, label: '7d' },
    { value: 14, label: '14d' },
    { value: 30, label: '30d' },
    { value: 90, label: '90d' },
];

const setRange = (value) => {
    if (value === range.value) return;

    range.value = value;
    router.get(
        routes.adminReports,
        { days: value },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

const syncUsage = () => {
    router.post(
        routes.adminReportsSync,
        { days: range.value },
        {
            preserveScroll: true,
            onStart: () => {
                syncing.value = true;
            },
            onFinish: () => {
                syncing.value = false;
            },
            onError: () =>
                toast.error('Provider usage sync failed. Check the credentials.', 'Sync error'),
        },
    );
};

const exportUrl = (format) => adminReportExportUrl(format, range.value);

const hasUsage = computed(() => Number(reconciliation.value.records ?? 0) > 0);

const variance = computed(() => totals.value.variance_micros);

const varianceLabel = computed(() => {
    if (!hasUsage.value || variance.value === null) return 'Waiting for provider usage';

    if (variance.value === 0) return 'Balanced against GET /v1/usage';
    if (variance.value > 0) return 'Provider API is higher than our records';

    return 'Our records are higher than the provider API';
});

const varianceTone = computed(() => {
    if (!hasUsage.value || variance.value === null) return 'pending';

    return variance.value === 0 ? 'success' : 'pending';
});

const signedUsd = (micros) => {
    if (micros === null || micros === undefined) return '—';

    return `${micros > 0 ? '+' : ''}${formatUsd(micros, 3)}`;
};

const usageValue = computed(() =>
    hasUsage.value ? formatUsd(totals.value.provider_usage_micros, 3) : 'Not synced',
);

const usageProfit = computed(() =>
    hasUsage.value ? formatUsd(totals.value.provider_usage_profit_micros, 3) : 'Not synced',
);

const margin = computed(() => Number(totals.value.margin_percent ?? 0));

const rangeLabel = computed(
    () => `${reportDate(props.report.from)} – ${reportDate(props.report.to)}`,
);

function reportDate(iso) {
    return iso ? formatDate(`${iso}T00:00:00Z`, { timeZone: 'UTC' }) : '—';
}

const lastSync = computed(() =>
    reconciliation.value.last_synced_at
        ? formatDateTime(reconciliation.value.last_synced_at)
        : 'Never synced',
);

const maxUserCharge = computed(() =>
    users.value.reduce((max, user) => Math.max(max, Number(user.charge_micros) || 0), 1),
);

const varianceClass = (value) => {
    if (value === null || value === undefined) return 'text-subtle';

    return value === 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-pending';
};
</script>

<template>
    <Head title="Operator Report" />

    <AppLayout container="max-w-[1500px]">
        <div class="space-y-6">
            <PageHeader
                eyebrow="Administration"
                title="Operator report"
                description="Per-day and per-user customer spend, Pixelforge provider costs and profit, checked against GET /v1/usage."
            >
                <template #meta>
                    <p class="mt-3 text-[11.5px] text-subtle">
                        {{ rangeLabel }} · generated {{ formatDateTime(report.generated_at) }}
                    </p>
                </template>

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

                    <button
                        type="button"
                        class="focus-ring inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-brand-600 px-3.5 text-[12.5px] font-semibold text-white transition hover:bg-brand-500 disabled:opacity-60"
                        :disabled="syncing || !providerReady"
                        @click="syncUsage"
                    >
                        <RefreshCw :class="['h-3.5 w-3.5', syncing ? 'animate-spin' : '']" />
                        {{ syncing ? 'Syncing…' : 'Sync provider usage' }}
                    </button>
                </template>
            </PageHeader>

            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                <StatCard
                    label="Customer charges"
                    :value="formatUsd(totals.charge_micros, 2)"
                    :hint="`${formatCredits(totals.charge_micros)} credits · ${formatNumber(totals.generations)} renders`"
                    :icon="CircleDollarSign"
                    tone="brand"
                />
                <StatCard
                    label="Provider cost · our records"
                    :value="formatUsd(totals.provider_micros, 3)"
                    hint="Settled provider cost per generation"
                    :icon="Database"
                    tone="cyan"
                />
                <StatCard
                    label="Gross profit · our records"
                    :value="formatUsd(totals.profit_micros, 3)"
                    :hint="`${formatNumber(margin, 1)}% margin after provider cost`"
                    :icon="ArrowUpRight"
                    tone="success"
                />
                <StatCard
                    label="Provider cost · Pixelforge API"
                    :value="usageValue"
                    :hint="hasUsage ? `${formatNumber(reconciliation.records)} usage record(s)` : 'Run Sync provider usage'"
                    :icon="ReceiptText"
                    :tone="hasUsage ? 'info' : 'pending'"
                />
                <StatCard
                    label="Gross profit · Pixelforge API"
                    :value="usageProfit"
                    :hint="hasUsage ? 'Charges minus provider API cost' : 'Needs provider usage'"
                    :icon="Coins"
                    :tone="hasUsage ? 'success' : 'pending'"
                />
                <StatCard
                    label="Variance · API minus ours"
                    :value="variance === null ? 'Not synced' : signedUsd(variance)"
                    :hint="varianceLabel"
                    :icon="variance === 0 ? CheckCircle2 : TriangleAlert"
                    :tone="varianceTone"
                />
            </div>

            <Card padding="none">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line/70 px-5 py-4">
                    <div class="min-w-0">
                        <h2 class="text-[15px] font-semibold">Provider reconciliation</h2>
                        <p class="mt-0.5 text-[12px] text-subtle">
                            Local settled costs compared with Pixelforge usage for the same range.
                        </p>
                    </div>

                    <Badge :tone="varianceTone" size="sm" dot>
                        {{ !hasUsage ? 'Not synced' : variance === 0 ? 'Balanced' : 'Review' }}
                    </Badge>
                </div>

                <div class="grid gap-4 p-5 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.1fr)]">
                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-4 lg:grid-cols-2 xl:grid-cols-4">
                        <div class="rounded-xl border border-line bg-muted/30 p-3">
                            <p class="font-alt text-[9px] font-bold uppercase tracking-[0.14em] text-subtle">Usage records</p>
                            <p class="tabular mt-1 text-[14px] font-semibold text-ink">
                                {{ formatNumber(reconciliation.records) }}
                            </p>
                        </div>
                        <div class="rounded-xl border border-line bg-muted/30 p-3">
                            <p class="font-alt text-[9px] font-bold uppercase tracking-[0.14em] text-subtle">Linked</p>
                            <p class="tabular mt-1 text-[14px] font-semibold text-ink">
                                {{ formatNumber(reconciliation.linked_records) }}
                            </p>
                        </div>
                        <div class="rounded-xl border border-line bg-muted/30 p-3">
                            <p class="font-alt text-[9px] font-bold uppercase tracking-[0.14em] text-subtle">Days with spend</p>
                            <p class="tabular mt-1 text-[14px] font-semibold text-ink">
                                {{ formatNumber(reconciliation.active_days) }}
                            </p>
                        </div>
                        <div class="rounded-xl border border-line bg-muted/30 p-3">
                            <p class="font-alt text-[9px] font-bold uppercase tracking-[0.14em] text-subtle">Variance</p>
                            <p :class="['tabular mt-1 text-[14px] font-semibold', varianceClass(variance)]">
                                {{ variance === null ? '—' : signedUsd(variance) }}
                            </p>
                        </div>
                    </div>

                    <div class="space-y-2 rounded-xl border border-line bg-muted/20 p-4">
                        <p class="flex items-start gap-2 text-[12px] leading-relaxed text-subtle">
                            <ShieldCheck class="mt-0.5 h-3.5 w-3.5 shrink-0 text-brand-500" />
                            Last synced {{ lastSync }}. Variance is the Pixelforge API cost minus our settled
                            provider records for {{ rangeLabel }}. Zero means the two agree. Pixelforge reports
                            daily usage aggregates, so most usage rows are not tied to a single render.
                        </p>

                        <p v-if="!hasUsage" class="flex items-start gap-2 text-[12px] leading-relaxed text-pending">
                            <TriangleAlert class="mt-0.5 h-3.5 w-3.5 shrink-0" />
                            No provider usage has been synced for this range yet.
                        </p>

                        <p
                            v-if="!providerReady"
                            class="flex items-start gap-2 text-[12px] leading-relaxed text-subtle"
                        >
                            <TriangleAlert class="mt-0.5 h-3.5 w-3.5 shrink-0 text-pending" />
                            Provider credentials are missing, so syncing is disabled. Local records are still shown.
                        </p>
                    </div>
                </div>
            </Card>

            <Card padding="none">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line/70 px-5 py-4">
                    <div class="min-w-0">
                        <h2 class="text-[15px] font-semibold">Daily breakdown</h2>
                        <p class="mt-0.5 text-[12px] text-subtle">
                            Charges in USD and credits, provider costs and profit per day.
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <a
                            :href="exportUrl('pdf')"
                            class="focus-ring inline-flex h-9 items-center gap-1.5 rounded-lg border border-line bg-surface px-3 text-[12px] font-medium text-ink transition hover:border-brand-400/70 hover:text-brand-700 dark:hover:text-brand-200"
                            download
                        >
                            <FileText class="h-3.5 w-3.5" />
                            PDF
                        </a>
                        <a
                            :href="exportUrl('xlsx')"
                            class="focus-ring inline-flex h-9 items-center gap-1.5 rounded-lg border border-line bg-surface px-3 text-[12px] font-medium text-ink transition hover:border-brand-400/70 hover:text-brand-700 dark:hover:text-brand-200"
                            download
                        >
                            <FileSpreadsheet class="h-3.5 w-3.5" />
                            Excel
                        </a>
                        <a
                            :href="exportUrl('png')"
                            class="focus-ring inline-flex h-9 items-center gap-1.5 rounded-lg border border-line bg-surface px-3 text-[12px] font-medium text-ink transition hover:border-brand-400/70 hover:text-brand-700 dark:hover:text-brand-200"
                            download
                        >
                            <FileImage class="h-3.5 w-3.5" />
                            PNG
                        </a>
                    </div>
                </div>

                <div v-if="daily.length" class="divide-y divide-line/60 lg:hidden">
                    <article v-for="day in daily" :key="day.date" class="p-4 sm:p-5">
                        <div class="flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-[13px] font-semibold text-ink">{{ day.label }}</p>
                                <p class="text-[10.5px] text-subtle">{{ day.date }}</p>
                            </div>

                            <span class="tabular shrink-0 rounded-full border border-line bg-muted/40 px-2 py-0.5 text-[10px] font-medium text-subtle">
                                {{ formatNumber(day.generations) }} renders
                            </span>
                        </div>

                        <dl class="mt-3 grid grid-cols-2 gap-2 rounded-xl border border-line bg-muted/30 p-3">
                            <div>
                                <dt class="font-alt text-[8.5px] font-bold uppercase tracking-[0.12em] text-subtle">Charges</dt>
                                <dd class="tabular mt-1 text-[12px] font-medium text-ink">{{ formatUsd(day.charge_micros, 3) }}</dd>
                                <dd class="tabular text-[10px] text-subtle">{{ formatCredits(day.charge_micros) }} credits</dd>
                            </div>
                            <div>
                                <dt class="font-alt text-[8.5px] font-bold uppercase tracking-[0.12em] text-subtle">Provider (ours)</dt>
                                <dd class="tabular mt-1 text-[12px] text-ink">{{ formatUsd(day.provider_micros, 3) }}</dd>
                            </div>
                            <div>
                                <dt class="font-alt text-[8.5px] font-bold uppercase tracking-[0.12em] text-subtle">Provider (API)</dt>
                                <dd class="tabular mt-1 text-[12px] text-ink">
                                    {{ day.usage_provider_micros === null ? 'Not synced' : formatUsd(day.usage_provider_micros, 3) }}
                                </dd>
                            </div>
                            <div>
                                <dt class="font-alt text-[8.5px] font-bold uppercase tracking-[0.12em] text-subtle">Variance</dt>
                                <dd :class="['tabular mt-1 text-[12px] font-medium', varianceClass(day.variance_micros)]">
                                    {{ day.variance_micros === null ? '—' : signedUsd(day.variance_micros) }}
                                </dd>
                            </div>
                            <div>
                                <dt class="font-alt text-[8.5px] font-bold uppercase tracking-[0.12em] text-subtle">Profit (ours)</dt>
                                <dd class="tabular mt-1 text-[12px] font-semibold text-emerald-600 dark:text-emerald-400">
                                    {{ formatUsd(day.profit_micros, 3) }}
                                </dd>
                            </div>
                            <div>
                                <dt class="font-alt text-[8.5px] font-bold uppercase tracking-[0.12em] text-subtle">Profit (API)</dt>
                                <dd class="tabular mt-1 text-[12px] text-subtle">
                                    {{ day.provider_usage_profit_micros === null ? '—' : formatUsd(day.provider_usage_profit_micros, 3) }}
                                </dd>
                            </div>
                        </dl>
                    </article>
                </div>

                <div v-else class="px-5 py-10">
                    <EmptyState
                        title="No settled activity in this range"
                        description="Daily spend appears once generations settle. Try a wider range or sync provider usage."
                    >
                        <template #icon>
                            <ReceiptText class="h-6 w-6" />
                        </template>
                    </EmptyState>
                </div>

                <div v-if="daily.length" class="hidden overflow-x-auto lg:block">
                    <table class="w-full min-w-[980px] text-left">
                        <thead>
                            <tr class="border-b border-line/60">
                                <th class="px-5 py-2.5 font-alt text-[9.5px] font-bold uppercase tracking-[0.16em] text-subtle">Date</th>
                                <th class="px-3 py-2.5 text-right font-alt text-[9.5px] font-bold uppercase tracking-[0.16em] text-subtle">Renders</th>
                                <th class="px-3 py-2.5 text-right font-alt text-[9.5px] font-bold uppercase tracking-[0.16em] text-subtle">Credits</th>
                                <th class="px-3 py-2.5 text-right font-alt text-[9.5px] font-bold uppercase tracking-[0.16em] text-subtle">Charges</th>
                                <th class="px-3 py-2.5 text-right font-alt text-[9.5px] font-bold uppercase tracking-[0.16em] text-subtle">Provider (ours)</th>
                                <th class="px-3 py-2.5 text-right font-alt text-[9.5px] font-bold uppercase tracking-[0.16em] text-subtle">Provider (API)</th>
                                <th class="px-3 py-2.5 text-right font-alt text-[9.5px] font-bold uppercase tracking-[0.16em] text-subtle">Variance</th>
                                <th class="px-3 py-2.5 text-right font-alt text-[9.5px] font-bold uppercase tracking-[0.16em] text-subtle">Profit (ours)</th>
                                <th class="px-5 py-2.5 text-right font-alt text-[9.5px] font-bold uppercase tracking-[0.16em] text-subtle">Profit (API)</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-line/50">
                            <tr v-for="day in daily" :key="day.date" class="transition hover:bg-elevated/40">
                                <td class="px-5 py-3">
                                    <p class="text-[12.5px] font-medium text-ink">{{ day.label }}</p>
                                    <p class="tabular text-[10px] text-subtle">{{ day.date }}</p>
                                </td>
                                <td class="tabular px-3 py-3 text-right text-[12px] text-ink">{{ formatNumber(day.generations) }}</td>
                                <td class="tabular px-3 py-3 text-right text-[12px] text-subtle">{{ formatCredits(day.charge_micros) }}</td>
                                <td class="tabular px-3 py-3 text-right text-[12px] font-medium text-ink">{{ formatUsd(day.charge_micros, 3) }}</td>
                                <td class="tabular px-3 py-3 text-right text-[12px] text-ink">{{ formatUsd(day.provider_micros, 3) }}</td>
                                <td class="tabular px-3 py-3 text-right text-[12px] text-ink">
                                    {{ day.usage_provider_micros === null ? 'Not synced' : formatUsd(day.usage_provider_micros, 3) }}
                                </td>
                                <td :class="['tabular px-3 py-3 text-right text-[12px] font-medium', varianceClass(day.variance_micros)]">
                                    {{ day.variance_micros === null ? '—' : signedUsd(day.variance_micros) }}
                                </td>
                                <td class="tabular px-3 py-3 text-right text-[12px] font-semibold text-emerald-600 dark:text-emerald-400">
                                    {{ formatUsd(day.profit_micros, 3) }}
                                </td>
                                <td class="tabular px-5 py-3 text-right text-[12px] text-subtle">
                                    {{ day.provider_usage_profit_micros === null ? '—' : formatUsd(day.provider_usage_profit_micros, 3) }}
                                </td>
                            </tr>
                        </tbody>

                        <tfoot>
                            <tr class="border-t border-line bg-muted/30">
                                <td class="px-5 py-3 text-[12px] font-semibold text-ink">Total</td>
                                <td class="tabular px-3 py-3 text-right text-[12px] font-semibold text-ink">{{ formatNumber(totals.generations) }}</td>
                                <td class="tabular px-3 py-3 text-right text-[12px] font-semibold text-ink">{{ formatCredits(totals.charge_micros) }}</td>
                                <td class="tabular px-3 py-3 text-right text-[12px] font-semibold text-ink">{{ formatUsd(totals.charge_micros, 3) }}</td>
                                <td class="tabular px-3 py-3 text-right text-[12px] font-semibold text-ink">{{ formatUsd(totals.provider_micros, 3) }}</td>
                                <td class="tabular px-3 py-3 text-right text-[12px] font-semibold text-ink">
                                    {{ hasUsage ? formatUsd(totals.provider_usage_micros, 3) : 'Not synced' }}
                                </td>
                                <td :class="['tabular px-3 py-3 text-right text-[12px] font-semibold', varianceClass(variance)]">
                                    {{ variance === null ? '—' : signedUsd(variance) }}
                                </td>
                                <td class="tabular px-3 py-3 text-right text-[12px] font-semibold text-emerald-600 dark:text-emerald-400">
                                    {{ formatUsd(totals.profit_micros, 3) }}
                                </td>
                                <td class="tabular px-5 py-3 text-right text-[12px] font-semibold text-ink">
                                    {{ hasUsage ? formatUsd(totals.provider_usage_profit_micros, 3) : '—' }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </Card>

            <Card padding="none">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line/70 px-5 py-4">
                    <div class="min-w-0">
                        <h2 class="text-[15px] font-semibold">Per-user breakdown</h2>
                        <p class="mt-0.5 text-[12px] text-subtle">
                            Every account with activity in this range, ranked by customer charges.
                        </p>
                    </div>

                    <Badge tone="neutral" size="sm">
                        <Users class="mr-1 h-3 w-3" />
                        {{ formatNumber(users.length) }} accounts
                    </Badge>
                </div>

                <div v-if="users.length" class="divide-y divide-line/60 lg:hidden">
                    <article v-for="creator in users" :key="creator.id" class="p-4 sm:p-5">
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

                        <dl class="mt-3 grid grid-cols-2 gap-2 rounded-xl border border-line bg-muted/30 p-3">
                            <div>
                                <dt class="font-alt text-[8.5px] font-bold uppercase tracking-[0.12em] text-subtle">Charged</dt>
                                <dd class="tabular mt-1 text-[12px] font-medium text-ink">{{ formatUsd(creator.charge_micros, 3) }}</dd>
                                <dd class="tabular text-[10px] text-subtle">{{ formatCredits(creator.charge_micros) }} credits</dd>
                            </div>
                            <div>
                                <dt class="font-alt text-[8.5px] font-bold uppercase tracking-[0.12em] text-subtle">Provider (ours)</dt>
                                <dd class="tabular mt-1 text-[12px] text-ink">{{ formatUsd(creator.provider_micros, 3) }}</dd>
                            </div>
                            <div>
                                <dt class="font-alt text-[8.5px] font-bold uppercase tracking-[0.12em] text-subtle">Provider (API)</dt>
                                <dd class="tabular mt-1 text-[12px] text-ink">
                                    {{ creator.usage_provider_micros === null ? 'Not synced' : formatUsd(creator.usage_provider_micros, 3) }}
                                </dd>
                            </div>
                            <div>
                                <dt class="font-alt text-[8.5px] font-bold uppercase tracking-[0.12em] text-subtle">Variance</dt>
                                <dd :class="['tabular mt-1 text-[12px] font-medium', varianceClass(creator.variance_micros)]">
                                    {{ creator.variance_micros === null ? '—' : signedUsd(creator.variance_micros) }}
                                </dd>
                            </div>
                            <div>
                                <dt class="font-alt text-[8.5px] font-bold uppercase tracking-[0.12em] text-subtle">Profit (ours)</dt>
                                <dd class="tabular mt-1 text-[12px] font-semibold text-emerald-600 dark:text-emerald-400">
                                    {{ formatUsd(creator.profit_micros, 3) }}
                                </dd>
                            </div>
                            <div>
                                <dt class="font-alt text-[8.5px] font-bold uppercase tracking-[0.12em] text-subtle">Balance</dt>
                                <dd class="tabular mt-1 text-[12px] text-subtle">{{ formatCredits(creator.balance_micros) }} credits</dd>
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
                                {{ formatCredits(creator.charge_micros) }} cr
                            </span>
                        </div>
                    </article>
                </div>

                <div v-else class="px-5 py-10">
                    <EmptyState
                        title="No user spend in this range"
                        description="Accounts appear here once their generations settle."
                    >
                        <template #icon>
                            <Users class="h-6 w-6" />
                        </template>
                    </EmptyState>
                </div>

                <div v-if="users.length" class="hidden overflow-x-auto lg:block">
                    <table class="w-full min-w-[1080px] text-left">
                        <thead>
                            <tr class="border-b border-line/60">
                                <th class="px-5 py-2.5 font-alt text-[9.5px] font-bold uppercase tracking-[0.16em] text-subtle">Creator</th>
                                <th class="px-3 py-2.5 text-right font-alt text-[9.5px] font-bold uppercase tracking-[0.16em] text-subtle">Renders</th>
                                <th class="px-3 py-2.5 text-right font-alt text-[9.5px] font-bold uppercase tracking-[0.16em] text-subtle">Credits</th>
                                <th class="px-3 py-2.5 text-right font-alt text-[9.5px] font-bold uppercase tracking-[0.16em] text-subtle">Charges</th>
                                <th class="px-3 py-2.5 text-right font-alt text-[9.5px] font-bold uppercase tracking-[0.16em] text-subtle">Provider (ours)</th>
                                <th class="px-3 py-2.5 text-right font-alt text-[9.5px] font-bold uppercase tracking-[0.16em] text-subtle">Provider (API)</th>
                                <th class="px-3 py-2.5 text-right font-alt text-[9.5px] font-bold uppercase tracking-[0.16em] text-subtle">Variance</th>
                                <th class="px-3 py-2.5 text-right font-alt text-[9.5px] font-bold uppercase tracking-[0.16em] text-subtle">Profit (ours)</th>
                                <th class="px-5 py-2.5 text-right font-alt text-[9.5px] font-bold uppercase tracking-[0.16em] text-subtle">Balance</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-line/50">
                            <tr v-for="creator in users" :key="creator.id" class="transition hover:bg-elevated/40">
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
                                <td class="tabular px-3 py-3 text-right text-[12px] text-subtle">{{ formatCredits(creator.charge_micros) }}</td>
                                <td class="tabular px-3 py-3 text-right text-[12px] font-medium text-ink">{{ formatUsd(creator.charge_micros, 3) }}</td>
                                <td class="tabular px-3 py-3 text-right text-[12px] text-ink">{{ formatUsd(creator.provider_micros, 3) }}</td>
                                <td class="tabular px-3 py-3 text-right text-[12px] text-ink">
                                    {{ creator.usage_provider_micros === null ? 'Not synced' : formatUsd(creator.usage_provider_micros, 3) }}
                                </td>
                                <td :class="['tabular px-3 py-3 text-right text-[12px] font-medium', varianceClass(creator.variance_micros)]">
                                    {{ creator.variance_micros === null ? '—' : signedUsd(creator.variance_micros) }}
                                </td>
                                <td class="tabular px-3 py-3 text-right text-[12px] font-semibold text-emerald-600 dark:text-emerald-400">
                                    {{ formatUsd(creator.profit_micros, 3) }}
                                </td>
                                <td class="tabular px-5 py-3 text-right text-[12px] text-subtle">{{ formatCredits(creator.balance_micros) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </Card>

            <p class="px-1 text-[11px] leading-relaxed text-subtle">
                Credits are customer spend at $0.01 each. Provider (ours) is the settled provider cost stored on each
                generation. Provider (API) comes from <span class="font-mono">GET /v1/usage</span> for the selected
                range; because Pixelforge reports daily totals, each account’s API cost is allocated by its share of
                that day’s local provider cost. Profit is customer charges minus provider cost.
            </p>
        </div>
    </AppLayout>
</template>
