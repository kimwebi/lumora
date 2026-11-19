<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import {
    CreditCard,
    FileText,
    HardDrive,
    Home,
    Images,
    LayoutDashboard,
    Settings,
    ShieldCheck,
    Sparkles,
} from 'lucide-vue-next';
import Sidebar from '@/components/layout/Sidebar.vue';
import Topbar from '@/components/layout/Topbar.vue';
import MobileDrawer from '@/components/layout/MobileDrawer.vue';
import ToastHost from '@/components/ui/ToastHost.vue';
import { useSidebar, initSidebar } from '@/composables/useSidebar';
import { useToast } from '@/composables/useToast';
import { useSeo } from '@/composables/useSeo';
import { routes } from '@/lib/routes';

defineProps({
    container: { type: String, default: 'max-w-7xl' },
});

const page = usePage();
const { collapsed, toggle } = useSidebar();
const toast = useToast();

useSeo();

const mobileOpen = ref(false);

const user = computed(() => page.props.auth?.user);

const navItems = computed(() => {
    const items = [
        { label: 'Overview', href: routes.dashboard, icon: LayoutDashboard, match: 'dashboard' },
        { label: 'Generate Image', href: routes.generate, icon: Sparkles, match: 'generate' },
        { label: 'My Creations', href: routes.creations, icon: Images, match: 'creations' },
        { label: 'Credits & Billing', href: routes.billing, icon: CreditCard, match: 'billing' },
        { label: 'Settings', href: routes.settings, icon: Settings, match: 'settings' },
    ];

    if (user.value?.is_admin) {
        items.push({ label: 'Admin Dashboard', href: routes.admin, icon: ShieldCheck, match: 'admin' });
        items.push({ label: 'Operator Report', href: routes.adminReports, icon: FileText, match: 'admin/reports' });
        items.push({ label: 'Image Storage', href: routes.adminImages, icon: HardDrive, match: 'admin/images' });
    }

    return items;
});

onMounted(() => {
    initSidebar();
});

watch(
    () => page.props.flash,
    (flash) => {
        if (!flash) return;

        if (flash.success) toast.success(flash.success);
        if (flash.error) toast.error(flash.error);
        if (flash.info) toast.info(flash.info);
        if (flash.warning) toast.warning(flash.warning);
    },
    { immediate: true, deep: true },
);

watch(
    () => page.url,
    () => {
        mobileOpen.value = false;
    },
);
</script>

<template>
    <div class="flex min-h-dvh bg-canvas">
        <Sidebar :collapsed="collapsed" @toggle="toggle" />

        <MobileDrawer :open="mobileOpen" :items="navItems" @close="mobileOpen = false" />

        <div class="flex min-w-0 flex-1 flex-col">
            <Topbar @open-menu="mobileOpen = true" />

            <main :class="['mx-auto w-full flex-1 px-5 pb-16 pt-6 sm:px-6 lg:px-8 lg:pt-8', container]">
                <slot />
            </main>

            <footer class="border-t border-line/60 px-5 py-5 sm:px-6 lg:px-8">
                <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-2 text-[11.5px] text-subtle sm:flex-row">
                    <p>
                        © {{ new Date().getFullYear() }}
                        <span class="font-display font-semibold text-ink">Lumora</span>
                        — Your imagination, beautifully rendered.
                    </p>
                    <div class="flex items-center gap-3">
                        <Link :href="routes.home" class="flex items-center gap-1 transition hover:text-ink">
                            <Home class="h-3 w-3" />
                            Home
                        </Link>
                        <span class="text-line">·</span>
                        <Link :href="routes.guide" class="transition hover:text-ink">User Guide</Link>
                        <span class="text-line">·</span>
                        <Link href="/terms" class="transition hover:text-ink">Terms</Link>
                        <span class="text-line">·</span>
                        <Link href="/privacy" class="transition hover:text-ink">Privacy</Link>
                    </div>
                </div>
            </footer>
        </div>

        <ToastHost />
    </div>
</template>
