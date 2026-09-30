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
        expect(wrapper.find('h1').text()).toBe('Café');

        const linhas = wrapper.findAll('li').map((li) => li.text().replace(/\s+/g, ' '));
        expect(linhas).toHaveLength(3);
        expect(linhas[0]).toContain('14/09/2026');
        expect(linhas[0]).toContain('Atacadão');
        expect(linhas[0]).toContain('500 g');
        expect(linhas[0]).toContain('R$ 18,90');
        expect(linhas[1]).toContain('02/08/2026');
        expect(linhas[1]).toContain('Mercado Central');
        expect(linhas[1]).toContain('1,5 kg');
        expect(linhas[1]).toContain('R$ 37,80');
        expect(linhas[2]).toContain('30/07/2026');
        expect(linhas[2]).toContain('2 un');
        expect(linhas[2]).toContain('R$ 1.234,56');
    });
});
