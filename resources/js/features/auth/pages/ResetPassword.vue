<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Eye, EyeOff, Lock, Mail } from 'lucide-vue-next';
import { ref } from 'vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import Button from '@/components/ui/Button.vue';
import Input from '@/components/ui/Input.vue';
import FormField from '@/components/ui/FormField.vue';
import { routes } from '@/lib/routes';

const props = defineProps({
    email: { type: String, default: null },
    token: { type: String, required: true },
});

const form = useForm({
    token: props.token,
    email: props.email ?? '',
    password: '',
    password_confirmation: '',
});

const showPassword = ref(false);

const submit = () => {
    form.post(routes.passwordReset, {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Choose a new password" />

    <AuthLayout
        title="Choose a new password"
        description="Pick something strong and unique to keep your studio safe."
        :back-href="routes.login"
        back-label="Back to sign in"
    >
        <form class="space-y-5" @submit.prevent="submit">
            <FormField label="Email address" for="reset-email" :error="form.errors.email">
                <template #default="{ id }">
                    <Input
                        :id="id"
                        v-model="form.email"
                        type="email"
                        autocomplete="email"
                        placeholder="you@example.com"
                        :error="Boolean(form.errors.email)"
                        required
                    >
                        <template #leading><Mail class="h-4 w-4" /></template>
                    </Input>
                </template>
            </FormField>

            <FormField label="New password" for="reset-password" :error="form.errors.password">
                <template #default="{ id }">
                    <Input
                        :id="id"
                        v-model="form.password"
                        :type="showPassword ? 'text' : 'password'"
                        autocomplete="new-password"
                        placeholder="At least 8 characters"
                        :error="Boolean(form.errors.password)"
                        autofocus
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
            </FormField>

            <FormField label="Confirm new password" for="reset-password-confirmation" :error="form.errors.password_confirmation">
                <template #default="{ id }">
                    <Input
                        :id="id"
                        v-model="form.password_confirmation"
                        :type="showPassword ? 'text' : 'password'"
                        autocomplete="new-password"
                        placeholder="Repeat your new password"
                        :error="Boolean(form.errors.password_confirmation)"
                        required
                    >
                        <template #leading><Lock class="h-4 w-4" /></template>
                    </Input>
                </template>
            </FormField>

            <Button type="submit" block size="lg" :loading="form.processing">
                Reset my password
            </Button>
        </form>

        <template #footer>
            <p class="text-[13px] text-subtle">
                Remembered it?
                <Link :href="routes.login" class="font-semibold text-brand-700 transition hover:text-brand-500 dark:text-brand-300">
                    Sign in instead
                </Link>
            </p>
        </template>
    </AuthLayout>
</template>
