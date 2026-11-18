<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Eye, EyeOff, Mail, Lock } from 'lucide-vue-next';
import { ref } from 'vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import Button from '@/components/ui/Button.vue';
import Input from '@/components/ui/Input.vue';
import FormField from '@/components/ui/FormField.vue';
import { routes } from '@/lib/routes';

defineProps({
    status: { type: String, default: null },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const showPassword = ref(false);

const submit = () => {
    form.post(routes.login, {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Sign in" />

    <AuthLayout
        title="Welcome back"
        description="Sign in to continue creating in your Lumora studio."
        :back-href="routes.home"
        back-label="Back to home"
    >
        <div v-if="status" class="mb-5 rounded-xl border border-success/25 bg-success/10 px-4 py-3 text-[13px] font-medium text-emerald-700 dark:text-emerald-300">
            {{ status }}
        </div>

        <form class="space-y-5" @submit.prevent="submit">
            <FormField label="Email address" for="login-email" :error="form.errors.email">
                <template #default="{ id }">
                    <Input
                        :id="id"
                        v-model="form.email"
                        type="email"
                        autocomplete="email"
                        placeholder="you@example.com"
                        :error="Boolean(form.errors.email)"
                        autofocus
                        required
                    >
                        <template #leading><Mail class="h-4 w-4" /></template>
                    </Input>
                </template>
            </FormField>

            <FormField label="Password" for="login-password" :error="form.errors.password">
                <template #default="{ id }">
                    <Input
                        :id="id"
                        v-model="form.password"
                        :type="showPassword ? 'text' : 'password'"
                        autocomplete="current-password"
                        placeholder="••••••••"
                        :error="Boolean(form.errors.password)"
                        required
                    >
                        <template #leading><Lock class="h-4 w-4" /></template>
                        <template #trailing>
                            <button
                                type="button"
                                class="focus-ring rounded-md p-1 text-subtle transition hover:text-ink"
                                :aria-label="showPassword ? 'Hide password' : 'Show password'"
                                @click="showPassword = !showPassword"
                            >
                                <EyeOff v-if="showPassword" class="h-4 w-4" />
                                <Eye v-else class="h-4 w-4" />
                            </button>
                        </template>
                    </Input>
                </template>
                <template #aside>
                    <Link
                        :href="routes.passwordEmail"
                        class="text-[12px] font-medium text-brand-700 transition hover:text-brand-500 dark:text-brand-300"
                    >
                        Forgot password?
                    </Link>
                </template>
            </FormField>

            <label class="flex cursor-pointer select-none items-center gap-2.5">
                <input
                    v-model="form.remember"
                    type="checkbox"
                    class="h-4 w-4 rounded border-line bg-input text-brand-600 focus:ring-2 focus:ring-brand-500/40"
                />
                <span class="text-[13px] text-subtle">Keep me signed in for 30 days</span>
            </label>

            <Button type="submit" block size="lg" :loading="form.processing">
                Sign in to Lumora
            </Button>
        </form>

        <template #footer>
            <p class="text-[13px] text-subtle">
                New to Lumora?
                <Link :href="routes.register" class="font-semibold text-brand-700 transition hover:text-brand-500 dark:text-brand-300">
                    Create your studio
                </Link>
            </p>
        </template>
    </AuthLayout>
</template>
