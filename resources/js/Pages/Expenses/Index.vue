<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faPen, faPlus, faTrash } from '@fortawesome/free-solid-svg-icons';
import AppLayout from '../../Layouts/AppLayout.vue';
import ConfirmDialog from '../../Components/ConfirmDialog.vue';
import { formatCents } from '../../utils/money.js';
import { formatDayLabel } from '../../utils/date.js';

defineProps({
    days: {
        type: Array,
        required: true,
    },
});

const expenseToDelete = ref(null);
const deleting = ref(false);

const confirmDelete = () => {
    router.delete(`/despesas/${expenseToDelete.value.id}`, {
        preserveScroll: true,
        onStart: () => (deleting.value = true),
        onFinish: () => {
            deleting.value = false;
            expenseToDelete.value = null;
        },
    });
};
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

            <div v-if="days.length === 0" class="empty">
                <p class="empty-title">Nenhum gasto registrado ainda</p>
                <p class="empty-text">Registre seu primeiro gasto para começar a acompanhar para onde vai o seu dinheiro.</p>
                <Link href="/despesas/nova" class="button button-primary">Registrar primeiro gasto</Link>
            </div>

            <template v-else>
                <section v-for="day in days" :key="day.date" class="day">
                    <header class="day-header">
                        <h2 class="day-title">{{ formatDayLabel(day.date) }}</h2>
                        <span class="day-total">{{ formatCents(day.total) }}</span>
                    </header>

                    <ul class="list">
                        <li v-for="expense in day.expenses" :key="expense.id" class="item">
                            <span class="item-main">
                                <span class="item-description" :class="{ 'item-description-empty': !expense.description }">
                                    {{ expense.description || 'Sem descrição' }}
                                </span>
                                <span v-if="expense.category" class="item-category">{{ expense.category }}</span>
                            </span>
                            <span class="item-amount">{{ formatCents(expense.amount) }}</span>
                            <Link
                                :href="`/despesas/${expense.id}/editar`"
                                class="item-action"
                                title="Editar gasto"
                                :aria-label="`Editar gasto: ${expense.description || 'sem descrição'}`"
                            >
                                <FontAwesomeIcon :icon="faPen" />
                            </Link>
                            <button
                                type="button"
                                class="item-action item-action-danger"
                                title="Excluir gasto"
                                :aria-label="`Excluir gasto: ${expense.description || 'sem descrição'}`"
                                @click="expenseToDelete = expense"
                            >
                                <FontAwesomeIcon :icon="faTrash" />
                            </button>
                        </li>
                    </ul>
                </section>
            </template>
        </div>

        <ConfirmDialog
            :open="expenseToDelete !== null"
            title="Excluir este gasto?"
            :message="expenseToDelete ? `${expenseToDelete.description || 'Gasto sem descrição'} (${formatCents(expenseToDelete.amount)}) será removido e não poderá ser recuperado.` : ''"
            confirm-label="Excluir"
            :processing="deleting"
            @confirm="confirmDelete"
            @cancel="expenseToDelete = null"
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

.day {
    margin-bottom: 1.5rem;
}

.day-header {
    display: flex;
    align-items: baseline;
    justify-content: space-between;
    gap: 1rem;
    padding: 0 0.25rem 0.5rem;
}

.day-title {
    margin: 0;
    font-size: 0.875rem;
    font-weight: 600;
    color: #6b7280;
}

.day-total {
    font-size: 0.875rem;
    font-weight: 600;
    color: #6b7280;
    white-space: nowrap;
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
    flex: 1;
    flex-direction: column;
    gap: 0.25rem;
    min-width: 0;
}

.item-category {
    align-self: flex-start;
    padding: 0.125rem 0.5rem;
    font-size: 0.75rem;
    font-weight: 600;
    color: #4f46e5;
    background-color: #eef2ff;
    border-radius: 8px;
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

.item-amount {
    font-weight: 600;
    color: #1f2937;
    white-space: nowrap;
}

.item-action {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 44px;
    height: 44px;
    flex-shrink: 0;
    color: #6b7280;
    background-color: transparent;
    border-radius: 8px;
    text-decoration: none;
    transition: all 0.2s ease;
}

.item-action:hover {
    color: #4f46e5;
    background-color: #eef2ff;
}

.item-action:active {
    background-color: #e0e7ff;
    transform: scale(0.95);
}

.item-action-danger {
    border: none;
    cursor: pointer;
    font-size: inherit;
}

.item-action-danger:hover {
    color: #dc2626;
    background-color: #fef2f2;
}

.item-action-danger:active {
    background-color: #fee2e2;
}

.item-action:focus-visible {
    outline: none;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.3);
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
