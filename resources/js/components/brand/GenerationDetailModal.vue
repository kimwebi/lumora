<script setup>
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { Copy, Download, ExternalLink, Sparkles, Trash2 } from 'lucide-vue-next';
import Modal from '@/components/ui/Modal.vue';
import Button from '@/components/ui/Button.vue';
import CostBreakdown from '@/components/brand/CostBreakdown.vue';
import SmartImage from '@/components/brand/SmartImage.vue';
import StatusBadge from '@/components/brand/StatusBadge.vue';
import { useClipboard } from '@/composables/useClipboard';
import { formatDateTime, formatUsd } from '@/lib/format';
import { generationUrl } from '@/lib/routes';

const props = defineProps({
    generation: { type: Object, default: null },
    open: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'deleted']);

const { copy } = useClipboard();

const inFlight = computed(() =>
    ['pending', 'processing'].includes(props.generation?.status),
);

const markupPercent = computed(() => {
    const estimated = props.generation?.estimated;

    if (!estimated || !estimated.provider_micros) return 25;

    return Math.round((estimated.markup_basis_points ?? 2500) / 100);
});

const download = () => {
    if (!props.generation?.image_url) return;

    window.open(props.generation.image_url, '_blank', 'noopener');
};

const remove = () => {
    if (!props.generation) return;

    router.delete(generationUrl(props.generation.uuid), {
        preserveScroll: true,
        onSuccess: () => emit('deleted'),
    });
};
</script>

<template>
    <Modal
        v-if="generation"
        size="xl"
        :title="null"
        @close="emit('close')"
    >
        <template #header>
            <div class="flex flex-wrap items-center gap-2">
                <StatusBadge :status="generation.status" />
                <span class="font-alt text-[10.5px] uppercase tracking-[0.12em] text-subtle">
                    {{ generation.size }} · {{ generation.quality }}
                </span>
            </div>
            <p class="mt-1.5 text-[12px] text-subtle">{{ formatDateTime(generation.created_at) }}</p>
        </template>

        <div class="grid gap-0 lg:grid-cols-[minmax(0,1.25fr)_minmax(0,1fr)]">
            <div class="bg-muted/40 p-4 sm:p-5">
                <SmartImage
                    :src="generation.image_url"
                    :alt="generation.prompt"
                    :processing="inFlight"
                    ratio="aspect-square"
                    rounded="rounded-2xl"
                />

                <div class="mt-4 grid grid-cols-2 gap-2">
                    <Button
                        variant="secondary"
                        :disabled="!generation.image_url"
                        @click="download"
                    >
                        <template #icon><Download class="h-4 w-4" /></template>
                        Download
                    </Button>

                    <Button variant="secondary" @click="copy(generation.prompt, 'Prompt copied')">
                        <template #icon><Copy class="h-4 w-4" /></template>
                        Copy prompt
                    </Button>
                </div>

                <a
                    v-if="generation.image_url"
                    :href="generation.image_url"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="mt-2 flex items-center justify-center gap-1.5 text-[11.5px] font-medium text-subtle transition hover:text-brand-600 dark:hover:text-brand-300"
                >
                    <ExternalLink class="h-3 w-3" />
                    Open original
                </a>
            </div>

            <div class="space-y-5 p-4 sm:p-5">
                <div>
                    <p class="font-alt text-[9.5px] font-bold uppercase tracking-[0.2em] text-subtle">Prompt</p>
                    <p class="mt-2 whitespace-pre-line text-[13.5px] leading-relaxed text-ink">
                        {{ generation.prompt }}
                    </p>
                </div>

                <div v-if="generation.reference_images?.length" class="flex flex-wrap gap-2">
                    <a
                        v-for="reference in generation.reference_images"
                        :key="reference"
                        :href="reference"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="rounded-lg border border-line bg-elevated px-2 py-1 font-alt text-[10px] font-semibold uppercase tracking-wide text-subtle transition hover:border-brand-400/50 hover:text-ink"
                    >
                        Reference image
                    </a>
                </div>

                <div class="grid grid-cols-2 gap-3 rounded-xl border border-line bg-muted/40 p-3.5">
                    <div>
                        <p class="font-alt text-[9.5px] font-bold uppercase tracking-[0.16em] text-subtle">Dimensions</p>
                        <p class="tabular mt-1 text-[13px] font-semibold text-ink">
                            {{ generation.width }} × {{ generation.height }}
                        </p>
                    </div>
                    <div>
                        <p class="font-alt text-[9.5px] font-bold uppercase tracking-[0.16em] text-subtle">Quality</p>
                        <p class="mt-1 text-[13px] font-semibold capitalize text-ink">{{ generation.quality }}</p>
                    </div>
                    <div>
                        <p class="font-alt text-[9.5px] font-bold uppercase tracking-[0.16em] text-subtle">Credits charged</p>
                        <p class="tabular mt-1 text-[13px] font-semibold text-ink">{{ generation.charged_credits }}</p>
                    </div>
                    <div>
                        <p class="font-alt text-[9.5px] font-bold uppercase tracking-[0.16em] text-subtle">Settled</p>
                        <p class="mt-1 text-[13px] font-semibold text-ink">
                            {{ generation.settled_at ? formatDateTime(generation.settled_at) : 'Pending' }}
                        </p>
                    </div>
                </div>

                <div
                    v-if="generation.error"
                    class="rounded-xl border border-danger/25 bg-danger/8 p-3.5"
                >
                    <p class="flex items-center gap-1.5 text-[12px] font-semibold text-danger">
                        <Sparkles class="h-3.5 w-3.5" />
                        {{ generation.error.code === 'provider_not_configured' ? 'Studio not connected' : 'Render failed' }}
                    </p>
                    <p class="mt-1.5 text-[12.5px] leading-relaxed text-subtle">{{ generation.error.message }}</p>
                </div>

                <div v-else-if="generation.estimated" class="rounded-xl border border-line p-3.5">
                    <p class="mb-2.5 font-alt text-[9.5px] font-bold uppercase tracking-[0.2em] text-subtle">
                        {{ generation.actual ? 'Final cost' : 'Estimated cost' }}
                    </p>
                    <CostBreakdown
                        :estimated="generation.estimated"
                        :actual="generation.actual"
                        :basis-points="markupPercent"
                        compact
                    />
                </div>

                <p v-if="generation.status === 'failed'" class="text-[11.5px] leading-relaxed text-subtle">
                    Failed renders are never charged — your credits were returned automatically.
                </p>

                <div v-if="generation.can_delete" class="border-t border-line/70 pt-4">
                    <Button variant="danger-ghost" size="sm" @click="remove">
                        <template #icon><Trash2 class="h-3.5 w-3.5" /></template>
                        Remove from gallery
                    </Button>
                </div>
            </div>
        </div>

        <template #footer>
            <div class="flex items-center justify-between gap-3">
                <p class="font-alt text-[10.5px] uppercase tracking-[0.14em] text-subtle">
                    {{ formatUsd(generation.actual?.total_micros ?? generation.estimated?.total_micros ?? 0, 4) }}
                    total
                </p>
                <Button variant="secondary" size="sm" @click="emit('close')">Close</Button>
            </div>
        </template>
    </Modal>
</template>
