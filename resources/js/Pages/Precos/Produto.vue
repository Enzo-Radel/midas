<script setup>
import { ref } from 'vue';
import axios from 'axios';
import AppLayout from '../../Layouts/AppLayout.vue';

const props = defineProps({
    produtoId: {
        type: Number,
        required: true,
    },
});

const produto = ref(null);
const compras = ref([]);

axios.get(`/api/precos/produtos/${props.produtoId}`).then(({ data }) => {
    produto.value = data.produto;
    compras.value = data.compras;
});

const data = (iso) => iso.split('-').reverse().join('/');
const reais = (centavos) => new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(centavos / 100);
</script>

<template>
    <AppLayout>
        <template v-if="produto">
            <h1 class="page-title">{{ produto.nome }}</h1>

            <ul class="list">
                <li v-for="compra in compras" :key="compra.id" class="card">
                    <div class="info">
                        <span class="data">{{ data(compra.data) }}</span>
                        <span class="mercado">{{ compra.mercado.nome }}</span>
                        <span class="quantidade">{{ compra.quantidade.toLocaleString('pt-BR') }} {{ compra.unidade }}</span>
                    </div>
                    <span class="preco">{{ reais(compra.preco_centavos) }}</span>
                </li>
            </ul>
        </template>
    </AppLayout>
</template>

<style scoped>
.page-title {
    margin: 0 0 2rem;
    font-size: 2rem;
    font-weight: 700;
    color: #1f2937;
}

.list {
    display: grid;
    gap: 0.75rem;
    margin: 0;
    padding: 0;
    list-style: none;
}

.card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 1rem 1.5rem;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
}

.info {
    display: flex;
    flex-wrap: wrap;
    gap: 0.25rem 1rem;
    color: #6b7280;
}

.data {
    font-weight: 600;
    color: #1f2937;
}

.preco {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1f2937;
    white-space: nowrap;
}
</style>
