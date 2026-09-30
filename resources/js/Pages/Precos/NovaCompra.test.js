import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { flushPromises, mount } from '@vue/test-utils';
import axios from 'axios';
import { router } from '@inertiajs/vue3';
import { contract, expectShape } from '../../test-support/contract';
import NovaCompra from './NovaCompra.vue';

vi.mock('axios', () => ({ default: { get: vi.fn(), post: vi.fn() } }));
vi.mock('@inertiajs/vue3', () => ({ router: { visit: vi.fn() } }));

const store = contract('compras.store');
const campo = (wrapper, nome, linha = 0) =>
    ['mercado', 'data'].includes(nome) ? wrapper.find(`[name=${nome}]`) : wrapper.findAll('.item')[linha].find(`[name=${nome}]`);
const linhas = (wrapper) => wrapper.findAll('.item').length;

async function preencherLinha(wrapper, i, { produto, quantidade, unidade, unidades_por_pacote, preco }) {
    await campo(wrapper, 'produto', i).setValue(produto);
    await campo(wrapper, 'quantidade', i).setValue(quantidade);
    await campo(wrapper, 'unidade', i).setValue(unidade);
    if (unidades_por_pacote) {
        await campo(wrapper, 'unidades_por_pacote', i).setValue(unidades_por_pacote);
    }
    await campo(wrapper, 'preco_centavos', i).setValue(preco);
}

async function preencherCabecalho(wrapper) {
    await campo(wrapper, 'mercado').setValue('Atacadão');
    await campo(wrapper, 'data').setValue('2026-09-14');
}

const cafe = { produto: 'Café', quantidade: '500', unidade: 'g', preco: '18,90' };

async function salvar(wrapper) {
    await wrapper.find('form').trigger('submit');
    await flushPromises();
}

