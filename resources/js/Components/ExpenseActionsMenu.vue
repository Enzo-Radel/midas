<script setup>
import { Link, router } from '@inertiajs/vue3';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faClone, faPen, faStar, faTrash } from '@fortawesome/free-solid-svg-icons';
import ActionMenu from './ActionMenu.vue';

const props = defineProps({
    expense: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['delete']);

// Favoritar e desfavoritar são imediatos e mantêm a posição na lista.
const toggleFavorite = (close) => {
    close();

    const url = `/despesas/${props.expense.id}/favorito`;

    if (props.expense.favorited) {
        router.delete(url, { preserveScroll: true });
    } else {
        router.post(url, {}, { preserveScroll: true });
    }
};

const remove = (close) => {
    // O foco volta ao botão do menu antes de abrir o diálogo, para o diálogo poder devolvê-lo.
    close();
    emit('delete');
};
</script>

<template>
    <ActionMenu :label="`Ações do gasto: ${expense.description || 'sem descrição'}`">
        <template #default="{ close }">
            <Link :href="`/despesas/${expense.id}/editar`" class="menu-item" role="menuitem">
                <FontAwesomeIcon class="menu-icon" :icon="faPen" />
                <span>Editar</span>
            </Link>
            <Link :href="`/despesas/${expense.id}/repetir`" class="menu-item" role="menuitem">
                <FontAwesomeIcon class="menu-icon" :icon="faClone" />
                <span>Repetir</span>
            </Link>
            <button type="button" class="menu-item" role="menuitem" @click="toggleFavorite(close)">
                <FontAwesomeIcon class="menu-icon" :icon="faStar" />
                <span>{{ expense.favorited ? 'Desfavoritar' : 'Favoritar' }}</span>
            </button>
            <button type="button" class="menu-item menu-item-danger" role="menuitem" @click="remove(close)">
                <FontAwesomeIcon class="menu-icon" :icon="faTrash" />
                <span>Excluir</span>
            </button>
        </template>
    </ActionMenu>
</template>
