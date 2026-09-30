import { beforeEach, describe, expect, it, vi } from 'vitest';
import { flushPromises, mount } from '@vue/test-utils';
import axios from 'axios';
import { contract } from '../../test-support/contract';
import Produto from './Produto.vue';

vi.mock('axios', () => ({ default: { get: vi.fn(), post: vi.fn() } }));

describe('Precos/Produto', () => {
    beforeEach(() => vi.clearAllMocks());

    it('mostra as compras na ordem recebida, com data, mercado, quantidade e preço formatados', async () => {
        const resposta = contract('produtos.show').response;
        const modelo = resposta.compras[0];
        resposta.compras = [
            { ...modelo, id: 3, data: '2026-09-14', mercado: { id: 1, nome: 'Atacadão' }, quantidade: 500, unidade: 'g', preco_centavos: 1890 },
            { ...modelo, id: 2, data: '2026-08-02', mercado: { id: 2, nome: 'Mercado Central' }, quantidade: 1.5, unidade: 'kg', preco_centavos: 3780 },
            { ...modelo, id: 1, data: '2026-07-30', mercado: { id: 1, nome: 'Atacadão' }, quantidade: 2, unidade: 'un', preco_centavos: 123456 },
        ];
        axios.get.mockResolvedValue({ data: resposta });

        const wrapper = mount(Produto, { props: { produtoId: 1 } });
        await flushPromises();

        expect(axios.get).toHaveBeenCalledWith('/api/precos/produtos/1');
        expect(wrapper.find('h1').text()).toBe('Café Pilão');

        const linhas = wrapper.findAll('li').map((li) => li.text().replace(/\s+/g, ' '));
        expect(linhas).toHaveLength(3);
        expect(linhas[0]).toContain('14/09/2026');
        expect(linhas[0]).toContain('Atacadão');
        expect(linhas[0]).toContain('500 g');
        expect(linhas[0]).toContain('R$ 18,90');
        expect(linhas[0]).toContain('R$ 37,80/kg');
        expect(linhas[1]).toContain('02/08/2026');
        expect(linhas[1]).toContain('Mercado Central');
        expect(linhas[1]).toContain('1,5 kg');
        expect(linhas[1]).toContain('R$ 37,80');
        expect(linhas[2]).toContain('30/07/2026');
        expect(linhas[2]).toContain('2 un');
        expect(linhas[2]).toContain('R$ 1.234,56');
    });

    it('mostra quantidade legível em dúzia e pacote e o preço por unidade base do produto', async () => {
        const resposta = contract('produtos.show').response;
        const modelo = resposta.compras[0];
        resposta.produto = { id: 2, nome: 'Ovos', unidade_base: 'un' };
        resposta.compras = [
            { ...modelo, id: 2, quantidade: 1, unidade: 'duzia', unidades_por_pacote: null, preco_centavos: 1200, preco_base_centavos: 100 },
            { ...modelo, id: 1, quantidade: 2, unidade: 'pacote', unidades_por_pacote: 6, preco_centavos: 1800, preco_base_centavos: 150 },
        ];
        axios.get.mockResolvedValue({ data: resposta });

        const wrapper = mount(Produto, { props: { produtoId: 2 } });
        await flushPromises();

        const linhas = wrapper.findAll('li').map((li) => li.text().replace(/\s+/g, ' '));
        expect(linhas[0]).toContain('1 dúzia');
        expect(linhas[0]).toContain('R$ 1,00/un');
        expect(linhas[1]).toContain('2 pacote (6 un)');
        expect(linhas[1]).toContain('R$ 1,50/un');
    });

    describe('resumo', () => {
        const resumoDe = async (resposta) => {
            axios.get.mockResolvedValue({ data: resposta });
            const wrapper = mount(Produto, { props: { produtoId: 1 } });
            await flushPromises();

            return wrapper;
        };
        const texto = (wrapper, seletor) => wrapper.find(seletor).text().replace(/\s+/g, ' ');

        it('mostra mediana, faixa, última compra, contagem e período formatados, acima do histórico', async () => {
            const resposta = contract('produtos.show').response;
            resposta.resumo.ultima.preco_base_centavos = 3490;
            resposta.resumo.ultima.mercado.nome = 'Mercado Central';
            const wrapper = await resumoDe(resposta);

            expect(texto(wrapper, '.mediana')).toBe('R$ 37,80/kg');
            expect(texto(wrapper, '.faixa')).toContain('R$ 34,90 a R$ 39,10/kg');
            expect(texto(wrapper, '.ultima')).toContain('R$ 34,90/kg');
            expect(texto(wrapper, '.ultima')).toContain('Mercado Central');
            expect(texto(wrapper, '.ultima')).toContain('14/09/2026');
            expect(texto(wrapper, '.contagem')).toBe('3 compras, das quais 1 em promoção');
            expect(texto(wrapper, '.periodo')).toContain('19/07/2026 a 14/09/2026');
            expect(wrapper.find('.resumo').element.compareDocumentPosition(wrapper.find('ul').element)).toBe(Node.DOCUMENT_POSITION_FOLLOWING);
            expect(wrapper.findAll('li')).toHaveLength(1);
        });

        it('renderiza uma única compra com "1 compra" e o período com uma data só', async () => {
            const resposta = contract('produtos.show').response;
            resposta.resumo = {
                mediana_centavos: 3780,
                minimo_centavos: 3780,
                maximo_centavos: 3780,
                contagem: 1,
                ultima: resposta.resumo.ultima,
                periodo: { inicio: '2026-09-14', fim: '2026-09-14' },
                em_promocao: 0,
            };
            const wrapper = await resumoDe(resposta);

            expect(texto(wrapper, '.mediana')).toBe('R$ 37,80/kg');
            expect(texto(wrapper, '.contagem')).toBe('1 compra');
            expect(texto(wrapper, '.periodo')).toContain('14/09/2026');
            expect(texto(wrapper, '.periodo')).not.toContain(' a ');
        });
    });

    it('o título é só o nome quando o produto não tem marca', async () => {
        const resposta = contract('produtos.show').response;
        resposta.produto.marca = null;
        axios.get.mockResolvedValue({ data: resposta });

        const wrapper = mount(Produto, { props: { produtoId: 1 } });
        await flushPromises();

        expect(wrapper.find('h1').text()).toBe('Café');
    });

    describe('promoção', () => {
        const montar = async (ajustar) => {
            const resposta = contract('produtos.show').response;
            ajustar(resposta);
            axios.get.mockResolvedValue({ data: resposta });
            const wrapper = mount(Produto, { props: { produtoId: 1 } });
            await flushPromises();

            return wrapper;
        };
        const compra = (resposta, campos) => ({ ...resposta.compras[0], ...campos });

        it('o histórico marca a compra em promoção, com ou sem preço original, e não mostra nada nas demais', async () => {
            const wrapper = await montar((r) => {
                r.compras = [
                    compra(r, { id: 3, promocao: true, preco_original_centavos: 2290 }),
                    compra(r, { id: 2, promocao: true, preco_original_centavos: null }),
                    compra(r, { id: 1, promocao: false, preco_original_centavos: null }),
                ];
            });

            const linhas = wrapper.findAll('li').map((li) => li.text().replace(/\s+/g, ' '));
            expect(linhas[0]).toContain('Promoção: sim, de R$ 22,90');
            expect(linhas[1]).toContain('Promoção: sim');
            expect(linhas[1]).not.toContain(' de R$');
            expect(linhas[2]).not.toContain('Promoção');
        });

        it('o resumo conta as compras em promoção', async () => {
            const comResumo = (contagem, emPromocao) => (r) => {
                r.resumo.contagem = contagem;
                r.resumo.em_promocao = emPromocao;
            };

            expect((await montar(comResumo(4, 1))).find('.contagem').text()).toBe('4 compras, das quais 1 em promoção');
            expect((await montar(comResumo(1, 1))).find('.contagem').text()).toBe('1 compra, em promoção');
            expect((await montar(comResumo(4, 0))).find('.contagem').text()).toBe('4 compras');
        });
    });

    describe('por mercado', () => {
        const montar = async (mercados) => {
            const resposta = contract('produtos.show').response;
            resposta.mercados = mercados;
            axios.get.mockResolvedValue({ data: resposta });
            const wrapper = mount(Produto, { props: { produtoId: 1 } });
            await flushPromises();

            return wrapper;
        };
        const linha = (nome, mediana, contagem, diferenca) => ({ mercado: { id: 1, nome }, mediana_centavos: mediana, contagem, diferenca_percentual: diferenca });
        const linhas = (wrapper) => wrapper.findAll('.por-mercado .linha').map((l) => l.text().replace(/\s+/g, ' '));

        it('mostra nome, mediana na unidade base, contagem e diferença na ordem recebida, entre o resumo e o histórico', async () => {
            const wrapper = await montar([linha('Atacadão', 3720, 9, 0), linha('Mercado do bairro', 3910, 1, 5.1), linha('Loja', 4000, 2, 10)]);

            const texto = linhas(wrapper);
            expect(texto).toHaveLength(3);
            expect(texto[0]).toContain('Atacadão');
            expect(texto[0]).toContain('R$ 37,20/kg');
            expect(texto[0]).toContain('9 compras');
            expect(texto[0]).toContain('melhor');
            expect(texto[1]).toContain('Mercado do bairro');
            expect(texto[1]).toContain('R$ 39,10/kg');
            expect(texto[1]).toContain('1 compra');
            expect(texto[1]).not.toContain('1 compras');
            expect(texto[1]).toContain('+5,1%');
            expect(texto[2]).toContain('+10,0%');

            const secao = wrapper.find('.por-mercado').element;
            expect(wrapper.find('.resumo').element.compareDocumentPosition(secao)).toBe(Node.DOCUMENT_POSITION_FOLLOWING);
            expect(secao.compareDocumentPosition(wrapper.find('ul').element)).toBe(Node.DOCUMENT_POSITION_FOLLOWING);
        });

        it('com um só mercado não mostra diferença nem "melhor"', async () => {
            const wrapper = await montar([linha('Atacadão', 3720, 9, null)]);

            const texto = linhas(wrapper);
            expect(texto).toHaveLength(1);
            expect(texto[0]).toContain('R$ 37,20/kg');
            expect(texto[0]).not.toContain('melhor');
            expect(texto[0]).not.toContain('%');
        });
    });
});
