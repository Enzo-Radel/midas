<script setup>
import { Link } from '@inertiajs/vue3';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faPlus } from '@fortawesome/free-solid-svg-icons';
import AppLayout from '../../Layouts/AppLayout.vue';
import { formatCents } from '../../utils/money.js';
import { formatDate } from '../../utils/date.js';

defineProps({
    expenses: {
        type: Array,
        required: true,
    },
});
</script>

<template>
    <AppLayout>
        <div class="page">
            <div class="page-header">
                <h1 class="page-title">Despesas</h1>
                <Link href="/despesas/nova" class="button button-primary">
                    <FontAwesomeIcon :icon="faPlus" />
                    <span>Novo gasto</span>
                </Link>
            </div>

            <div v-if="expenses.length === 0" class="empty">
                <p class="empty-title">Nenhum gasto registrado ainda</p>
                <p class="empty-text">Registre seu primeiro gasto para começar a acompanhar para onde vai o seu dinheiro.</p>
                <Link href="/despesas/nova" class="button button-primary">Registrar primeiro gasto</Link>
            </div>

            <ul v-else class="list">
                <li v-for="expense in expenses" :key="expense.id" class="item">
                    <div class="item-main">
                        <span class="item-description" :class="{ 'item-description-empty': !expense.description }">
                            {{ expense.description || 'Sem descrição' }}
                        </span>
                        <span class="item-date">{{ formatDate(expense.date) }}</span>
                    </div>
                    <span class="item-amount">{{ formatCents(expense.amount) }}</span>
                </li>
            </ul>
        </div>
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
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.page-title {
    margin: 0;
    font-size: 1.75rem;
    font-weight: 700;
    color: #1f2937;
}

.button {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.25rem;
    font-size: 1rem;
    font-weight: 500;
    border-radius: 8px;
    text-decoration: none;
    cursor: pointer;
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
    list-style: none;
    margin: 0;
    padding: 0;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 14px;
    overflow: hidden;
}

.item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #f3f4f6;
}

.item:last-child {
    border-bottom: none;
}

.item-main {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    min-width: 0;
}

.item-description {
    font-weight: 500;
    color: #1f2937;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.item-description-empty {
    color: #6b7280;
    font-style: italic;
}

.item-date {
    font-size: 0.875rem;
    color: #6b7280;
}

.item-amount {
    font-weight: 600;
    color: #1f2937;
    white-space: nowrap;
}

@media (max-width: 768px) {
    .page-title {
        font-size: 1.5rem;
    }

    .item {
        padding: 0.875rem 1rem;
    }
}
</style>
