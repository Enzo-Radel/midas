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
const resumo = ref(null);
const mercados = ref([]);

axios.get(`/api/precos/produtos/${props.produtoId}`).then(({ data }) => {
    produto.value = data.produto;
    compras.value = data.compras;
    resumo.value = data.resumo;
    mercados.value = data.mercados;
});

const data = (iso) => iso.split('-').reverse().join('/');
const quantidade = (c) =>
    `${c.quantidade.toLocaleString('pt-BR')} ${c.unidade === 'duzia' ? 'dúzia' : c.unidade}${c.unidades_por_pacote ? ` (${c.unidades_por_pacote} un)` : ''}`;
const reais = (centavos) => new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(centavos / 100);
const contagem = (n) => `${n} ${n === 1 ? 'compra' : 'compras'}`;
const diferenca = (percentual) =>
    percentual === null ? '' : percentual === 0 ? 'melhor' : `+${percentual.toLocaleString('pt-BR', { minimumFractionDigits: 1 })}%`;
const porBase = (centavos) => `${reais(centavos)}/${produto.value.unidade_base}`;
</script>

<template>
    <AppLayout>
        <template v-if="produto">
            <h1 class="page-title">{{ produto.nome }}</h1>

            <section class="resumo">
                <p class="mediana">{{ porBase(resumo.mediana_centavos) }}</p>
                <p class="faixa">
                    Faixa: {{ reais(resumo.minimo_centavos) }} a {{ porBase(resumo.maximo_centavos) }}
                </p>
                <p class="ultima">
                    Última compra: {{ porBase(resumo.ultima.preco_base_centavos) }} em {{ resumo.ultima.mercado.nome }},
                    {{ data(resumo.ultima.data) }}
                </p>
                <p class="contagem">{{ contagem(resumo.contagem) }}</p>
                <p class="periodo">
                    Período: {{ data(resumo.periodo.inicio) }}<template v-if="resumo.periodo.inicio !== resumo.periodo.fim"> a {{ data(resumo.periodo.fim) }}</template>
                </p>
            </section>

            <section class="por-mercado">
                <h2 class="secao-titulo">Por mercado</h2>
                <div v-for="m in mercados" :key="m.mercado.id" class="linha">
                    <span class="nome">{{ m.mercado.nome }}</span>
                    <span class="mediana-mercado">{{ porBase(m.mediana_centavos) }}</span>
                    <span class="detalhe">{{ contagem(m.contagem) }}</span>
                    <span class="detalhe diferenca">{{ diferenca(m.diferenca_percentual) }}</span>
                </div>
            </section>

            <ul class="list">
                <li v-for="compra in compras" :key="compra.id" class="card">
                    <div class="info">
                        <span class="data">{{ data(compra.data) }}</span>
                        <span class="mercado">{{ compra.mercado.nome }}</span>
                        <span class="quantidade">{{ quantidade(compra) }}</span>
                    </div>
                    <div class="precos">
                        <span class="preco">{{ reais(compra.preco_centavos) }}</span>
                        <span class="preco-base">{{ porBase(compra.preco_base_centavos) }}</span>
                    </div>
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

.resumo {
    display: grid;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
    padding: 1.5rem;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 14px;
    color: #6b7280;
}

.resumo p {
    margin: 0;
}

.mediana {
    font-size: 2.25rem;
    font-weight: 700;
    color: #6366f1;
}

.por-mercado {
    margin-bottom: 1.5rem;
    padding: 1.5rem;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 14px;
}

.secao-titulo {
    margin: 0 0 0.75rem;
    font-size: 1rem;
    font-weight: 600;
    color: #6b7280;
}

.linha {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 0.125rem 1rem;
    padding: 0.75rem 0;
    border-top: 1px solid #e5e7eb;
}

.nome,
.mediana-mercado {
    font-weight: 600;
    color: #1f2937;
}

.mediana-mercado,
.diferenca {
    text-align: right;
}

.detalhe {
    font-size: 0.875rem;
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

.precos {
    display: grid;
    justify-items: end;
}

.preco-base {
    font-size: 0.875rem;
    color: #6b7280;
}

.preco {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1f2937;
    white-space: nowrap;
}
</style>
