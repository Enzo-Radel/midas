<script setup>
import { nextTick, ref } from 'vue';
import axios from 'axios';
import { router } from '@inertiajs/vue3';
import AppLayout from '../../Layouts/AppLayout.vue';

const unidades = [['kg', 'kg'], ['g', 'g'], ['L', 'L'], ['ml', 'ml'], ['un', 'un'], ['duzia', 'dúzia'], ['pacote', 'pacote']];

let chaves = 0;
const novaLinha = () => ({ chave: ++chaves, produto: '', quantidade: '', unidade: 'kg', unidades_por_pacote: '', preco: '', levei: true });

const formulario = ref(null);
const mercado = ref('');
const data = ref(new Date().toLocaleDateString('sv-SE'));
const itens = ref([novaLinha()]);
const sugestoes = ref([]);
const ativa = ref(null);
const aviso = ref('');
const errors = ref({});
const enviando = ref(false);

// Aceita "1.234,56" e "18.90": com vírgula, o ponto é separador de milhar.
const numero = (texto) => {
    const limpo = texto.replace(/[^\d,.-]/g, '');

    return Number(limpo.includes(',') ? limpo.replaceAll('.', '').replace(',', '.') : limpo);
};

const erro = (campo, linha) => errors.value[linha === undefined ? campo : `itens.${linha}.${campo}`]?.[0];

let consulta = 0;

async function sugerir(linha, q) {
    const atual = ++consulta;
    const resposta = await axios.get('/api/precos/produtos', { params: { q } });

    if (atual === consulta) {
        ativa.value = linha;
        sugestoes.value = resposta.data.produtos;
    }
}

function preencher(item, ultima) {
    item.quantidade = String(ultima.quantidade).replace('.', ',');
    item.unidade = ultima.unidade;
    item.unidades_por_pacote = String(ultima.unidades_por_pacote ?? '');
    item.preco = (ultima.preco_centavos / 100).toFixed(2).replace('.', ',');
}

function escolher(item, produto) {
    consulta++;
    sugestoes.value = [];
    item.produto = produto.nome;
    preencher(item, produto.ultima_compra);
}

const vazia = (item) => !item.produto && !item.quantidade && !item.unidades_por_pacote && !item.preco;
let consultaIda = 0;

async function repetir() {
    const atual = ++consultaIda;
    const { data: ida } = await axios.get('/api/precos/compras/ultima-ida', { params: { mercado: mercado.value } });

    if (atual !== consultaIda) {
        return;
    }

    aviso.value = ida.itens.length ? '' : 'Nenhuma compra anterior neste mercado.';

    if (ida.itens.length) {
        itens.value = [
            ...itens.value.filter((item) => !vazia(item)),
            ...ida.itens.map((compra) => {
                const item = { ...novaLinha(), produto: compra.produto };
                preencher(item, compra);

                return item;
            }),
        ];
    }
}

function remover(linha) {
    sugestoes.value = [];
    itens.value.splice(linha, 1);
}

async function proximaLinha(linha) {
    if (linha === itens.value.length - 1) {
        itens.value.push(novaLinha());
    }

    await nextTick();
    formulario.value.querySelectorAll('input[name=produto]')[linha + 1].focus();
}

// Enter nos campos não envia o formulário; no botão "Salvar compra" continua valendo.
const bloquearEnter = (evento) => {
    if (evento.target.tagName === 'INPUT') {
        evento.preventDefault();
    }
};

async function salvar() {
    const posicoes = itens.value.flatMap((item, i) => (item.levei ? [i] : []));

    errors.value = {};

    if (!posicoes.length) {
        errors.value = { itens: ['Marque ao menos um item.'] };

        return;
    }

    enviando.value = true;

    try {
        await axios.post('/api/precos/compras', {
            mercado: mercado.value,
            data: data.value,
            itens: itens.value.filter((item) => item.levei).map((item) => ({
                produto: item.produto,
                quantidade: numero(item.quantidade),
                unidade: item.unidade,
                unidades_por_pacote: item.unidade === 'pacote' ? numero(item.unidades_por_pacote) : null,
                preco_centavos: Math.round(numero(item.preco) * 100),
            })),
        });

        router.visit('/precos');
    } catch (e) {
        if (e.response?.status !== 422) {
            throw e;
        }

        // O backend numera os itens pela lista enviada; a tela pela posição da linha.
        errors.value = Object.fromEntries(
            Object.entries(e.response.data.errors).map(([chave, mensagens]) => [chave.replace(/^itens\.(\d+)\./, (_, n) => `itens.${posicoes[n]}.`), mensagens]),
        );
    } finally {
        enviando.value = false;
    }
}
</script>

