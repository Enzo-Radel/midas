<script setup>
import { nextTick, onBeforeUnmount, ref, useId, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faClone, faEllipsisVertical, faPen, faStar, faTrash } from '@fortawesome/free-solid-svg-icons';

const props = defineProps({
    expense: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['delete']);

const id = useId();
const open = ref(false);
const root = ref(null);
const trigger = ref(null);

const label = () => props.expense.description || 'sem descrição';

const close = () => {
    open.value = false;
};

const onClickOutside = (event) => {
    if (root.value && !root.value.contains(event.target)) close();
};

const items = () => [...(root.value?.querySelectorAll('[role="menuitem"]') ?? [])];

const onKeydown = (event) => {
    if (event.key === 'Escape') {
        close();
        trigger.value?.focus();
        return;
    }

    // Tab sai do menu: fecha e deixa o foco seguir a partir do botão do menu.
    if (event.key === 'Tab') {
        close();
        trigger.value?.focus();
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
        root.value?.querySelector('[role="menuitem"]')?.focus();
    } else {
        document.removeEventListener('click', onClickOutside);
        document.removeEventListener('keydown', onKeydown);
    }
});

onBeforeUnmount(() => {
    document.removeEventListener('click', onClickOutside);
    document.removeEventListener('keydown', onKeydown);
});

// Favoritar e desfavoritar são imediatos e mantêm a posição na lista.
const toggleFavorite = () => {
    close();
    trigger.value?.focus();

    const url = `/despesas/${props.expense.id}/favorito`;

    if (props.expense.favorited) {
        router.delete(url, { preserveScroll: true });
    } else {
        router.post(url, {}, { preserveScroll: true });
    }
};

const remove = () => {
    // O item do menu some ao fechar; o foco volta ao botão do menu para o diálogo poder devolvê-lo.
    close();
    trigger.value?.focus();
    emit('delete');
};
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
            :aria-label="`Ações do gasto: ${label()}`"
            @click="open = !open"
        >
            <FontAwesomeIcon :icon="faEllipsisVertical" />
        </button>

        <div v-if="open" :id="`${id}-menu`" class="menu-list" role="menu">
            <Link :href="`/despesas/${expense.id}/editar`" class="menu-item" role="menuitem">
                <FontAwesomeIcon class="menu-icon" :icon="faPen" />
                <span>Editar</span>
            </Link>
            <Link :href="`/despesas/${expense.id}/repetir`" class="menu-item" role="menuitem">
                <FontAwesomeIcon class="menu-icon" :icon="faClone" />
                <span>Repetir</span>
            </Link>
            <button type="button" class="menu-item" role="menuitem" @click="toggleFavorite">
                <FontAwesomeIcon class="menu-icon" :icon="faStar" />
                <span>{{ expense.favorited ? 'Desfavoritar' : 'Favoritar' }}</span>
            </button>
            <button type="button" class="menu-item menu-item-danger" role="menuitem" @click="remove">
                <FontAwesomeIcon class="menu-icon" :icon="faTrash" />
                <span>Excluir</span>
            </button>
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
.menu-item:focus-visible {
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

.menu-item {
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

.menu-item:hover {
    color: #4f46e5;
    background-color: #eef2ff;
}

.menu-item:active {
    background-color: #e0e7ff;
}

.menu-item-danger:hover {
    color: #dc2626;
    background-color: #fef2f2;
}

.menu-item-danger:active {
    background-color: #fee2e2;
}

.menu-icon {
    width: 1rem;
}
</style>
