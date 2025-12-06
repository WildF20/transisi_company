import { ref } from 'vue';

export type ToastVariant = 'success' | 'error' | 'info';

export interface ToastPayload {
    title?: string;
    message: string;
    variant?: ToastVariant;
    timeout?: number;
}

interface ToastInternal extends ToastPayload {
    id: number;
    variant: ToastVariant;
}

const toasts = ref<ToastInternal[]>([]);
const defaultTimeout = 3500;
let counter = 0;

const dismiss = (id: number) => {
    toasts.value = toasts.value.filter((toast) => toast.id !== id);
};

const showToast = (payload: ToastPayload) => {
    const id = ++counter;
    const variant = payload.variant ?? 'info';
    const timeout = payload.timeout ?? defaultTimeout;
    toasts.value.push({
        ...payload,
        id,
        variant,
    });

    if (timeout > 0) {
        window.setTimeout(() => dismiss(id), timeout);
    }

    return id;
};

export const useToast = () => ({
    toasts,
    showToast,
    dismiss,
});

