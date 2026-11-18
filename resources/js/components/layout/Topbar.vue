<script setup>
import { Menu, Plus, Search } from 'lucide-vue-next';
import { Link } from '@inertiajs/vue3';
import Logo from '@/components/brand/Logo.vue';
import ThemeToggle from '@/components/brand/ThemeToggle.vue';
import NotificationsDropdown from './NotificationsDropdown.vue';
import UserMenu from './UserMenu.vue';
import { routes } from '@/lib/routes';

defineEmits(['open-menu']);
</script>

<template>
    <header class="sticky top-0 z-40 border-b border-line/80 bg-canvas/85 backdrop-blur-xl">
        <div class="flex h-16 items-center gap-3 px-5 sm:px-6 lg:px-8">
            <button
                type="button"
                class="focus-ring grid h-10 w-10 place-items-center rounded-xl border border-line bg-surface text-subtle transition hover:text-ink lg:hidden"
                aria-label="Open navigation menu"
                @click="$emit('open-menu')"
            >
                <Menu class="h-[18px] w-[18px]" />
            </button>

            <div class="lg:hidden">
                <Logo size="sm" />
            </div>

            <div class="hidden min-w-0 flex-1 items-center gap-2 lg:flex">
                <Link
                    :href="routes.creations"
                    class="focus-ring group flex h-9 w-full max-w-xs items-center gap-2 rounded-xl border border-line bg-surface px-3 text-[12.5px] text-subtle/80 transition hover:border-brand-400/50 hover:text-subtle"
                >
                    <Search class="h-3.5 w-3.5" />
                    <span>Search your creations…</span>
                    <kbd class="ml-auto hidden rounded border border-line bg-elevated px-1.5 py-0.5 font-alt text-[9.5px] font-semibold text-subtle xl:block">
                        /
                    </kbd>
                </Link>
            </div>

            <div class="ml-auto flex items-center gap-2">
                <Link
                    :href="routes.generate"
                    class="focus-ring hidden h-10 items-center gap-1.5 rounded-xl bg-brand-600 px-3.5 text-[12.5px] font-semibold text-white shadow-[0_10px_26px_-14px_rgba(124,58,237,0.95)] transition hover:bg-brand-500 sm:inline-flex"
                >
                    <Plus class="h-4 w-4" />
                    New image
                </Link>

                <ThemeToggle />
                <NotificationsDropdown />
                <UserMenu />
            </div>
        </div>
    </header>
</template>
