<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Mail, MailCheck } from 'lucide-vue-next';
import AuthLayout from '@/layouts/AuthLayout.vue';
import Button from '@/components/ui/Button.vue';
import Input from '@/components/ui/Input.vue';
import FormField from '@/components/ui/FormField.vue';
import { routes } from '@/lib/routes';

const props = defineProps({
    status: { type: String, default: null },
});

const form = useForm({ email: '' });

const submit = () => {
    form.post(routes.passwordEmail, {
        preserveScroll: true,
        onSuccess: () => form.reset('email'),
    });
};
</script>

<template>
    <Head title="Reset password" />

    <AuthLayout
        title="Forgot your password?"
        description="Enter your email and we'll send a secure link to reset it."
        :back-href="routes.login"
        back-label="Back to sign in"
    >
        <div
            v-if="status"
            class="mb-5 flex items-start gap-2.5 rounded-xl border border-success/25 bg-success/10 px-4 py-3 text-[13px] font-medium text-emerald-700 dark:text-emerald-300"
        >
            <MailCheck class="mt-0.5 h-4 w-4 shrink-0" />
            {{ status }}
        </div>

        <form class="space-y-5" @submit.prevent="submit">
            <FormField label="Email address" for="forgot-email" :error="form.errors.email">
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

            <Button type="submit" block size="lg" :loading="form.processing">
                Send reset link
            </Button>
        </form>

        <template #footer>
            <p class="text-[13px] text-subtle">
                <Link :href="routes.login" class="font-semibold text-brand-700 transition hover:text-brand-500 dark:text-brand-300">
                    Return to sign in
                </Link>
            </p>
        </template>
    </AuthLayout>
</template>
