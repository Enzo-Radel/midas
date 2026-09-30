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
- Unidades na API, sem acento: `kg`, `g`, `L`, `ml`, `un`, `duzia`, `pacote` (as duas últimas a partir da etapa 2). A tela mostra "dúzia".
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

### Etapa 2: comparar embalagens diferentes

Cada produto tem uma **unidade base** (`kg`, `L` ou `un`), definida pela unidade da **primeira compra** dele: `kg`/`g` dão `kg`; `L`/`ml` dão `L`; `un`/`duzia`/`pacote` dão `un`. Compras seguintes só aceitam unidades da mesma família.

- `POST /api/precos/compras` (`compras.store`): `unidade` passa a aceitar `duzia` e `pacote`. O corpo **sempre** inclui `unidades_por_pacote`: inteiro >= 1 obrigatório quando `unidade` é `pacote`, `null` nos demais casos. Unidade incompatível com a base do produto: `422` com o erro em `unidade`, em português, dizendo qual é a unidade do produto.
- `GET /api/precos/produtos/{id}` (`produtos.show`): `produto.unidade_base`; cada compra ganha `unidades_por_pacote` e `preco_base_centavos` = preço pago dividido pela quantidade convertida para a unidade base, em centavos, arredondado a 2 casas.
- Conversão para a unidade base: `kg`, `L`, `un` valem 1; `g` e `ml` dividem a quantidade por 1000; `duzia` multiplica por 12; `pacote` multiplica por `unidades_por_pacote`.
- Exemplos que viram testes: café 500 g por R$ 18,90 = 3780 (R$ 37,80/kg); 1 kg por R$ 34,90 = 3490; leite 500 ml por R$ 3,00 = 600 (R$ 6,00/L); ovos, 1 dúzia por R$ 12,00 = 100 (R$ 1,00/un); 1 pacote de 6 un por R$ 9,00 = 150. Registrar `g` para um produto cuja base é `un` é rejeitado.
- Tela: o formulário oferece `dúzia` e `pacote` (este pede "unidades por pacote"); o histórico do produto mostra o preço por unidade base de cada compra no formato `R$ 37,80/kg`.
