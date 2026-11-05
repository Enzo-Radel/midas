<script setup>
import { nextTick, ref } from 'vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faArrowLeft, faPen, faTag, faTrash } from '@fortawesome/free-solid-svg-icons';
import AppLayout from '../../Layouts/AppLayout.vue';
import ActionMenu from '../../Components/ActionMenu.vue';
import ConfirmDialog from '../../Components/ConfirmDialog.vue';

defineProps({
    tags: {
        type: Array,
        required: true,
    },
});

const plural = (count, one, many) => `${count} ${count === 1 ? one : many}`;

const usageParts = (tag) => [
    tag.expenses_count > 0 ? plural(tag.expenses_count, 'gasto', 'gastos') : null,
    tag.favorites_count > 0 ? plural(tag.favorites_count, 'favorito', 'favoritos') : null,
].filter(Boolean);

// Para a lista ("2 gastos · 1 favorito") e para frases ("2 gastos e 1 favorito").
const usage = (tag) => (usageParts(tag).length === 0 ? 'Não está em uso' : usageParts(tag).join(' · '));
const usageSentence = (tag) => usageParts(tag).join(' e ');

// Renomear no próprio item.
const renameForm = useForm({ name: '' });
const renamingId = ref(null);
const renameInput = ref(null);

const startRename = async (tag) => {
    renameForm.clearErrors();
    renameForm.name = tag.name;
    renamingId.value = tag.id;

    await nextTick();
    renameInput.value?.[0]?.focus();
    renameInput.value?.[0]?.select();
};

const cancelRename = () => {
    renamingId.value = null;
    renameForm.reset();
    renameForm.clearErrors();
};

const saveRename = (tag) => {
    renameForm.put(`/tags/${tag.id}`, {
        preserveScroll: true,
        onSuccess: () => cancelRename(),
        // O botão Salvar é desabilitado durante o envio e o foco se perde; volta para o campo com o erro.
        onError: () => nextTick(() => renameInput.value?.[0]?.focus()),
    });
};

// Excluir, com confirmação: a tag só é retirada dos gastos e favoritos que a tinham.
const tagToDelete = ref(null);
const deleting = ref(false);

const confirmDelete = () => {
    router.delete(`/tags/${tagToDelete.value.id}`, {
        preserveScroll: true,
        onStart: () => (deleting.value = true),
        onFinish: () => {
            deleting.value = false;
            tagToDelete.value = null;
        },
    });
};

const deleteMessage = () => {
    if (!tagToDelete.value) return '';

    const tag = tagToDelete.value;

    if (tag.expenses_count + tag.favorites_count === 0) return `A tag "${tag.name}" não está em uso e será removida.`;

    return `A tag "${tag.name}" será retirada de ${usageSentence(tag)}. Os gastos e favoritos não são excluídos.`;
};
</script>

<template>
    <AppLayout>
        <div class="page">
            <div class="page-header">
                <Link href="/despesas" class="back" aria-label="Voltar para as despesas" title="Voltar para as despesas">
                    <FontAwesomeIcon :icon="faArrowLeft" />
                </Link>
                <h1 class="page-title">Tags</h1>
            </div>

            <div v-if="tags.length === 0" class="empty">
                <FontAwesomeIcon class="empty-icon" :icon="faTag" />
                <p class="empty-title">Nenhuma tag ainda</p>
                <p class="empty-text">As tags são criadas ao registrar ou editar um gasto: digite o nome no campo "Tags".</p>
                <Link href="/despesas/nova" class="button button-primary">Registrar um gasto</Link>
            </div>

            <ul v-else class="list">
                <li v-for="tag in tags" :key="tag.id" class="row">
                    <form v-if="renamingId === tag.id" class="rename" novalidate @submit.prevent="saveRename(tag)" @keydown.esc="cancelRename">
                        <label :for="`rename-${tag.id}`" class="sr-only">Novo nome da tag {{ tag.name }}</label>
                        <input
                            :id="`rename-${tag.id}`"
                            ref="renameInput"
                            v-model="renameForm.name"
                            type="text"
                            maxlength="50"
                            autocomplete="off"
                            class="input"
                            :class="{ 'input-error': renameForm.errors.name }"
                            :aria-invalid="!!renameForm.errors.name"
                            :aria-describedby="renameForm.errors.name ? `rename-${tag.id}-error` : undefined"
                        />
                        <p v-if="renameForm.errors.name" :id="`rename-${tag.id}-error`" class="error" role="alert">
                            {{ renameForm.errors.name }}
                        </p>
                        <div class="rename-actions">
                            <button type="button" class="button button-secondary" @click="cancelRename">Cancelar</button>
                            <button type="submit" class="button button-primary" :disabled="renameForm.processing || !renameForm.name.trim()">Salvar</button>
                        </div>
                    </form>

                    <template v-else>
                        <span class="info">
                            <span class="name">{{ tag.name }}</span>
                            <span class="usage">{{ usage(tag) }}</span>
                        </span>
                        <ActionMenu :label="`Ações da tag: ${tag.name}`">
                            <template #default="{ close }">
                                <button type="button" class="menu-item" role="menuitem" @click="close(); startRename(tag)">
                                    <FontAwesomeIcon class="menu-icon" :icon="faPen" />
                                    <span>Renomear</span>
                                </button>
                                <button type="button" class="menu-item menu-item-danger" role="menuitem" @click="close(); tagToDelete = tag">
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
            :open="tagToDelete !== null"
            title="Excluir esta tag?"
            :message="deleteMessage()"
            confirm-label="Excluir"
            :processing="deleting"
            @confirm="confirmDelete"
            @cancel="tagToDelete = null"
        />
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
    text-decoration: none;
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

.empty {
    padding: 2.5rem 1.5rem;
    text-align: center;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 14px;
}

.empty-icon {
    margin-bottom: 0.75rem;
    font-size: 1.75rem;
    color: #6366f1;
}

.empty-title {
    margin: 0 0 0.5rem;
    font-size: 1.125rem;
    font-weight: 600;
    color: #1f2937;
}

.empty-text {
    margin: 0 0 1.5rem;
    line-height: 1.6;
    color: #6b7280;
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

    .rename-actions .button {
        flex: 1;
    }
}
</style>
