<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import {
    CreditCard,
    FileText,
    Images,
    LayoutDashboard,
    PanelLeftClose,
    PanelLeftOpen,
    Settings,
    ShieldCheck,
    Sparkles,
} from 'lucide-vue-next';
import Logo from '@/components/brand/Logo.vue';
import { routes } from '@/lib/routes';
import { formatCredits } from '@/lib/format';

const props = defineProps({
    collapsed: { type: Boolean, default: false },
});

defineEmits(['toggle']);

const page = usePage();

const user = computed(() => page.props.auth?.user);

const items = computed(() => {
    const nav = [
        { label: 'Overview', href: routes.dashboard, icon: LayoutDashboard, match: 'dashboard' },
        { label: 'Generate Image', href: routes.generate, icon: Sparkles, match: 'generate' },
        { label: 'My Creations', href: routes.creations, icon: Images, match: 'creations' },
        { label: 'Credits & Billing', href: routes.billing, icon: CreditCard, match: 'billing' },
        { label: 'Settings', href: routes.settings, icon: Settings, match: 'settings' },
    ];

    if (user.value?.is_admin) {
        nav.push({ label: 'Admin Dashboard', href: routes.admin, icon: ShieldCheck, match: 'admin' });
        nav.push({ label: 'Operator Report', href: routes.adminReports, icon: FileText, match: 'admin/reports' });
    }

    return nav;
});

const currentPath = computed(() => {
    const [path] = page.url.split('?');
    return path.length > 1 ? path.replace(/\/+$/, '') : path;
});

const activeHref = computed(() => {
    return items.value
        .filter((item) => currentPath.value === item.href || currentPath.value.startsWith(`${item.href}/`))
        .sort((a, b) => b.href.length - a.href.length)[0]?.href ?? null;
});

const isActive = (item) => activeHref.value === item.href;

const credits = computed(() => formatCredits(user.value?.credit_balance_micros ?? 0));
</script>

<template>
    <aside
        :class="[
            'sticky top-0 hidden h-dvh shrink-0 flex-col border-r border-line bg-surface/80 backdrop-blur-xl transition-[width] duration-300 ease-out lg:flex',
            collapsed ? 'w-[76px]' : 'w-[264px]',
        ]"
        aria-label="Primary navigation"
    >
        <div :class="['flex h-16 items-center border-b border-line/70', collapsed ? 'justify-center px-3' : 'justify-between px-5']">
            <Logo v-if="!collapsed" size="md" />
            <Logo v-else size="md" icon-only />

            <button
                v-if="!collapsed"
                type="button"
                class="focus-ring grid h-8 w-8 place-items-center rounded-lg text-subtle transition hover:bg-elevated hover:text-ink"
                aria-label="Collapse sidebar"
                @click="$emit('toggle')"
            >
                <PanelLeftClose class="h-4 w-4" />
            </button>
        </div>

        <nav class="flex-1 space-y-1 overflow-y-auto p-3">
            <p
                v-if="!collapsed"
                class="px-3 pb-1.5 pt-2 font-alt text-[9.5px] font-bold uppercase tracking-[0.22em] text-subtle/80"
            >
                Studio
            </p>

            <Link
                v-for="item in items"
                :key="item.href"
                :href="item.href"
                :aria-current="isActive(item) ? 'page' : undefined"
                :title="collapsed ? item.label : undefined"
                :class="[
                    'focus-ring group relative flex items-center gap-3 rounded-xl py-2.5 text-[13.5px] font-medium transition-all duration-200',
                    collapsed ? 'justify-center px-0' : 'px-3',
                    isActive(item)
                        ? 'bg-brand-500/10 text-brand-700 ring-1 ring-inset ring-brand-500/20 dark:text-brand-200'
                        : 'text-subtle hover:bg-elevated hover:text-ink',
                ]"
            >
                <span
                    v-if="isActive(item)"
                    class="absolute left-0 top-1/2 h-5 w-[3px] -translate-y-1/2 rounded-r-full bg-brand-500"
                    aria-hidden="true"
                />

                <component
                    :is="item.icon"
                    :class="['h-[18px] w-[18px] shrink-0', isActive(item) ? 'text-brand-600 dark:text-brand-300' : '']"
                />

                <span v-if="!collapsed" class="truncate">{{ item.label }}</span>

                <span
                    v-if="!collapsed && (item.match === 'admin' || item.match === 'admin/reports')"
                    class="ml-auto rounded-full bg-brand-500/12 px-1.5 py-0.5 font-alt text-[9px] font-bold uppercase tracking-wider text-brand-700 dark:text-brand-200"
                >
                    Admin
                </span>
            </Link>
        </nav>

        <div class="space-y-3 border-t border-line/70 p-3">
            <Link
                v-if="collapsed"
                :href="routes.billing"
                class="focus-ring grid place-items-center rounded-xl border border-line bg-elevated py-2.5 text-[11px] font-semibold text-brand-700 transition hover:border-brand-400/50 dark:text-brand-200"
                :title="`${credits} credits available`"
            >
                <Sparkles class="mb-0.5 h-3.5 w-3.5" />
                {{ credits }}
            </Link>

            <div v-else class="rounded-xl border border-line bg-gradient-to-br from-brand-500/10 to-transparent p-3.5">
                <div class="flex items-center justify-between gap-2">
                    <p class="font-alt text-[9.5px] font-bold uppercase tracking-[0.18em] text-subtle">Credits</p>
                    <Sparkles class="h-3.5 w-3.5 text-brand-500" />
                </div>
                <p class="tabular mt-1.5 text-xl font-semibold leading-none text-ink">
                    {{ credits }}
                    <span class="font-alt text-[11px] font-medium uppercase tracking-wide text-subtle">credits</span>
                </p>
                <Link
                    :href="routes.billing"
                    class="focus-ring mt-2.5 flex h-8 items-center justify-center rounded-lg bg-brand-600 text-[12px] font-semibold text-white transition hover:bg-brand-500"
                >
                    Top up credits
                </Link>
            </div>

            <button
                v-if="collapsed"
                type="button"
                class="focus-ring grid h-9 w-full place-items-center rounded-lg text-subtle transition hover:bg-elevated hover:text-ink"
                aria-label="Expand sidebar"
                @click="$emit('toggle')"
            >
                <PanelLeftOpen class="h-4 w-4" />
            </button>
        </div>
    </aside>
</template>
