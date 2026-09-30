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
| `GET /api/precos/produtos` | `produtos.index` | Todos os produtos (a partir da etapa 4: mais comprados primeiro, empate em ordem alfabética, com filtro `q`). |
| `GET /api/precos/produtos/{id}` | `produtos.show` | Produto e todas as suas compras, da mais recente para a mais antiga (empate de data: a registrada por último primeiro). |
| `POST /api/precos/compras` | `compras.store` | (etapa 6: vira registro em lote, ver abaixo) Cria a compra. Produto e mercado criados pelo nome se não existirem. Validação: `produto` e `mercado` obrigatórios (texto, até 255); `data` obrigatória (`YYYY-MM-DD`); `quantidade` numérica > 0; `unidade` em `kg, g, L, ml, un`; `preco_centavos` inteiro > 0. |

Páginas (`paginas.json`): `/precos` lista os produtos com link para cada um e para registrar compra; `/precos/compras/nova` tem o formulário; `/precos/produtos/{id}` mostra o histórico. Depois de registrar, o frontend leva o usuário à página do produto da compra. O item "Preços" entra no menu lateral apontando para `/precos`.

### Etapa 2: comparar embalagens diferentes

Cada produto tem uma **unidade base** (`kg`, `L` ou `un`), definida pela unidade da **primeira compra** dele: `kg`/`g` dão `kg`; `L`/`ml` dão `L`; `un`/`duzia`/`pacote` dão `un`. Compras seguintes só aceitam unidades da mesma família.

- `POST /api/precos/compras` (`compras.store`): `unidade` passa a aceitar `duzia` e `pacote`. O corpo **sempre** inclui `unidades_por_pacote`: inteiro >= 1 obrigatório quando `unidade` é `pacote`, `null` nos demais casos. Unidade incompatível com a base do produto: `422` com o erro em `unidade`, em português, dizendo qual é a unidade do produto.
- `GET /api/precos/produtos/{id}` (`produtos.show`): `produto.unidade_base`; cada compra ganha `unidades_por_pacote` e `preco_base_centavos` = preço pago dividido pela quantidade convertida para a unidade base, em centavos, arredondado a 2 casas.
- Conversão para a unidade base: `kg`, `L`, `un` valem 1; `g` e `ml` dividem a quantidade por 1000; `duzia` multiplica por 12; `pacote` multiplica por `unidades_por_pacote`.
- Exemplos que viram testes: café 500 g por R$ 18,90 = 3780 (R$ 37,80/kg); 1 kg por R$ 34,90 = 3490; leite 500 ml por R$ 3,00 = 600 (R$ 6,00/L); ovos, 1 dúzia por R$ 12,00 = 100 (R$ 1,00/un); 1 pacote de 6 un por R$ 9,00 = 150. Registrar `g` para um produto cuja base é `un` é rejeitado.
- Tela: o formulário oferece `dúzia` e `pacote` (este pede "unidades por pacote"); o histórico do produto mostra o preço por unidade base de cada compra no formato `R$ 37,80/kg`.

### Etapa 3: saber quanto costumo pagar

- `GET /api/precos/produtos/{id}` (`produtos.show`) ganha `resumo`, calculado sobre o **preço por unidade base de todas as compras do produto** (sem excluir nenhuma):
  - `mediana_centavos`: valor central; com quantidade par de compras, a média dos dois centrais. Arredondado a 2 casas.
  - `minimo_centavos` e `maximo_centavos`: menor e maior preço por unidade base já pago.
  - `contagem`: número de compras.
  - `ultima`: a compra mais recente (mesma ordem do histórico: data e, em empate, a registrada por último), com `preco_base_centavos`, `mercado` (`id`, `nome`) e `data`.
  - `periodo`: `inicio` e `fim`, datas da compra mais antiga e da mais recente.
- Todo produto tem ao menos uma compra, então `resumo` nunca é nulo. Com uma única compra: mediana, mínimo e máximo iguais ao preço dela e `contagem` 1.
- Exemplos que viram testes: compras a R$ 10, 10, 10 e 30 por kg dão mediana 1000, faixa 1000 a 3000, contagem 4; compras a R$ 10 e 20 por kg dão mediana 1500.
- Tela (`Precos/Produto`): o resumo aparece no topo, acima do histórico, com a mediana como número de destaque no formato `R$ 37,80/kg`, e abaixo dela, menores: faixa (`R$ 34,90 a R$ 39,10/kg`), última compra (preço por unidade base, mercado e data `dd/mm/aaaa`), contagem ("3 compras", "1 compra") e período coberto (`dd/mm/aaaa a dd/mm/aaaa`, ou uma data só quando início e fim coincidem). Sem veredito de caro ou barato, sem limiar, sem aviso de amostra pequena.

### Etapa 4: consultar na prateleira em segundos

