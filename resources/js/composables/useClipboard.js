import { useToast } from '@/composables/useToast';

export function useClipboard() {
    const toast = useToast();

    const copy = async (value, message = 'Copied to clipboard') => {
        try {
            await navigator.clipboard.writeText(value);
            toast.success(message, 'Copied');
            return true;
        } catch {
            toast.error('Your browser blocked clipboard access.', 'Copy failed');
            return false;
        }
    };

    return { copy };
}
