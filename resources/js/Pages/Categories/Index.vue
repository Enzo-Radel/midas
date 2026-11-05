<script setup>
import { nextTick, ref } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faArrowLeft, faEye, faEyeSlash, faPen, faPlus, faTrash } from '@fortawesome/free-solid-svg-icons';
import AppLayout from '../../Layouts/AppLayout.vue';
import ActionMenu from '../../Components/ActionMenu.vue';
import ConfirmDialog from '../../Components/ConfirmDialog.vue';

const props = defineProps({
    categories: {
        type: Array,
        required: true,
    },
});

// Categoria em uso é a que gastos ou favoritos ainda usam; ela só é excluída dizendo para onde eles vão.
const inUse = (category) => category.expenses_count + category.favorites_count > 0;

const plural = (count, one, many) => `${count} ${count === 1 ? one : many}`;

const usageParts = (category) => [
    category.expenses_count > 0 ? plural(category.expenses_count, 'gasto', 'gastos') : null,
    category.favorites_count > 0 ? plural(category.favorites_count, 'favorito', 'favoritos') : null,
].filter(Boolean);

// Para a lista ("2 gastos · 1 favorito") e para frases ("2 gastos e 1 favorito").
const usage = (category) => (inUse(category) ? usageParts(category).join(' · ') : 'Não está em uso');
const usageSentence = (category) => usageParts(category).join(' e ');

// Diálogo aberto: exclusão de categoria sem uso, ou de categoria em uso (que precisa dizer para
// onde vão os gastos e favoritos dela, ou ser apenas desativada).
const dialog = ref(null); // { type: 'delete' | 'replace', category }
const replacement = ref(''); // '' (não escolhido) | 'none' | id da categoria que recebe os gastos
const working = ref(false);

const askDelete = (category) => {
    replacement.value = '';
    dialog.value = { type: inUse(category) ? 'replace' : 'delete', category };
};

const closeDialog = () => {
    dialog.value = null;
};

// Categorias ativas, exceto a que está sendo excluída, para receber os gastos e favoritos dela.
const destinations = () => props.categories.filter((category) => category.active && category.id !== dialog.value?.category.id);

const visit = (method, url, data = {}) => {
    const options = {
        preserveScroll: true,
        onStart: () => (working.value = true),
        onFinish: () => {
            working.value = false;
            closeDialog();
        },
    };

    // O `delete` do Inertia recebe (url, opções); o corpo do pedido vai em `data`.
    if (method === 'delete') router.delete(url, { ...options, data });
    else router.post(url, data, options);
};

const confirmDialog = () => {
    const { type, category } = dialog.value;

    if (type === 'delete') {
        visit('delete', `/categorias/${category.id}`);
    } else if (replacement.value === 'none') {
        visit('delete', `/categorias/${category.id}`, { without_category: true });
    } else {
        visit('delete', `/categorias/${category.id}`, { replacement_id: Number(replacement.value) });
    }
};

const deactivateFromDialog = () => visit('post', `/categorias/${dialog.value.category.id}/desativar`);

const dialogTitle = () => (dialog.value?.type === 'delete' ? 'Excluir esta categoria?' : 'Excluir categoria em uso');

const dialogMessage = () => {
    if (!dialog.value) return '';

    const { type, category } = dialog.value;

    if (type === 'delete') return `A categoria "${category.name}" não está em uso e será removida.`;

    return `A categoria "${category.name}" é usada em ${usageSentence(category)}. Para excluí-la, escolha para onde eles vão.`;
};

const deactivate = (category) => router.post(`/categorias/${category.id}/desativar`, {}, { preserveScroll: true });
const reactivate = (category) => router.post(`/categorias/${category.id}/reativar`, {}, { preserveScroll: true });

const createForm = useForm({ name: '' });

const createCategory = () => {
    createForm.post('/categorias', {
        preserveScroll: true,
        onSuccess: () => createForm.reset(),
    });
};

const renameForm = useForm({ name: '' });
const renamingId = ref(null);
const renameInput = ref(null);

const startRename = async (category) => {
    renameForm.clearErrors();
    renameForm.name = category.name;
    renamingId.value = category.id;

    await nextTick();
    renameInput.value?.[0]?.focus();
    renameInput.value?.[0]?.select();
};

const cancelRename = () => {
    renamingId.value = null;
    renameForm.reset();
    renameForm.clearErrors();
};