- `GET /api/precos/produtos?q=caf` (`produtos.index`): com `q`, só os produtos cujo nome **contém** o texto (sem diferenciar maiúsculas/minúsculas; `%` e `_` valem como texto comum). Sem `q` (ou vazio), todos. A ordem é sempre **mais comprados primeiro** (`total_compras` decrescente), empate em ordem alfabética. Cada produto ganha `total_compras` (número de compras dele).
- Consultar nunca cria nem altera registro: só `GET`.
- Página `/precos` (`Precos/Index`) vira a tela de consulta: campo de busca em foco ao abrir, sugestões logo abaixo a cada mudança do texto (a resposta mais recente é a que vale, respostas atrasadas de texto anterior são ignoradas) e, sem texto, a lista dos mais comprados. Tocar em uma sugestão abre o produto com o resumo. O link "Registrar compra" continua na página. Campo e sugestões com alvos de toque grandes, usáveis com uma mão no celular.
- Menu no celular: abaixo de 768 px o menu lateral hoje some e não há como abri-lo, então "Preços" só seria alcançável pela URL. Um botão de menu visível nessa largura abre e fecha o menu lateral (o item "Preços" precisa ser alcançável e tocável no celular).

### Etapa 5: comparar o preço entre mercados

- `GET /api/precos/produtos/{id}` (`produtos.show`) ganha `mercados`: uma linha por mercado onde o produto já foi comprado (basta uma compra), ordenada da menor para a maior mediana (empate: nome do mercado em ordem alfabética):
  - `mercado` (`id`, `nome`), `mediana_centavos` (mediana do preço por unidade base das compras **daquele mercado**, 2 casas), `contagem` (compras naquele mercado).
  - `diferenca_percentual`: `(mediana - menor mediana) / menor mediana * 100`, arredondado a 1 casa. O mercado mais barato tem `0`. Produto comprado em um só mercado: `null` (sem diferença a mostrar).
- Exemplos que viram testes: medianas R$ 37,20 (Atacadão) e R$ 39,10 (bairro) dão `0` e `5.1`; mercado com uma única compra aparece com `contagem` 1; produto em um só mercado traz uma linha com `diferenca_percentual` nulo. `resumo` e `compras` não mudam.
- Tela (`Precos/Produto`): abaixo do resumo e acima do histórico, a lista "Por mercado" na mesma ordem recebida: nome do mercado, mediana no formato `R$ 37,20/kg`, contagem ("9 compras", "1 compra") e a diferença: `+5,1%` (vírgula decimal, com sinal) ou, para diferença `0` com mais de um mercado na lista, o rótulo "melhor"; com um só mercado na lista (diferença nula) não aparece nada de diferença. Sem recomendação, destaque de cor por "bom/ruim" nem limiar.

### Etapa 6: registrar a ida ao mercado de uma vez

- `POST /api/precos/compras` (`compras.store`) **passa a registrar vários itens de uma vez** e substitui o corpo de item único das etapas anteriores (que deixa de existir): o corpo é `{ mercado, data, itens: [ { produto, quantidade, unidade, unidades_por_pacote, preco_centavos } ] }`. Mercado e data valem para todos os itens; cada item segue as regras de antes (`unidades_por_pacote` sempre presente: inteiro >= 1 para `pacote`, `null` nos demais). `itens` precisa ter ao menos um item. Resposta `201 { compras: [ ... ] }`, cada compra no formato de antes, na ordem enviada.
- **Tudo ou nada:** se qualquer item for inválido (inclusive unidade incompatível com a do produto), nada é salvo (nem compra, nem produto, nem mercado novos) e a resposta é `422` com os erros por item no formato padrão do Laravel: chaves `itens.0.unidade`, `itens.2.preco_centavos` etc. (o número é a posição do item na lista enviada). As mensagens saem em português com nomes legíveis (ex.: "O campo preço pago deve ser maior que zero."); sem itens: "Adicione ao menos um item." Um produto que aparece duas vezes na lista vale como dois itens (duas compras).
- `GET /api/precos/produtos` (`produtos.index`): cada produto ganha `ultima_compra` (`quantidade`, `unidade`, `unidades_por_pacote`, `preco_centavos` da compra mais recente dele: data e, em empate, a registrada por último). É a fonte do pré-preenchimento.
- Tela `Precos/NovaCompra`: **mercado e data uma vez** (data começa em hoje, no fuso do navegador) e uma lista de itens, cada linha com produto, quantidade, unidade (e unidades por pacote quando pacote) e preço pago. A linha começa vazia; o campo de produto sugere os produtos já cadastrados (`GET /api/precos/produtos?q=`, mais comprados primeiro) e, ao **escolher uma sugestão**, quantidade, unidade, unidades por pacote e preço da linha são preenchidos com a `ultima_compra` (preço em reais, editável). Digitar um nome que não existe é produto novo, criado ao salvar, **sem sair da tela e sem perder os itens já digitados**. Botão para adicionar linha e para remover linha (sempre resta pelo menos uma). Teclado: Enter no campo de preço vai ao campo de produto da linha seguinte, criando-a se for a última; Enter nunca envia o formulário (só o botão "Salvar compra"). Erros `itens.N.campo` aparecem ao lado do campo da linha N; erros de `mercado` e `data` ao lado deles. Ao salvar com sucesso, leva o usuário a `/precos`.
