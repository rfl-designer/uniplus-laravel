<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Conexão Padrão
    |--------------------------------------------------------------------------
    |
    | Define qual conexão será usada por padrão quando nenhuma for especificada.
    |
    */
    'default' => env('UNIPLUS_CONNECTION', 'default'),

    /*
    |--------------------------------------------------------------------------
    | Conexões
    |--------------------------------------------------------------------------
    |
    | Configurações das conexões com a API Uniplus. Suporte a múltiplas
    | conexões para cenários multi-tenant.
    |
    | - account: Nome da conta no Uniplus (usado para resolver URL via routing service)
    | - authorization_code: Código de autorização Base64 (usuário:token)
    | - user_id: ID do usuário para requisições
    | - branch_id: ID da filial padrão
    |
    */
    'connections' => [
        'default' => [
            'account' => env('UNIPLUS_ACCOUNT'),
            'authorization_code' => env('UNIPLUS_AUTH_CODE'),
            'user_id' => env('UNIPLUS_USER_ID', 1),
            'branch_id' => env('UNIPLUS_BRANCH_ID', 1),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache de Tokens
    |--------------------------------------------------------------------------
    |
    | Configurações para cache dos tokens OAuth2.
    | Os tokens da API Uniplus expiram em 60 minutos.
    | O TTL padrão é 58 minutos para garantir renovação antes da expiração.
    |
    */
    'cache' => [
        'enabled' => env('UNIPLUS_CACHE_ENABLED', true),
        'store' => env('UNIPLUS_CACHE_STORE'),
        'prefix' => 'uniplus_token_',
        'ttl' => 3500, // 58 minutos
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP Client
    |--------------------------------------------------------------------------
    |
    | Configurações do cliente HTTP para requisições à API.
    |
    */
    'http' => [
        'timeout' => env('UNIPLUS_TIMEOUT', 30),
        'retry' => [
            'times' => 3,
            'sleep' => 100,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Configurações de logging das requisições.
    | Útil para debug e auditoria.
    |
    */
    'logging' => [
        'enabled' => env('UNIPLUS_LOGGING', true),
        'channel' => env('UNIPLUS_LOG_CHANNEL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Routing Service
    |--------------------------------------------------------------------------
    |
    | URL do serviço de roteamento para resolver o endereço do servidor
    | baseado no nome da conta.
    |
    */
    'routing_service' => 'https://server-portal.intelidata.inf.br/roteador/endereco-servidor',

    /*
    |--------------------------------------------------------------------------
    | API Configuration
    |--------------------------------------------------------------------------
    |
    | Configurações base da API.
    |
    */
    'api' => [
        'base_path' => '/public-api',
        'versions' => [
            'produtos' => 'v1',
            'entidades' => 'v1',
            'davs' => 'v1',
            'saldo-estoque' => 'v2',
            'venda' => 'v2',
            'venda-item' => 'v2',
        ],
    ],
];
