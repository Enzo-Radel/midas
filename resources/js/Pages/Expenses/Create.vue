<script setup>
import AppLayout from '../../Layouts/AppLayout.vue';
import ExpenseForm from '../../Components/ExpenseForm.vue';

defineProps({
    /** Dados de um gasto a repetir; ausente no cadastro em branco. */
    prefill: {
        type: Object,
        default: null,
    },
    categories: {
        type: Array,
        required: true,
    },
    tags: {
        type: Array,
        required: true,
    },
});
</script>

<template>
    <AppLayout>
        <div class="page">
            <h1 class="page-title">{{ prefill ? 'Repetir gasto' : 'Novo gasto' }}</h1>
            <ExpenseForm :prefill="prefill" :categories="categories" :tags="tags" />
        </div>
    </AppLayout>
</template>

<style scoped>
.page {
    max-width: 560px;
    margin: 0 auto;
}

.page-title {
    margin: 0 0 1.5rem;
    font-size: 1.75rem;
    font-weight: 700;
    color: #1f2937;
}
</style>
