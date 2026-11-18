import { onBeforeUnmount, ref } from 'vue';
import { getJson } from '@/lib/http';
import { generationStatusUrl } from '@/lib/routes';

/**
 * Polls the status endpoint while a generation is in flight. Webhooks remain
 * the primary path; this is the documented fallback.
 */
export function useGenerationPolling(interval = 4500) {
    const polling = ref(false);
    let timeout = null;
    let stopped = true;

    const stop = () => {
        stopped = true;
        polling.value = false;

        if (timeout) {
            clearTimeout(timeout);
            timeout = null;
        }
    };

    const schedule = (callback) => {
        if (stopped) return;

        timeout = window.setTimeout(async () => {
            try {
                await callback();
            } finally {
                schedule(callback);
            }
        }, interval);
    };

    const start = async (uuid, onUpdate) => {
        if (!uuid) return;

        stop();
        stopped = false;
        polling.value = true;

        const tick = async () => {
            if (stopped) return;

            try {
                const payload = await getJson(generationStatusUrl(uuid));
                await onUpdate?.(payload);

                if (payload?.generation?.is_terminal) {
                    stop();
                }
            } catch (error) {
                // Transient network errors are expected; keep polling.
                if (error?.status === 404) {
                    stop();
                }
            }
        };

        await tick();
        schedule(tick);
    };

    onBeforeUnmount(stop);

    return { start, stop, polling };
}