const saveRename = (category) => {
    renameForm.put(`/categorias/${category.id}`, {
        preserveScroll: true,
        onSuccess: () => cancelRename(),
        // O botão Salvar é desabilitado durante o envio e o foco se perde; volta para o campo com o erro.
        onError: () => nextTick(() => renameInput.value?.[0]?.focus()),
    });
};
</script>

<template>
    <AppLayout>
        <div class="page">
            <div class="page-header">
                <Link href="/despesas" class="back" aria-label="Voltar para as despesas" title="Voltar para as despesas">
                    <FontAwesomeIcon :icon="faArrowLeft" />
                </Link>
                <h1 class="page-title">Categorias</h1>
            </div>

            <p v-if="$page.props.errors?.category || $page.props.errors?.replacement_id" class="alert" role="alert">
                {{ $page.props.errors.category || $page.props.errors.replacement_id }}
            </p>

            <form class="card add" novalidate @submit.prevent="createCategory">
                <label for="new-category" class="label">Nova categoria</label>
                <div class="add-row">
                    <input
                        id="new-category"
                        v-model="createForm.name"
                        type="text"
                        maxlength="50"
                        autocomplete="off"
                        placeholder="Ex.: Pets"
                        class="input"
                        :class="{ 'input-error': createForm.errors.name }"
                        :aria-invalid="!!createForm.errors.name"
                        :aria-describedby="createForm.errors.name ? 'new-category-error' : undefined"
                    />
                    <button type="submit" class="button button-primary" :disabled="createForm.processing || !createForm.name.trim()">
                        <FontAwesomeIcon :icon="faPlus" />
                        <span>Adicionar</span>
                    </button>
                </div>
                <p v-if="createForm.errors.name" id="new-category-error" class="error" role="alert">{{ createForm.errors.name }}</p>
            </form>

            <ul class="list">
                <li v-for="category in categories" :key="category.id" class="row">
                    <form v-if="renamingId === category.id" class="rename" novalidate @submit.prevent="saveRename(category)" @keydown.esc="cancelRename">
                        <label :for="`rename-${category.id}`" class="sr-only">Novo nome da categoria {{ category.name }}</label>
                        <input
                            :id="`rename-${category.id}`"
                            ref="renameInput"
                            v-model="renameForm.name"
                            type="text"
                            maxlength="50"
                            autocomplete="off"
                            class="input"
                            :class="{ 'input-error': renameForm.errors.name }"
                            :aria-invalid="!!renameForm.errors.name"
                            :aria-describedby="renameForm.errors.name ? `rename-${category.id}-error` : undefined"
                        />
                        <p v-if="renameForm.errors.name" :id="`rename-${category.id}-error`" class="error" role="alert">
                            {{ renameForm.errors.name }}
                        </p>
                        <div class="rename-actions">
                            <button type="button" class="button button-secondary" @click="cancelRename">Cancelar</button>
                            <button type="submit" class="button button-primary" :disabled="renameForm.processing || !renameForm.name.trim()">Salvar</button>
                        </div>
                    </form>

                    <template v-else>
                        <span class="info" :class="{ 'info-inactive': !category.active }">
                            <span class="name">
                                {{ category.name }}
                                <span v-if="!category.active" class="badge">Desativada</span>
                            </span>
                            <span class="usage">{{ usage(category) }}</span>
                        </span>
                        <ActionMenu :label="`Ações da categoria: ${category.name}`">
                            <template #default="{ close }">
                                <button type="button" class="menu-item" role="menuitem" @click="close(); startRename(category)">
                                    <FontAwesomeIcon class="menu-icon" :icon="faPen" />
                                    <span>Renomear</span>
                                </button>
                                <button
                                    v-if="category.active"
                                    type="button"
                                    class="menu-item"
                                    role="menuitem"
                                    @click="close(); deactivate(category)"
                                >
                                    <FontAwesomeIcon class="menu-icon" :icon="faEyeSlash" />
                                    <span>Desativar</span>
                                </button>
                                <button v-else type="button" class="menu-item" role="menuitem" @click="close(); reactivate(category)">
                                    <FontAwesomeIcon class="menu-icon" :icon="faEye" />
                                    <span>Reativar</span>
                                </button>
                                <button
                                    type="button"
                                    class="menu-item menu-item-danger"
                                    role="menuitem"
                                    @click="close(); askDelete(category)"
                                >
                                    <FontAwesomeIcon class="menu-icon" :icon="faTrash" />
                                    <span>Excluir</span>
                                </button>
                            </template>
                        </ActionMenu>
                    </template>
                </li>
            </ul>
        </div>

        <ConfirmDialog
            :open="dialog !== null"
            :title="dialogTitle()"
            :message="dialogMessage()"
            :confirm-label="dialog?.type === 'delete' ? 'Excluir' : 'Excluir e trocar'"
            :confirm-disabled="dialog?.type === 'replace' && replacement === ''"
            :processing="working"
            @confirm="confirmDialog"
            @cancel="closeDialog"
        >
            <div v-if="dialog?.type === 'replace'" class="destination">
                <label for="destination" class="label">Mover os gastos e favoritos para</label>
                <select id="destination" v-model="replacement" class="input">
                    <option value="" disabled>Escolha uma opção</option>
                    <option value="none">Sem categoria</option>
                    <option v-for="category in destinations()" :key="category.id" :value="String(category.id)">
                        {{ category.name }}
                    </option>
                </select>
            </div>

            <template v-if="dialog?.type === 'replace' && dialog.category.active" #extra-actions>
                <button type="button" class="button button-secondary" :disabled="working" @click="deactivateFromDialog">
                    Só desativar
                </button>
            </template>
        </ConfirmDialog>
    </AppLayout>
