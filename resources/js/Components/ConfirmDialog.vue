<script setup>
import { nextTick, onBeforeUnmount, ref, useId, watch } from 'vue';

const props = defineProps({
    open: {
        type: Boolean,
        default: false,
    },
    title: {
        type: String,
        required: true,
    },
    message: {
        type: String,
        default: '',
    },
    confirmLabel: {
        type: String,
        default: 'Confirmar',
    },
    cancelLabel: {
        type: String,
        default: 'Cancelar',
    },
    processing: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['confirm', 'cancel']);

const id = useId();
const dialog = ref(null);
const cancelButton = ref(null);
let previouslyFocused = null;

// Durante o processamento o diálogo não pode ser fechado, para não parecer que a ação foi cancelada.
const cancel = () => {
    if (!props.processing) emit('cancel');
};

const onKeydown = (event) => {
    if (event.key === 'Escape') {
        cancel();
        return;
    }

    if (event.key !== 'Tab' || !dialog.value) return;

    // Mantém o foco dentro do diálogo enquanto ele está aberto.
    const focusable = [...dialog.value.querySelectorAll('button:not(:disabled)')];
    if (focusable.length === 0) return;

    const first = focusable[0];
    const last = focusable[focusable.length - 1];

    if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
    } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
    }
};

// O foco começa em "Cancelar", a opção segura para uma ação destrutiva, e volta
// para quem abriu o diálogo quando ele fecha.
watch(
    () => props.open,
    async (isOpen) => {
        if (isOpen) {
            previouslyFocused = document.activeElement;
            document.addEventListener('keydown', onKeydown);
            await nextTick();
            cancelButton.value?.focus();
        } else {
            document.removeEventListener('keydown', onKeydown);
            previouslyFocused?.focus?.();
            previouslyFocused = null;
        }
    },
);

onBeforeUnmount(() => document.removeEventListener('keydown', onKeydown));
</script>

<template>
    <Teleport to="body">
        <div v-if="open" class="overlay" @click.self="cancel">
            <div
                ref="dialog"
                class="dialog"
                role="alertdialog"
                aria-modal="true"
                :aria-labelledby="`${id}-title`"
                :aria-describedby="message ? `${id}-message` : undefined"
            >
                <h2 :id="`${id}-title`" class="dialog-title">{{ title }}</h2>
                <p v-if="message" :id="`${id}-message`" class="dialog-message">{{ message }}</p>

                <div class="dialog-actions">
                    <button
                        ref="cancelButton"
                        type="button"
                        class="button button-secondary"
                        :disabled="processing"
                        @click="cancel"
                    >
                        {{ cancelLabel }}
                    </button>
                    <button
                        type="button"
                        class="button button-danger"
                        :disabled="processing"
                        @click="emit('confirm')"
                    >
                        {{ confirmLabel }}
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<style scoped>
.overlay {
    position: fixed;
    inset: 0;
    z-index: 2000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    background-color: rgba(17, 24, 39, 0.5);
}

.dialog {
    width: 100%;
    max-width: 420px;
    padding: 1.5rem;
    background: white;
    border-radius: 16px;
    box-shadow: 0 16px 40px rgba(0, 0, 0, 0.2);
}

.dialog-title {
    margin: 0 0 0.5rem;
    font-size: 1.25rem;
    font-weight: 700;
    color: #1f2937;
}

.dialog-message {
    margin: 0 0 1.5rem;
    line-height: 1.6;
    color: #6b7280;
}

.dialog-actions {
    display: flex;
    gap: 0.75rem;
    justify-content: flex-end;
}

.button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.75rem 1.25rem;
    font-size: 1rem;
    font-weight: 500;
    border: 1px solid transparent;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.button-secondary {
    background-color: white;
    color: #374151;
    border-color: #d1d5db;
}

.button-secondary:hover:not(:disabled) {
    background-color: #f3f4f6;
    border-color: #9ca3af;
}

.button-danger {
    background-color: #ef4444;
    color: white;
}

.button-danger:hover:not(:disabled) {
    background-color: #dc2626;
    box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
    transform: translateY(-2px);
}

.button:active:not(:disabled) {
    transform: scale(0.98);
}

.button:focus-visible {
    outline: none;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.3);
}

.button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

@media (max-width: 768px) {
    .dialog-actions .button {
        flex: 1;
    }
}
</style>
