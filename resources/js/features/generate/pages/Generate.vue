<script setup>
import { computed, ref, watch } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import {
    ArrowRight,
    Check,
    Coins,
    ImagePlus,
    Info,
    Link2,
    Sparkles,
    Wand2,
    X,
} from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import PageHeader from '@/components/layout/PageHeader.vue';
import Card from '@/components/ui/Card.vue';
import Button from '@/components/ui/Button.vue';
import Badge from '@/components/ui/Badge.vue';
import Textarea from '@/components/ui/Textarea.vue';
import Input from '@/components/ui/Input.vue';
import FormField from '@/components/ui/FormField.vue';
import StatusBadge from '@/components/brand/StatusBadge.vue';
import CostBreakdown from '@/components/brand/CostBreakdown.vue';
import SmartImage from '@/components/brand/SmartImage.vue';
import { formatCredits, formatRelative } from '@/lib/format';
import { estimatePrice } from '@/lib/pricing';
import { routes } from '@/lib/routes';
import { useGenerationPolling } from '@/composables/useGenerationPolling';
import { useToast } from '@/composables/useToast';

const props = defineProps({
    options: { type: Object, required: true },
    pricing: { type: Object, required: true },
    balanceMicros: { type: Number, default: 0 },
    providerReady: { type: Boolean, default: true },
    activeCount: { type: Number, default: 0 },
    maxActive: { type: Number, default: 3 },
    active: { type: Object, default: null },
});

const page = usePage();
const toast = useToast();

const form = useForm({
    prompt: '',
    size: props.options.defaults.size,
    quality: props.options.defaults.quality,
    reference_images: [],
});

const prompts = [
    'A bioluminescent cathedral floating above a midnight ocean, volumetric light, cinematic',
    'Portrait of a desert nomad in woven chrome fabric, soft rim lighting, editorial photography',
    'An electric violet koi swimming through a nebula, ultra detailed, dreamlike atmosphere',
    'Brutalist observatory on Europa, cyan aurora, photorealistic architectural render',
    'A botanical greenhouse on Mars, glass domes, warm sunlight, retro-futurism',
    'Abstract liquid mercury sculpture with lavender highlights, studio lighting, 8k',
];

const lastIdea = ref(-1);

const useIdea = () => {
    let index = Math.floor(Math.random() * prompts.length);

    if (index === lastIdea.value) index = (index + 1) % prompts.length;

    lastIdea.value = index;
    form.prompt = prompts[index];
};

const referenceDraft = ref('');
const referenceError = ref(null);

const addReference = () => {
    const value = referenceDraft.value.trim();

    if (!value) return;

    try {
        const parsed = new URL(value);

        if (!['http:', 'https:'].includes(parsed.protocol)) {
            throw new Error('protocol');
        }
    } catch {
        referenceError.value = 'Enter a valid http(s) image URL.';
        return;
    }

    if (form.reference_images.length >= 2) {
        referenceError.value = 'You can attach up to two reference images.';
        return;
    }

    form.reference_images.push(value);
    referenceDraft.value = '';
    referenceError.value = null;
};

const removeReference = (index) => {
    form.reference_images.splice(index, 1);
};

const estimate = computed(() => estimatePrice(props.pricing, form.size, form.quality));

const balance = ref(props.balanceMicros);

watch(
    () => props.balanceMicros,
    (value) => {
        balance.value = value;
    },
);

const affordable = computed(() => balance.value >= estimate.value.total);
const limitReached = computed(() => props.activeCount >= props.maxActive);
const charCount = computed(() => form.prompt.trim().length);
const canSubmit = computed(
    () => props.providerReady && affordable.value && !limitReached.value && charCount.value >= 3,
);

const submitBlockedReason = computed(() => {
    if (!props.providerReady) return 'The image provider is not connected yet.';
    if (limitReached.value) return `You have ${props.activeCount} renders in progress. Wait for one to finish.`;
    if (!affordable.value) return 'Not enough credits for this render. Top up to continue.';

    return null;
});

