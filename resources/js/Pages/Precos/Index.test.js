import { beforeEach, describe, expect, it, vi } from 'vitest';
import { flushPromises, mount } from '@vue/test-utils';
import axios from 'axios';
import { contract } from '../../test-support/contract';
import Index from './Index.vue';

vi.mock('axios', () => ({ default: { get: vi.fn(), post: vi.fn() } }));

const respostaCom = (...produtos) => ({ data: { produtos } });
const links = (wrapper) => wrapper.findAll('a[href^="/precos/produtos/"]').map((a) => [a.text(), a.attributes('href')]);

describe('Precos/Index', () => {
    beforeEach(() => vi.clearAllMocks());

    it('abre com o campo de busca em foco e os mais comprados na ordem recebida, cada um com link para o produto', async () => {
        const resposta = contract('produtos.index').response;
        resposta.produtos.push({ id: 7, nome: 'Arroz', marca: null, total_compras: 2 }, { id: 2, nome: 'Leite', marca: 'Piracanjuba', total_compras: 1 });
        axios.get.mockResolvedValue({ data: resposta });

        const wrapper = mount(Index, { attachTo: document.body });
        await flushPromises();

        expect(document.activeElement).toBe(wrapper.find('input[type=search]').element);
        expect(axios.get).toHaveBeenCalledWith('/api/precos/produtos', { params: { q: '' } });
        expect(links(wrapper)).toEqual([
            ['Café Pilão', '/precos/produtos/1'],
            ['Arroz', '/precos/produtos/7'],
            ['Leite Piracanjuba', '/precos/produtos/2'],
        ]);
        expect(wrapper.find('a[href="/precos/compras/nova"]').exists()).toBe(true);
        wrapper.unmount();
    });

    it('ao digitar consulta a API com q e mostra as sugestões recebidas; apagar volta aos mais comprados', async () => {
        const todos = contract('produtos.index').response;
        axios.get.mockImplementation(async (url, { params }) => (params.q === 'caf' ? respostaCom({ id: 9, nome: 'Café Pilão', total_compras: 3 }) : { data: todos }));

        const wrapper = mount(Index);
        await flushPromises();
        await wrapper.find('input[type=search]').setValue('caf');
        await flushPromises();

        expect(axios.get).toHaveBeenLastCalledWith('/api/precos/produtos', { params: { q: 'caf' } });
        expect(links(wrapper)).toEqual([['Café Pilão', '/precos/produtos/9']]);

        await wrapper.find('input[type=search]').setValue('');
        await flushPromises();

        expect(links(wrapper)).toEqual([['Café Pilão', '/precos/produtos/1']]);
    });

    it('ignora a resposta atrasada de um texto anterior', async () => {
        const pendentes = {};
        axios.get.mockImplementation((url, { params }) => new Promise((resolve) => (pendentes[params.q] = resolve)));

        const wrapper = mount(Index);
        await wrapper.find('input[type=search]').setValue('ca');
        await wrapper.find('input[type=search]').setValue('caf');

        pendentes.caf(respostaCom({ id: 1, nome: 'Café', marca: null, total_compras: 4 }));
        await flushPromises();
        pendentes.ca(respostaCom({ id: 5, nome: 'Carne', total_compras: 1 }));
        await flushPromises();

        expect(links(wrapper)).toEqual([['Café', '/precos/produtos/1']]);
    });
});
