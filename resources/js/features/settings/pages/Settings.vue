<script setup>
import { computed, ref } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import {
    Check,
    KeyRound,
    Monitor,
    Moon,
    Palette,
    ShieldCheck,
    Sun,
    Trash2,
    TriangleAlert,
    UserRound,
} from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import PageHeader from '@/components/layout/PageHeader.vue';
import Card from '@/components/ui/Card.vue';
import Button from '@/components/ui/Button.vue';
import Badge from '@/components/ui/Badge.vue';
import Modal from '@/components/ui/Modal.vue';
import Input from '@/components/ui/Input.vue';
import FormField from '@/components/ui/FormField.vue';
import Avatar from '@/components/brand/Avatar.vue';
import { formatDate, formatNumber } from '@/lib/format';
import { routes } from '@/lib/routes';
import { useTheme } from '@/composables/useTheme';

const props = defineProps({
    profile: { type: Object, required: true },
    stats: { type: Object, default: () => ({}) },
});

const page = usePage();
const { preference, setTheme } = useTheme();

const tab = ref('profile');

const tabs = [
    { key: 'profile', label: 'Profile', icon: UserRound },
    { key: 'security', label: 'Security', icon: ShieldCheck },
    { key: 'appearance', label: 'Appearance', icon: Palette },
    { key: 'account', label: 'Account', icon: TriangleAlert },
];

const profileForm = useForm({
    name: props.profile.name,
    email: props.profile.email,
});

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const deleteForm = useForm({
    password: '',
});

const deleteOpen = ref(false);

const saveProfile = () => {
    profileForm.put(routes.settingsProfile, {
        preserveScroll: true,
        onSuccess: () => profileForm.reset('name', 'email'),
    });
};

const savePassword = () => {
    passwordForm.put(routes.settingsPassword, {
        preserveScroll: true,
        onSuccess: () => passwordForm.reset(),
        onError: () => passwordForm.reset('current_password', 'password', 'password_confirmation'),
    });
};

const destroyAccount = () => {
    deleteForm.delete(routes.settingsAccount, {
        preserveScroll: true,
        onFinish: () => {
            deleteForm.reset('password');
            deleteOpen.value = false;
        },
    });
};

const themes = [
    { key: 'light', label: 'Light', icon: Sun, description: 'Bright canvas with soft shadows.' },
    { key: 'dark', label: 'Dark', icon: Moon, description: 'Midnight studio, easier on the eyes.' },
    { key: 'system', label: 'System', icon: Monitor, description: 'Follows your device preference.' },
];

const isAdmin = computed(() => page.props.auth?.user?.is_admin);
</script>

