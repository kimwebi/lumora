<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Link } from '@inertiajs/vue3';
import {
    ArrowLeft,
    ChevronDown,
    Compass,
    Copy,
    Database,
    EyeOff,
    Layers,
    PanelsTopLeft,
    Search,
    Server,
    Workflow,
    X,
} from 'lucide-vue-next';
import Logo from '@/components/brand/Logo.vue';
import ThemeToggle from '@/components/brand/ThemeToggle.vue';
import ToastHost from '@/components/ui/ToastHost.vue';
import DocBlocks from '@/features/docs/components/DocBlocks.vue';
import RichText from '@/features/docs/components/RichText.vue';
import { sections as content, tabs } from '@/features/docs/data/codeExplanation';
import { publicApi } from '@/features/docs/data/codeExplanationApi';
import { useClipboard } from '@/composables/useClipboard';
import { useSeo } from '@/composables/useSeo';
import { routes } from '@/lib/routes';

useSeo();

const tabIcons = { Compass, Server, PanelsTopLeft, Database, Workflow };

const STORAGE_KEY = 'lumora-codex-tab';
const searchInput = ref(null);
const query = ref('');
const expanded = ref({});
const { copy } = useClipboard();

const storedTab = (() => {
    try {
        return window.sessionStorage.getItem(STORAGE_KEY);
    } catch {
        return null;
    }
})();

const activeTab = ref(tabs.some((tab) => tab.id === storedTab) ? storedTab : tabs[0].id);

const entriesFor = (tabId) => content[tabId] ?? [];

const normalise = (value) => String(value ?? '').toLowerCase();

const matches = (haystack, needle) => normalise(haystack).includes(needle);

const cleanSignature = (signature) =>
    String(signature ?? '')
        .replace(/^function\s+/, '')
        .replace(/\s+/g, ' ')
        .trim();

const stepText = (step) =>
    typeof step === 'string' ? step : [step?.title, step?.text, step?.description].filter(Boolean).join(' — ');

const stepBlock = (step, index) =>
    typeof step === 'string' ? { title: `Step ${index + 1}`, text: step } : step;

/**
 * Build the same CreditService-style explanation for every entry. Entries can
 * still supply their own `blocks` when they need hand-written snippets; the
 * rest are expanded from their role, details, steps and generated public API.
 */
const blocksFor = (entry) => {
    if (entry.blocks?.length) return entry.blocks;

    const details = entry.details ?? [];
    const steps = entry.steps ?? [];
    const ideas = details.length
        ? details
        : steps.length
            ? steps.map(stepText)
            : [`Its job is: ${entry.role}`];

    const apiRows = publicApi[entry.path] ?? [];
    const surfaceRows = apiRows.length
        ? apiRows.map(([signature, purpose]) => {
              const name = String(signature).match(/function\s+(\w+)/)?.[1];
              const documented = name
                  ? details.find((detail) =>
                        detail.includes(`\`${name}(`) || detail.includes(`${name}()`),
                    )
                  : null;

              return [cleanSignature(signature), documented ?? purpose];
          })
        : ideas.map((text, index) => [`${index + 1}`, text]);

    const isPhp = entry.path.endsWith('.php') && apiRows.length > 0;
    const blocks = [
        { type: 'subheading', title: 'Core ideas' },
        { type: 'list', items: ideas },
    ];

    if (surfaceRows.length) {
        blocks.push(
            { type: 'subheading', title: isPhp ? 'Main public methods' : 'Main surface' },
            {
                type: 'table',
                head: isPhp ? ['Method', 'Purpose'] : ['Area', 'What it does'],
                rows: surfaceRows,
            },
        );
    }

    if (steps.length) {
        blocks.push(
            { type: 'subheading', title: 'How it works' },
            { type: 'steps', items: steps.map(stepBlock) },
        );
    }

    blocks.push(
        { type: 'subheading', title: 'Summary' },
        { type: 'paragraph', text: entry.summaryText ?? `In short: ${entry.role}` },
    );

    return blocks;
};

/**
 * Flatten every textual field so search behaves the same for hand-written and
 * generated explanations.
 */
const blockText = (block) => {
    if (!block || typeof block !== 'object') return String(block ?? '');

    return [
        block.text,
        block.title,
        block.code,
        block.language,
        ...(block.head ?? []),
        ...(block.items ?? []).map((item) =>
            typeof item === 'string'
                ? item
                : [item.term, item.title, item.text, item.description].filter(Boolean).join(' '),
        ),
        ...(block.rows ?? []).map((row) => (Array.isArray(row) ? row : [row]).join(' ')),
    ]
        .filter(Boolean)
        .join(' ');
};

