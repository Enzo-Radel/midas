<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faPlus, faXmark } from '@fortawesome/free-solid-svg-icons';
import { normalizeAmount, toAmountInput } from '../utils/money.js';
import { todayIso } from '../utils/date.js';

const props = defineProps({
    /** Gasto a editar; sem ele o formulário cadastra um gasto novo. */
    expense: {
        type: Object,
        default: null,
    },
    categories: {
        type: Array,
        default: () => [],
    },
    /** Dados de um gasto a repetir: preenchem o cadastro, que segue criando um gasto novo com a data de hoje. */
    prefill: {
        type: Object,
        default: null,
    },
    /** Nomes das tags que já existem, para sugerir. */
    tags: {
        type: Array,
        default: () => [],
    },
});

// Edição e repetição começam dos dados de um gasto; só a edição leva a data dele.
const source = props.expense ?? props.prefill;

const form = useForm({
    amount: source ? toAmountInput(source.amount) : '',
    date: props.expense?.date ?? todayIso(),
    description: source?.description ?? '',
    category_id: source?.category_id ?? '',
    tags: [...(source?.tags ?? [])],
    favorite: props.expense?.favorited ?? false,
});

const amountInput = ref(null);
const tagText = ref('');

const sameTag = (a, b) => a.toLowerCase() === b.toLowerCase();

const tagInput = ref(null);

const addTag = (name) => {
    const clean = String(name ?? '').trim();

    if (clean && !form.tags.some((tag) => sameTag(tag, clean))) {
        // Usa a grafia da tag que já existe, para a tela mostrar o mesmo nome de antes e depois de salvar.
        form.tags.push(props.tags.find((tag) => sameTag(tag, clean)) ?? clean);
    }

    tagText.value = '';
};

// O botão clicado some da tela; devolve o foco ao campo para quem usa teclado ou leitor de tela.
const addSuggestion = (name) => {
    addTag(name);
    tagInput.value?.focus();
};

const removeTag = (name) => {
    form.tags = form.tags.filter((tag) => tag !== name);
    tagInput.value?.focus();
};

// A vírgula também confirma a tag. Tratada no texto (e não na tecla) porque teclados de celular
// nem sempre informam a tecla pressionada.
watch(tagText, (text) => {
    if (!text.includes(',')) return;

    const parts = text.split(',');
    const rest = parts.pop();

    parts.forEach(addTag);
    tagText.value = rest;
});

const onTagKeydown = (event) => {
    if (event.key === 'Enter') {
        event.preventDefault();
        addTag(tagText.value);
    }
};

// Sugestões: tags que já existem, ainda não escolhidas e que combinam com o que está sendo digitado.
const suggestions = computed(() => {
    const typed = tagText.value.trim().toLowerCase();

    return props.tags
        .filter((tag) => !form.tags.some((chosen) => sameTag(chosen, tag)))
        .filter((tag) => tag.toLowerCase().includes(typed))
        .slice(0, 10);
});

const tagError = computed(() => Object.entries(form.errors).find(([key]) => key === 'tags' || key.startsWith('tags.'))?.[1]);

// Só no cadastro em branco: na edição e na repetição, abrir o teclado sem necessidade atrapalha no celular.
onMounted(() => {
    if (!source) amountInput.value?.focus();
});

const submit = () => {
    addTag(tagText.value); // não perde a tag que foi digitada e ainda não confirmada

    const request = form.transform((data) => ({ ...data, amount: normalizeAmount(data.amount) }));

    if (props.expense) {
        request.put(`/despesas/${props.expense.id}`);
    } else {
        request.post('/despesas');
    }
};
</script>

