<script setup>
import { computed, onBeforeUnmount, onMounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { X, LogOut, Sparkles } from 'lucide-vue-next';
import Logo from '@/components/brand/Logo.vue';
import ThemeToggle from '@/components/brand/ThemeToggle.vue';
import Avatar from '@/components/brand/Avatar.vue';
import { routes } from '@/lib/routes';
import { formatCredits } from '@/lib/format';

const props = defineProps({
    open: { type: Boolean, default: false },
    items: { type: Array, required: true },
});

const emit = defineEmits(['close']);

const page = usePage();
const user = computed(() => page.props.auth?.user);

const strip = (value) => value.replace(/^\/+/, '').split('/')[0];

const isActive = (item) => strip(page.url) === strip(item.match);

const onKeydown = (event) => {
    if (event.key === 'Escape') emit('close');
};

onMounted(() => document.addEventListener('keydown', onKeydown));
onBeforeUnmount(() => document.removeEventListener('keydown', onKeydown));
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="open" class="fixed inset-0 z-[70] lg:hidden">
                <div class="absolute inset-0 bg-midnight/60 backdrop-blur-sm" aria-hidden="true" @click="emit('close')" />

                <Transition
                    appear
                    enter-active-class="transition duration-300 ease-out"
                    enter-from-class="-translate-x-full"
                    enter-to-class="translate-x-0"
                    leave-active-class="transition duration-200 ease-in"
                    leave-from-class="translate-x-0"
                    leave-to-class="-translate-x-full"
                >
                    <aside
                        v-if="open"
                        class="relative flex h-full w-[85%] max-w-[320px] flex-col border-r border-line bg-surface shadow-2xl"
                        aria-label="Mobile navigation"
                    >
                        <div class="flex h-16 items-center justify-between border-b border-line/70 px-4">
                            <Logo size="md" show-tagline />
                            <button
                                type="button"
                                class="focus-ring grid h-9 w-9 place-items-center rounded-lg text-subtle transition hover:bg-elevated hover:text-ink"
                                aria-label="Close navigation menu"
                                @click="emit('close')"
                            >
                                <X class="h-4 w-4" />
                            </button>
                        </div>

                        <div class="border-b border-line/70 px-4 py-4">
                            <div class="flex items-center gap-2.5">
                                <Avatar :name="user?.name" :initials="user?.initials" size="md" />
                                <div class="min-w-0">
                                    <p class="truncate text-[13.5px] font-semibold text-ink">{{ user?.name }}</p>
                                    <p class="truncate text-[11.5px] text-subtle">{{ user?.email }}</p>
                                </div>
                            </div>

                            <Link
                                :href="routes.billing"
                                class="mt-3 flex items-center justify-between rounded-xl border border-brand-500/20 bg-brand-500/8 px-3 py-2.5"
                                @click="emit('close')"
                            >
                                <span class="flex items-center gap-1.5 font-alt text-[10px] font-bold uppercase tracking-[0.14em] text-brand-700 dark:text-brand-200">
                                    <Sparkles class="h-3 w-3" />
                                    Credit balance
                                </span>
                                <span class="tabular text-[13px] font-semibold text-ink">
                                    {{ formatCredits(user?.credit_balance_micros ?? 0) }} credits
                                </span>
                            </Link>
                        </div>

                        <nav class="flex-1 space-y-1 overflow-y-auto p-3">
                            <Link
                                v-for="item in items"
                                :key="item.href"
                                :href="item.href"
                                :class="[
                                    'flex items-center gap-3 rounded-xl px-3 py-2.5 text-[13.5px] font-medium transition',
                                    isActive(item)
                                        ? 'bg-brand-500/10 text-brand-700 ring-1 ring-inset ring-brand-500/20 dark:text-brand-200'
                                        : 'text-subtle hover:bg-elevated hover:text-ink',
                                ]"
                                @click="emit('close')"
                            >
                                <component :is="item.icon" class="h-[18px] w-[18px]" />
                                {{ item.label }}
                            </Link>
                        </nav>

                        <div class="flex items-center justify-between gap-2 border-t border-line/70 p-3">
                            <ThemeToggle />

                            <Link
                                :href="routes.logout"
                                method="post"
                                as="button"
                                type="button"
                                class="focus-ring flex h-10 items-center gap-2 rounded-xl px-3 text-[12.5px] font-semibold text-danger transition hover:bg-danger/10"
                            >
                                <LogOut class="h-4 w-4" />
                                Sign out
                            </Link>
                        </div>
                    </aside>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
