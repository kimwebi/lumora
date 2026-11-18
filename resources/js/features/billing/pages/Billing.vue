<script setup>
import { computed, ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import {
    ArrowDownRight,
    ArrowUpRight,
    Check,
    Coins,
    CreditCard,
    Receipt,
    Sparkles,
    Wallet,
} from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import PageHeader from '@/components/layout/PageHeader.vue';
import Card from '@/components/ui/Card.vue';
import Button from '@/components/ui/Button.vue';
import Badge from '@/components/ui/Badge.vue';
import Modal from '@/components/ui/Modal.vue';
import StatCard from '@/components/ui/StatCard.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import Pagination from '@/components/ui/Pagination.vue';
import { formatCredits, formatDateTime, formatNumber, formatUsd } from '@/lib/format';
import { routes } from '@/lib/routes';
import { uuid } from '@/lib/http';
import { useToast } from '@/composables/useToast';

const props = defineProps({
    summary: { type: Object, required: true },
    packages: { type: Array, required: true },
    transactions: { type: Object, required: true },
    creditMicros: { type: Number, default: 10000 },
});

const toast = useToast();

const selected = ref(null);

const form = useForm({
    package: '',
    purchase_token: uuid(),
});

const items = computed(() => props.transactions.data ?? []);

const meta = computed(() => ({
    ...props.transactions,
    links: props.transactions.links ?? [],
}));

const totalAvailable = computed(() => Number(props.summary.balance_micros ?? 0));

const lifetimeCredits = computed(() =>
    Number(props.summary.purchased_micros ?? 0) + Number(props.summary.bonus_micros ?? 0),
);

const openPackage = (pack) => {
    selected.value = pack;
    form.package = pack.key;
    form.purchase_token = uuid();
    form.clearErrors();
};

const closePackage = () => {
    selected.value = null;
    form.package = '';
};

const confirmPurchase = () => {
    form.post(routes.billingPurchase, {
        preserveScroll: true,
        onSuccess: () => {
            closePackage();
        },
        onError: () => toast.error('That purchase could not be completed. Please try again.', 'Purchase failed'),
    });
};

const typeMeta = {
    purchase: { label: 'Purchase', tone: 'success', icon: ArrowUpRight, sign: '+' },
    bonus: { label: 'Bonus', tone: 'info', icon: Sparkles, sign: '+' },
    hold: { label: 'Hold', tone: 'pending', icon: ArrowDownRight, sign: '−' },
    settlement: { label: 'Settlement', tone: 'brand', icon: Receipt, sign: '' },
    refund: { label: 'Refund', tone: 'success', icon: ArrowUpRight, sign: '+' },
    adjustment: { label: 'Adjustment', tone: 'neutral', icon: CreditCard, sign: '' },
};

const metaFor = (type) => typeMeta[type] ?? { label: type, tone: 'neutral', icon: Receipt, sign: '' };

const packagePerCredit = (pack) => {
    const per = pack.credits > 0 ? pack.price_micros / pack.credits : 0;

    return formatUsd(Math.round(per), 3);
};
</script>

<template>
    <Head title="Credits & Billing" />

    <AppLayout>
        <div class="space-y-6">
            <PageHeader
                eyebrow="Wallet"
                title="Credits & billing"
                description="Transparent, prepaid credits. You only ever pay for renders that succeed."
            >
                <template #actions>
                    <Button :href="routes.generate" variant="secondary">
                        <template #icon><Sparkles class="h-4 w-4" /></template>
                        Back to studio
                    </Button>
                </template>
            </PageHeader>

            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard
                    label="Available balance"
                    :value="`${formatCredits(summary.balance_micros)} credits`"
                    :hint="`${formatUsd(summary.balance_micros, 2)} ready to spend`"
                    :icon="Wallet"
                    tone="brand"
                />
                <StatCard
                    label="Lifetime credits"
                    :value="`${formatCredits(lifetimeCredits)} credits`"
                    :hint="`${formatCredits(summary.purchased_micros)} purchased · ${formatCredits(summary.bonus_micros)} bonus`"
                    :icon="Coins"
                    tone="cyan"
                />
                <StatCard
                    label="Spent on renders"
                    :value="formatUsd(summary.consumed_micros, 2)"
                    :hint="`${formatCredits(summary.consumed_micros)} credits consumed`"
                    :icon="ArrowDownRight"
                    tone="success"
                />
                <StatCard
                    label="Held for renders"
                    :value="`${formatCredits(summary.pending_micros)} credits`"
                    hint="Released on settlement or refund"
                    :icon="Receipt"
                    tone="pending"
                />
            </div>

            <div class="grid gap-5 xl:grid-cols-[minmax(0,1.15fr)_minmax(0,1fr)]">
                <div class="space-y-5">
                    <Card padding="none">
                        <div class="flex items-center justify-between gap-3 border-b border-line/70 px-5 py-4">
                            <div>
                                <h2 class="text-[15px] font-semibold">Buy credits</h2>
                                <p class="mt-0.5 text-[12px] text-subtle">Instant top-up. Demo checkout — no card required.</p>
                            </div>

                            <Badge tone="info" size="sm">Instant</Badge>
                        </div>

                        <div class="grid gap-3 p-4 sm:grid-cols-3 sm:p-5">
                            <button
                                v-for="pack in packages"
                                :key="pack.key"
                                type="button"
                                :class="[
                                    'group relative flex flex-col rounded-2xl border p-4 text-left transition-all duration-200',
                                    pack.popular
                                        ? 'border-brand-500/50 bg-brand-500/6 ring-1 ring-brand-500/20'
                                        : 'border-line bg-surface hover:border-brand-400/50 hover:bg-elevated/50',
                                ]"
                                @click="openPackage(pack)"
                            >
                                <span
                                    v-if="pack.popular"
                                    class="absolute -top-2 left-3 rounded-full bg-brand-600 px-2 py-0.5 font-alt text-[8.5px] font-bold uppercase tracking-[0.16em] text-white"
                                >
                                    Most popular
                                </span>

                                <span class="font-alt text-[10px] font-bold uppercase tracking-[0.18em] text-subtle">
                                    {{ pack.name }}
                                </span>

                                <span class="tabular mt-2 text-[22px] font-semibold leading-none text-ink">
                                    {{ formatNumber(pack.credits) }}
                                    <span class="font-alt text-[11px] font-medium uppercase tracking-wide text-subtle">credits</span>
                                </span>

                                <span class="tabular mt-1 text-[13px] font-medium text-brand-700 dark:text-brand-300">
                                    {{ pack.price }}
                                </span>

                                <span class="mt-3 text-[11px] leading-relaxed text-subtle">{{ pack.description }}</span>

                                <span class="mt-3 flex items-center justify-between border-t border-line/70 pt-2.5">
                                    <span class="font-alt text-[9.5px] uppercase tracking-wide text-subtle">
                                        {{ packagePerCredit(pack) }} / credit
                                    </span>
                                    <span class="grid h-6 w-6 place-items-center rounded-full bg-brand-500/10 text-brand-600 transition group-hover:bg-brand-500 group-hover:text-white dark:text-brand-300">
                                        <ArrowUpRight class="h-3 w-3" />
                                    </span>
                                </span>
                            </button>
                        </div>
                    </Card>

                    <div class="rounded-2xl border border-line bg-muted/40 p-4">
                        <div class="flex items-start gap-2.5">
                            <Check class="mt-0.5 h-4 w-4 shrink-0 text-success" />
                            <p class="text-[12px] leading-relaxed text-subtle">
                                <span class="font-semibold text-ink">One credit = $0.01.</span>
                                Every render is priced at the provider's live cost plus a transparent 25% markup. Failed
                                renders are refunded in full, automatically.
                            </p>
                        </div>
                    </div>
                </div>

                <Card padding="none">
                    <div class="flex items-center justify-between gap-3 border-b border-line/70 px-5 py-4">
                        <div>
                            <h2 class="text-[15px] font-semibold">Transaction history</h2>
                            <p class="mt-0.5 text-[12px] text-subtle">The complete ledger for your wallet.</p>
                        </div>

                        <Receipt class="h-4 w-4 text-subtle" />
                    </div>

                    <div v-if="items.length" class="divide-y divide-line/60">
                        <div
                            v-for="transaction in items"
                            :key="transaction.id"
                            class="flex items-center gap-3 px-4 py-3 transition hover:bg-elevated/40 sm:px-5"
                        >
                            <span
                                :class="[
                                    'grid h-8 w-8 shrink-0 place-items-center rounded-lg ring-1 ring-inset',
                                    transaction.type === 'purchase' || transaction.type === 'bonus' || transaction.type === 'refund'
                                        ? 'bg-success/10 text-emerald-600 ring-success/20 dark:text-emerald-300'
                                        : transaction.type === 'hold'
                                            ? 'bg-pending/10 text-amber-600 ring-pending/20 dark:text-amber-300'
                                            : 'bg-brand-500/10 text-brand-600 ring-brand-500/20 dark:text-brand-300',
                                ]"
                            >
                                <component :is="metaFor(transaction.type).icon" class="h-3.5 w-3.5" />
                            </span>

                            <div class="min-w-0 flex-1">
                                <p class="truncate text-[12.5px] font-medium text-ink">{{ transaction.description }}</p>
                                <p class="mt-0.5 flex items-center gap-1.5 text-[10.5px] text-subtle">
                                    <span class="font-alt uppercase tracking-wide">{{ metaFor(transaction.type).label }}</span>
                                    <span class="text-line">·</span>
                                    {{ formatDateTime(transaction.created_at) }}
                                </p>
                            </div>

                            <div class="shrink-0 text-right">
                                <p
                                    :class="[
                                        'tabular text-[12.5px] font-semibold',
                                        Number(transaction.amount_micros) > 0
                                            ? 'text-emerald-600 dark:text-emerald-400'
                                            : Number(transaction.amount_micros) < 0
                                                ? 'text-ink'
                                                : 'text-subtle',
                                    ]"
                                >
                                    {{ Number(transaction.amount_micros) > 0 ? '+' : Number(transaction.amount_micros) < 0 ? '−' : '' }}{{ formatCredits(transaction.amount_micros) }}
                                </p>
                                <p class="tabular text-[10px] text-subtle">
                                    {{ formatCredits(transaction.balance_after_micros) }} credits
                                </p>
                            </div>
                        </div>
                    </div>

                    <div v-else class="p-4">
                        <EmptyState
                            title="No transactions yet"
                            description="Your purchases, holds, settlements and refunds will appear here."
                        >
                            <template #icon><Receipt class="h-6 w-6" /></template>
                        </EmptyState>
                    </div>

                    <div v-if="items.length" class="border-t border-line/70 p-4">
                        <Pagination :meta="meta" />
                    </div>
                </Card>
            </div>
        </div>

        <Modal
            v-if="selected"
            size="sm"
            :title="`Buy ${selected.name} credits`"
            :description="`${formatNumber(selected.credits)} credits for ${selected.price}.`"
            @close="closePackage"
        >
            <div class="space-y-4 p-5">
                <div class="rounded-xl border border-line bg-muted/40 p-4">
                    <div class="flex items-center justify-between">
                        <span class="text-[12.5px] text-subtle">Credits added</span>
                        <span class="tabular text-[13px] font-semibold text-ink">+{{ formatNumber(selected.credits) }}</span>
                    </div>
                    <div class="mt-2 flex items-center justify-between">
                        <span class="text-[12.5px] text-subtle">New balance</span>
                        <span class="tabular text-[13px] font-semibold text-ink">
                            {{ formatCredits(totalAvailable + selected.credits * creditMicros) }} credits
                        </span>
                    </div>
                    <div class="mt-2 flex items-center justify-between border-t border-line/70 pt-2">
                        <span class="text-[12.5px] font-semibold text-ink">Total</span>
                        <span class="tabular text-[15px] font-semibold text-ink">{{ selected.price }}</span>
                    </div>
                </div>

                <p class="text-[11.5px] leading-relaxed text-subtle">
                    This is a demo checkout. In production this step would hand off to your payment provider before
                    credits are granted.
                </p>
            </div>

            <template #footer>
                <div class="flex items-center justify-between gap-2">
                    <Button variant="ghost" size="sm" :disabled="form.processing" @click="closePackage">Cancel</Button>
                    <Button size="sm" :loading="form.processing" @click="confirmPurchase">
                        <template #icon><CreditCard class="h-3.5 w-3.5" /></template>
                        Confirm purchase
                    </Button>
                </div>
            </template>
        </Modal>
    </AppLayout>
</template>
