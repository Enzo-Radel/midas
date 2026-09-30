import { beforeEach, describe, expect, it, vi } from 'vitest';
import { flushPromises, mount } from '@vue/test-utils';
import axios from 'axios';
import { router } from '@inertiajs/vue3';
import { contract, expectShape } from '../../test-support/contract';
import NovaCompra from './NovaCompra.vue';

vi.mock('axios', () => ({ default: { get: vi.fn(), post: vi.fn() } }));
vi.mock('@inertiajs/vue3', () => ({ router: { visit: vi.fn() } }));

async function preencher(wrapper) {
    await wrapper.find('input[name=produto]').setValue('Café');
    await wrapper.find('input[name=mercado]').setValue('Atacadão');
    await wrapper.find('input[name=data]').setValue('2026-09-14');
    await wrapper.find('input[name=quantidade]').setValue('500');
    await wrapper.find('select[name=unidade]').setValue('g');
    await wrapper.find('input[name=preco_centavos]').setValue('18,90');
}

describe('Precos/NovaCompra', () => {
    beforeEach(() => vi.clearAllMocks());

    it('envia o formato de compras.store.request, com o preço em centavos, e navega para o produto da compra', async () => {
        const store = contract('compras.store');
        axios.post.mockResolvedValue({ data: store.response });

        const wrapper = mount(NovaCompra);
        await preencher(wrapper);
        await wrapper.find('form').trigger('submit');
        await flushPromises();

        expect(axios.post).toHaveBeenCalledTimes(1);
        const [url, corpo] = axios.post.mock.calls[0];
        expect(url).toBe('/api/precos/compras');
        expectShape(corpo, store.request);
        expect(corpo).toEqual({
            produto: 'Café',
            mercado: 'Atacadão',
            data: '2026-09-14',
            quantidade: 500,
            unidade: 'g',
            preco_centavos: 1890,
        });
        expect(router.visit).toHaveBeenCalledWith('/precos/produtos/1');
    });

    it('aceita quantidade decimal com vírgula e arredonda o preço para centavos inteiros', async () => {
        axios.post.mockResolvedValue({ data: contract('compras.store').response });

        const wrapper = mount(NovaCompra);
        await preencher(wrapper);
        await wrapper.find('input[name=quantidade]').setValue('1,5');
        await wrapper.find('input[name=preco_centavos]').setValue('R$ 1.234,56');
        await wrapper.find('form').trigger('submit');
        await flushPromises();

        expect(axios.post.mock.calls[0][1]).toMatchObject({ quantidade: 1.5, preco_centavos: 123456 });
    });

    it('trata o ponto como decimal quando não há vírgula', async () => {
        axios.post.mockResolvedValue({ data: contract('compras.store').response });

        const wrapper = mount(NovaCompra);
        await preencher(wrapper);
        await wrapper.find('input[name=quantidade]').setValue('1.5');
        await wrapper.find('input[name=preco_centavos]').setValue('18.90');
        await wrapper.find('form').trigger('submit');
        await flushPromises();

        expect(axios.post.mock.calls[0][1]).toMatchObject({ quantidade: 1.5, preco_centavos: 1890 });
    });

    it('mostra o erro 422 ao lado do campo e não navega', async () => {
        axios.post.mockRejectedValue({
            response: {
                status: 422,
                data: { message: 'inválido', errors: { preco_centavos: ['O preço deve ser maior que zero.'] } },
            },
        });

        const wrapper = mount(NovaCompra);
        await preencher(wrapper);
        await wrapper.find('form').trigger('submit');
        await flushPromises();

        const campo = wrapper.find('input[name=preco_centavos]').element.closest('label');
        expect(campo.textContent).toContain('O preço deve ser maior que zero.');
        const outro = wrapper.find('input[name=produto]').element.closest('label');
        expect(outro.textContent).not.toContain('O preço deve ser maior que zero.');
        expect(router.visit).not.toHaveBeenCalled();
    });

    it('oferece as unidades do contrato sem dúzia', () => {
        const valores = mount(NovaCompra).findAll('select[name=unidade] option').map((o) => o.element.value);

        expect(valores).toEqual(['kg', 'g', 'L', 'ml', 'un']);
    });
});
