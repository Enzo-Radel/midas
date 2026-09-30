import { describe, expect, it } from 'vitest';
import { shapeErrors } from './contract';

describe('shapeErrors', () => {
    it('aceita resposta com o mesmo formato', () => {
        const esperado = { id: 1, nome: 'Café', preco: 3780.5, ativo: true, itens: [{ a: 1 }] };
        const recebido = { id: 9, nome: 'Leite', preco: 600, ativo: false, itens: [{ a: 2 }, { a: 3 }] };

        expect(shapeErrors(recebido, esperado)).toEqual([]);
    });

    it('aceita lista vazia e nulo', () => {
        const esperado = { itens: [{ a: 1 }], ultima: { preco: 10 } };

        expect(shapeErrors({ itens: [], ultima: null }, esperado)).toEqual([]);
    });

    it('aponta campo ausente, extra e tipo errado', () => {
        const esperado = { id: 1, nome: 'Café' };

        expect(shapeErrors({ id: 1 }, esperado)).toEqual(['$.nome: campo ausente']);
        expect(shapeErrors({ id: 1, nome: 'a', x: 1 }, esperado)).toEqual(['$.x: campo fora do contrato']);
        expect(shapeErrors({ id: '1', nome: 'a' }, esperado)).toEqual(['$.id: esperado number, recebido string']);
    });

    it('confere cada item da lista contra o primeiro exemplo', () => {
        expect(shapeErrors({ itens: [{ a: 1 }, {}] }, { itens: [{ a: 1 }] })).toEqual(['$.itens[1].a: campo ausente']);
    });
});
