<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { Menu, Plus, Search, X } from 'lucide-vue-next';
import Logo from '@/components/brand/Logo.vue';
import ThemeToggle from '@/components/brand/ThemeToggle.vue';
import NotificationsDropdown from './NotificationsDropdown.vue';
import UserMenu from './UserMenu.vue';
import { routes } from '@/lib/routes';

defineEmits(['open-menu']);

const page = usePage();
const query = ref('');
const searchInput = ref(null);
const focused = ref(false);

let debounceTimer = null;

const urlQuery = () => {
    if (!page.url.startsWith(routes.creations)) {
        return '';
    }

    return (new URL(page.url, window.location.origin).searchParams.get('q') ?? '').trim();
};

const syncFromUrl = () => {
    if (focused.value) {
        return;
    }

    query.value = urlQuery();
};

watch(() => page.url, syncFromUrl, { immediate: true });

const visit = () => {
    const value = query.value.trim();
    const onCreations = page.url.startsWith(routes.creations);

    router.get(
        routes.creations,
        value ? { q: value } : {},
        {
            preserveState: onCreations,
            preserveScroll: true,
            replace: true,
        },
    );
};

// Search as you type. The debounce means a burst of keystrokes produces one visit.
watch(query, () => {
    const value = query.value.trim();

    if (value === urlQuery()) {
        return;
    }

    window.clearTimeout(debounceTimer);
    debounceTimer = window.setTimeout(visit, 350);
});

const submit = () => {
    window.clearTimeout(debounceTimer);
    visit();
};

const clearSearch = () => {
    query.value = '';
    searchInput.value?.focus();
};

const cancelSearch = () => {
    window.clearTimeout(debounceTimer);
    searchInput.value?.blur();
    focused.value = false;
    syncFromUrl();
};

const isTyping = (target) => target instanceof HTMLElement
    && (target.isContentEditable || ['INPUT', 'TEXTAREA', 'SELECT'].includes(target.tagName));

const onKeydown = (event) => {
    if (event.key !== '/' || event.metaKey || event.ctrlKey || event.altKey) {
        return;
    }

    if (isTyping(event.target) || !window.matchMedia('(min-width: 1024px)').matches) {
        return;
    }

    event.preventDefault();
    searchInput.value?.focus();
};

onMounted(() => window.addEventListener('keydown', onKeydown));

onBeforeUnmount(() => {
    window.removeEventListener('keydown', onKeydown);
    window.clearTimeout(debounceTimer);
});
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
                <form class="relative w-full max-w-xs" role="search" @submit.prevent="submit">
                    <Search class="pointer-events-none absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-subtle/80" />

                    <input
                        ref="searchInput"
                        v-model="query"
                        type="text"
                        name="q"
                        placeholder="Search your creations…"
                        aria-label="Search your creations"
                        autocomplete="off"
                        class="h-9 w-full rounded-xl border border-line bg-surface pl-9 pr-14 text-[12.5px] text-ink transition placeholder:text-subtle/70 hover:border-brand-400/50 focus:border-brand-500 focus:outline-none focus:ring-4 focus:ring-brand-500/12"
                        @focus="focused = true"
                        @blur="focused = false"
                        @keydown.esc.prevent="cancelSearch"
                    />

                    <button
                        v-if="query"
                        type="button"
                        class="focus-ring absolute right-2 top-1/2 grid h-5 w-5 -translate-y-1/2 place-items-center rounded text-subtle transition hover:text-ink"
                        aria-label="Clear search"
                        @click="clearSearch"
                    >
                        <X class="h-3 w-3" />
                    </button>

                    <kbd
                        v-else
                        class="pointer-events-none absolute right-2.5 top-1/2 hidden -translate-y-1/2 rounded border border-line bg-elevated px-1.5 py-0.5 font-alt text-[9.5px] font-semibold text-subtle xl:block"
                    >
                        /
                    </kbd>
                </form>
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
