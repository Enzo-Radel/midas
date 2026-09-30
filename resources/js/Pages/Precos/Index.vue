<script setup>
import { onMounted, ref, watch } from 'vue';
import axios from 'axios';
import AppLayout from '../../Layouts/AppLayout.vue';

const busca = ref('');
const campo = ref(null);
const produtos = ref([]);
let consulta = 0;

onMounted(() => campo.value.focus());

watch(
    busca,
    async (q) => {
        const atual = ++consulta;
        const { data } = await axios.get('/api/precos/produtos', { params: { q } });

        if (atual === consulta) {
            produtos.value = data.produtos;
        }
    },
    { immediate: true },
);
</script>

<template>
    <AppLayout>
        <div class="page-header">
            <h1 class="page-title">Preços</h1>
            <a href="/precos/compras/nova" class="button">Registrar compra</a>
        </div>

        <input
            ref="campo"
            v-model="busca"
            type="search"
            class="input"
            placeholder="Buscar produto"
            aria-label="Buscar produto"
            autocomplete="off"
        />

        <ul class="list">
            <li v-for="produto in produtos" :key="produto.id">
                <a :href="`/precos/produtos/${produto.id}`" class="card">{{ produto.nome }}</a>
            </li>
        </ul>
    </AppLayout>
</template>

<style scoped>
.page-header {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 2rem;
}

.page-title {
    margin: 0;
    font-size: 2rem;
    font-weight: 700;
    color: #1f2937;
}

.button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 48px;
    padding: 0 1.5rem;
    border-radius: 8px;
    background-color: #6366f1;
    color: white;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.2s ease;
}

.button:hover {
    background-color: #4f46e5;
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
    transform: translateY(-2px);
}

.button:active {
    transform: scale(0.98);
}

.input {
    width: 100%;
    min-height: 56px;
    margin-bottom: 1rem;
    padding: 0 1rem;
    background: white;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    color: #1f2937;
    font: inherit;
    font-size: 1.125rem;
    transition: all 0.2s ease;
}

.input:hover {
    border-color: #9ca3af;
}

.input:focus {
    outline: none;
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
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
    min-height: 56px;
    padding: 0 1.5rem;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    color: #1f2937;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.2s ease;
}

.card:hover {
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
    transform: translateY(-4px);
    border-color: #d1d5db;
}

.card:active {
    transform: translateY(-2px);
}
</style>