</template>

<style scoped>
.page {
    max-width: 560px;
    margin: 0 auto;
}

.page-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.page-title {
    margin: 0;
    font-size: 1.75rem;
    font-weight: 700;
    color: #1f2937;
}

.back {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 44px;
    height: 44px;
    color: #6b7280;
    border-radius: 8px;
    text-decoration: none;
    transition: all 0.2s ease;
}

.back:hover {
    color: #4f46e5;
    background-color: #eef2ff;
}

.back:active {
    background-color: #e0e7ff;
    transform: scale(0.95);
}

.back:focus-visible {
    outline: none;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.3);
}

.card {
    padding: 1.25rem;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
}

.alert {
    margin: 0 0 1rem;
    padding: 0.75rem 1rem;
    font-size: 0.875rem;
    color: #dc2626;
    background-color: #fef2f2;
    border: 1px solid #fecaca;
    border-radius: 8px;
}

.add {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
}

.add-row {
    display: flex;
    gap: 0.75rem;
}

.label {
    font-size: 0.875rem;
    font-weight: 600;
    color: #374151;
}

.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
}

.input {
    flex: 1;
    min-width: 0;
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

.button {
    display: inline-flex;
    flex-shrink: 0;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    min-height: 44px;
    padding: 0.75rem 1.25rem;
    font-size: 1rem;
    font-weight: 500;
    border: 1px solid transparent;
    border-radius: 8px;
    cursor: pointer;
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

.list {
    margin: 0;
    padding: 0;
    list-style: none;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 14px;
}

.row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    min-height: 60px;
    padding: 0.5rem 0.5rem 0.5rem 1.25rem;
    border-bottom: 1px solid #f3f4f6;
}

.row:last-child {
    border-bottom: none;
}

.info {
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
    min-width: 0;
}

.name {
    font-weight: 500;
    color: #1f2937;
    overflow-wrap: anywhere;
}

.destination {
    display: flex;
    flex-direction: column;
    gap: 0.375rem;
    margin-bottom: 1.5rem;
}

.info-inactive .name {
    color: #6b7280;
}

.badge {
    display: inline-block;
    margin-left: 0.5rem;
    padding: 0.125rem 0.5rem;
    font-size: 0.75rem;
    font-weight: 600;
    color: #92400e;
    background-color: #fef3c7;
    border-radius: 8px;
}

.usage {
    font-size: 0.875rem;
    color: #6b7280;
}

.rename {
    display: flex;
    flex: 1;
    flex-direction: column;
    gap: 0.5rem;
    padding: 0.5rem 0.75rem 0.5rem 0;
}

.rename-actions {
    display: flex;
    gap: 0.5rem;
    justify-content: flex-end;
}

@media (max-width: 768px) {
    .page-title {
        font-size: 1.5rem;
    }

    .add-row {
        flex-direction: column;
    }

    .rename-actions .button {
        flex: 1;
    }
}
</style>
