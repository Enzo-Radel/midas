# 08 — Favoritar um gasto

**Status:** concluída · **Atualizada em:** 2026-11-05
**Depende de:** etapa 07

## O que esta etapa entrega
Passa a ser possível favoritar um gasto no cadastro, na edição ou direto da lista. O favorito é uma cópia independente dos dados do gasto (descrição, valor, categoria, tags).

## Critérios de aceite
- [x] No cadastro (inclusive vindo de repetir) existe a opção favoritar, e salvar com ela marcada cria o favorito.
- [x] Na edição e na lista é possível favoritar um gasto já cadastrado.
- [x] Um gasto já favoritado aparece como tal, e favoritá-lo de novo não cria um favorito duplicado.
- [x] É possível desfavoritar a partir do gasto que deu origem ao favorito.
- [x] Editar ou excluir o gasto de origem não altera nem remove o favorito.
- [x] O nome do favorito começa sendo a descrição; sem descrição, ele é identificado pelo valor e pela categoria.
- [x] A tela é confortável de usar no celular.

## Fora desta etapa
- A lista de favoritos para repetir (etapa 09).

## Registro da implementação
**O que foi alterado**
- O cadastro e a edição de gasto ganharam a opção "Salvar nos favoritos". Marcada ao salvar, cria o favorito com uma cópia dos dados do gasto (valor, descrição, categoria e tags); vale também para o gasto novo vindo de "Repetir". Na edição de um gasto já favoritado a caixa vem marcada, e desmarcá-la remove o favorito.
- No menu de ações de cada gasto passaram a existir **Favoritar** e **Desfavoritar**, imediatos. Gastos favoritados mostram uma estrela ao lado da descrição.
- O favorito é independente: editar o gasto de origem não o altera, excluir o gasto não o remove, e as tags do favorito não acompanham as do gasto. Favoritar duas vezes o mesmo gasto não cria um segundo favorito.
- O nome do favorito começa sendo a descrição do gasto. Para gasto sem descrição o nome fica vazio e o valor e a categoria são guardados (a lista de favoritos, na etapa 09, decide como exibir).
- Só é possível favoritar e desfavoritar gastos do próprio usuário.

**Testes**
- `tests/Feature/Expenses/FavoriteExpenseTest.php` — favorito criado ao salvar (com cópia dos dados) e não criado sem a opção; favoritar da lista e na edição; sem duplicar; indicação na lista e na edição; desfavoritar pelo gasto e desmarcando na edição; campo ausente não muda nada; editar ou excluir o gasto de origem não altera o favorito; favoritar gasto vindo de repetição; favorito sem descrição; tags independentes; gasto inválido não cria favorito; outro usuário recebe "não encontrado"; o banco não aceita dois favoritos do mesmo gasto e favoritos de gastos excluídos não conflitam.

**Verificação manual** (navegador, tela de celular, banco temporário)
- Menu com Editar, Repetir, Favoritar, Excluir; "Favoritar" põe a estrela e o menu passa a mostrar "Desfavoritar", que a remove. Cadastrar com a caixa marcada mostra a estrela; na edição a caixa vem marcada, e desmarcar e salvar tira a estrela.
- Para o usuário conferir no próprio celular: conforto da caixa de favoritos e do menu com 4 itens.

**Desvios do planejado**
- Um erro de teste foi corrigido no meio da etapa: o auxiliar de dados dos testes ignorava os valores passados (o `+` do PHP mantém os da esquerda), e isso deixava três testes sem verificar nada. Foi corrigido, e os testes passaram a confirmar também que a ação funcionou (redirecionamento e estado do gasto).
- Foi adicionado um índice único em `source_expense_id` (um favorito por gasto), para impedir duplicados mesmo com dois salvamentos simultâneos.

**Revisão automática**
- Corrigido: testes que não confirmavam a ação, teste de repetir passou a usar a rota de repetir, método sem uso removido, favoritar da lista agora em transação, índice único, contraste da estrela, estado `:active` da caixa, injeção do serviço.
- Pontos não corrigidos estão em `../pontos-para-revisao-final.md`.
