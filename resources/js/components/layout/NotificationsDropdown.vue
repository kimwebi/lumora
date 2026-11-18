<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { Bell, CheckCircle2, Images, Sparkles } from 'lucide-vue-next';
import Dropdown from '@/components/ui/Dropdown.vue';
import { routes } from '@/lib/routes';
import { formatRelative } from '@/lib/format';

const page = usePage();

const notifications = computed(() => page.props.notifications ?? []);
const hasNotifications = computed(() => notifications.value.length > 0);
</script>

<template>
    <Dropdown align="end" width="w-[calc(100vw-5.5rem)] max-w-[320px]">
        <template #trigger>
            <button
                type="button"
                class="focus-ring relative grid h-10 w-10 place-items-center rounded-xl border border-line bg-surface text-subtle transition hover:border-brand-400/50 hover:text-ink"
                :aria-label="hasNotifications ? `${notifications.length} new notifications` : 'Notifications'"
            >
                <Bell class="h-[18px] w-[18px]" />
                <span
                    v-if="hasNotifications"
                    class="absolute right-2 top-2 h-1.5 w-1.5 rounded-full bg-brand-500 ring-2 ring-surface"
                    aria-hidden="true"
                />
            </button>
        </template>

        <div class="px-2.5 py-2">
            <p class="font-alt text-[9.5px] font-bold uppercase tracking-[0.2em] text-subtle">Recent activity</p>
        </div>

        <div v-if="!hasNotifications" class="px-2.5 pb-3 pt-1">
            <div class="rounded-xl border border-dashed border-line px-3 py-6 text-center">
                <Bell class="mx-auto h-5 w-5 text-subtle/60" />
                <p class="mt-2 text-[12.5px] font-medium text-ink">You're all caught up</p>
                <p class="mt-1 text-[11.5px] leading-relaxed text-subtle">
                    Render updates from the last 24 hours appear here.
                </p>
            </div>
        </div>

        <ul v-else class="max-h-[320px] space-y-0.5 overflow-y-auto">
            <li v-for="notification in notifications" :key="notification.id">
                <Link
                    :href="routes.creations"
                    class="focus-ring flex items-start gap-2.5 rounded-xl px-2.5 py-2.5 transition hover:bg-elevated"
                >
                    <span
                        :class="[
                            'mt-0.5 grid h-7 w-7 shrink-0 place-items-center rounded-lg ring-1 ring-inset',
                            notification.status === 'succeeded'
                                ? 'bg-success/10 text-emerald-600 ring-success/20 dark:text-emerald-300'
                                : 'bg-danger/10 text-danger ring-danger/20',
                        ]"
                    >
                        <Sparkles v-if="notification.image_url" class="h-3.5 w-3.5" />
                        <CheckCircle2 v-else class="h-3.5 w-3.5" />
                    </span>

                    <span class="min-w-0 flex-1">
                        <span class="clamp-2 block text-[12.5px] font-medium leading-snug text-ink">
                            {{ notification.prompt }}
                        </span>
                        <span class="mt-0.5 flex items-center gap-1.5 text-[11px] text-subtle">
                            {{ notification.status === 'succeeded' ? 'Render complete' : 'Render failed' }}
                            <span class="text-line">·</span>
                            {{ formatRelative(notification.settled_at) }}
                        </span>
                    </span>
                </Link>
            </li>
        </ul>

        <div class="mt-1 border-t border-line/70 p-1">
            <Link
                :href="routes.creations"
                class="focus-ring flex items-center justify-center gap-1.5 rounded-lg py-2 text-[12px] font-semibold text-brand-700 transition hover:bg-brand-500/10 dark:text-brand-200"
            >
                <Images class="h-3.5 w-3.5" />
                View all creations
            </Link>
        </div>
    </Dropdown>
</template>
