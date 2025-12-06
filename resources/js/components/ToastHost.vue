<script setup lang="ts">
import { useToast } from '@/composables/useToast';

const { toasts, dismiss } = useToast();

const variants = {
    success: 'bg-green-50 border-green-200 text-green-900',
    error: 'bg-red-50 border-red-200 text-red-900',
    info: 'bg-blue-50 border-blue-200 text-blue-900',
} as const;

const iconFor = (variant: keyof typeof variants) =>
    variant === 'success'
        ? 'M5 10.5 8.5 14 15 6'
        : variant === 'error'
          ? 'M6 6l8 8M14 6l-8 8'
          : 'M12 6v4m0 4h.01';
</script>

<template>
    <Teleport to="body">
        <div class="pointer-events-none fixed right-4 top-4 z-[9999] flex max-w-md flex-col gap-3">
            <TransitionGroup name="toast">
                <div
                    v-for="toast in toasts"
                    :key="toast.id"
                    class="pointer-events-auto flex items-start gap-3 rounded-lg border p-3 shadow-lg ring-1 ring-black/5"
                    :class="variants[toast.variant]"
                >
                    <svg class="mt-1 h-5 w-5 flex-shrink-0" viewBox="0 0 20 20" fill="none" stroke="currentColor">
                        <path :d="iconFor(toast.variant)" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" />
                    </svg>
                    <div class="flex-1">
                        <p class="text-sm font-semibold leading-tight">{{ toast.title ?? (toast.variant === 'error' ? 'Request failed' : 'Notice') }}</p>
                        <p class="mt-1 text-sm leading-snug">{{ toast.message }}</p>
                    </div>
                    <button class="rounded p-1 text-sm font-semibold opacity-60 hover:opacity-100" type="button" aria-label="Dismiss" @click="dismiss(toast.id)">×</button>
                </div>
            </TransitionGroup>
        </div>
    </Teleport>
</template>

<style scoped>
.toast-enter-active,
.toast-leave-active {
    transition: all 0.2s ease, opacity 0.15s ease;
}
.toast-enter-from,
.toast-leave-to {
    opacity: 0;
    transform: translateY(-8px);
}
</style>
