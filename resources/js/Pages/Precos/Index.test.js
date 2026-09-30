import { beforeEach, describe, expect, it, vi } from 'vitest';
import { flushPromises, mount } from '@vue/test-utils';
import axios from 'axios';
import { contract } from '../../test-support/contract';
import Index from './Index.vue';

vi.mock('axios', () => ({ default: { get: vi.fn(), post: vi.fn() } }));

describe('Precos/Index', () => {
    beforeEach(() => vi.clearAllMocks());

    it('lista os produtos com um link para a página de cada um e um link para registrar compra', async () => {
        const resposta = contract('produtos.index').response;
        resposta.produtos.push({ id: 2, nome: 'Leite' }, { id: 7, nome: 'Arroz' });
        axios.get.mockResolvedValue({ data: resposta });

        const wrapper = mount(Index);
        await flushPromises();

        expect(axios.get).toHaveBeenCalledWith('/api/precos/produtos');

        const links = wrapper.findAll('a[href^="/precos/produtos/"]');
        expect(links.map((a) => [a.text(), a.attributes('href')])).toEqual([
            ['Café', '/precos/produtos/1'],
            ['Leite', '/precos/produtos/2'],
            ['Arroz', '/precos/produtos/7'],
        ]);
        expect(wrapper.find('a[href="/precos/compras/nova"]').exists()).toBe(true);
    });
});
