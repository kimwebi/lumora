/**
 * Display formatters. Monetary values arrive as integer micro-dollars and are
 * formatted with integer math so no float rounding sneaks into the UI.
 */

export function formatUsd(micros, maximumFractionDigits = 4) {
    const value = Math.round(Number(micros) || 0);
    const negative = value < 0;
    const absolute = Math.abs(value);
    const whole = Math.floor(absolute / 1_000_000);
    let fraction = String(absolute % 1_000_000).padStart(6, '0').slice(0, maximumFractionDigits);
    fraction = fraction.replace(/0+$/, '');

    if (fraction.length < 2) {
        fraction = `${fraction}00`.slice(0, 2);
    }

    return `${negative ? '-' : ''}$${whole.toLocaleString('en-US')}.${fraction}`;
}

export function formatCredits(micros, maximumFractionDigits = 2) {
    const value = Math.abs(Math.round(Number(micros) || 0));
    const whole = Math.floor(value / 10_000);
    let fraction = String(value % 10_000).padStart(4, '0').slice(0, maximumFractionDigits);
    fraction = fraction.replace(/0+$/, '');

    if (fraction.length === 0) {
        return whole.toLocaleString('en-US');
    }

    return `${whole.toLocaleString('en-US')}.${fraction}`;
}

export function formatNumber(value) {
    return new Intl.NumberFormat('en-US').format(Number(value) || 0);
}

export function formatDate(iso, options = {}) {
    if (!iso) return '—';

    return new Intl.DateTimeFormat('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        ...options,
    }).format(new Date(iso));
}

export function formatDateTime(iso) {
    if (!iso) return '—';

    return new Intl.DateTimeFormat('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    }).format(new Date(iso));
}

export function formatTime(iso) {
    if (!iso) return '—';

    return new Intl.DateTimeFormat('en-US', {
        hour: 'numeric',
        minute: '2-digit',
    }).format(new Date(iso));
}

export function formatRelative(iso) {
    if (!iso) return '—';

    const date = new Date(iso).getTime();
    const diffSeconds = Math.round((date - Date.now()) / 1000);
    const formatter = new Intl.RelativeTimeFormat('en-US', { numeric: 'auto' });

    const thresholds = [
        ['year', 31_536_000],
        ['month', 2_592_000],
        ['week', 604_800],
        ['day', 86_400],
        ['hour', 3_600],
        ['minute', 60],
    ];

    for (const [unit, seconds] of thresholds) {
        if (Math.abs(diffSeconds) >= seconds) {
            return formatter.format(Math.round(diffSeconds / seconds), unit);
        }
    }

    return 'just now';
}

export const statusMeta = {
    pending: {
        label: 'Queued',
        tone: 'pending',
        description: 'Waiting in the render queue',
    },
    processing: {
        label: 'Processing',
        tone: 'processing',
        description: 'The model is painting your image',
    },
    succeeded: {
        label: 'Succeeded',
        tone: 'success',
        description: 'Ready to download',
    },
    failed: {
        label: 'Failed',
        tone: 'danger',
        description: 'Credits refunded',
    },
};

export function statusFor(status) {
    return statusMeta[status] ?? { label: status, tone: 'neutral', description: '' };
}

export function pluralize(count, singular, plural = `${singular}s`) {
    return `${formatNumber(count)} ${Number(count) === 1 ? singular : plural}`;
}