const searchableText = (entry) =>
    [
        entry.path,
        entry.role,
        ...(entry.details ?? []),
        ...(entry.steps ?? []).map((step) =>
            typeof step === 'string' ? step : [step.title, step.text].filter(Boolean).join(' '),
        ),
        ...blocksFor(entry).map(blockText),
    ]
        .filter(Boolean)
        .join(' ');

const searching = computed(() => query.value.trim().length > 0);
const needle = computed(() => normalise(query.value.trim()));

const tabCounts = computed(() =>
    Object.fromEntries(tabs.map((tab) => [tab.id, entriesFor(tab.id).reduce((total, section) => total + section.entries.length, 0)])),
);

const totalEntries = computed(() => Object.values(tabCounts.value).reduce((total, count) => total + count, 0));

const visibleSections = computed(() => {
    const sections = entriesFor(activeTab.value);

    if (!searching.value) {
        return sections.map((section) => ({ ...section, visibleEntries: section.entries }));
    }

    return sections
        .map((section) => {
            const sectionMatches = matches(`${section.title} ${section.summary}`, needle.value);

            const visibleEntries = sectionMatches
                ? section.entries
                : section.entries.filter((entry) => matches(searchableText(entry), needle.value));

            return { ...section, visibleEntries };
        })
        .filter((section) => section.visibleEntries.length > 0);
});

const resultCount = computed(() =>
    visibleSections.value.reduce((total, section) => total + section.visibleEntries.length, 0),
);

const activeTabMeta = computed(() => tabs.find((tab) => tab.id === activeTab.value));

const sectionKey = (sectionId) => `${activeTab.value}:${sectionId}`;

const isOpen = (section) => {
    if (searching.value) return true;

    const key = sectionKey(section.id);

    if (expanded.value[key] !== undefined) {
        return expanded.value[key];
    }

    return entriesFor(activeTab.value)[0]?.id === section.id;
};

const toggleSection = (section) => {
    expanded.value[sectionKey(section.id)] = !isOpen(section);
};

const expandAll = () => {
    visibleSections.value.forEach((section) => {
        expanded.value[sectionKey(section.id)] = true;
    });
};

const collapseAll = () => {
    visibleSections.value.forEach((section) => {
        expanded.value[sectionKey(section.id)] = false;
    });
};

const clearSearch = () => {
    query.value = '';
    searchInput.value?.focus();
};

const onKeydown = (event) => {
    if (event.key === 'Escape' && searching.value) {
        clearSearch();
        return;
    }

    if (event.key !== '/' || searching.value) return;

    const target = event.target;
    const typing =
        target instanceof HTMLElement &&
        (['INPUT', 'TEXTAREA', 'SELECT'].includes(target.tagName) || target.isContentEditable);

    if (typing) return;

    event.preventDefault();
    searchInput.value?.focus();
};

const copyPath = (path) => copy(path, 'Path copied');

watch(activeTab, (tab) => {
    try {
        window.sessionStorage.setItem(STORAGE_KEY, tab);
    } catch {
        // Tab persistence is optional.
    }

    const first = entriesFor(tab)[0];

    if (first) {
        expanded.value[`${tab}:${first.id}`] = true;
    }
});

onMounted(() => {
    const first = entriesFor(activeTab.value)[0];

    if (first) {
        expanded.value[`${activeTab.value}:${first.id}`] = true;
    }

    window.addEventListener('keydown', onKeydown);
});

onBeforeUnmount(() => window.removeEventListener('keydown', onKeydown));
</script>

