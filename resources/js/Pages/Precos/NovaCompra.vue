<script setup>
import { reactive, ref } from 'vue';
import axios from 'axios';
import { router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';

const form = reactive({ produto: '', mercado: '', data: '', quantidade: '', unidade: 'kg', preco: '' });
const errors = ref({});
const enviando = ref(false);

// Aceita "1.234,56" e "18.90": com vírgula, o ponto é separador de milhar.
const numero = (texto) => {
    const limpo = texto.replace(/[^\d,.-]/g, '');

    return Number(limpo.includes(',') ? limpo.replaceAll('.', '').replace(',', '.') : limpo);
};

async function salvar() {
    enviando.value = true;
    errors.value = {};

    try {
        const { data } = await axios.post('/api/precos/compras', {
            produto: form.produto,
            mercado: form.mercado,
            data: form.data,
            quantidade: numero(form.quantidade),
            unidade: form.unidade,
            preco_centavos: Math.round(numero(form.preco) * 100),
        });

        router.visit(`/precos/produtos/${data.compra.produto_id}`);
    } catch (e) {
        if (e.response?.status !== 422) {
            throw e;
        }

        errors.value = e.response.data.errors;
    } finally {
        enviando.value = false;
    }
}
</script>

<template>
    <AppLayout>
        <h1 class="page-title">Registrar compra</h1>

        <form class="form" @submit.prevent="salvar">
            <label class="field">
                <span class="label">Produto</span>
                <input v-model="form.produto" name="produto" class="input" type="text" autocomplete="off" />
                <span v-if="errors.produto" class="error">{{ errors.produto[0] }}</span>
            </label>

            <label class="field">
                <span class="label">Mercado</span>
                <input v-model="form.mercado" name="mercado" class="input" type="text" autocomplete="off" />
                <span v-if="errors.mercado" class="error">{{ errors.mercado[0] }}</span>
            </label>

            <label class="field">
                <span class="label">Data</span>
                <input v-model="form.data" name="data" class="input" type="date" />
                <span v-if="errors.data" class="error">{{ errors.data[0] }}</span>
            </label>

            <label class="field">
                <span class="label">Quantidade</span>
                <input v-model="form.quantidade" name="quantidade" class="input" type="text" inputmode="decimal" />
                <span v-if="errors.quantidade" class="error">{{ errors.quantidade[0] }}</span>
            </label>

            <label class="field">
                <span class="label">Unidade</span>
                <select v-model="form.unidade" name="unidade" class="input">
                    <option v-for="unidade in ['kg', 'g', 'L', 'ml', 'un']" :key="unidade" :value="unidade">{{ unidade }}</option>
                </select>
                <span v-if="errors.unidade" class="error">{{ errors.unidade[0] }}</span>
            </label>

            <label class="field">
                <span class="label">Preço pago (R$)</span>
                <input v-model="form.preco" name="preco_centavos" class="input" type="text" inputmode="decimal" placeholder="18,90" />
                <span v-if="errors.preco_centavos" class="error">{{ errors.preco_centavos[0] }}</span>
            </label>

            <button class="button" type="submit" :disabled="enviando">Salvar compra</button>
        </form>
    </AppLayout>
</template>

<style scoped>
.page-title {
    margin: 0 0 2rem;
    font-size: 2rem;
    font-weight: 700;
    color: #1f2937;
}

.form {
    display: grid;
    gap: 1.25rem;
    max-width: 480px;
    padding: 1.5rem;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 16px;
}

.field {
    display: grid;
    gap: 0.375rem;
}

.label {
    font-size: 0.875rem;
    font-weight: 600;
    color: #6b7280;
}

.input {
    min-height: 48px;
    padding: 0 0.75rem;
    background: white;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    color: #1f2937;
    font: inherit;
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

.error {
    font-size: 0.875rem;
    color: #ef4444;
}

.button {
    min-height: 48px;
    border: none;
    border-radius: 8px;
    background-color: #6366f1;
    color: white;
    font: inherit;
    font-weight: 500;
    cursor: pointer;
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

.button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none;
    box-shadow: none;
}
</style>
