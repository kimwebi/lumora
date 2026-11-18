<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Eye, EyeOff, Lock, Mail, User } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import Button from '@/components/ui/Button.vue';
import Input from '@/components/ui/Input.vue';
import FormField from '@/components/ui/FormField.vue';
import { routes } from '@/lib/routes';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    terms: false,
});

const showPassword = ref(false);

const strength = computed(() => {
    const value = form.password ?? '';
    let score = 0;

    if (value.length >= 8) score += 1;
    if (value.length >= 12) score += 1;
    if (/[A-Z]/.test(value) && /[a-z]/.test(value)) score += 1;
    if (/\d/.test(value)) score += 1;
    if (/[^A-Za-z0-9]/.test(value)) score += 1;

    return Math.min(score, 4);
});

const strengthLabel = computed(() => ['Too short', 'Weak', 'Fair', 'Good', 'Strong'][strength.value]);
const strengthTone = computed(
    () => ['bg-line', 'bg-danger', 'bg-pending', 'bg-brand-400', 'bg-success'][strength.value],
);

const submit = () => {
    form.post(routes.register, {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Create your studio" />

    <AuthLayout
        title="Create your studio"
        description="Start with complimentary credits — no card required."
        :back-href="routes.home"
        back-label="Back to home"
    >
        <form class="space-y-5" @submit.prevent="submit">
            <FormField label="Your name" for="register-name" :error="form.errors.name">
                <template #default="{ id }">
                    <Input
                        :id="id"
                        v-model="form.name"
                        autocomplete="name"
                        placeholder="Maya Chen"
                        :error="Boolean(form.errors.name)"
                        autofocus
                        required
                    >
                        <template #leading><User class="h-4 w-4" /></template>
                    </Input>
                </template>
            </FormField>

            <FormField label="Email address" for="register-email" :error="form.errors.email">
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

            <FormField label="Password" for="register-password" :error="form.errors.password">
                <template #default="{ id }">
                    <Input
                        :id="id"
                        v-model="form.password"
                        :type="showPassword ? 'text' : 'password'"
                        autocomplete="new-password"
                        placeholder="At least 8 characters"
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
                    <span v-if="form.password" class="font-alt text-[10.5px] font-semibold uppercase tracking-[0.12em] text-subtle">
                        {{ strengthLabel }}
                    </span>
                </template>
            </FormField>

            <div v-if="form.password" class="-mt-2 flex gap-1" aria-hidden="true">
                <span
                    v-for="step in 4"
                    :key="step"
                    class="h-1 flex-1 rounded-full transition-colors duration-300"
                    :class="step <= strength ? strengthTone : 'bg-line'"
                />
            </div>

            <FormField label="Confirm password" for="register-password-confirmation" :error="form.errors.password_confirmation">
                <template #default="{ id }">
                    <Input
                        :id="id"
                        v-model="form.password_confirmation"
                        :type="showPassword ? 'text' : 'password'"
                        autocomplete="new-password"
                        placeholder="Repeat your password"
                        :error="Boolean(form.errors.password_confirmation)"
                        required
                    >
                        <template #leading><Lock class="h-4 w-4" /></template>
                    </Input>
                </template>
            </FormField>

            <div>
                <label class="flex cursor-pointer select-none items-start gap-2.5">
                    <input
                        v-model="form.terms"
                        type="checkbox"
                        class="mt-0.5 h-4 w-4 rounded border-line bg-input text-brand-600 focus:ring-2 focus:ring-brand-500/40"
                    />
                    <span class="text-[12.5px] leading-relaxed text-subtle">
                        I agree to the
                        <Link href="/terms" class="font-medium text-brand-700 hover:text-brand-500 dark:text-brand-300">terms of service</Link>
                        and
                        <Link href="/privacy" class="font-medium text-brand-700 hover:text-brand-500 dark:text-brand-300">privacy policy</Link>.
                    </span>
                </label>
                <p v-if="form.errors.terms" class="mt-1.5 text-[13px] font-medium text-danger">{{ form.errors.terms }}</p>
            </div>

            <Button type="submit" block size="lg" :loading="form.processing">
                Create my studio
            </Button>
        </form>

        <template #footer>
            <p class="text-[13px] text-subtle">
                Already have an account?
                <Link :href="routes.login" class="font-semibold text-brand-700 transition hover:text-brand-500 dark:text-brand-300">
                    Sign in
                </Link>
            </p>
        </template>
    </AuthLayout>
</template>
