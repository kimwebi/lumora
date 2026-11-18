<script setup>
import { computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { ChevronDown, CreditCard, LogOut, Settings, ShieldCheck, Sparkles } from 'lucide-vue-next';
import Avatar from '@/components/brand/Avatar.vue';
import Dropdown from '@/components/ui/Dropdown.vue';
import { routes } from '@/lib/routes';
import { formatCredits } from '@/lib/format';

const page = usePage();

const user = computed(() => page.props.auth?.user);

const credits = computed(() => formatCredits(user.value?.credit_balance_micros ?? 0));

const logout = () => {
    router.post(routes.logout, {}, { preserveScroll: false });
};
</script>

<template>
    <Dropdown align="end" width="w-[248px]">
        <template #trigger>
            <button
                type="button"
                class="focus-ring flex items-center gap-2 rounded-xl border border-line bg-surface p-1 pr-2 text-left transition hover:border-brand-400/50"
                aria-label="Account menu"
            >
                <Avatar :name="user?.name" :initials="user?.initials" size="sm" />
                <span class="hidden min-w-0 sm:block">
                    <span class="block max-w-[9rem] truncate text-[12.5px] font-semibold leading-tight text-ink">
                        {{ user?.name }}
                    </span>
                    <span class="block font-alt text-[10px] uppercase tracking-wide text-subtle">
                        {{ credits }} credits
                    </span>
                </span>
                <ChevronDown class="hidden h-3.5 w-3.5 text-subtle sm:block" />
            </button>
        </template>

        <div class="px-2.5 py-2">
            <p class="truncate text-[13px] font-semibold text-ink">{{ user?.name }}</p>
            <p class="truncate text-[11.5px] text-subtle">{{ user?.email }}</p>
        </div>

        <div class="mx-1 mb-1 flex items-center justify-between rounded-lg bg-brand-500/8 px-2.5 py-2">
            <span class="flex items-center gap-1.5 font-alt text-[10px] font-bold uppercase tracking-[0.12em] text-brand-700 dark:text-brand-200">
                <Sparkles class="h-3 w-3" />
                Balance
            </span>
            <span class="tabular text-[12.5px] font-semibold text-ink">{{ credits }} cr</span>
        </div>

        <div class="border-t border-line/70 pt-1">
            <Link
                :href="routes.dashboard"
                class="focus-ring flex items-center gap-2.5 rounded-lg px-2.5 py-2 text-[13px] font-medium text-subtle transition hover:bg-elevated hover:text-ink"
            >
                <Sparkles class="h-4 w-4" />
                Overview
            </Link>
            <Link
                :href="routes.billing"
                class="focus-ring flex items-center gap-2.5 rounded-lg px-2.5 py-2 text-[13px] font-medium text-subtle transition hover:bg-elevated hover:text-ink"
            >
                <CreditCard class="h-4 w-4" />
                Credits & Billing
            </Link>
            <Link
                :href="routes.settings"
                class="focus-ring flex items-center gap-2.5 rounded-lg px-2.5 py-2 text-[13px] font-medium text-subtle transition hover:bg-elevated hover:text-ink"
            >
                <Settings class="h-4 w-4" />
                Settings
            </Link>
            <Link
                v-if="user?.is_admin"
                :href="routes.admin"
                class="focus-ring flex items-center gap-2.5 rounded-lg px-2.5 py-2 text-[13px] font-medium text-subtle transition hover:bg-elevated hover:text-ink"
            >
                <ShieldCheck class="h-4 w-4" />
                Admin Dashboard
            </Link>
        </div>

        <div class="mt-1 border-t border-line/70 pt-1">
            <button
                type="button"
                class="focus-ring flex w-full items-center gap-2.5 rounded-lg px-2.5 py-2 text-[13px] font-medium text-danger transition hover:bg-danger/10"
                @click="logout"
            >
                <LogOut class="h-4 w-4" />
                Sign out
            </button>
        </div>
    </Dropdown>
</template>