const selectedSize = computed(
    () => props.options.sizes.find((size) => size.value === form.size) ?? props.options.sizes[0],
);

const current = ref(props.active);
const { start, stop } = useGenerationPolling(4000);

watch(
    () => props.active,
    (value) => {
        current.value = value;
    },
);

const trackActive = (generation) => {
    if (!generation || generation.is_terminal) return;

    start(generation.uuid, async (payload) => {
        current.value = payload.generation;
        balance.value = payload.balance_micros;

        if (payload.generation.is_terminal) {
            stop();

            if (payload.generation.status === 'succeeded') {
                toast.success('Your image is ready. Scroll down to preview and download.', 'Render complete');
            } else {
                toast.error(payload.generation.error?.message ?? 'The render failed. Credits were returned.', 'Render failed');
            }

            router.reload({ only: ['active', 'activeCount', 'balanceMicros', 'notifications'], preserveScroll: true });
        }
    });
};

watch(
    () => current.value?.uuid,
    (uuid) => {
        if (uuid) trackActive(current.value);
    },
    { immediate: true },
);

const submit = () => {
    form.post(routes.generationsStore, {
        preserveScroll: true,
        onSuccess: () => {
            form.prompt = '';
            form.reference_images = [];
            toast.info('We will update this studio the moment your image is ready.', 'Render queued');
            router.reload({ only: ['active', 'activeCount', 'balanceMicros', 'notifications'], preserveScroll: true });
        },
        onError: () => toast.error('Check the highlighted fields and try again.', 'Could not start render'),
    });
};

const visitActive = () => {
    if (current.value) router.visit(routes.creations);
};
</script>

