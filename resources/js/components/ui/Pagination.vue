<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';

const props = defineProps({
    meta: { type: Object, required: true },
});

const numericLinks = computed(() =>
    (props.meta.links ?? []).filter((link) => /^\d+$/.test(String(link.label).trim())),
);

const hasPages = computed(() => Number(props.meta.last_page ?? 1) > 1);
</script>

<template>
    <nav v-if="hasPages" class="flex flex-col items-center justify-between gap-4 pt-2 sm:flex-row" aria-label="Pagination">
        <p class="font-alt text-[12px] tracking-wide text-subtle">
            Showing
            <span class="text-ink">{{ meta.from ?? 0 }}</span>
            –
            <span class="text-ink">{{ meta.to ?? 0 }}</span>
            of
            <span class="text-ink">{{ meta.total ?? 0 }}</span>
        </p>

        <div class="flex items-center gap-1.5">
            <Link
                v-if="meta.prev_page_url"
                :href="meta.prev_page_url"
                preserve-scroll
                class="focus-ring inline-flex h-9 items-center gap-1 rounded-lg border border-line bg-surface px-3 text-[13px] font-medium text-subtle transition hover:border-brand-400 hover:text-ink"
                aria-label="Previous page"
            >
                <ChevronLeft class="h-4 w-4" />
                <span class="hidden sm:inline">Prev</span>
            </Link>
            <span
                v-else
                class="inline-flex h-9 items-center gap-1 rounded-lg border border-line/60 px-3 text-[13px] text-subtle/40"
                aria-disabled="true"
            >
                <ChevronLeft class="h-4 w-4" />
                <span class="hidden sm:inline">Prev</span>
            </span>

            <Link
                v-for="link in numericLinks"
                :key="link.label"
                :href="link.url"
                preserve-scroll
                :aria-current="link.active ? 'page' : undefined"
                :class="[
                    'focus-ring grid h-9 min-w-9 place-items-center rounded-lg border px-2 text-[13px] font-semibold transition',
                    link.active
                        ? 'border-brand-500 bg-brand-500/12 text-brand-700 dark:text-brand-200'
                        : 'border-line bg-surface text-subtle hover:border-brand-300 hover:text-ink',
                ]"
            >
                {{ link.label }}
            </Link>

            <Link
                v-if="meta.next_page_url"
                :href="meta.next_page_url"
                preserve-scroll
                class="focus-ring inline-flex h-9 items-center gap-1 rounded-lg border border-line bg-surface px-3 text-[13px] font-medium text-subtle transition hover:border-brand-400 hover:text-ink"
                aria-label="Next page"
            >
                <span class="hidden sm:inline">Next</span>
                <ChevronRight class="h-4 w-4" />
            </Link>
            <span
                v-else
                class="inline-flex h-9 items-center gap-1 rounded-lg border border-line/60 px-3 text-[13px] text-subtle/40"
                aria-disabled="true"
            >
                <span class="hidden sm:inline">Next</span>
                <ChevronRight class="h-4 w-4" />
            </span>
        </div>
    </nav>
</template>
