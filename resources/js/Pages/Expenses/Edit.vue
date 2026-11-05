<script setup>
import AppLayout from '../../Layouts/AppLayout.vue';
import ExpenseForm from '../../Components/ExpenseForm.vue';

defineProps({
    expense: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <AppLayout>
        <div class="page">
            <h1 class="page-title">Editar gasto</h1>
            <ExpenseForm :expense="expense" />
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