<template>
    <Head title="Generate Image" />

    <AppLayout container="max-w-[1440px]">
        <div class="space-y-6">
            <PageHeader
                eyebrow="Studio"
                title="Generate an image"
                description="Describe your vision, choose a canvas, and see the exact price before you render."
            >
                <template #actions>
                    <div class="flex items-center gap-2 rounded-xl border border-line bg-surface px-3 py-2">
                        <Coins class="h-4 w-4 text-brand-500" />
                        <div class="leading-tight">
                            <p class="font-alt text-[9px] font-bold uppercase tracking-[0.16em] text-subtle">Balance</p>
                            <p class="tabular text-[13px] font-semibold text-ink">{{ formatCredits(balance) }} cr</p>
                        </div>
                    </div>
                </template>
            </PageHeader>

            <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_380px]">
                <div class="space-y-4">
                    <Card padding="none">
                        <div class="flex items-center justify-between gap-3 border-b border-line/70 px-5 py-3.5">
                            <h2 class="flex items-center gap-2 text-[14px] font-semibold">
                                <Wand2 class="h-4 w-4 text-brand-500" />
                                Your prompt
                            </h2>

                            <button
                                type="button"
                                class="focus-ring flex items-center gap-1.5 rounded-lg px-2 py-1 font-alt text-[10px] font-bold uppercase tracking-[0.12em] text-brand-700 transition hover:bg-brand-500/10 dark:text-brand-300"
                                @click="useIdea"
                            >
                                <Sparkles class="h-3 w-3" />
                                Inspire me
                            </button>
                        </div>

                        <div class="p-4 sm:p-5">
                            <Textarea
                                v-model="form.prompt"
                                :rows="5"
                                :error="Boolean(form.errors.prompt)"
                                placeholder="A cinematic still of a glass whale drifting through a violet nebula, volumetric light, ultra detailed…"
                                :maxlength="1200"
                                aria-label="Image prompt"
                            />

                            <div class="mt-2 flex items-center justify-between gap-3">
                                <p v-if="form.errors.prompt" class="text-[12.5px] font-medium text-danger">
                                    {{ form.errors.prompt }}
                                </p>
                                <p v-else class="text-[11.5px] text-subtle">
                                    Be specific about subject, lighting, mood and style.
                                </p>

                                <span
                                    :class="[
                                        'tabular shrink-0 text-[11px]',
                                        charCount > 1100 ? 'text-pending' : 'text-subtle',
                                    ]"
                                >
                                    {{ charCount }}/1200
                                </span>
                            </div>
                        </div>
                    </Card>

                    <Card padding="none">
                        <div class="border-b border-line/70 px-5 py-3.5">
                            <h2 class="text-[14px] font-semibold">Canvas size</h2>
                            <p class="mt-0.5 text-[11.5px] text-subtle">Provider-supported dimensions.</p>
                        </div>

                        <div class="grid grid-cols-2 gap-2 p-4 sm:grid-cols-4 sm:p-5">
                            <button
                                v-for="size in options.sizes"
                                :key="size.value"
                                type="button"
                                :aria-pressed="form.size === size.value"
                                :class="[
                                    'group relative flex flex-col items-center gap-2 rounded-xl border p-3 text-center transition-all duration-200',
                                    form.size === size.value
                                        ? 'border-brand-500 bg-brand-500/8 ring-1 ring-brand-500/20'
                                        : 'border-line bg-surface hover:border-brand-400/50 hover:bg-elevated/60',
                                ]"
                                @click="form.size = size.value"
                            >
                                <span class="grid h-9 w-full place-items-center">
                                    <span
                                        :class="[
                                            'rounded-[3px] border-2 transition-colors',
                                            form.size === size.value
                                                ? 'border-brand-500 bg-brand-500/15'
                                                : 'border-subtle/40 group-hover:border-subtle/70',
                                        ]"
                                        :style="{
                                            width: `${Math.min(34, (size.width / size.height) * 26)}px`,
                                            height: `${Math.min(34, (size.height / size.width) * 26)}px`,
                                        }"
                                        aria-hidden="true"
                                    />
                                </span>

                                <span class="block text-[11.5px] font-semibold leading-tight text-ink">{{ size.label }}</span>
                                <span class="tabular block text-[10px] text-subtle">{{ size.value }}</span>

                                <Check
                                    v-if="form.size === size.value"
                                    class="absolute right-1.5 top-1.5 h-3 w-3 text-brand-600 dark:text-brand-300"
                                />
                            </button>
                        </div>
                    </Card>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <Card padding="none">
                            <div class="border-b border-line/70 px-5 py-3.5">
                                <h2 class="text-[14px] font-semibold">Quality</h2>
                            </div>

                            <div class="space-y-2 p-4 sm:p-5">
                                <button
                                    v-for="quality in options.qualities"
                                    :key="quality.value"
                                    type="button"
                                    :aria-pressed="form.quality === quality.value"
                                    :class="[
                                        'flex w-full items-start gap-3 rounded-xl border p-3 text-left transition-all duration-200',
                                        form.quality === quality.value
                                            ? 'border-brand-500 bg-brand-500/8'
                                            : 'border-line hover:border-brand-400/50 hover:bg-elevated/60',
                                    ]"
                                    @click="form.quality = quality.value"
                                >
                                    <span
                                        :class="[
                                            'mt-0.5 grid h-4 w-4 shrink-0 place-items-center rounded-full border-2 transition',
                                            form.quality === quality.value
                                                ? 'border-brand-500 bg-brand-500'
                                                : 'border-subtle/50',
                                        ]"
                                    >
                                        <span v-if="form.quality === quality.value" class="h-1.5 w-1.5 rounded-full bg-white" />
                                    </span>

                                    <span class="min-w-0">
                                        <span class="block text-[12.5px] font-semibold text-ink">{{ quality.label }}</span>
                                        <span class="mt-0.5 block text-[11px] leading-snug text-subtle">{{ quality.description }}</span>
                                    </span>
                                </button>
                            </div>
                        </Card>

                        <Card padding="none">
                            <div class="flex items-center justify-between border-b border-line/70 px-5 py-3.5">
                                <h2 class="text-[14px] font-semibold">Reference images</h2>
                                <Badge tone="neutral" size="sm">Optional</Badge>
                            </div>

                            <div class="space-y-3 p-4 sm:p-5">
                                <div class="flex gap-2">
                                    <Input
                                        v-model="referenceDraft"
                                        placeholder="https://…/reference.jpg"
                                        aria-label="Reference image URL"
                                        size="sm"
                                        @keydown.enter.prevent="addReference"
                                    >
                                        <template #leading><Link2 class="h-3.5 w-3.5" /></template>
                                    </Input>

                                    <Button
                                        variant="secondary"
                                        size="sm"
                                        :disabled="form.reference_images.length >= 2"
                                        @click="addReference"
                                    >
                                        Add
                                    </Button>
                                </div>

                                <p v-if="referenceError" class="text-[11.5px] font-medium text-danger">{{ referenceError }}</p>
                                <p v-else-if="form.errors.reference_images" class="text-[11.5px] font-medium text-danger">
                                    {{ form.errors.reference_images }}
                                </p>
                                <p v-else class="text-[11px] leading-relaxed text-subtle">
                                    Guide the model with up to two image URLs.
                                </p>

                                <ul v-if="form.reference_images.length" class="space-y-1.5">
                                    <li
                                        v-for="(reference, index) in form.reference_images"
                                        :key="reference"
                                        class="flex items-center gap-2 rounded-lg border border-line bg-muted/50 px-2.5 py-1.5"
                                    >
                                        <ImagePlus class="h-3.5 w-3.5 shrink-0 text-brand-500" />
                                        <span class="truncate font-alt text-[10.5px] text-subtle">{{ reference }}</span>
                                        <button
                                            type="button"
                                            class="focus-ring ml-auto grid h-5 w-5 shrink-0 place-items-center rounded text-subtle transition hover:bg-danger/10 hover:text-danger"
                                            :aria-label="`Remove reference ${index + 1}`"
                                            @click="removeReference(index)"
                                        >
                                            <X class="h-3 w-3" />
                                        </button>
                                    </li>
                                </ul>
                            </div>
                        </Card>
                    </div>
                </div>

                <div class="space-y-4 lg:sticky lg:top-[88px] lg:self-start">
                    <Card padding="none" class="overflow-hidden">
                        <div class="flex items-center justify-between border-b border-line/70 px-4 py-3">
                            <p class="font-alt text-[9.5px] font-bold uppercase tracking-[0.2em] text-subtle">Preview</p>
                            <span class="tabular text-[10.5px] text-subtle">{{ selectedSize.ratio }} · {{ form.size }}</span>
                        </div>

                        <div class="bg-muted/40 p-4">
                            <div
                                class="relative mx-auto overflow-hidden rounded-xl border border-line bg-surface"
                                :style="{
                                    aspectRatio: `${selectedSize.width} / ${selectedSize.height}`,
                                    maxHeight: '260px',
                                    maxWidth: '100%',
                                }"
                            >
                                <div class="absolute inset-0 dot-grid opacity-40" aria-hidden="true" />
                                <div class="absolute inset-0 grid place-items-center">
                                    <div class="text-center">
                                        <span class="mx-auto grid h-10 w-10 place-items-center rounded-full bg-brand-500/10 ring-1 ring-brand-500/20">
                                            <Sparkles class="h-4 w-4 text-brand-500" />
                                        </span>
                                        <p class="mt-2 font-alt text-[9.5px] font-bold uppercase tracking-[0.2em] text-subtle">
                                            {{ form.quality }} · {{ selectedSize.label }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-4 p-4">
                            <CostBreakdown
                                :estimated="{
                                    provider_micros: estimate.provider,
                                    markup_micros: estimate.markup,
                                    total_micros: estimate.total,
                                }"
                                :basis-points="estimate.markupBasisPoints / 100"
                            />

                            <div class="rounded-xl border border-line bg-muted/40 p-3">
                                <div class="flex items-center justify-between">
                                    <span class="font-alt text-[9.5px] font-bold uppercase tracking-[0.16em] text-subtle">In credits</span>
                                    <span class="tabular text-[13px] font-semibold text-ink">
                                        {{ formatCredits(estimate.total) }} cr
                                    </span>
                                </div>
                                <div class="mt-2 flex items-center justify-between">
                                    <span class="font-alt text-[9.5px] font-bold uppercase tracking-[0.16em] text-subtle">Balance after</span>
                                    <span
                                        :class="[
                                            'tabular text-[12.5px] font-semibold',
                                            affordable ? 'text-ink' : 'text-danger',
                                        ]"
                                    >
                                        {{ formatCredits(Math.max(0, balance - estimate.total)) }} cr
                                    </span>
                                </div>
                            </div>

                            <div
                                v-if="form.errors.credits"
                                class="flex items-start gap-2 rounded-lg border border-danger/25 bg-danger/8 px-2.5 py-2"
                            >
                                <Info class="mt-0.5 h-3.5 w-3.5 shrink-0 text-danger" />
                                <p class="text-[11px] leading-relaxed text-danger">{{ form.errors.credits }}</p>
                            </div>

                            <Button
                                block
                                size="lg"
                                :loading="form.processing"
                                :disabled="!canSubmit"
                                @click="submit"
                            >
                                <template #icon><Sparkles class="h-4 w-4" /></template>
                                Generate image
                            </Button>

                            <div
                                v-if="submitBlockedReason"
                                class="flex items-start gap-2 rounded-lg border border-pending/25 bg-pending/8 px-2.5 py-2"
                            >
                                <Info class="mt-0.5 h-3.5 w-3.5 shrink-0 text-pending" />
                                <p class="text-[11px] leading-relaxed text-subtle">{{ submitBlockedReason }}</p>
                            </div>

                            <Link
                                v-if="!affordable"
                                :href="routes.billing"
                                class="flex h-9 items-center justify-center gap-1.5 rounded-lg bg-brand-600 text-[12px] font-semibold text-white transition hover:bg-brand-500"
                            >
                                <Coins class="h-3.5 w-3.5" />
                                Buy credits
                            </Link>

                            <p class="text-center text-[10.5px] leading-relaxed text-subtle">
                                {{ activeCount }}/{{ maxActive }} concurrent renders ·
                                Failed renders are refunded automatically.
                            </p>
                        </div>
                    </Card>

                    <Card v-if="current" padding="none" class="overflow-hidden">
                        <div class="flex items-center justify-between gap-2 border-b border-line/70 px-4 py-3">
                            <p class="font-alt text-[9.5px] font-bold uppercase tracking-[0.2em] text-subtle">Current render</p>
                            <StatusBadge :status="current.status" size="sm" />
                        </div>

                        <SmartImage
                            :src="current.image_url"
                            :alt="current.prompt"
                            :processing="!current.is_terminal"
                            ratio="aspect-[4/3]"
                        />

                        <div class="space-y-2 p-4">
                            <p class="clamp-2 text-[12.5px] font-medium leading-snug text-ink">{{ current.prompt }}</p>
                            <p class="text-[11px] text-subtle">{{ formatRelative(current.created_at) }}</p>

                            <Button variant="secondary" block size="sm" @click="visitActive">
                                Open gallery
                                <ArrowRight class="ml-1 h-3.5 w-3.5" />
                            </Button>
                        </div>
                    </Card>

                    <p v-if="!providerReady" class="rounded-xl border border-line bg-muted/50 p-3 text-[11px] leading-relaxed text-subtle">
                        Rendering is disabled until Pixelforge credentials are added to your environment.
                    </p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
