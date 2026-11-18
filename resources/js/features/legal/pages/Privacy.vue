<script setup>
import { Head } from '@inertiajs/vue3';
import LegalPage from '@/features/legal/components/LegalPage.vue';

const sections = [
    {
        heading: '1. What we collect',
        body: [
            'Account details you provide (name, email, hashed password), the prompts you submit, reference image URLs you attach, and metadata about each render such as size, quality and status.',
            'Billing records are kept as an append-only credit ledger so that every credit movement can be audited.',
        ],
    },
    {
        heading: '2. How we use data',
        body: [
            'Your prompts and reference images are sent to Pixelforge to produce the images you request. We store the resulting image URLs and usage data so your gallery and statements stay accurate.',
            'We use aggregate, de-identified metrics to operate and improve the studio. We do not sell your personal data.',
        ],
    },
    {
        heading: '3. Cookies and local storage',
        body: [
            'Lumora uses a session cookie to keep you signed in. Your theme and sidebar preferences are stored in your browser\'s local storage and never leave your device.',
        ],
    },
    {
        heading: '4. Retention',
        body: [
            'Creations remain in your gallery until you delete them or close your account. Deleting your account removes your profile, creations and ledger, except where retention is required by law.',
        ],
    },
    {
        heading: '5. Your rights',
        body: [
            'You can update your profile, change your password and delete your account from Settings at any time. For data export requests, contact hello@lumora.app.',
        ],
    },
    {
        heading: '6. Security',
        body: [
            'Passwords are hashed with bcrypt. Payments, when enabled, are processed by a PCI-compliant provider. We never store full card numbers.',
        ],
    },
];
</script>

<template>
    <Head title="Privacy Policy" />

    <LegalPage title="Privacy Policy" updated="November 18, 2026" :sections="sections" />
</template>