<template>
    <div class="min-h-dvh bg-canvas">
        <header class="glass sticky top-0 z-40 border-b border-line/70">
            <div class="mx-auto flex w-full max-w-6xl items-center gap-3 px-5 py-3 sm:px-8">
                <Logo size="sm" />

                <span
                    class="hidden items-center gap-1.5 rounded-full border border-brand-500/25 bg-brand-500/10 px-2.5 py-1 font-alt text-[10px] font-semibold uppercase tracking-[0.16em] text-brand-700 sm:inline-flex dark:text-brand-200"
                >
                    <EyeOff class="h-3 w-3" />
                    Unlisted
                </span>

                <div class="ml-auto flex items-center gap-2">
                    <Link
                        :href="routes.dashboard"
                        class="focus-ring hidden items-center gap-1.5 rounded-xl px-3 py-2 text-[12.5px] font-medium text-subtle transition hover:bg-elevated hover:text-ink sm:inline-flex"
                    >
                        <ArrowLeft class="h-3.5 w-3.5" />
                        Back to studio
                    </Link>
                    <ThemeToggle />
                </div>
            </div>
        </header>

        <main class="mx-auto w-full max-w-6xl px-5 pb-24 pt-9 sm:px-8 sm:pt-12">
            <section class="ambient overflow-hidden rounded-3xl border border-line bg-surface px-5 py-7 shadow-card sm:px-8 sm:py-9">
                <p class="font-alt text-[10.5px] font-semibold uppercase tracking-[0.28em] text-brand-600 dark:text-brand-300">
                    Unlisted code reference
                </p>
                <h1 class="mt-3 max-w-3xl text-[clamp(1.55rem,4.4vw,2.5rem)] leading-[1.08] tracking-[-0.03em]">
                    <span class="text-gradient">Lumora</span> codebase, explained.
                </h1>
                <p class="mt-3 max-w-2xl text-[13.5px] leading-relaxed text-subtle sm:text-sm">
                    Every directory, class and component, what it owns, and the details that are expensive to
                    rediscover. Anyone with this URL can read it; nothing in the app links here. Add an entry
                    whenever you add a file.
                </p>

                <div class="mt-5 flex flex-wrap gap-2">
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-line bg-elevated/70 px-2.5 py-1 text-[11px] text-subtle">
                        <Layers class="h-3 w-3" />
                        {{ totalEntries }} mapped references
                    </span>
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-line bg-elevated/70 px-2.5 py-1 text-[11px] text-subtle">
                        {{ tabs.length }} areas
                    </span>
                    <span class="inline-flex items-center gap-1.5 rounded-full border border-line bg-elevated/70 px-2.5 py-1 text-[11px] text-subtle">
                        <EyeOff class="h-3 w-3" />
                        noindex · not in sitemap · linked from nowhere
                    </span>
                </div>
            </section>

            <section class="sticky top-[65px] z-30 -mx-5 mt-6 bg-canvas/90 px-5 py-3 backdrop-blur sm:-mx-8 sm:px-8">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center">
                    <div class="relative flex-1">
                        <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-subtle" />
                        <label for="codex-search" class="sr-only">Search the code reference</label>
                        <input
                            id="codex-search"
                            ref="searchInput"
                            v-model="query"
                            type="search"
                            autocomplete="off"
                            spellcheck="false"
                            placeholder="Search files, methods, concepts…  (press /)"
                            class="w-full rounded-xl border border-line bg-surface py-2.5 pl-9 pr-9 text-[13px] text-ink shadow-sm outline-none transition placeholder:text-subtle/70 focus:border-brand-400/60 focus:ring-2 focus:ring-brand-500/20"
                        />
                        <button
                            v-if="searching"
                            type="button"
                            class="focus-ring absolute right-2 top-1/2 grid h-6 w-6 -translate-y-1/2 place-items-center rounded-md text-subtle hover:bg-elevated hover:text-ink"
                            aria-label="Clear search"
                            @click="clearSearch"
                        >
                            <X class="h-3.5 w-3.5" />
                        </button>
                    </div>

                    <div class="flex items-center gap-2">
                        <span v-if="searching" class="mr-auto text-[11.5px] text-subtle lg:mr-0">
                            {{ resultCount }} {{ resultCount === 1 ? 'match' : 'matches' }}
                        </span>
                        <template v-else>
                            <button
                                type="button"
                                class="focus-ring rounded-lg border border-line bg-surface px-2.5 py-1.5 text-[11.5px] font-medium text-subtle transition hover:text-ink"
                                @click="expandAll"
                            >
                                Expand all
                            </button>
                            <button
                                type="button"
                                class="focus-ring rounded-lg border border-line bg-surface px-2.5 py-1.5 text-[11.5px] font-medium text-subtle transition hover:text-ink"
                                @click="collapseAll"
                            >
                                Collapse all
                            </button>
                        </template>
                    </div>
                </div>

                <div class="-mx-1 mt-3 overflow-x-auto px-1 pb-0.5" role="tablist" aria-label="Code reference areas">
                    <div class="flex w-max gap-1.5">
                        <button
                            v-for="tab in tabs"
                            :key="tab.id"
                            type="button"
                            role="tab"
                            :aria-selected="activeTab === tab.id"
                            :aria-controls="activeTab === tab.id ? `panel-${tab.id}` : undefined"
                            :class="[
                                'focus-ring inline-flex items-center gap-2 rounded-xl border px-3 py-2 text-[12.5px] font-medium transition',
                                activeTab === tab.id
                                    ? 'border-brand-500/35 bg-brand-500/10 text-brand-700 shadow-sm dark:text-brand-200'
                                    : 'border-transparent text-subtle hover:border-line hover:bg-surface hover:text-ink',
                            ]"
                            @click="activeTab = tab.id"
                        >
                            <component :is="tabIcons[tab.icon]" class="h-3.5 w-3.5" />
                            {{ tab.label }}
                            <span
                                :class="[
                                    'rounded-full px-1.5 py-0.5 font-alt text-[10px]',
                                    activeTab === tab.id ? 'bg-brand-500/15 text-brand-700 dark:text-brand-200' : 'bg-elevated text-subtle',
                                ]"
                            >
                                {{ tabCounts[tab.id] }}
                            </span>
                        </button>
                    </div>
                </div>
            </section>

            <div
                :id="`panel-${activeTab}`"
                role="tabpanel"
                :aria-label="activeTabMeta?.label"
                class="mt-5 space-y-3"
            >
                <section
                    v-for="section in visibleSections"
                    :id="`section-${activeTab}-${section.id}`"
                    :key="section.id"
                    class="overflow-hidden rounded-2xl border border-line bg-surface shadow-card"
                >
                    <h2>
                        <button
                            type="button"
                            class="flex w-full items-start gap-3 px-4 py-4 text-left transition hover:bg-elevated/40 sm:px-5"
                            :aria-expanded="isOpen(section)"
                            :aria-controls="`panel-${activeTab}-${section.id}`"
                            @click="toggleSection(section)"
                        >
                            <span class="mt-0.5 grid h-7 w-7 shrink-0 place-items-center rounded-lg bg-brand-500/10 text-brand-600 dark:text-brand-300">
                                <ChevronDown
                                    class="h-4 w-4 transition-transform duration-200"
                                    :class="isOpen(section) ? 'rotate-0' : '-rotate-90'"
                                />
                            </span>

                            <span class="min-w-0 flex-1">
                                <span class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                    <span class="font-display text-[15px] font-semibold tracking-[-0.01em] text-ink">{{ section.title }}</span>
                                    <span class="rounded-full bg-elevated px-1.5 py-0.5 font-alt text-[10px] text-subtle">
                                        {{ section.visibleEntries.length }}
                                    </span>
                                </span>
                                <RichText tag="span" :text="section.summary" class="mt-1 block text-[12.5px] leading-relaxed text-subtle" />
                            </span>
                        </button>
                    </h2>

                    <div
                        v-show="isOpen(section)"
                        :id="`panel-${activeTab}-${section.id}`"
                        class="border-t border-line/70 px-3 pb-4 pt-3 sm:px-4"
                    >
                        <ul class="space-y-2.5">
                            <li
                                v-for="entry in section.visibleEntries"
                                :key="`${section.id}:${entry.path}`"
                                class="rounded-xl border border-line/70 bg-canvas/60 p-3.5 sm:p-4"
                            >
                                <div class="flex flex-wrap items-start gap-x-2 gap-y-1.5">
                                    <code class="min-w-0 break-all font-mono text-[12px] font-semibold text-ink">
                                        {{ entry.path }}
                                    </code>

                                    <span
                                        v-if="entry.lineCount"
                                        class="rounded-full border border-line bg-elevated px-1.5 py-0.5 font-alt text-[10px] text-subtle"
                                    >
                                        {{ entry.lineCount }} lines
                                    </span>

                                    <button
                                        v-if="entry.path.includes('/')"
                                        type="button"
                                        class="focus-ring ml-auto inline-flex items-center gap-1 rounded-md px-1.5 py-0.5 text-[10.5px] text-subtle transition hover:bg-elevated hover:text-ink"
                                        :aria-label="`Copy ${entry.path}`"
                                        @click="copyPath(entry.path)"
                                    >
                                        <Copy class="h-3 w-3" />
                                        Copy
                                    </button>
                                </div>

                                <RichText
                                    tag="p"
                                    :text="entry.role"
                                    class="mt-1.5 text-[12.5px] leading-relaxed text-subtle [overflow-wrap:anywhere]"
                                />

                                <div class="mt-4 border-t border-line/70 pt-4">
                                    <DocBlocks :blocks="blocksFor(entry)" />
                                </div>
                            </li>
                        </ul>
                    </div>
                </section>

                <div
                    v-if="visibleSections.length === 0"
                    class="rounded-2xl border border-dashed border-line bg-surface px-6 py-14 text-center"
                >
                    <p class="font-display text-[15px] font-semibold text-ink">No matches in {{ activeTabMeta?.label }}.</p>
                    <p class="mt-1 text-[12.5px] text-subtle">
                        Try a shorter term, or clear the search to browse every area.
                    </p>
                    <button
                        type="button"
                        class="focus-ring mt-4 rounded-lg border border-line bg-elevated px-3 py-1.5 text-[12px] font-medium text-ink"
                        @click="clearSearch"
                    >
                        Clear search
                    </button>
                </div>
            </div>

            <footer class="mt-10 flex flex-col gap-2 border-t border-line/70 pt-5 text-[11px] text-subtle sm:flex-row sm:items-center sm:justify-between">
                <p>
                    Unlisted reference · reachable only by knowing the URL ·
                    <code class="font-mono">/code-explanation</code>
                </p>
                <p class="font-alt uppercase tracking-[0.16em]">Not linked anywhere</p>
            </footer>
        </main>

        <ToastHost />
    </div>
</template>
