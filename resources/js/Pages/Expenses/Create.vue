<script setup>
import { computed } from 'vue';
import AppLayout from '../../Layouts/AppLayout.vue';
import ExpenseForm from '../../Components/ExpenseForm.vue';

const props = defineProps({
    /** Dados de um gasto a repetir; ausente no cadastro em branco. */
    prefill: {
        type: Object,
        default: null,
    },
    /** De onde vem o preenchimento: 'expense' (repetir um gasto) ou 'favorite' (usar um favorito). */
    source: {
        type: String,
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

const title = computed(() => {
    if (props.source === 'favorite') return 'Usar favorito';

    return props.prefill ? 'Repetir gasto' : 'Novo gasto';
});

// Cancelar volta para a tela de onde a pessoa veio.
const cancelHref = computed(() => (props.source === 'favorite' ? '/favoritos' : '/despesas'));
</script>

<template>
    <AppLayout>
        <div class="page">
            <h1 class="page-title">{{ title }}</h1>
            <ExpenseForm :prefill="prefill" :categories="categories" :tags="tags" :cancel-href="cancelHref" />
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