<template>
    <Head title="Settings" />

    <AppLayout>
        <div class="space-y-6">
            <PageHeader
                eyebrow="Account"
                title="Settings"
                description="Manage your profile, security and studio appearance."
            />

            <div class="grid gap-5 lg:grid-cols-[220px_minmax(0,1fr)]">
                <nav class="grid grid-cols-2 gap-1 rounded-xl border border-line bg-surface p-1 sm:grid-cols-4 lg:grid-cols-1" aria-label="Settings sections">
                    <button
                        v-for="item in tabs"
                        :key="item.key"
                        type="button"
                        :aria-current="tab === item.key ? 'page' : undefined"
                        :class="[
                            'focus-ring flex min-w-0 items-center gap-2 rounded-lg px-3 py-2 text-[12.5px] font-medium transition lg:w-full',
                            tab === item.key
                                ? 'bg-brand-500/12 text-brand-700 ring-1 ring-inset ring-brand-500/20 dark:text-brand-200'
                                : 'text-subtle hover:bg-elevated hover:text-ink',
                            item.key === 'account' && tab !== 'account' ? 'text-danger/80 hover:text-danger' : '',
                        ]"
                        @click="tab = item.key"
                    >
                        <component :is="item.icon" class="h-4 w-4" />
                        {{ item.label }}
                    </button>
                </nav>

                <div class="min-w-0 space-y-5">
                    <Card v-if="tab === 'profile'" padding="none">
                        <div class="border-b border-line/70 px-5 py-4">
                            <h2 class="text-[15px] font-semibold">Profile</h2>
                            <p class="mt-0.5 text-[12px] text-subtle">How you appear across your Lumora studio.</p>
                        </div>

                        <div class="space-y-5 p-5">
                            <div class="flex items-center gap-4">
                                <Avatar :name="profile.name" :initials="profile.initials" size="lg" />
                                <div>
                                    <p class="text-[14px] font-semibold text-ink">{{ profile.name }}</p>
                                    <p class="text-[12px] text-subtle">
                                        {{ profile.email }}
                                    </p>
                                    <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
                                        <Badge v-if="isAdmin" tone="brand" size="sm">Administrator</Badge>
                                        <Badge tone="neutral" size="sm">
                                            Member since {{ formatDate(stats.member_since) }}
                                        </Badge>
                                    </div>
                                </div>
                            </div>

                            <form class="grid gap-4 sm:grid-cols-2" @submit.prevent="saveProfile">
                                <FormField label="Full name" for="settings-name" :error="profileForm.errors.name">
                                    <template #default="{ id }">
                                        <Input
                                            :id="id"
                                            v-model="profileForm.name"
                                            autocomplete="name"
                                            :error="Boolean(profileForm.errors.name)"
                                            required
                                        />
                                    </template>
                                </FormField>

                                <FormField label="Email address" for="settings-email" :error="profileForm.errors.email">
                                    <template #default="{ id }">
                                        <Input
                                            :id="id"
                                            v-model="profileForm.email"
                                            type="email"
                                            autocomplete="email"
                                            :error="Boolean(profileForm.errors.email)"
                                            required
                                        />
                                    </template>
                                </FormField>

                                <div class="sm:col-span-2 flex items-center justify-between gap-3 border-t border-line/70 pt-4">
                                    <p class="text-[11.5px] text-subtle">
                                        {{ formatNumber(stats.generations ?? 0) }} creations in your gallery.
                                    </p>
                                    <Button type="submit" size="sm" :loading="profileForm.processing">
                                        Save changes
                                    </Button>
                                </div>
                            </form>
                        </div>
                    </Card>

                    <Card v-if="tab === 'security'" padding="none">
                        <div class="border-b border-line/70 px-5 py-4">
                            <h2 class="text-[15px] font-semibold">Password</h2>
                            <p class="mt-0.5 text-[12px] text-subtle">Use a long, unique password you don't reuse elsewhere.</p>
                        </div>

                        <form class="space-y-4 p-5" @submit.prevent="savePassword">
                            <FormField label="Current password" for="settings-current-password" :error="passwordForm.errors.current_password">
                                <template #default="{ id }">
                                    <Input
                                        :id="id"
                                        v-model="passwordForm.current_password"
                                        type="password"
                                        autocomplete="current-password"
                                        :error="Boolean(passwordForm.errors.current_password)"
                                        required
                                    >
                                        <template #leading><KeyRound class="h-4 w-4" /></template>
                                    </Input>
                                </template>
                            </FormField>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <FormField label="New password" for="settings-new-password" :error="passwordForm.errors.password">
                                    <template #default="{ id }">
                                        <Input
                                            :id="id"
                                            v-model="passwordForm.password"
                                            type="password"
                                            autocomplete="new-password"
                                            placeholder="At least 8 characters"
                                            :error="Boolean(passwordForm.errors.password)"
                                            required
                                        />
                                    </template>
                                </FormField>

                                <FormField label="Confirm password" for="settings-confirm-password">
                                    <template #default="{ id }">
                                        <Input
                                            :id="id"
                                            v-model="passwordForm.password_confirmation"
                                            type="password"
                                            autocomplete="new-password"
                                            placeholder="Repeat new password"
                                            required
                                        />
                                    </template>
                                </FormField>
                            </div>

                            <div class="flex items-center justify-between gap-3 border-t border-line/70 pt-4">
                                <p class="text-[11.5px] text-subtle">Signing out of other sessions is recommended after a change.</p>
                                <Button type="submit" size="sm" :loading="passwordForm.processing">Update password</Button>
                            </div>
                        </form>
                    </Card>

                    <Card v-if="tab === 'appearance'" padding="none">
                        <div class="border-b border-line/70 px-5 py-4">
                            <h2 class="text-[15px] font-semibold">Appearance</h2>
                            <p class="mt-0.5 text-[12px] text-subtle">Lumora remembers your choice on this device.</p>
                        </div>

                        <div class="grid gap-3 p-5 sm:grid-cols-3">
                            <button
                                v-for="theme in themes"
                                :key="theme.key"
                                type="button"
                                :aria-pressed="preference === theme.key"
                                :class="[
                                    'flex flex-col items-start gap-2 rounded-xl border p-4 text-left transition',
                                    preference === theme.key
                                        ? 'border-brand-500 bg-brand-500/8 ring-1 ring-brand-500/20'
                                        : 'border-line hover:border-brand-400/50 hover:bg-elevated/60',
                                ]"
                                @click="setTheme(theme.key)"
                            >
                                <span class="flex w-full items-center justify-between">
                                    <component :is="theme.icon" class="h-4 w-4 text-brand-600 dark:text-brand-300" />
                                    <Check v-if="preference === theme.key" class="h-3.5 w-3.5 text-brand-600 dark:text-brand-300" />
                                </span>
                                <span class="text-[13px] font-semibold text-ink">{{ theme.label }}</span>
                                <span class="text-[11px] leading-relaxed text-subtle">{{ theme.description }}</span>
                            </button>
                        </div>
                    </Card>

                    <Card v-if="tab === 'account'" padding="none" class="border-danger/30">
                        <div class="border-b border-danger/20 px-5 py-4">
                            <h2 class="text-[15px] font-semibold text-danger">Delete account</h2>
                            <p class="mt-0.5 text-[12px] text-subtle">
                                Permanently remove your profile, creations and credit history. This cannot be undone.
                            </p>
                        </div>

                        <div class="flex flex-col gap-3 p-5 sm:flex-row sm:items-center sm:justify-between">
                            <p class="text-[12px] leading-relaxed text-subtle">
                                Your remaining balance of credits is forfeited when the account is deleted.
                            </p>

                            <Button variant="danger" size="sm" @click="deleteOpen = true">
                                <template #icon><Trash2 class="h-3.5 w-3.5" /></template>
                                Delete my account
                            </Button>
                        </div>
                    </Card>
                </div>
            </div>
        </div>

        <Modal
            v-if="deleteOpen"
            size="sm"
            title="Delete your account?"
            description="This permanently removes your studio, creations and ledger."
            @close="deleteOpen = false"
        >
            <form class="space-y-4 p-5" @submit.prevent="destroyAccount">
                <div class="flex items-start gap-2.5 rounded-xl border border-danger/25 bg-danger/8 p-3.5">
                    <TriangleAlert class="mt-0.5 h-4 w-4 shrink-0 text-danger" />
                    <p class="text-[11.5px] leading-relaxed text-subtle">
                        This action is irreversible. Any remaining credits will be lost.
                    </p>
                </div>

                <FormField label="Confirm with your password" for="delete-password" :error="deleteForm.errors.password">
                    <template #default="{ id }">
                        <Input
                            :id="id"
                            v-model="deleteForm.password"
                            type="password"
                            autocomplete="current-password"
                            placeholder="Your current password"
                            :error="Boolean(deleteForm.errors.password)"
                            required
                        />
                    </template>
                </FormField>
            </form>

            <template #footer>
                <div class="flex items-center justify-between gap-2">
                    <Button variant="ghost" size="sm" :disabled="deleteForm.processing" @click="deleteOpen = false">Cancel</Button>
                    <Button variant="danger" size="sm" :loading="deleteForm.processing" @click="destroyAccount">
                        Permanently delete
                    </Button>
                </div>
            </template>
        </Modal>
    </AppLayout>
</template>
