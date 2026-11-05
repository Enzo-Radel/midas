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

];
