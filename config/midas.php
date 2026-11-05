<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Usuário padrão
    |--------------------------------------------------------------------------
    |
    | Enquanto o sistema não tem login, todas as requisições usam este usuário.
    | Quando o login existir, basta trocar o middleware UseDefaultUser das rotas por autenticação.
    |
    */

    'owner' => [
        'name' => env('MIDAS_OWNER_NAME', 'Usuário'),
        'email' => env('MIDAS_OWNER_EMAIL', 'owner@midas.local'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Categorias iniciais
    |--------------------------------------------------------------------------
    |
    | Lista provisória criada para cada usuário novo. Pode ser trocada aqui,
    | num único lugar; quem já tem categorias não é afetado.
    |
    */

    'default_categories' => [
        'Alimentação',
        'Transporte',
        'Moradia',
        'Saúde',
        'Lazer',
        'Educação',
        'Compras',
        'Outros',
    ],

];
