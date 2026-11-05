<script setup>
import { nextTick, onBeforeUnmount, ref, useId, watch } from 'vue';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faEllipsisVertical } from '@fortawesome/free-solid-svg-icons';

/**
 * Menu de três pontos. Os itens vêm pelo slot, cada um um link ou botão com class="menu-item" e
 * role="menuitem". Dentro do item: ícone com class="menu-icon" e, em ação destrutiva, a classe
 * extra "menu-item-danger" no item. O slot recebe `close`, que fecha o menu e devolve o foco ao botão.
 */
defineProps({
    /** Rótulo do botão do menu, para leitores de tela. */
    label: {
        type: String,
        required: true,
    },
});

const id = useId();
const open = ref(false);
const root = ref(null);
const trigger = ref(null);

const close = () => {
    open.value = false;
};

// Fecha o menu e devolve o foco ao botão: o item clicado some da tela ao fechar.
const closeAndFocus = () => {
    close();
    trigger.value?.focus();
};

const onClickOutside = (event) => {
    if (root.value && !root.value.contains(event.target)) close();
};

const items = () => [...(root.value?.querySelectorAll('[role="menuitem"]') ?? [])];

const onKeydown = (event) => {
    // Esc e Tab saem do menu; o foco volta para o botão.
    if (event.key === 'Escape' || event.key === 'Tab') {
        closeAndFocus();
        return;
    }

    const list = items();
    if (list.length === 0) return;

    const current = list.indexOf(document.activeElement);
    let next = null;

    if (event.key === 'ArrowDown') next = (current + 1) % list.length;
    else if (event.key === 'ArrowUp') next = (current - 1 + list.length) % list.length;
    else if (event.key === 'Home') next = 0;
    else if (event.key === 'End') next = list.length - 1;

    if (next !== null) {
        event.preventDefault();
        list[next].focus();
    }
};

watch(open, async (isOpen) => {
    if (isOpen) {
        document.addEventListener('click', onClickOutside);
        document.addEventListener('keydown', onKeydown);
        await nextTick();
        items()[0]?.focus();
    } else {
        document.removeEventListener('click', onClickOutside);
        document.removeEventListener('keydown', onKeydown);
    }
});

onBeforeUnmount(() => {
    document.removeEventListener('click', onClickOutside);
    document.removeEventListener('keydown', onKeydown);
});
</script>

<template>
    <div ref="root" class="menu">
        <button
            ref="trigger"
            type="button"
            class="menu-trigger"
            aria-haspopup="menu"
            :aria-expanded="open"
            :aria-controls="`${id}-menu`"
            :aria-label="label"
            @click="open = !open"
        >
            <FontAwesomeIcon :icon="faEllipsisVertical" />
        </button>

        <div v-if="open" :id="`${id}-menu`" class="menu-list" role="menu">
            <slot :close="closeAndFocus" />
        </div>
    </div>
</template>

<style scoped>
.menu {
    position: relative;
    flex-shrink: 0;
}

.menu-trigger {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 44px;
    height: 44px;
    color: #6b7280;
    background-color: transparent;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.menu-trigger:hover,
.menu-trigger[aria-expanded='true'] {
    color: #4f46e5;
    background-color: #eef2ff;
}

.menu-trigger:active {
    background-color: #e0e7ff;
    transform: scale(0.95);
}

.menu-trigger:focus-visible,
.menu-list :slotted(.menu-item:focus-visible) {
    outline: none;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.3);
}

.menu-list {
    position: absolute;
    top: calc(100% + 0.25rem);
    right: 0;
    z-index: 20;
    display: flex;
    flex-direction: column;
    min-width: 11rem;
    padding: 0.25rem;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
}

.menu-list :slotted(.menu-item) {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    width: 100%;
    min-height: 44px;
    padding: 0 0.75rem;
    font-size: 1rem;
    font-weight: 500;
    color: #374151;
    text-align: left;
    text-decoration: none;
    background: transparent;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.menu-list :slotted(.menu-item:hover) {
    color: #4f46e5;
    background-color: #eef2ff;
}

.menu-list :slotted(.menu-item:active) {
    background-color: #e0e7ff;
}

.menu-list :slotted(.menu-item-danger:hover) {
    color: #dc2626;
    background-color: #fef2f2;
}

.menu-list :slotted(.menu-item-danger:active) {
    background-color: #fee2e2;
}

.menu-list :slotted(.menu-icon) {
    width: 1rem;
}
</style>
