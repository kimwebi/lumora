<script setup>
import DocLayout from '@/features/docs/components/DocLayout.vue';

const sections = [
    {
        id: 'welcome',
        title: 'Welcome to Lumora',
        intro: 'Lumora is a studio for turning written ideas into finished images. You describe what you want, choose a shape and quality, and Lumora handles the rest — pricing, rendering, saving and organising your gallery.',
        blocks: [
            {
                type: 'paragraph',
                text: 'You do not need any design or technical experience. If you can describe an image in a sentence, you can use Lumora. This guide walks you through your first render, explains credits, and shows where everything lives.',
            },
            {
                type: 'table',
                head: ['Where to go', 'What it is for'],
                rows: [
                    ['**Overview**', 'Your home base. See your credit balance, total creations, recent renders and a shortcut back to the studio.'],
                    ['**Generate Image**', 'The studio. Write a prompt, choose a size and quality, review the price and start a render.'],
                    ['**My Creations**', 'Your gallery. Search, filter, open, download or remove finished images.'],
                    ['**Credits & Billing**', 'Your wallet. Check your balance, see every transaction and top up with a credit pack.'],
                    ['**Settings**', 'Your profile, password and account controls.'],
                    ['**Admin Dashboard**', 'Only visible to administrators. Shows platform revenue, provider cost, profit and usage health.'],
                ],
            },
            {
                type: 'callout',
                tone: 'tip',
                title: 'New here?',
                text: 'Open **Generate Image** and try one of the example prompts. A first render is the quickest way to understand the flow.',
            },
        ],
    },
    {
        id: 'first-image',
        title: 'Create your first image',
        intro: 'A render usually takes a few seconds to a couple of minutes, depending on the size and quality you choose.',
        blocks: [
            {
                type: 'steps',
                items: [
                    {
                        title: 'Sign in to your studio',
                        text: 'Create an account or sign in. New accounts receive complimentary welcome credits, so you can try the studio before buying anything.',
                    },
                    {
                        title: 'Open Generate Image',
                        text: 'Use the sidebar on desktop, or the menu button in the top bar on mobile.',
                    },
                    {
                        title: 'Describe your image',
                        text: 'Type a prompt in the large text box. Be specific about the subject, setting, lighting and style. The studio suggests an example if you need a starting point.',
                    },
                    {
                        title: 'Choose a size and quality',
                        text: 'Pick a canvas shape such as square, portrait or landscape. Start with **Low** for drafts; switch to **Medium** for everyday work or **High** when you want extra detail.',
                    },
                    {
                        title: 'Add reference images (optional)',
                        text: 'Paste up to two public image URLs if you want to guide the style or composition. Skip this step for a pure text-to-image render.',
                    },
                    {
                        title: 'Check the price',
                        text: 'The price panel shows the provider cost, the 25% Lumora markup and the total credits that will be held. Nothing is charged until you press the generate button.',
                    },
                    {
                        title: 'Press Generate',
                        text: 'Lumora holds the estimated credits, sends the job to the image provider and streams progress back to the page. You can keep the tab open or come back later.',
                    },
                    {
                        title: 'Open the result',
                        text: 'When the render succeeds it appears on the page and in **My Creations**. Open it to download the image, copy the prompt or inspect the cost breakdown.',
                    },
                ],
            },
            {
                type: 'callout',
                tone: 'info',
                title: 'The button is disabled?',
                text: 'The studio only starts a render when the provider is connected, you have enough credits and you have fewer than three images in progress. The message beside the button tells you which condition is blocking you.',
            },
        ],
    },
    {
        id: 'prompting',
        title: 'Write a prompt that works',
        intro: 'A good prompt reads like a short creative brief. Name the subject, then add the details that make the image feel intentional.',
        blocks: [
            {
                type: 'list',
                ordered: true,
                items: [
                    '**Subject** — the main thing in the image: a glass whale, a mountain cabin, a portrait of an astronaut.',
                    '**Scene** — where it is and what surrounds it: drifting through a violet nebula, on a rainy street, in a sunlit studio.',
                    '**Light and mood** — soft morning light, dramatic rim light, calm and dreamy, bold and graphic.',
                    '**Style** — cinematic still, editorial photograph, watercolour, isometric illustration, product render.',
                    '**Details** — composition, camera angle, colour palette and any texture or finish you care about.',
                ],
            },
            {
                type: 'table',
                head: ['Instead of', 'Try'],
                rows: [
                    ['A nice landscape', 'A quiet alpine lake at sunrise, mirror-still water, low mist, pine forest, soft golden light, wide cinematic composition'],
                    ['A cool robot', 'A friendly gardener robot with copper plating and moss on its shoulders, tending tomatoes in a greenhouse, warm afternoon light, editorial photograph'],
                    ['Something futuristic', 'A floating transit station above a cloud sea, white ceramic and brushed steel, cyan accent lights, clean minimal sci-fi concept art'],
                ],
            },
            {
                type: 'list',
                items: [
                    'Keep one clear idea per prompt. Two unrelated subjects often produce a muddled image.',
                    'Describe what you want to see rather than what you want to avoid.',
                    'Use style words you would use with a human artist or photographer.',
                    'Small wording changes can produce very different results — generate a few variations and keep the best.',
                ],
            },
            {
                type: 'callout',
                tone: 'tip',
                text: 'Your prompt is saved exactly as you typed it. You can copy it later from **My Creations** and reuse it with a different size or quality.',
            },
        ],
    },
    {
        id: 'canvas',
        title: 'Choose a size and quality',
        intro: 'Every canvas is generated at the pixel size shown. Pick the shape that fits where the image will live.',
        blocks: [
            {
                type: 'table',
                head: ['Canvas', 'Pixels', 'Ratio', 'Low', 'Medium', 'High'],
                rows: [
                    ['**Square**', '1024 × 1024', '1:1', '~2.2 cr', '~8.7 cr', '~34 cr'],
                    ['**Portrait**', '1024 × 1536', '2:3', '~2.9 cr', '~11.4 cr', '~44.8 cr'],
                    ['**Landscape**', '1536 × 1024', '3:2', '~2.9 cr', '~11.4 cr', '~44.8 cr'],
                ],
            },
            {
                type: 'definitions',
                items: [
                    { term: 'Low', text: 'Fast drafts and everyday concepts. Great for exploring ideas and testing prompts.' },
                    { term: 'Medium', text: 'Balanced detail for most ideas. The default choice for finished social and product images.' },
                    { term: 'High', text: 'Sharper detail and richer lighting. Use it for portfolio pieces, hero images and final exports.' },
                ],
            },
            {
                type: 'callout',
                tone: 'info',
                title: 'Estimates can move slightly',
                text: 'The table shows typical estimates. The exact charge is settled from the provider’s reported usage for that render, so a final charge can differ a little from the estimate. Any unused hold is returned automatically.',
            },
        ],
    },
    {
        id: 'references',
        title: 'Use reference images',
        intro: 'References are optional images that give the model visual guidance for style, palette or composition.',
        blocks: [
            {
                type: 'steps',
                items: [
                    {
                        title: 'Find a public image URL',
                        text: 'The link must start with `http://` or `https://` and must open without a login. Image search results and private album links usually will not work.',
                    },
                    {
                        title: 'Paste it into Reference images',
                        text: 'Add one or two URLs in the studio. Duplicate links are ignored and the studio caps the list at two.',
                    },
                    {
                        title: 'Describe what to borrow',
                        text: 'In the prompt, say whether you want the palette, lighting, texture or composition. “Use the reference palette” is clearer than leaving the model to guess.',
                    },
                ],
            },
            {
                type: 'callout',
                tone: 'warning',
                title: 'Only use images you have the right to use',
                text: 'Do not attach private photos, client work or images you do not have permission to use. Reference URLs are stored with the render so you can review them later.',
            },
        ],
    },
    {
        id: 'credits',
        title: 'Credits, estimates and final charges',
        intro: 'Lumora uses prepaid credits. One credit is worth 0.01 US dollars, and you only keep paying for renders that succeed.',
        blocks: [
            {
                type: 'list',
                items: [
                    '**One credit = US$0.01.** Your balance is shown in credits and in dollars on the billing page.',
                    '**New accounts get welcome credits.** The default is 500 credits (about US$5) and it is added automatically when you register.',
                    '**The studio holds an estimate.** When you start a render, the estimated charge is reserved from your balance as a hold. It is not a separate fee.',
                    '**The final charge is based on real usage.** When the provider reports the render, Lumora recalculates the cost from the size, quality and token usage, adds the 25% markup and settles the difference.',
                    '**Unused credit comes back.** If the actual charge is lower than the hold, the difference is refunded immediately. If it is higher, the extra is taken from your balance; wallets never go negative.',
                    '**Failures are free.** If a render fails or times out, the full hold is returned and the final charge is zero.',
                ],
            },
            {
                type: 'table',
                head: ['Pack', 'Credits', 'Price', 'Best for'],
                rows: [
                    ['**Starter**', '500', 'US$5', 'Trying ideas and a first small project'],
                    ['**Studio**', '1,200', 'US$10', 'Everyday creator output, with 200 bonus credits'],
                    ['**Pro**', '3,000', 'US$25', 'High-volume studio work and the best value per render'],
                ],
            },
            {
                type: 'callout',
                tone: 'warning',
                title: 'Demo checkout',
                text: 'In this build, **Buy credits** uses a demo checkout. Credits are added instantly and no card is charged. Connect your payment provider before taking real payments.',
            },
            {
                type: 'callout',
                tone: 'info',
                title: 'Where to see every movement',
                text: 'Open **Credits & Billing** to see available balance, lifetime credits, total spent, held credits and a complete transaction history with holds, settlements, refunds, purchases and bonuses.',
            },
        ],
    },
    {
        id: 'gallery',
        title: 'Find and manage your creations',
        intro: 'Every render is saved in **My Creations**, including failed attempts and images still in progress.',
        blocks: [
            {
                type: 'table',
                head: ['Filter', 'What it shows'],
                rows: [
                    ['**All**', 'Everything in your gallery, newest first.'],
                    ['**Processing**', 'Renders that are queued or currently being generated.'],
                    ['**Succeeded**', 'Finished images that are ready to download.'],
                    ['**Failed**', 'Renders that did not complete. These are always free.'],
                ],
            },
            {
                type: 'list',
                items: [
                    'Use the search box to find a creation by words in its prompt.',
                    'Each card shows the prompt, date, dimensions, quality, status and cost at a glance.',
                    'Select a card to open the detail view with the full cost breakdown and the original image link.',
                    'Use **Download** to save the image, or **Copy prompt** to reuse the wording in a new render.',
                    'Only finished creations can be removed. Deleting an image removes it from your gallery; it does not refund credits that were already spent on a successful render.',
                ],
            },
            {
                type: 'callout',
                tone: 'tip',
                title: 'Keep a prompt library',
                text: 'When a render works well, copy the prompt into your notes. Reusing a strong prompt with a new size or quality is the fastest way to build a consistent set.',
            },
        ],
    },
    {
        id: 'account',
        title: 'Personalise your account',
        intro: 'Lumora adapts to your device and your working style.',
        blocks: [
            {
                type: 'definitions',
                items: [
                    { term: 'Light, dark or system theme', text: 'Use the theme toggle in the top bar. Your choice is saved on this device; on your first visit Lumora follows your operating system.' },
                    { term: 'Collapsible sidebar', text: 'On desktop, collapse the sidebar for a wider canvas. It remembers your preference.' },
                    { term: 'Notifications', text: 'The bell in the top bar lists renders that finished in the last day, with their status and charge.' },
                    { term: 'Profile', text: 'Update your display name and email in **Settings**. The new address is used for account notices from that point on.' },
                    { term: 'Password', text: 'Change your password from **Settings** by entering your current password and a new one.' },
                    { term: 'Account deletion', text: 'You can permanently delete your account from **Settings**. This removes your profile and creations and cannot be undone.' },
                ],
            },
            {
                type: 'callout',
                tone: 'info',
                title: 'Forgot your password?',
                text: 'Use **Forgot password?** on the sign-in page to request a reset link. If the email does not arrive, check your spam folder or contact support.',
            },
        ],
    },
    {
        id: 'troubleshooting',
        title: 'Troubleshooting and common questions',
        intro: 'Most issues have a simple explanation. Start with the message shown in the studio or on the creation card.',
        blocks: [
            {
                type: 'table',
                head: ['What you see', 'What to do'],
                rows: [
                    ['The generate button is disabled and mentions the image provider', 'The platform has not connected its Pixelforge credentials yet. Contact the Lumora administrator or support; nothing is wrong with your account.'],
                    ['“You have renders in progress”', 'You can have three active renders at once. Wait for one to finish, or check **My Creations → Processing**.'],
                    ['“Not enough credits”', 'Top up on **Credits & Billing**, or choose a smaller canvas or lower quality to reduce the estimate.'],
                    ['A render stays on Processing for a while', 'High quality images can take a couple of minutes. Keep the page open or return to it later. Renders that exceed the timeout are failed and refunded automatically.'],
                    ['A render failed', 'Your credits were returned. Try a simpler prompt, a smaller canvas or lower quality, then run it again.'],
                    ['An image will not download', 'Use **Open original** in the detail view and save the image from the browser. On mobile, press and hold the image and choose Save.'],
                    ['I cannot find a creation', 'Check the status filter and search box, then clear both. Deleted creations cannot be restored.'],
                    ['I did not receive welcome credits', 'Refresh the overview page. If the balance is still zero, contact support with the email address you registered with.'],
                ],
            },
            {
                type: 'callout',
                tone: 'tip',
                title: 'Before contacting support',
                text: 'Note the prompt, canvas size, quality and approximate time of the render. The creation card also shows a status and any error message, which helps us find the job quickly.',
            },
        ],
    },
    {
        id: 'privacy',
        title: 'Your content and privacy',
        intro: 'Your prompts and creations belong to you. Lumora stores them so you can revisit and manage your gallery.',
        blocks: [
            {
                type: 'list',
                items: [
                    'Prompts, settings, reference URLs, costs and statuses are stored with your account and shown only to you and the platform administrators.',
                    'Generated images are served from the image provider’s content network. Lumora stores the link rather than a second copy.',
                    'Do not include passwords, personal identification numbers, private addresses or other sensitive information in a prompt or reference URL.',
                    'Deleting your account removes your profile, creations and wallet history. Encrypted backups may retain copies for a limited period before they are overwritten.',
                ],
            },
            {
                type: 'paragraph',
                text: 'Read the full **Terms of Service** and **Privacy Policy** for the legal details. Both are linked in the footer of every page.',
            },
            {
                type: 'callout',
                tone: 'info',
                title: 'Questions?',
                text: 'Email **hello@lumora.app** and a human will help. Include your account email and as much detail about the render as you can.',
            },
        ],
    },
];
</script>

<template>
    <DocLayout
        eyebrow="User guide"
        title="Everything you need to create with Lumora"
        intro="A plain-language guide to rendering your first image, writing better prompts, understanding credits and keeping your gallery organised. No technical knowledge required."
        :sections="sections"
        updated="November 2026"
    />
</template>
