<script setup>
import { Copy, Info, Lightbulb, ShieldCheck, TriangleAlert } from 'lucide-vue-next';
import RichText from './RichText.vue';
import { useClipboard } from '@/composables/useClipboard';

defineProps({
    blocks: { type: Array, required: true },
});

const { copy } = useClipboard();

const callouts = {
    info: {
        icon: Info,
        label: 'Good to know',
        classes: 'border-accent/35 bg-accent/5',
        iconClass: 'text-cyan-600 dark:text-cyan-300',
    },
    tip: {
        icon: Lightbulb,
        label: 'Tip',
        classes: 'border-success/35 bg-success/5',
        iconClass: 'text-emerald-600 dark:text-emerald-300',
    },
    warning: {
        icon: TriangleAlert,
        label: 'Heads up',
        classes: 'border-pending/40 bg-pending/5',
        iconClass: 'text-amber-600 dark:text-amber-300',
    },
    danger: {
        icon: ShieldCheck,
        label: 'Important',
        classes: 'border-danger/35 bg-danger/5',
        iconClass: 'text-danger',
    },
};

const calloutFor = (tone) => callouts[tone] ?? callouts.info;
</script>

<template>
    <div class="space-y-4">
        <template v-for="(block, index) in blocks" :key="index">
            <h3 v-if="block.type === 'subheading'" :id="block.id" class="scroll-mt-28 pt-2 text-[15px] font-semibold tracking-[-0.01em]">
                {{ block.title }}
            </h3>

            <RichText
                v-else-if="block.type === 'paragraph'"
                :text="block.text"
                class="text-[13.5px] leading-[1.75] text-subtle"
            />

            <component
                :is="block.ordered ? 'ol' : 'ul'"
                v-else-if="block.type === 'list'"
                :class="block.ordered ? 'list-decimal' : 'list-disc'"
                class="ml-5 space-y-1.5 text-[13.5px] leading-relaxed text-subtle marker:text-brand-500"
            >
                <li v-for="item in block.items" :key="item" class="pl-1">
                    <RichText :text="item" tag="span" />
                </li>
            </component>

            <ol v-else-if="block.type === 'steps'" class="space-y-4">
                <li v-for="(step, stepIndex) in block.items" :key="step.title" class="flex gap-3">
                    <span class="mt-0.5 grid h-6 w-6 shrink-0 place-items-center rounded-full bg-brand-500/12 text-[11px] font-semibold text-brand-700 ring-1 ring-inset ring-brand-500/20 dark:text-brand-300">
                        {{ stepIndex + 1 }}
                    </span>
                    <div class="min-w-0">
                        <p class="text-[13.5px] font-semibold text-ink">{{ step.title }}</p>
                        <RichText :text="step.text" class="mt-1 text-[13px] leading-relaxed text-subtle" />
                    </div>
                </li>
            </ol>

            <div v-else-if="block.type === 'callout'" :class="['flex gap-3 rounded-xl border p-4', calloutFor(block.tone).classes]">
                <component
                    :is="calloutFor(block.tone).icon"
                    :class="['mt-0.5 h-4 w-4 shrink-0', calloutFor(block.tone).iconClass]"
                    aria-hidden="true"
                />
                <div class="min-w-0">
                    <p class="text-[12.5px] font-semibold text-ink">{{ block.title || calloutFor(block.tone).label }}</p>
                    <RichText :text="block.text" class="mt-1 text-[12.5px] leading-relaxed text-subtle" />
                </div>
            </div>

            <figure v-else-if="block.type === 'code'" class="overflow-hidden rounded-xl border border-line bg-midnight/95">
                <figcaption class="flex items-center justify-between gap-3 border-b border-white/10 px-4 py-2">
                    <span class="font-alt text-[9px] font-bold uppercase tracking-[0.2em] text-white/45">
                        {{ block.language || 'text' }}
                    </span>
                    <button
                        type="button"
                        class="focus-ring inline-flex items-center gap-1.5 rounded-md px-2 py-1 text-[10.5px] font-medium text-white/55 transition hover:bg-white/10 hover:text-white"
                        :aria-label="`Copy ${block.language || 'code'} snippet`"
                        @click="copy(block.code, 'Snippet copied')"
                    >
                        <Copy class="h-3 w-3" aria-hidden="true" />
                        Copy
                    </button>
                </figcaption>
                <pre class="whitespace-pre-wrap break-words px-4 py-4 text-[12px] leading-[1.7] text-white/85"><code>{{ block.code }}</code></pre>
            </figure>

            <div v-else-if="block.type === 'table'">
                <div class="space-y-2.5 sm:hidden">
                    <div
                        v-for="(row, rowIndex) in block.rows"
                        :key="rowIndex"
                        class="rounded-xl border border-line bg-surface/70 p-4"
                    >
                        <dl class="space-y-3">
                            <div v-for="(cell, cellIndex) in row" :key="cellIndex">
                                <dt class="font-alt text-[9px] font-bold uppercase tracking-[0.14em] text-subtle">
                                    {{ block.head[cellIndex] }}
                                </dt>
                                <dd class="mt-1 text-[12.5px] leading-relaxed text-subtle">
                                    <RichText :text="String(cell)" tag="span" />
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <div class="hidden overflow-x-auto rounded-xl border border-line sm:block">
                    <table class="w-full text-left text-[12.5px] sm:min-w-[34rem]">
                        <thead class="bg-muted/50">
                            <tr>
                                <th
                                    v-for="heading in block.head"
                                    :key="heading"
                                    scope="col"
                                    class="whitespace-nowrap px-4 py-2.5 font-alt text-[9.5px] font-bold uppercase tracking-[0.16em] text-subtle"
                                >
                                    {{ heading }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line/60">
                            <tr v-for="(row, rowIndex) in block.rows" :key="rowIndex" class="align-top transition hover:bg-elevated/30">
                                <td v-for="(cell, cellIndex) in row" :key="cellIndex" class="px-4 py-2.5 leading-relaxed text-subtle">
                                    <RichText :text="String(cell)" tag="span" />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <dl v-else-if="block.type === 'definitions'" class="grid gap-2.5 sm:grid-cols-2">
                <div v-for="item in block.items" :key="item.term" class="rounded-xl border border-line bg-surface/70 p-3.5">
                    <dt class="text-[12.5px] font-semibold text-ink">{{ item.term }}</dt>
                    <RichText :text="item.text ?? item.description" class="mt-1 text-[12px] leading-relaxed text-subtle" />
                </div>
            </dl>
        </template>
    </div>
</template>
