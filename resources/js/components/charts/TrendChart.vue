<script setup>
import { computed } from 'vue';

const props = defineProps({
    points: { type: Array, required: true },
    valueKey: { type: String, default: 'value' },
    secondaryKey: { type: String, default: null },
    height: { type: Number, default: 180 },
    formatValue: { type: Function, default: (value) => String(value) },
});

const width = 640;
const padding = { top: 12, right: 8, bottom: 26, left: 8 };

const labels = computed(() => props.points.map((point) => point.label));

const values = computed(() => props.points.map((point) => Number(point[props.valueKey] ?? 0)));

const secondaryValues = computed(() =>
    props.secondaryKey ? props.points.map((point) => Number(point[props.secondaryKey] ?? 0)) : [],
);

const max = computed(() => {
    const all = [...values.value, ...secondaryValues.value];
    const highest = Math.max(1, ...all);

    return highest * 1.15;
});

const innerWidth = computed(() => width - padding.left - padding.right);
const innerHeight = computed(() => props.height - padding.top - padding.bottom);

const x = (index) => {
    if (props.points.length <= 1) return padding.left + innerWidth.value / 2;

    return padding.left + (index / (props.points.length - 1)) * innerWidth.value;
};

const y = (value) => padding.top + innerHeight.value - (value / max.value) * innerHeight.value;

const toPath = (series) =>
    series
        .map((value, index) => `${index === 0 ? 'M' : 'L'} ${x(index).toFixed(2)} ${y(value).toFixed(2)}`)
        .join(' ');

const linePath = computed(() => toPath(values.value));
const secondaryPath = computed(() => (props.secondaryKey ? toPath(secondaryValues.value) : null));

const areaPath = computed(() => {
    if (!props.points.length) return '';

    const bottom = padding.top + innerHeight.value;

    return `${linePath.value} L ${x(props.points.length - 1).toFixed(2)} ${bottom} L ${padding.left} ${bottom} Z`;
});

const gridLines = computed(() => [0, 0.25, 0.5, 0.75, 1].map((ratio) => padding.top + innerHeight.value * ratio));

const tickIndices = computed(() => {
    const count = props.points.length;

    if (count <= 4) return props.points.map((_, index) => index);

    const step = Math.ceil(count / 4);

    return props.points.map((_, index) => index).filter((index) => index % step === 0 || index === count - 1);
});

const hasData = computed(() => values.value.some((value) => value > 0) || secondaryValues.value.some((value) => value > 0));
</script>

<template>
    <div class="w-full">
        <svg
            :viewBox="`0 0 ${width} ${height}`"
            class="h-auto w-full overflow-visible"
            role="img"
            aria-label="Trend over time"
            preserveAspectRatio="none"
        >
            <defs>
                <linearGradient id="lumora-area" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stop-color="#8b5cf6" stop-opacity="0.34" />
                    <stop offset="100%" stop-color="#8b5cf6" stop-opacity="0" />
                </linearGradient>
            </defs>

            <line
                v-for="(line, index) in gridLines"
                :key="index"
                :x1="padding.left"
                :x2="width - padding.right"
                :y1="line"
                :y2="line"
                stroke="var(--chart-grid)"
                stroke-width="1"
                stroke-dasharray="3 4"
            />

            <template v-if="hasData">
                <path :d="areaPath" fill="url(#lumora-area)" />

                <path
                    v-if="secondaryPath"
                    :d="secondaryPath"
                    fill="none"
                    stroke="var(--color-accent)"
                    stroke-width="1.8"
                    stroke-linecap="round"
                    stroke-dasharray="4 4"
                    opacity="0.85"
                />

                <path
                    :d="linePath"
                    fill="none"
                    stroke="#8b5cf6"
                    stroke-width="2.2"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                />

                <g v-for="(value, index) in values" :key="`dot-${index}`">
                    <circle
                        v-if="value > 0"
                        :cx="x(index)"
                        :cy="y(value)"
                        r="2.6"
                        fill="var(--surface)"
                        stroke="#8b5cf6"
                        stroke-width="2"
                    >
                        <title>{{ labels[index] }} · {{ formatValue(value) }}</title>
                    </circle>
                </g>
            </template>

            <text
                v-for="index in tickIndices"
                :key="`label-${index}`"
                :x="x(index)"
                :y="height - 6"
                text-anchor="middle"
                class="fill-current text-[9px]"
                style="fill: var(--subtle)"
            >
                {{ labels[index] }}
            </text>
        </svg>

        <p v-if="!hasData" class="-mt-14 pb-6 text-center text-[11.5px] text-subtle">
            No settled activity in this range yet.
        </p>
    </div>
</template>
