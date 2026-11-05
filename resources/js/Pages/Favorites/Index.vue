<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faArrowLeft, faPen, faStar, faTrash } from '@fortawesome/free-solid-svg-icons';
import AppLayout from '../../Layouts/AppLayout.vue';
import ActionMenu from '../../Components/ActionMenu.vue';
import ConfirmDialog from '../../Components/ConfirmDialog.vue';
import { formatCents } from '../../utils/money.js';

defineProps({
    favorites: {
        type: Array,
        required: true,
    },
});

const favoriteToRemove = ref(null);
const removing = ref(false);

const confirmRemove = () => {
    router.delete(`/favoritos/${favoriteToRemove.value.id}`, {
        preserveScroll: true,
        onStart: () => (removing.value = true),
        onFinish: () => {
            removing.value = false;
            favoriteToRemove.value = null;
        },
    });
};

// Favorito sem nome (criado de um gasto sem descrição) é identificado pelo valor e pela categoria.
const title = (favorite) => {
    if (favorite.name) return favorite.name;

    return favorite.category ? `${formatCents(favorite.amount)} · ${favorite.category}` : formatCents(favorite.amount);
};
</script>

<template>
    <AppLayout>
        <div class="page">
            <div class="page-header">
                <Link href="/despesas" class="back" aria-label="Voltar para as despesas" title="Voltar para as despesas">
                    <FontAwesomeIcon :icon="faArrowLeft" />
                </Link>
                <h1 class="page-title">Favoritos</h1>
            </div>

            <p v-if="favorites.length > 0" class="hint">Toque em um favorito para registrar um gasto igual, com a data de hoje.</p>

            <div v-if="favorites.length === 0" class="empty">
                <FontAwesomeIcon class="empty-icon" :icon="faStar" />
                <p class="empty-title">Nenhum favorito ainda</p>
                <p class="empty-text">
                    Para favoritar, abra o menu de um gasto na lista e escolha "Favoritar", ou marque "Salvar nos
                    favoritos" ao registrar um gasto.
                </p>
                <Link href="/despesas" class="button button-primary">Ver despesas</Link>
            </div>

            <ul v-else class="list">
                <li v-for="favorite in favorites" :key="favorite.id" class="item">
                    <Link :href="`/favoritos/${favorite.id}/usar`" class="card">
                        <span class="card-main">
                            <span class="card-title" :class="{ 'card-title-fallback': !favorite.name }">{{ title(favorite) }}</span>
                            <span v-if="(favorite.name && favorite.category) || favorite.tags.length > 0" class="card-labels">
                                <span v-if="favorite.name && favorite.category" class="card-category">{{ favorite.category }}</span>
                                <span v-for="tag in favorite.tags" :key="tag" class="card-tag">{{ tag }}</span>
                            </span>
                        </span>
                        <span class="card-amount">{{ formatCents(favorite.amount) }}</span>
                    </Link>
                    <ActionMenu :label="`Ações do favorito: ${title(favorite)}`">
                        <template #default="{ close }">
                            <Link :href="`/favoritos/${favorite.id}/editar`" class="menu-item" role="menuitem">
                                <FontAwesomeIcon class="menu-icon" :icon="faPen" />
                                <span>Editar</span>
                            </Link>
                            <button
                                type="button"
                                class="menu-item menu-item-danger"
                                role="menuitem"
                                @click="close(); favoriteToRemove = favorite"
                            >
                                <FontAwesomeIcon class="menu-icon" :icon="faTrash" />
                                <span>Remover</span>
                            </button>
                        </template>
                    </ActionMenu>
                </li>
            </ul>
        </div>

        <ConfirmDialog
            :open="favoriteToRemove !== null"
            title="Remover este favorito?"
            :message="favoriteToRemove ? `${title(favoriteToRemove)} será removido dos favoritos. Os gastos já registrados não são afetados.` : ''"
            confirm-label="Remover"
            :processing="removing"
            @confirm="confirmRemove"
            @cancel="favoriteToRemove = null"
        />
    </AppLayout>
</template>

<style scoped>
.page {
    max-width: 720px;
    margin: 0 auto;
}

.page-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.page-title {
    margin: 0;
    font-size: 1.75rem;
    font-weight: 700;
    color: #1f2937;
}

.back {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 44px;
    height: 44px;
    color: #6b7280;
    border-radius: 8px;
    text-decoration: none;
    transition: all 0.2s ease;
}

.back:hover {
    color: #4f46e5;
    background-color: #eef2ff;
}

.back:active {
    background-color: #e0e7ff;
    transform: scale(0.95);
}

.back:focus-visible {
    outline: none;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.3);
}

.hint {
    margin: 0 0 1rem;
    color: #6b7280;
    line-height: 1.6;
}

.button {
    display: inline-flex;
    align-items: center;
    padding: 0.75rem 1.25rem;
    font-size: 1rem;
    font-weight: 500;
    border-radius: 8px;
    text-decoration: none;
    transition: all 0.2s ease;
}

.button-primary {
    background-color: #6366f1;
    color: white;
}

.button-primary:hover {
    background-color: #4f46e5;
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
    transform: translateY(-2px);
}

.button-primary:active {
    transform: scale(0.98);
}

.button-primary:focus-visible {
    outline: none;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.3);
}

.empty {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 14px;
    padding: 2.5rem 1.5rem;
    text-align: center;
}

.empty-icon {
    margin-bottom: 0.75rem;
    font-size: 1.75rem;
    color: #d97706;
}

.empty-title {
    margin: 0 0 0.5rem;
    font-size: 1.125rem;
    font-weight: 600;
    color: #1f2937;
}

.empty-text {
    margin: 0 0 1.5rem;
    color: #6b7280;
    line-height: 1.6;
}

.list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    margin: 0;
    padding: 0;
    list-style: none;
}

.item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.card {
    flex: 1;
    min-width: 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    min-height: 44px;
    padding: 1rem 1.25rem;
    color: inherit;
    text-decoration: none;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    transition: all 0.2s ease;
}

.card:hover {
    border-color: #c7d2fe;
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.08);
    transform: translateY(-2px);
}

.card:active {
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
    transform: translateY(0);
}

.card:focus-visible {
    outline: none;
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.3);
}

.card-main {
    display: flex;
    flex: 1;
    flex-direction: column;
    gap: 0.375rem;
    min-width: 0;
}

.card-title {
    font-weight: 500;
    color: #1f2937;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.card-title-fallback {
    color: #374151;
}

.card-labels {
    display: flex;
    flex-wrap: wrap;
    gap: 0.375rem;
}

.card-category {
    padding: 0.125rem 0.5rem;
    font-size: 0.75rem;
    font-weight: 600;
    color: #4f46e5;
    background-color: #eef2ff;
    border-radius: 8px;
}

.card-tag {
    padding: 0.125rem 0.5rem;
    font-size: 0.75rem;
    font-weight: 500;
    color: #4b5563;
    background-color: #f3f4f6;
    border-radius: 8px;
}

.card-amount {
    font-weight: 600;
    color: #1f2937;
    white-space: nowrap;
}

@media (max-width: 768px) {
    .page-title {
        font-size: 1.5rem;
    }
}
</style>
