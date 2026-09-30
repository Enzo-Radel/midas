# Contrato da API de Preços

Fonte única de verdade entre backend (Laravel) e frontend (Vue + Inertia). Cada arquivo `<nome>.json` descreve um endpoint:

```json
{ "request": { ...corpo de exemplo ou null }, "status": 200, "response": { ...resposta de exemplo } }
```

- **Backend** confere cada resposta real com `assertContract($response, '<nome>')` (`tests/Support/AssertsContract.php`) e envia o `request` do contrato para provar que aceita o que o frontend envia.
- **Frontend** usa `contract('<nome>')` (`resources/js/test-support/contract.js`) como resposta simulada da API e confere com `expectShape` o que envia.
- Compara-se o **formato** (campos e tipos), não os valores. Nulo na resposta é sempre aceito; nulo no contrato aceita qualquer tipo; em listas, todo item segue o primeiro exemplo. Campo a mais ou a menos é erro.
- Ninguém altera estes arquivos além do orquestrador. Se o contrato atrapalhar, peça a mudança e explique por quê.

## Convenções

- Rotas da API em `/api/precos/...`, JSON, sem autenticação (projeto pessoal, sem login hoje).
- Nomes de campos em português, `snake_case`. Datas `YYYY-MM-DD`.
- Dinheiro: inteiro em **centavos** (`*_centavos`) para valores pagos. Valores derivados por unidade base (mediana, mínimo, máximo, preço por unidade base) também em centavos, como número (pode ter casas decimais, arredondado a 2).
- Quantidades são **números** JSON (nunca string).
- Unidades na API, sem acento: `kg`, `g`, `L`, `ml`, `un`, `duzia`, `pacote`. A tela mostra "dúzia".
- Validação: resposta padrão do Laravel, `422 {"message": "...", "errors": {"campo": ["mensagem"]}}`. Recurso inexistente: `404`. Não há fixtures para esses dois casos.
- Produto e mercado são identificados pelo **nome** no registro (sem diferenciar maiúsculas/minúsculas nem espaços nas pontas): se existe, reaproveita; se não, cria.
- Páginas Inertia: `paginas.json` lista rota, componente e props. As páginas recebem só ids/parâmetros e buscam os dados na API com axios.
- Textos da interface em português do Brasil, com acentos. Valores em reais no formato `R$ 37,80`.

## Endpoints e páginas por etapa

O contrato cresce a cada etapa; o que está aqui é o estado atual.

### Etapa 1: registrar uma compra e rever o histórico

| Endpoint | Contrato | Regras |
| --- | --- | --- |
| `GET /api/precos/produtos` | `produtos.index` | Todos os produtos, em ordem alfabética. |
| `GET /api/precos/produtos/{id}` | `produtos.show` | Produto e todas as suas compras, da mais recente para a mais antiga (empate de data: a registrada por último primeiro). |
| `POST /api/precos/compras` | `compras.store` | Cria a compra. Produto e mercado criados pelo nome se não existirem. Validação: `produto` e `mercado` obrigatórios (texto, até 255); `data` obrigatória (`YYYY-MM-DD`); `quantidade` numérica > 0; `unidade` em `kg, g, L, ml, un`; `preco_centavos` inteiro > 0. |

Páginas (`paginas.json`): `/precos` lista os produtos com link para cada um e para registrar compra; `/precos/compras/nova` tem o formulário; `/precos/produtos/{id}` mostra o histórico. Depois de registrar, o frontend leva o usuário à página do produto da compra. O item "Preços" entra no menu lateral apontando para `/precos`.
