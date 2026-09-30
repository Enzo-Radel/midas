<?php

return [
    'required' => 'O campo :attribute é obrigatório.',
    'array' => 'O campo :attribute deve ser uma lista.',
    'string' => 'O campo :attribute deve ser um texto.',
    'max' => ['string' => 'O campo :attribute não pode ter mais de :max caracteres.'],
    'date_format' => 'O campo :attribute deve estar no formato :format.',
    'numeric' => 'O campo :attribute deve ser um número.',
    'integer' => 'O campo :attribute deve ser um número inteiro.',
    // Só o gt:0 é usado hoje; se surgir outro valor, trocar "zero" por :value.
    'gt' => ['numeric' => 'O campo :attribute deve ser maior que zero.'],
    'required_if' => 'O campo :attribute é obrigatório quando :other é :value.',
    'min' => ['numeric' => 'O campo :attribute deve ser no mínimo :min.'],
    'in' => 'O campo :attribute selecionado é inválido.',

    'custom' => ['itens' => ['required' => 'Adicione ao menos um item.']],

    'attributes' => [
        'itens.*.produto' => 'produto',
        'itens.*.quantidade' => 'quantidade',
        'itens.*.unidade' => 'unidade',
        'itens.*.unidades_por_pacote' => 'unidades por pacote',
        'itens.*.preco_centavos' => 'preço pago',
    ],
];
