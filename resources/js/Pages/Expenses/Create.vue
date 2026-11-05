<script setup>
import AppLayout from '../../Layouts/AppLayout.vue';
import ExpenseForm from '../../Components/ExpenseForm.vue';

defineProps({
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
            <h1 class="page-title">Novo gasto</h1>
            <ExpenseForm :categories="categories" :tags="tags" />
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