<template>
    <AppLayout>
        <h1 class="page-title">Registrar compra</h1>

        <form ref="formulario" class="form" @submit.prevent="salvar" @keydown.enter="bloquearEnter">
            <label class="field">
                <span class="label">Mercado</span>
                <input v-model="mercado" name="mercado" class="input" type="text" autocomplete="off" />
                <span v-if="erro('mercado')" class="error">{{ erro('mercado') }}</span>
            </label>

            <button type="button" class="button button-secondary repetir" :disabled="!mercado.trim()" @click="repetir">Repetir última compra</button>
            <p v-if="aviso" class="aviso">{{ aviso }}</p>

            <label class="field">
                <span class="label">Data</span>
                <input v-model="data" name="data" class="input" type="date" />
                <span v-if="erro('data')" class="error">{{ erro('data') }}</span>
            </label>

            <p v-if="erro('itens')" class="error">{{ erro('itens') }}</p>

            <div v-for="(item, i) in itens" :key="item.chave" class="item">
                <div class="produto">
                    <label class="field">
                        <span class="label">Produto</span>
                        <input
                            v-model="item.produto"
                            name="produto"
                            class="input"
                            type="text"
                            autocomplete="off"
                            @input="sugerir(i, $event.target.value)"
                            @blur="sugestoes = []"
                        />
                        <span v-if="erro('produto', i)" class="error">{{ erro('produto', i) }}</span>
                    </label>

                    <ul v-if="ativa === i && sugestoes.length" class="sugestoes">
                        <li v-for="produto in sugestoes" :key="produto.id">
                            <button type="button" class="sugestao" @mousedown.prevent @click="escolher(item, produto)">{{ produto.nome }}</button>
                        </li>
                    </ul>
                </div>

                <label class="levei">
                    <input v-model="item.levei" name="levei" type="checkbox" />
                    Levei
                </label>

                <div class="campos">
                    <label class="field">
                        <span class="label">Quantidade</span>
                        <input v-model="item.quantidade" name="quantidade" class="input" type="text" inputmode="decimal" />
                        <span v-if="erro('quantidade', i)" class="error">{{ erro('quantidade', i) }}</span>
                    </label>

                    <label class="field">
                        <span class="label">Unidade</span>
                        <select v-model="item.unidade" name="unidade" class="input">
                            <option v-for="[valor, rotulo] in unidades" :key="valor" :value="valor">{{ rotulo }}</option>
                        </select>
                        <span v-if="erro('unidade', i)" class="error">{{ erro('unidade', i) }}</span>
                    </label>

                    <label v-if="item.unidade === 'pacote'" class="field">
                        <span class="label">Unidades por pacote</span>
                        <input v-model="item.unidades_por_pacote" name="unidades_por_pacote" class="input" type="text" inputmode="numeric" />
                        <span v-if="erro('unidades_por_pacote', i)" class="error">{{ erro('unidades_por_pacote', i) }}</span>
                    </label>

                    <label class="field">
                        <span class="label">Preço pago (R$)</span>
                        <input
                            v-model="item.preco"
                            name="preco_centavos"
                            class="input"
                            type="text"
                            inputmode="decimal"
                            placeholder="18,90"
                            @keydown.enter="proximaLinha(i)"
                        />
                        <span v-if="erro('preco_centavos', i)" class="error">{{ erro('preco_centavos', i) }}</span>
                    </label>
                </div>

                <button type="button" class="button button-danger remover" :disabled="itens.length === 1" @click="remover(i)">Remover item</button>
            </div>

            <button type="button" class="button button-secondary adicionar" @click="itens.push(novaLinha())">Adicionar item</button>
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
    max-width: 640px;
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
    box-sizing: border-box;
    width: 100%;
    min-width: 0;
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

.item {
    display: grid;
    gap: 1rem;
    padding: 1rem;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
}

.produto {
    position: relative;
}

.aviso {
    margin: 0;
    color: #6b7280;
}

.levei {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    min-height: 48px;
    font-weight: 500;
    color: #1f2937;
    cursor: pointer;
}

.levei input {
    width: 24px;
    height: 24px;
    accent-color: #6366f1;
    cursor: pointer;
}

.campos {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1rem;
}

.sugestoes {
    position: absolute;
    top: 100%;
    right: 0;
    left: 0;
    z-index: 10;
    margin: 0.25rem 0 0;
    padding: 0.25rem;
    list-style: none;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
}

.sugestao {
    width: 100%;
    min-height: 48px;
    padding: 0 0.75rem;
    background: transparent;
    border: none;
    border-radius: 8px;
    color: #1f2937;
    font: inherit;
    text-align: left;
    cursor: pointer;
    transition: all 0.2s ease;
}

.sugestao:hover {
    background-color: #f3f4f6;
}

.sugestao:active {
    background-color: #e5e7eb;
}

.button-secondary {
    background-color: white;
    border: 1px solid #6366f1;
    color: #6366f1;
}

.button-secondary:hover {
    background-color: #eef2ff;
}

.button-danger {
    background-color: white;
    border: 1px solid #ef4444;
    color: #ef4444;
}

.button-danger:hover {
    background-color: #fef2f2;
    box-shadow: none;
}

.button:disabled:hover {
    transform: none;
}
</style>
