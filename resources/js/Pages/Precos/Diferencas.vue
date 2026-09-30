<script setup>
import { ref } from 'vue';
import axios from 'axios';
import AppLayout from '../../Layouts/AppLayout.vue';
import { nomeExibido } from '../../lib/nomeExibido';
import { percentual, reais } from '../../lib/formatos';

const produtos = ref(null);

axios.get('/api/precos/diferencas').then(({ data }) => {
    produtos.value = data.produtos;
});

const mercadoEPreco = ({ mercado, mediana_centavos }, unidadeBase) => `${mercado.nome} ${reais(mediana_centavos)}/${unidadeBase}`;
</script>

<template>
    <AppLayout>
        <h1 class="page-title">Diferenças entre mercados</h1>

        <template v-if="produtos">
            <p v-if="!produtos.length" class="vazio">Nenhum produto comprado em mais de um mercado ainda.</p>

            <ul v-else class="list">
                <li v-for="item in produtos" :key="item.produto.id" class="card">
                    <a :href="`/precos/produtos/${item.produto.id}`" class="nome">{{ nomeExibido(item.produto) }}</a>
                    <span class="diferenca">{{ percentual(item.diferenca_percentual) }}</span>
                    <p class="mercado">Mais barato: {{ mercadoEPreco(item.menor, item.produto.unidade_base) }}</p>
                    <p class="mercado">Mais caro: {{ mercadoEPreco(item.maior, item.produto.unidade_base) }}</p>
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

.vazio {
    margin: 0;
    color: #6b7280;
}

.list {
    display: grid;
    gap: 0.75rem;
    margin: 0;
    padding: 0;
    list-style: none;
}

.card {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 0.25rem 1rem;
    padding: 1rem 1.5rem;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
}

.nome {
    display: flex;
    align-items: center;
    min-height: 48px;
    color: #1f2937;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s ease;
}

.nome:hover {
    color: #6366f1;
}

.nome:active {
    color: #4f46e5;
}

.diferenca {
    align-self: center;
    font-size: 1.25rem;
    font-weight: 700;
    color: #1f2937;
}

.mercado {
    grid-column: 1 / -1;
    margin: 0;
    color: #6b7280;
}
</style>