describe('Precos/NovaCompra', () => {
    beforeEach(() => {
        vi.clearAllMocks();
        axios.get.mockResolvedValue({ data: contract('produtos.index').response });
        axios.post.mockResolvedValue({ data: store.response });
    });
    afterEach(() => vi.useRealTimers());

    it('envia uma requisição no formato de compras.store.request, com vários itens (pacote e não-pacote), e vai para /precos', async () => {
        const wrapper = mount(NovaCompra);
        await preencherCabecalho(wrapper);
        await preencherLinha(wrapper, 0, cafe);
        await wrapper.find('button.adicionar').trigger('click');
        await preencherLinha(wrapper, 1, { produto: 'Ovos', quantidade: '2', unidade: 'pacote', unidades_por_pacote: '6', preco: '9,00' });
        await salvar(wrapper);

        expect(axios.post).toHaveBeenCalledTimes(1);
        const [url, corpo] = axios.post.mock.calls[0];
        expect(url).toBe('/api/precos/compras');
        expectShape(corpo, store.request);
        expect(corpo).toEqual({
            mercado: 'Atacadão',
            data: '2026-09-14',
            itens: [
                { produto: 'Café', quantidade: 500, unidade: 'g', unidades_por_pacote: null, preco_centavos: 1890 },
                { produto: 'Ovos', quantidade: 2, unidade: 'pacote', unidades_por_pacote: 6, preco_centavos: 900 },
            ],
        });
        expect(router.visit).toHaveBeenCalledWith('/precos');
    });

    it('converte preço e quantidade digitados: vírgula ou ponto decimal, milhar e símbolo R$', async () => {
        const wrapper = mount(NovaCompra);
        await preencherCabecalho(wrapper);
        await preencherLinha(wrapper, 0, { ...cafe, quantidade: '1,5', preco: 'R$ 1.234,56' });
        await wrapper.find('button.adicionar').trigger('click');
        await preencherLinha(wrapper, 1, { ...cafe, quantidade: '1.5', preco: '18.90' });
        await salvar(wrapper);

        expect(axios.post.mock.calls[0][1].itens).toMatchObject([
            { quantidade: 1.5, preco_centavos: 123456 },
            { quantidade: 1.5, preco_centavos: 1890 },
        ]);
    });

    it('oferece as unidades do contrato, inclusive dúzia e pacote, e só pede unidades por pacote para pacote', async () => {
        const wrapper = mount(NovaCompra);
        const opcoes = campo(wrapper, 'unidade').findAll('option');

        expect(opcoes.map((o) => o.element.value)).toEqual(['kg', 'g', 'L', 'ml', 'un', 'duzia', 'pacote']);
        expect(opcoes[5].text()).toBe('dúzia');
        expect(wrapper.find('[name=unidades_por_pacote]').exists()).toBe(false);

        await campo(wrapper, 'unidade').setValue('pacote');
        expect(wrapper.text()).toContain('Unidades por pacote');
        await campo(wrapper, 'unidades_por_pacote').setValue('6');
        await campo(wrapper, 'unidade').setValue('duzia');
        expect(wrapper.find('[name=unidades_por_pacote]').exists()).toBe(false);

        await preencherCabecalho(wrapper);
        await preencherLinha(wrapper, 0, { ...cafe, unidade: 'duzia' });
        await salvar(wrapper);

        expect(axios.post.mock.calls[0][1].itens[0]).toMatchObject({ unidade: 'duzia', unidades_por_pacote: null });
    });

    it('a data começa em hoje', () => {
        vi.useFakeTimers({ toFake: ['Date'] });
        vi.setSystemTime(new Date(2026, 8, 30, 21, 30));

        expect(campo(mount(NovaCompra), 'data').element.value).toBe('2026-09-30');
    });

    it('escolher uma sugestão preenche a linha com a última compra, e os campos continuam editáveis', async () => {
        const resposta = contract('produtos.index').response;
        resposta.produtos = [
            { id: 1, nome: 'Café', total_compras: 4, ultima_compra: { quantidade: 1.5, unidade: 'kg', unidades_por_pacote: null, preco_centavos: 3490 } },
            { id: 2, nome: 'Ovos', total_compras: 2, ultima_compra: { quantidade: 2, unidade: 'pacote', unidades_por_pacote: 6, preco_centavos: 1800 } },
        ];
        axios.get.mockResolvedValue({ data: resposta });

        const wrapper = mount(NovaCompra);
        await campo(wrapper, 'produto').setValue('ca');
        await flushPromises();

        expect(axios.get).toHaveBeenLastCalledWith('/api/precos/produtos', { params: { q: 'ca' } });
        const sugestoes = wrapper.findAll('.sugestao');
        expect(sugestoes.map((s) => s.text())).toEqual(['Café', 'Ovos']);

        await sugestoes[0].trigger('click');
        expect(campo(wrapper, 'produto').element.value).toBe('Café');
        expect(campo(wrapper, 'quantidade').element.value).toBe('1,5');
        expect(campo(wrapper, 'unidade').element.value).toBe('kg');
        expect(campo(wrapper, 'preco_centavos').element.value).toBe('34,90');
        expect(wrapper.findAll('.sugestao')).toHaveLength(0);

        await campo(wrapper, 'produto').setValue('o');
        await flushPromises();
        await wrapper.findAll('.sugestao')[1].trigger('click');
        expect(campo(wrapper, 'unidade').element.value).toBe('pacote');
        expect(campo(wrapper, 'unidades_por_pacote').element.value).toBe('6');
        expect(campo(wrapper, 'preco_centavos').element.value).toBe('18,00');

        await campo(wrapper, 'preco_centavos').setValue('17,50');
        await preencherCabecalho(wrapper);
        await salvar(wrapper);

        expect(axios.post.mock.calls[0][1].itens[0]).toMatchObject({ produto: 'Ovos', quantidade: 2, unidade: 'pacote', unidades_por_pacote: 6, preco_centavos: 1750 });
    });

    it('produto novo é enviado como digitado e não apaga as outras linhas', async () => {
        const wrapper = mount(NovaCompra);
        await preencherCabecalho(wrapper);
        await preencherLinha(wrapper, 0, cafe);
        await wrapper.find('button.adicionar').trigger('click');
        await preencherLinha(wrapper, 1, { produto: 'Produto Inédito', quantidade: '1', unidade: 'un', preco: '5' });
        await flushPromises();

        expect(campo(wrapper, 'produto', 0).element.value).toBe('Café');
        expect(campo(wrapper, 'preco_centavos', 0).element.value).toBe('18,90');
        await salvar(wrapper);

        expect(axios.post.mock.calls[0][1].itens.map((i) => i.produto)).toEqual(['Café', 'Produto Inédito']);
    });

    it('adiciona e remove linhas, sem nunca ficar sem nenhuma', async () => {
        const wrapper = mount(NovaCompra);
        expect(linhas(wrapper)).toBe(1);
        expect(wrapper.find('button.remover').attributes('disabled')).toBeDefined();

        await wrapper.find('button.adicionar').trigger('click');
        await wrapper.find('button.adicionar').trigger('click');
        await campo(wrapper, 'produto', 0).setValue('Primeiro');
        await campo(wrapper, 'produto', 1).setValue('Segundo');
        await campo(wrapper, 'produto', 2).setValue('Terceiro');
        expect(linhas(wrapper)).toBe(3);

        await wrapper.findAll('button.remover')[1].trigger('click');
        expect(wrapper.findAll('input[name=produto]').map((i) => i.element.value)).toEqual(['Primeiro', 'Terceiro']);

        await wrapper.findAll('button.remover')[0].trigger('click');
        await wrapper.findAll('button.remover')[0].trigger('click');
        expect(linhas(wrapper)).toBe(1);
    });

    it('Enter no preço vai ao produto da linha seguinte, criando-a se for a última', async () => {
        const wrapper = mount(NovaCompra, { attachTo: document.body });

        await campo(wrapper, 'preco_centavos', 0).trigger('keydown.enter');
        expect(linhas(wrapper)).toBe(2);
        expect(document.activeElement).toBe(campo(wrapper, 'produto', 1).element);

        await campo(wrapper, 'preco_centavos', 0).trigger('keydown.enter');
        expect(linhas(wrapper)).toBe(2);
        expect(document.activeElement).toBe(campo(wrapper, 'produto', 1).element);
        wrapper.unmount();
    });

    it('Enter nunca envia o formulário', async () => {
        const wrapper = mount(NovaCompra);
        await preencherCabecalho(wrapper);
        await preencherLinha(wrapper, 0, cafe);

        for (const nome of ['produto', 'mercado', 'data', 'quantidade', 'preco_centavos']) {
            const evento = new KeyboardEvent('keydown', { key: 'Enter', bubbles: true, cancelable: true });
            campo(wrapper, nome).element.dispatchEvent(evento);
            expect(evento.defaultPrevented).toBe(true);
        }

        expect(axios.post).not.toHaveBeenCalled();
        expect(wrapper.find('button[type=submit]').text()).toBe('Salvar compra');
    });

    it('mostra erros 422 ao lado do campo da linha, de mercado, de data e do erro geral de itens', async () => {
        axios.post.mockRejectedValue({
            response: {
                status: 422,
                data: {
                    message: 'inválido',
                    errors: {
                        mercado: ['Informe o mercado.'],
                        data: ['Data inválida.'],
                        itens: ['Adicione ao menos um item.'],
                        'itens.1.preco_centavos': ['O campo preço pago deve ser maior que zero.'],
                        'itens.1.unidade': ['Este produto é vendido em kg.'],
                        'itens.1.unidades_por_pacote': ['Informe as unidades do pacote.'],
                    },
                },
            },
        });

        const wrapper = mount(NovaCompra);
        await preencherLinha(wrapper, 0, cafe);
        await wrapper.find('button.adicionar').trigger('click');
        await campo(wrapper, 'unidade', 1).setValue('pacote');
        await salvar(wrapper);

        const rotulo = (nome, linha = 0) => campo(wrapper, nome, linha).element.closest('label').textContent;
        expect(rotulo('mercado')).toContain('Informe o mercado.');
        expect(rotulo('data')).toContain('Data inválida.');
        expect(rotulo('preco_centavos', 1)).toContain('O campo preço pago deve ser maior que zero.');
        expect(rotulo('unidade', 1)).toContain('Este produto é vendido em kg.');
        expect(rotulo('unidades_por_pacote', 1)).toContain('Informe as unidades do pacote.');
        expect(rotulo('preco_centavos', 0)).not.toContain('preço pago deve');
        expect(wrapper.text()).toContain('Adicione ao menos um item.');
        expect(router.visit).not.toHaveBeenCalled();
    });

    it('monta e envia uma compra de 15 itens em uma requisição', async () => {
        const wrapper = mount(NovaCompra);
        await preencherCabecalho(wrapper);
        for (let i = 0; i < 15; i++) {
            if (i > 0) {
                await wrapper.find('button.adicionar').trigger('click');
            }
            await preencherLinha(wrapper, i, { produto: `Produto ${i}`, quantidade: '1', unidade: 'un', preco: `${i + 1},00` });
        }
        await salvar(wrapper);

        expect(axios.post).toHaveBeenCalledTimes(1);
        const corpo = axios.post.mock.calls[0][1];
        expectShape(corpo, store.request);
        expect(corpo.itens).toHaveLength(15);
        expect(corpo.itens[14]).toEqual({ produto: 'Produto 14', quantidade: 1, unidade: 'un', unidades_por_pacote: null, preco_centavos: 1500 });
    });

    describe('repetir última compra', () => {
        const ultimaIda = contract('compras.ultima-ida');
        const itemIda = (produto, quantidade, unidade, unidades_por_pacote, preco_centavos) => ({ produto, quantidade, unidade, unidades_por_pacote, preco_centavos });
        const ida = (...itens) => ({ data: { data: '2026-08-01', itens } });
        const responder = (resposta) =>
            axios.get.mockImplementation(async (url) => (url.includes('ultima-ida') ? resposta : { data: contract('produtos.index').response }));
        const repetir = async (wrapper) => {
            await wrapper.find('button.repetir').trigger('click');
            await flushPromises();
        };

        it('consulta a última ida com o mercado digitado e acrescenta as linhas depois das existentes, sem a linha vazia', async () => {
            responder({ data: ultimaIda.response });

            const wrapper = mount(NovaCompra);
            expect(wrapper.find('button.repetir').text()).toBe('Repetir última compra');
            await campo(wrapper, 'mercado').setValue('Atacadão');
            await repetir(wrapper);

            expect(axios.get).toHaveBeenCalledWith('/api/precos/compras/ultima-ida', { params: { mercado: 'Atacadão' } });
            expect(linhas(wrapper)).toBe(1);
            expect(campo(wrapper, 'produto').element.value).toBe('Café');
            expect(campo(wrapper, 'quantidade').element.value).toBe('500');
            expect(campo(wrapper, 'unidade').element.value).toBe('g');
            expect(campo(wrapper, 'preco_centavos').element.value).toBe('18,90');
            expect(campo(wrapper, 'levei').element.checked).toBe(true);
        });

        it('mantém as linhas já digitadas, traz pacote preenchido e deixa tudo editável; a data continua a da tela', async () => {
            responder(ida(itemIda('Ovos', 2, 'pacote', 6, 900), itemIda('Leite', 1.5, 'L', null, 650)));

            const wrapper = mount(NovaCompra);
            await campo(wrapper, 'mercado').setValue('Atacadão');
            await campo(wrapper, 'data').setValue('2026-09-14');
            await preencherLinha(wrapper, 0, cafe);
            await repetir(wrapper);

            expect(wrapper.findAll('input[name=produto]').map((i) => i.element.value)).toEqual(['Café', 'Ovos', 'Leite']);
            expect(campo(wrapper, 'unidade', 1).element.value).toBe('pacote');
            expect(campo(wrapper, 'unidades_por_pacote', 1).element.value).toBe('6');
            expect(campo(wrapper, 'quantidade', 2).element.value).toBe('1,5');

            await campo(wrapper, 'preco_centavos', 1).setValue('8,50');
            await salvar(wrapper);

            expect(axios.post.mock.calls[0][1].data).toBe('2026-09-14');
            expect(axios.post.mock.calls[0][1].itens).toMatchObject([
                { produto: 'Café' },
                { produto: 'Ovos', unidade: 'pacote', unidades_por_pacote: 6, preco_centavos: 850 },
                { produto: 'Leite', quantidade: 1.5, preco_centavos: 650 },
            ]);
        });

        it('envia só as linhas marcadas em "Levei"', async () => {
            responder(ida(itemIda('Ovos', 1, 'duzia', null, 1200), itemIda('Leite', 1, 'L', null, 650), itemIda('Pão', 1, 'un', null, 800)));

            const wrapper = mount(NovaCompra);
            await preencherCabecalho(wrapper);
            await repetir(wrapper);
            await campo(wrapper, 'levei', 1).setValue(false);
            await salvar(wrapper);

            const corpo = axios.post.mock.calls[0][1];
            expectShape(corpo, store.request);
            expect(corpo.itens.map((i) => i.produto)).toEqual(['Ovos', 'Pão']);
        });

        it('sem nenhuma marcada não envia e mostra "Marque ao menos um item."', async () => {
            const wrapper = mount(NovaCompra);
            await preencherCabecalho(wrapper);
            await preencherLinha(wrapper, 0, cafe);
            await campo(wrapper, 'levei').setValue(false);
            await salvar(wrapper);

            expect(axios.post).not.toHaveBeenCalled();
            expect(wrapper.text()).toContain('Marque ao menos um item.');
        });

        it('mostra o erro 422 na linha certa mesmo com linhas desmarcadas antes dela', async () => {
            axios.post.mockRejectedValue({ response: { status: 422, data: { message: 'inválido', errors: { 'itens.0.preco_centavos': ['Preço inválido.'] } } } });

            const wrapper = mount(NovaCompra);
            await preencherCabecalho(wrapper);
            await preencherLinha(wrapper, 0, cafe);
            await wrapper.find('button.adicionar').trigger('click');
            await preencherLinha(wrapper, 1, { ...cafe, produto: 'Leite' });
            await campo(wrapper, 'levei', 0).setValue(false);
            await salvar(wrapper);

            expect(campo(wrapper, 'preco_centavos', 1).element.closest('label').textContent).toContain('Preço inválido.');
            expect(campo(wrapper, 'preco_centavos', 0).element.closest('label').textContent).not.toContain('Preço inválido.');
        });

        it('avisa quando não há compra anterior e não muda as linhas', async () => {
            responder({ data: { data: null, itens: [] } });

            const wrapper = mount(NovaCompra);
            await campo(wrapper, 'mercado').setValue('Mercado Novo');
            await preencherLinha(wrapper, 0, cafe);
            await repetir(wrapper);

            expect(wrapper.text()).toContain('Nenhuma compra anterior neste mercado.');
            expect(linhas(wrapper)).toBe(1);
            expect(campo(wrapper, 'produto').element.value).toBe('Café');
        });

        it('o botão fica desabilitado com o mercado vazio', async () => {
            const wrapper = mount(NovaCompra);

            expect(wrapper.find('button.repetir').attributes('disabled')).toBeDefined();
            await campo(wrapper, 'mercado').setValue('Atacadão');
            expect(wrapper.find('button.repetir').attributes('disabled')).toBeUndefined();
        });

        it('ignora a resposta atrasada de uma consulta antiga', async () => {
            const pendentes = {};
            axios.get.mockImplementation((url, { params }) => new Promise((resolve) => (pendentes[params.mercado] = resolve)));

            const wrapper = mount(NovaCompra);
            await campo(wrapper, 'mercado').setValue('Antigo');
            await wrapper.find('button.repetir').trigger('click');
            await campo(wrapper, 'mercado').setValue('Atual');
            await wrapper.find('button.repetir').trigger('click');

            pendentes.Atual(ida(itemIda('Café', 500, 'g', null, 1890)));
            await flushPromises();
            pendentes.Antigo(ida(itemIda('Velho', 1, 'un', null, 100)));
            await flushPromises();

            expect(wrapper.findAll('input[name=produto]').map((i) => i.element.value)).toEqual(['Café']);
        });
    });
});