<template>
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

        <div class="field">
            <label for="category" class="label">Categoria <span class="optional">(opcional)</span></label>
            <select
                id="category"
                v-model="form.category_id"
                class="input"
                :class="{ 'input-error': form.errors.category_id }"
            >
                <option value="">Sem categoria</option>
                <option v-for="category in categories" :key="category.id" :value="category.id">
                    {{ category.name }}
                </option>
            </select>
            <p v-if="form.errors.category_id" class="error">{{ form.errors.category_id }}</p>
        </div>

        <div class="field">
            <label for="tag-input" class="label">Tags <span class="optional">(opcional)</span></label>

            <ul v-if="form.tags.length > 0" class="chips" aria-label="Tags do gasto">
                <li v-for="tag in form.tags" :key="tag" class="chip">
                    <span>{{ tag }}</span>
                    <button type="button" class="chip-remove" :aria-label="`Retirar a tag ${tag}`" @click="removeTag(tag)">
                        <FontAwesomeIcon :icon="faXmark" />
                    </button>
                </li>
            </ul>

            <div class="tag-entry">
                <input
                    id="tag-input"
                    ref="tagInput"
                    v-model="tagText"
                    type="text"
                    maxlength="50"
                    autocomplete="off"
                    placeholder="Digite e tecle Enter"
                    class="input"
                    :class="{ 'input-error': tagError }"
                    @keydown="onTagKeydown"
                />
                <button type="button" class="button button-secondary tag-add" :disabled="!tagText.trim()" aria-label="Adicionar tag" @click="addTag(tagText)">
                    <FontAwesomeIcon :icon="faPlus" />
                </button>
            </div>

            <ul v-if="suggestions.length > 0" class="chips" aria-label="Tags existentes">
                <li v-for="tag in suggestions" :key="tag">
                    <button type="button" class="chip chip-suggestion" @click="addSuggestion(tag)">{{ tag }}</button>
                </li>
            </ul>

            <p v-if="tagError" class="error">{{ tagError }}</p>
        </div>

        <label class="check">
            <input v-model="form.favorite" type="checkbox" class="check-input" />
            <span class="check-text">
                <span class="check-title">Salvar nos favoritos</span>
                <span class="check-hint">Guarda uma cópia para repetir com um toque depois.</span>
            </span>
        </label>

        <div class="actions">
            <Link href="/despesas" class="button button-secondary">Cancelar</Link>
            <button type="submit" class="button button-primary" :disabled="form.processing">Salvar</button>
        </div>
    </form>
</template>

<style scoped>
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

.chips {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin: 0;
    padding: 0;
    list-style: none;
}

.chip {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.375rem 0.75rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: #4f46e5;
    background-color: #eef2ff;
    border: 1px solid transparent;
    border-radius: 8px;
}

.chip-remove {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    margin: -0.375rem -0.5rem -0.375rem 0;
    padding: 0;
    color: inherit;
    background: transparent;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.chip-remove:hover {
    color: #dc2626;
    background-color: #fee2e2;
}

.chip-remove:active {
    transform: scale(0.95);
}

.chip-remove:focus-visible,
.chip-suggestion:focus-visible {
    outline: none;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.3);
}

.chip-suggestion {
    min-height: 44px;
    padding: 0.5rem 1rem;
    color: #374151;
    background-color: white;
    border-color: #d1d5db;
    cursor: pointer;
    transition: all 0.2s ease;
}

.chip-suggestion:hover {
    color: #4f46e5;
    background-color: #eef2ff;
    border-color: #c7d2fe;
}

.chip-suggestion:active {
    transform: scale(0.97);
}

.tag-entry {
    display: flex;
    gap: 0.5rem;
}

.tag-add {
    flex-shrink: 0;
    padding: 0.75rem 1rem;
}

.check {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    min-height: 44px;
    padding: 0.75rem 0.875rem;
    cursor: pointer;
    background: white;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    transition: all 0.2s ease;
}

.check:hover {
    background-color: #f9fafb;
    border-color: #9ca3af;
}

.check:active {
    background-color: #f3f4f6;
}

.check:focus-within {
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
}

.check-input {
    flex-shrink: 0;
    width: 1.25rem;
    height: 1.25rem;
    margin: 0.125rem 0 0;
    cursor: pointer;
    accent-color: #6366f1;
}

.check-text {
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
}

.check-title {
    font-size: 1rem;
    font-weight: 500;
    color: #1f2937;
}

.check-hint {
    font-size: 0.875rem;
    color: #6b7280;
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
