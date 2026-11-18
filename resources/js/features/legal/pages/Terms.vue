<script setup>
import { Head } from '@inertiajs/vue3';
import LegalPage from '@/features/legal/components/LegalPage.vue';

const sections = [
    {
        heading: '1. Using Lumora',
        body: [
            'Lumora is an AI image generation studio. By creating an account you agree to use the service lawfully and to follow the acceptable use policy of our image provider, Pixelforge.',
            'You must be at least 18 years old, or the age of majority in your jurisdiction, to purchase credits.',
        ],
    },
    {
        heading: '2. Credits and pricing',
        body: [
            'Credits are prepaid and pegged to US dollars at one credit per $0.01. Credits never expire and are not transferable between accounts.',
            'Each render is priced at the provider cost reported for that job plus a 25% platform markup. The estimated price is shown before you generate; the final charge is settled from reported usage.',
        ],
    },
    {
        heading: '3. Failed renders',
        body: [
            'If a render fails or times out, the estimated charge held for that job is refunded to your balance automatically. Failed renders are never charged.',
        ],
    },
    {
        heading: '4. Your content',
        body: [
            'You retain ownership of the prompts you write and the images you generate, subject to the provider terms and applicable law. You grant Lumora a limited licence to store and display your creations inside your own account.',
            'Do not generate content that infringes intellectual property, depicts real people in misleading ways, or violates applicable law.',
        ],
    },
    {
        heading: '5. Availability and changes',
        body: [
            'We may update features, pricing logic or these terms as the studio evolves. Material changes will be communicated in the product before they take effect.',
        ],
    },
    {
        heading: '6. Contact',
        body: [
            'Questions about these terms can be sent to hello@lumora.app.',
        ],
    },
];
</script>

<template>
    <Head title="Terms of Service" />

    <LegalPage title="Terms of Service" updated="November 18, 2026" :sections="sections" />
</template>
