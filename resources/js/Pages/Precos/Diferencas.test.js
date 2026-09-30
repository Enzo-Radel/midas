import { beforeEach, describe, expect, it, vi } from 'vitest';
import { flushPromises, mount } from '@vue/test-utils';
import axios from 'axios';
import { contract } from '../../test-support/contract';
import Diferencas from './Diferencas.vue';

vi.mock('axios', () => ({ default: { get: vi.fn(), post: vi.fn() } }));

const montar = async (resposta) => {
    axios.get.mockResolvedValue({ data: resposta });
    const wrapper = mount(Diferencas);
    await flushPromises();

    return wrapper;
};

describe('Precos/Diferencas', () => {
    beforeEach(() => vi.clearAllMocks());

    it('chama a API e mostra uma linha por produto na ordem recebida, com diferença e mercados mais barato e mais caro', async () => {
        const resposta = contract('diferencas.index').response;
        resposta.produtos.push({
            produto: { id: 2, nome: 'Leite', marca: null, unidade_base: 'L' },
            menor: { mercado: { id: 1, nome: 'Atacadão' }, mediana_centavos: 600 },
            maior: { mercado: { id: 3, nome: 'Padaria' }, mediana_centavos: 650 },
            diferenca_percentual: 8.3,
        });

        const wrapper = await montar(resposta);

        expect(axios.get).toHaveBeenCalledWith('/api/precos/diferencas');

        const linhas = wrapper.findAll('li');
        expect(linhas).toHaveLength(2);

        const primeira = linhas[0].text().replace(/\s+/g, ' ');
        expect(linhas[0].find('a').text()).toBe('Café Pilão');
        expect(linhas[0].find('a').attributes('href')).toBe('/precos/produtos/1');
        expect(primeira).toContain('+19,6%');
        expect(primeira).toContain('Atacadão R$ 37,20/kg');
        expect(primeira).toContain('Mercado do bairro R$ 44,50/kg');
        expect(primeira.indexOf('Atacadão')).toBeLessThan(primeira.indexOf('Mercado do bairro'));

        const segunda = linhas[1].text().replace(/\s+/g, ' ');
        expect(linhas[1].find('a').text()).toBe('Leite');
        expect(linhas[1].find('a').attributes('href')).toBe('/precos/produtos/2');
        expect(segunda).toContain('+8,3%');
        expect(segunda).toContain('Atacadão R$ 6,00/L');
        expect(segunda).toContain('Padaria R$ 6,50/L');
    });

    it('sem produtos mostra que nenhum foi comprado em mais de um mercado', async () => {
        const wrapper = await montar({ produtos: [] });

        expect(wrapper.findAll('li')).toHaveLength(0);
        expect(wrapper.text()).toContain('Nenhum produto comprado em mais de um mercado ainda.');
    });
});
