<script setup>
import { computed, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    AlertTriangle,
    Check,
    Copy,
    FileText,
    HardDrive,
    ImageOff,
    RefreshCw,
    ShieldCheck,
    Upload,
} from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import PageHeader from '@/components/layout/PageHeader.vue';
import Card from '@/components/ui/Card.vue';
import Badge from '@/components/ui/Badge.vue';
import Button from '@/components/ui/Button.vue';
import StatCard from '@/components/ui/StatCard.vue';
import { formatDateTime, formatNumber } from '@/lib/format';
import { adminImageRefetchUrl, routes } from '@/lib/routes';
import { useToast } from '@/composables/useToast';

const props = defineProps({
    storage: { type: Object, required: true },
});

const toast = useToast();

const fileInput = ref(null);
const refetching = ref(null);
const copied = ref(null);

const form = useForm({ images: [] });

const missing = computed(() => props.storage.missing ?? []);
const missingCount = computed(() => Number(props.storage.missing_count ?? 0));
const storedCount = computed(() => Number(props.storage.stored_count ?? 0));

const progress = computed(() =>
    form.progress ? Math.round(form.progress.percentage) : null,
);

const onFiles = (event) => {
    form.images = Array.from(event.target.files ?? []);
    form.clearErrors();
};

const clearFiles = () => {
    form.images = [];
    form.clearErrors();

    if (fileInput.value) {
        fileInput.value.value = '';
    }
};

const submit = () => {
    if (!form.images.length || form.processing) return;

    form.post(routes.adminImagesStore, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            clearFiles();
        },
    });
};

const refetch = (row) => {
    if (refetching.value) return;

    router.post(adminImageRefetchUrl(row.uuid), {}, {
        preserveScroll: true,
        onStart: () => {
            refetching.value = row.uuid;
        },
        onFinish: () => {
            refetching.value = null;
        },
        onError: () => toast.error('The image could not be re-downloaded.', 'Restore failed'),
    });
};

const copyFilename = async (filename) => {
    try {
        await navigator.clipboard.writeText(filename);
        copied.value = filename;
        toast.success('Filename copied', 'Clipboard');

        window.setTimeout(() => {
            if (copied.value === filename) copied.value = null;
        }, 2000);
    } catch {
        toast.error('Your browser blocked clipboard access.', 'Copy failed');
    }
};
</script>

