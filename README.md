# Midas

Gestor de finanças e investimentos pessoais — projeto pessoal para controle de receitas, despesas e acompanhamento de investimentos.

## Sobre o projeto

O **Midas** é uma aplicação para gerenciamento financeiro pessoal, permitindo:

- Controle de receitas e despesas
- Acompanhamento de investimentos
- Visão consolidada da saúde financeira

## Stack tecnológica

- **Backend:** Laravel 12 (PHP 8.2+)
- **Banco de dados:** MySQL 8.4
- **Ambiente:** Laravel Sail (Docker)
- **Frontend:** Vite

## Pré-requisitos

- PHP 8.2+
- Composer
- Node.js e npm
- Docker e Docker Compose (para usar com Sail)

## Instalação

1. Clone o repositório e entre na pasta do projeto:

```bash
cd midas
```

2. Instale as dependências PHP:

```bash
composer install
```

3. Configure o ambiente:

```bash
cp .env.example .env
php artisan key:generate
```

4. Configure as variáveis de banco de dados no `.env` (ou use os valores padrão do Sail).

5. Com Docker (Sail):

```bash
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate
```

6. Sem Docker:

```bash
php artisan migrate
npm install && npm run build
php artisan serve
```

## Desenvolvimento

- **Com Sail:** `./vendor/bin/sail up` e acesse `http://localhost` (ou a porta definida em `APP_PORT` no `.env`).
- **Script de desenvolvimento (Laravel):** `composer run dev` — sobe servidor, fila, logs e Vite em paralelo.

## API

*(Documentação da API será adicionada aqui — endpoints, autenticação e exemplos de uso.)*

## Licença

Projeto pessoal — uso livre.
