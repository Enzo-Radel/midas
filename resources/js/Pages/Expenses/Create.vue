<script setup>
import { onMounted, ref } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';
import { normalizeAmount } from '../../utils/money.js';
import { todayIso } from '../../utils/date.js';

const form = useForm({
    amount: '',
    date: todayIso(),
    description: '',
});

const amountInput = ref(null);

onMounted(() => amountInput.value?.focus());

const submit = () => {
    form.transform((data) => ({ ...data, amount: normalizeAmount(data.amount) }))
        .post('/despesas');
};
</script>

<template>
    <AppLayout>
        <div class="page">
            <h1 class="page-title">Novo gasto</h1>

            <form class="form-card" novalidate @submit.prevent="submit">
                <div class="field">
                    <label for="amount" class="label">Valor (R$)</label>
                    <input
                        id="amount"
                        ref="amountInput"
                        v-model="form.amount"
                        type="text"
                        inputmode="decimal"
                        autocomplete="off"
                        placeholder="0,00"
                        class="input input-amount"
                        :class="{ 'input-error': form.errors.amount }"
                        :aria-invalid="!!form.errors.amount"
                        aria-describedby="amount-error"
                    />
                    <p v-if="form.errors.amount" id="amount-error" class="error">{{ form.errors.amount }}</p>
                </div>

                <div class="field">
                    <label for="date" class="label">Data</label>
                    <input
                        id="date"
                        v-model="form.date"
                        type="date"
                        class="input"
                        :class="{ 'input-error': form.errors.date }"
                        :aria-invalid="!!form.errors.date"
                        aria-describedby="date-error"
                    />
                    <p v-if="form.errors.date" id="date-error" class="error">{{ form.errors.date }}</p>
                </div>

                <div class="field">
                    <label for="description" class="label">Descrição <span class="optional">(opcional)</span></label>
                    <input
                        id="description"
                        v-model="form.description"
                        type="text"
                        maxlength="255"
                        autocomplete="off"
                        placeholder="Ex.: Almoço"
                        class="input"
                        :class="{ 'input-error': form.errors.description }"
                    />
                    <p v-if="form.errors.description" class="error">{{ form.errors.description }}</p>
                </div>

                <div class="actions">
                    <Link href="/despesas" class="button button-secondary">Cancelar</Link>
                    <button type="submit" class="button button-primary" :disabled="form.processing">Salvar</button>
                </div>
            </form>
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

.form-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 14px;
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}

.field {
    display: flex;
    flex-direction: column;
    gap: 0.375rem;
}

.label {
    font-size: 0.875rem;
    font-weight: 600;
    color: #374151;
}

.optional {
    font-weight: 400;
    color: #9ca3af;
}

.input {
    width: 100%;
    padding: 0.75rem 0.875rem;
    font-size: 1rem;
    color: #1f2937;
    background: white;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    transition: all 0.2s ease;
}

.input:hover {
    border-color: #9ca3af;
}

.input:focus {
    outline: none;
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
}

.input-amount {
    font-size: 1.5rem;
    font-weight: 600;
}

.input-error,
.input-error:hover {
    border-color: #ef4444;
}

.input-error:focus {
    border-color: #ef4444;
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.15);
}

.error {
    margin: 0;
    font-size: 0.875rem;
    color: #dc2626;
}

.actions {
    display: flex;
    gap: 0.75rem;
    justify-content: flex-end;
}

.button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.75rem 1.5rem;
    font-size: 1rem;
    font-weight: 500;
    border: 1px solid transparent;
    border-radius: 8px;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.2s ease;
}

.button-primary {
    background-color: #6366f1;
    color: white;
}

.button-primary:hover:not(:disabled) {
    background-color: #4f46e5;
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
    transform: translateY(-2px);
}

.button-secondary {
    background-color: white;
    color: #374151;
    border-color: #d1d5db;
}

.button-secondary:hover {
    background-color: #f3f4f6;
    border-color: #9ca3af;
    transform: translateY(-2px);
}

.button:active:not(:disabled) {
    transform: scale(0.98);
}

.button:focus-visible {
    outline: none;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.3);
}

.button:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

@media (max-width: 768px) {
    .form-card {
        padding: 1.25rem;
    }

    .actions .button {
        flex: 1;
    }
}
</style>