<template>
    <Head title="Image Storage" />

    <AppLayout container="max-w-[1500px]">
        <div class="space-y-6">
            <PageHeader
                eyebrow="Administration"
                title="Image storage"
                description="Check that every completed render still has its image file, and restore files lost when a host rebuilt the container."
            >
                <template #actions>
                    <Button :href="routes.adminReports" variant="secondary" size="md">
                        <template #icon><FileText class="h-4 w-4" /></template>
                        Operator report
                    </Button>
                </template>
            </PageHeader>

            <div class="grid gap-3 sm:grid-cols-3">
                <StatCard
                    label="Images available"
                    :value="formatNumber(storedCount)"
                    hint="Completed renders with a readable image"
                    :icon="HardDrive"
                    tone="success"
                />
                <StatCard
                    label="Missing images"
                    :value="formatNumber(missingCount)"
                    :hint="missingCount ? 'Restore them below' : 'Everything is in place'"
                    :icon="missingCount ? AlertTriangle : Check"
                    :tone="missingCount ? 'pending' : 'success'"
                />
                <StatCard
                    :label="storage.mode === 'database' ? 'Storage driver' : 'Storage disk'"
                    :value="storage.mode === 'database' ? 'Database' : storage.disk"
                    :hint="storage.mode === 'database' ? 'Survives redeploys' : 'LUMORA_IMAGE_DISK'"
                    :icon="ShieldCheck"
                    tone="info"
                />
            </div>

            <Card v-if="missingCount" tone="quiet" padding="md">
                <div class="flex items-start gap-3">
                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-pending/15 text-amber-600 dark:text-amber-300">
                        <AlertTriangle class="h-4 w-4" />
                    </span>
                    <div class="min-w-0 space-y-2">
                        <p class="text-[13.5px] font-semibold text-ink">
                            Why are these images missing?
                        </p>
                        <p v-if="storage.mode === 'database'" class="text-[12.5px] leading-relaxed text-subtle">
                            New renders are now kept in the database, so they survive redeploys and restarts. The
                            renders listed below finished before that change, or were stored on a filesystem that
                            has since been rebuilt. Upload the files you downloaded and they will be stored durably.
                        </p>
                        <p v-else class="text-[12.5px] leading-relaxed text-subtle">
                            Generation images are written to the app's storage disk. Container hosts such as Render
                            replace that filesystem on every deploy unless a persistent disk is mounted, while the
                            database keeps the generation rows. Set
                            <code class="rounded bg-elevated px-1 py-0.5 font-mono text-[11px]">LUMORA_IMAGE_STORE=database</code>
                            to keep images in the database, or mount a disk at
                            <code class="rounded bg-elevated px-1 py-0.5 font-mono text-[11px]">/var/www/html/storage/app/public</code>.
                        </p>
                        <p class="text-[12px] leading-relaxed text-subtle">
                            You can also verify from the server:
                            <code class="rounded bg-elevated px-1 py-0.5 font-mono text-[11px]">php artisan lumora:verify-images</code>
                            and restore a folder of recovered files with
                            <code class="rounded bg-elevated px-1 py-0.5 font-mono text-[11px]">php artisan lumora:restore-images &lt;directory&gt;</code>.
                        </p>
                    </div>
                </div>
            </Card>

            <Card padding="none">
                <div class="border-b border-line/70 px-5 py-4">
                    <h2 class="text-[15px] font-semibold">Restore from file</h2>
                    <p class="mt-0.5 text-[12px] text-subtle">
                        Upload the PNG, JPG or WEBP files you downloaded earlier. Each filename must contain the
                        generation UUID, for example
                        <code class="rounded bg-elevated px-1 py-0.5 font-mono text-[11px]">258a328b-9697-452e-a08b-33b99a9f5ce4.png</code>.
                    </p>
                </div>

                <form class="space-y-4 p-5" @submit.prevent="submit">
                    <input
                        id="image-files"
                        ref="fileInput"
                        type="file"
                        multiple
                        accept="image/png,image/jpeg,image/webp,image/gif"
                        class="sr-only"
                        @change="onFiles"
                    />

                    <label
                        for="image-files"
                        class="focus-ring flex cursor-pointer flex-col items-center justify-center gap-3 rounded-2xl border border-dashed border-line bg-muted/30 px-5 py-9 text-center transition hover:border-brand-400/70 hover:bg-brand-500/5"
                    >
                        <span class="grid h-11 w-11 place-items-center rounded-xl bg-brand-500/10 text-brand-600 ring-1 ring-brand-500/20 dark:text-brand-300">
                            <Upload class="h-5 w-5" />
                        </span>
                        <span class="text-[13.5px] font-semibold text-ink">Choose recovered images</span>
                        <span class="max-w-md text-[12px] leading-relaxed text-subtle">
                            Up to 50 files at a time, 15 MB each. Files that do not match a completed render are
                            reported and left untouched.
                        </span>
                    </label>

                    <div v-if="form.images.length" class="flex flex-wrap items-center gap-2">
                        <Badge tone="brand">{{ form.images.length }} selected</Badge>
                        <span
                            v-for="file in form.images.slice(0, 4)"
                            :key="file.name"
                            class="max-w-[20rem] truncate rounded-lg bg-elevated px-2 py-1 font-mono text-[11px] text-subtle"
                        >
                            {{ file.name }}
                        </span>
                        <span v-if="form.images.length > 4" class="text-[11.5px] text-subtle">
                            +{{ form.images.length - 4 }} more
                        </span>
                    </div>

                    <p v-if="form.errors.images" class="text-[12px] text-danger">{{ form.errors.images }}</p>
                    <p v-if="form.errors['images.0']" class="text-[12px] text-danger">{{ form.errors['images.0'] }}</p>

                    <div v-if="progress !== null" class="space-y-1.5">
                        <div class="h-1.5 overflow-hidden rounded-full bg-elevated">
                            <div class="h-full rounded-full bg-brand-500 transition-[width]" :style="{ width: `${progress}%` }" />
                        </div>
                        <p class="text-[11.5px] text-subtle">Uploading {{ progress }}%</p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <Button
                            type="submit"
                            :loading="form.processing"
                            :disabled="form.images.length === 0"
                        >
                            <template #icon><Upload class="h-4 w-4" /></template>
                            Restore {{ form.images.length || '' }} image{{ form.images.length === 1 ? '' : 's' }}
                        </Button>
                        <Button
                            v-if="form.images.length"
                            variant="ghost"
                            @click="clearFiles"
                        >
                            Clear
                        </Button>
                    </div>
                </form>
            </Card>

            <Card padding="none">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-line/70 px-5 py-4">
                    <div>
                        <h2 class="text-[15px] font-semibold">Missing images</h2>
                        <p class="mt-0.5 text-[12px] text-subtle">
                            Completed renders whose image is no longer available in {{ storage.mode === 'database' ? 'the database' : `the ${storage.disk} disk` }}.
                        </p>
                    </div>
                    <Badge :tone="missingCount ? 'pending' : 'success'">
                        {{ formatNumber(missingCount) }} missing
                    </Badge>
                </div>

                <div v-if="missing.length" class="divide-y divide-line/70">
                    <div
                        v-for="row in missing"
                        :key="row.uuid"
                        class="grid gap-3 px-5 py-4 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center"
                    >
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <ImageOff class="h-3.5 w-3.5 shrink-0 text-pending" />
                                <code class="max-w-full truncate rounded bg-elevated px-1.5 py-0.5 font-mono text-[11px] text-subtle">
                                    {{ row.filename }}
                                </code>
                                <button
                                    type="button"
                                    class="focus-ring grid h-7 w-7 shrink-0 place-items-center rounded-md text-subtle transition hover:bg-elevated hover:text-ink"
                                    :aria-label="`Copy filename ${row.filename}`"
                                    @click="copyFilename(row.filename)"
                                >
                                    <Check v-if="copied === row.filename" class="h-3.5 w-3.5 text-success" />
                                    <Copy v-else class="h-3.5 w-3.5" />
                                </button>
                            </div>

                            <p class="mt-1.5 line-clamp-2 text-[13px] font-medium leading-snug text-ink">
                                {{ row.prompt }}
                            </p>

                            <p class="mt-1 text-[11.5px] text-subtle">
                                {{ row.user?.email ?? 'Unknown creator' }}
                                <span class="text-line">·</span> {{ row.size }}
                                <span class="text-line">·</span> {{ formatDateTime(row.created_at) }}
                            </p>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <Button
                                v-if="row.has_provider_url"
                                variant="secondary"
                                size="sm"
                                :loading="refetching === row.uuid"
                                @click="refetch(row)"
                            >
                                <template #icon><RefreshCw class="h-3.5 w-3.5" /></template>
                                Re-download
                            </Button>
                            <span v-else class="text-[11.5px] text-subtle">Upload the file to restore</span>
                        </div>
                    </div>
                </div>

                <div v-else class="flex flex-col items-center gap-2 px-5 py-12 text-center">
                    <span class="grid h-11 w-11 place-items-center rounded-xl bg-success/10 text-emerald-600 ring-1 ring-success/20 dark:text-emerald-300">
                        <Check class="h-5 w-5" />
                    </span>
                    <p class="text-[14px] font-semibold text-ink">Every image is where it should be</p>
                    <p class="max-w-md text-[12.5px] leading-relaxed text-subtle">
                        No completed render is missing its image in {{ storage.mode === 'database' ? 'the database' : `the ${storage.disk} disk` }}.
                    </p>
                </div>

                <p
                    v-if="missingCount > missing.length"
                    class="border-t border-line/70 px-5 py-3 text-[11.5px] text-subtle"
                >
                    Showing the {{ missing.length }} most recent of {{ formatNumber(missingCount) }} missing images.
                    Use <code class="font-mono">php artisan lumora:restore-images</code> for a full bulk restore.
                </p>
            </Card>
        </div>
    </AppLayout>
</template>
