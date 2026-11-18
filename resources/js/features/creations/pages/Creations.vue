<script setup>
import { computed, ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { Images, Search, Sparkles, X } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import PageHeader from '@/components/layout/PageHeader.vue';
import Button from '@/components/ui/Button.vue';
import Input from '@/components/ui/Input.vue';
import EmptyState from '@/components/ui/EmptyState.vue';
import Pagination from '@/components/ui/Pagination.vue';
import Skeleton from '@/components/ui/Skeleton.vue';
import GenerationCard from '@/components/brand/GenerationCard.vue';
import GenerationDetailModal from '@/components/brand/GenerationDetailModal.vue';
import { formatNumber } from '@/lib/format';
import { routes } from '@/lib/routes';

const props = defineProps({
    generations: { type: Object, required: true },
    filters: { type: Object, required: true },
    counts: { type: Object, required: true },
    activeCount: { type: Number, default: 0 },
});

const search = ref(props.filters.q ?? '');
const loading = ref(false);

const tabs = computed(() => [
    { key: 'all', label: 'All', count: props.counts.all },
    { key: 'processing', label: 'Processing', count: props.counts.processing },
    { key: 'succeeded', label: 'Succeeded', count: props.counts.succeeded },
    { key: 'failed', label: 'Failed', count: props.counts.failed },
]);

const items = computed(() => props.generations.data ?? []);

const meta = computed(() => ({
    ...props.generations,
    links: props.generations.links ?? [],
}));

const selected = ref(null);

let debounce = null;

const applyFilters = (overrides = {}) => {
    router.get(
        routes.creations,
        {
            status: props.filters.status,
            q: search.value || undefined,
            ...overrides,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            onStart: () => {
                loading.value = true;
            },
            onFinish: () => {
                loading.value = false;
            },
        },
    );
};

const setStatus = (status) => {
    if (status === props.filters.status) return;
    applyFilters({ status });
};

watch(search, () => {
    clearTimeout(debounce);
    debounce = setTimeout(() => applyFilters(), 350);
});

const clearSearch = () => {
    search.value = '';
};

const openGeneration = (generation) => {
    selected.value = generation;
};

const closeGeneration = () => {
    selected.value = null;
};

const onDeleted = () => {
    selected.value = null;
    router.reload({ only: ['generations', 'counts', 'activeCount'], preserveScroll: true });
};

const isFiltered = computed(() => props.filters.status !== 'all' || Boolean(props.filters.q));
</script>

<template>
    <Head title="My Creations" />

    <AppLayout>
        <div class="space-y-6">
            <PageHeader
                eyebrow="Gallery"
                title="My creations"
                description="Every render, archived with its prompt, dimensions and true cost."
            >
                <template #actions>
                    <Button :href="routes.generate">
                        <template #icon><Sparkles class="h-4 w-4" /></template>
                        New image
                    </Button>
                </template>
            </PageHeader>

            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div
                    class="flex flex-wrap items-center gap-1 rounded-xl border border-line bg-surface p-1"
                    role="tablist"
                    aria-label="Filter creations by status"
                >
                    <button
                        v-for="tab in tabs"
                        :key="tab.key"
                        type="button"
                        role="tab"
                        :aria-selected="filters.status === tab.key"
                        :class="[
                            'focus-ring flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-[12.5px] font-medium transition',
                            filters.status === tab.key
                                ? 'bg-brand-500/12 text-brand-700 ring-1 ring-inset ring-brand-500/20 dark:text-brand-200'
                                : 'text-subtle hover:bg-elevated hover:text-ink',
                        ]"
                        @click="setStatus(tab.key)"
                    >
                        {{ tab.label }}
                        <span class="tabular text-[10.5px] opacity-70">{{ formatNumber(tab.count) }}</span>
                    </button>
                </div>

                <div class="relative w-full lg:max-w-xs">
                    <Input
                        v-model="search"
                        placeholder="Search prompts…"
                        aria-label="Search creations"
                        size="sm"
                    >
                        <template #leading><Search class="h-3.5 w-3.5" /></template>
                        <template #trailing>
                            <button
                                v-if="search"
                                type="button"
                                class="focus-ring rounded-md p-1 text-subtle transition hover:text-ink"
                                aria-label="Clear search"
                                @click="clearSearch"
                            >
                                <X class="h-3.5 w-3.5" />
                            </button>
                        </template>
                    </Input>
                </div>
            </div>

            <div v-if="activeCount > 0" class="flex items-center gap-2 rounded-xl border border-brand-500/25 bg-brand-500/8 px-3.5 py-2.5">
                <span class="relative flex h-2 w-2">
                    <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-brand-500 opacity-60" />
                    <span class="relative inline-flex h-2 w-2 rounded-full bg-brand-500" />
                </span>
                <p class="text-[12px] font-medium text-ink">
                    {{ activeCount }} {{ activeCount === 1 ? 'render' : 'renders' }} in progress — this page refreshes as they finish.
                </p>
                <button
                    type="button"
                    class="focus-ring ml-auto rounded-lg px-2 py-1 font-alt text-[10px] font-bold uppercase tracking-[0.12em] text-brand-700 transition hover:bg-brand-500/10 dark:text-brand-300"
                    @click="router.reload({ only: ['generations', 'counts', 'activeCount'] })"
                >
                    Refresh
                </button>
            </div>

            <div v-if="loading" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                <div v-for="index in 8" :key="index" class="overflow-hidden rounded-2xl border border-line bg-surface">
                    <Skeleton rounded="rounded-none" class="aspect-[4/3] w-full" />
                    <div class="space-y-2 p-4">
                        <Skeleton class="h-3 w-3/4" />
                        <Skeleton class="h-3 w-1/2" />
                    </div>
                </div>
            </div>

            <div v-else-if="items.length" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                <GenerationCard
                    v-for="generation in items"
                    :key="generation.uuid"
                    :generation="generation"
                    @open="openGeneration"
                />
            </div>

            <EmptyState
                v-else
                :title="isFiltered ? 'No creations match these filters' : 'Your gallery is waiting'"
                :description="
                    isFiltered
                        ? 'Try a different status or clear your search to see everything.'
                        : 'Describe anything you can imagine — your first render takes just a few seconds.'
                "
            >
                <template #icon>
                    <Images v-if="isFiltered" class="h-6 w-6" />
                    <Sparkles v-else class="h-6 w-6" />
                </template>

                <template #action>
                    <Button v-if="isFiltered" variant="secondary" @click="() => { search = ''; applyFilters({ status: 'all' }); }">
                        Clear filters
                    </Button>
                    <Button v-else :href="routes.generate">Create your first image</Button>
                </template>
            </EmptyState>

            <Pagination v-if="items.length" :meta="meta" />
        </div>

        <GenerationDetailModal
            :generation="selected"
            @close="closeGeneration"
            @deleted="onDeleted"
        />
    </AppLayout>
</template>
