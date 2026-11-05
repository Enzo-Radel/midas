# 09 — Lista de favoritos para repetir

**Status:** concluída · **Atualizada em:** 2026-11-05
**Depende de:** etapa 08

## O que esta etapa entrega
Passa a existir a lista de favoritos, acessível a partir da tela inicial. Tocar num favorito abre o cadastro preenchido com os dados dele e a data de hoje.

## Critérios de aceite
- [x] A lista de favoritos é acessível a partir da tela inicial.
- [x] Tocar num favorito abre o cadastro com os dados dele e a data de hoje, e nada é salvo até a pessoa confirmar.
- [x] Qualquer campo pode ser alterado antes de salvar, sem alterar o favorito.
- [x] Com a lista vazia, aparece uma mensagem explicando que dá para favoritar a partir de qualquer gasto.
- [x] A tela é confortável de usar no celular.

## Registro da implementação
**O que foi alterado**
- A lista de despesas ganhou o botão "Favoritos" no cabeçalho (ao lado de "Novo gasto"), que abre a tela "Favoritos".
- A tela lista os favoritos em ordem alfabética (sem diferenciar maiúsculas nem acentos; "Água" fica junto do "A"), com os que não têm nome por último. Cada favorito mostra nome, valor, categoria e tags. Um favorito sem nome é identificado pelo valor e pela categoria (ex.: "R$ 12,00 · Transporte").
- Tocar num favorito abre a tela "Usar favorito": o cadastro preenchido com os dados dele e a data de hoje. Nada é salvo até a pessoa confirmar; qualquer campo pode ser alterado, e salvar cria um gasto novo sem mexer no favorito. Cancelar volta para a lista de favoritos.
- Com a lista vazia, aparece a mensagem "Nenhum favorito ainda", explicando como favoritar (pelo menu de um gasto ou marcando "Salvar nos favoritos" ao registrar).
- Cada usuário só vê e usa os próprios favoritos; favoritos cujo gasto de origem foi excluído continuam na lista.

**Testes**
- `tests/Feature/Expenses/FavoriteListTest.php` — lista com os dados do favorito; lista vazia; ordem alfabética com acentos e sem nome por último; favorito sem descrição traz valor e categoria; só os favoritos do usuário; favorito de gasto excluído continua listado; usar abre o cadastro preenchido sem a data original; usar não grava nada e não toca o favorito; salvar um gasto com dados alterados não muda o favorito; favorito sem dados opcionais pode ser usado; favorito de outro usuário dá "não encontrado".
- `tests/Feature/Expenses/RepeatExpenseTest.php` — passou a confirmar também a origem do preenchimento.

**Verificação manual** (navegador, tela de celular, banco temporário)
- O botão "Favoritos" na lista de despesas abre a tela; com a lista vazia a mensagem aparece; com 4 favoritos (um sem descrição) a ordem e os rótulos estão certos; tocar em "Almoço" abre com "25,50", a descrição, as tags e a data de hoje; alterar valor e descrição e salvar cria o gasto e deixa o favorito igual; Cancelar volta para a lista de favoritos e não cria gasto.
- Para o usuário conferir no próprio celular: o cabeçalho da lista com os dois botões e o conforto de toque nos cartões.

**Desvios do planejado**
- A montagem dos dados do formulário (categorias, tags, valor em texto e preenchimento a partir de gasto ou favorito) foi extraída para `ExpenseFormData`, usada por "Repetir" e "Usar favorito", para não duplicar essa lógica.
- A tela de preenchimento passou a saber de onde vem: "Usar favorito" (Cancelar volta aos favoritos) ou "Repetir gasto" (Cancelar volta às despesas).
- Um bug de ordenação por acentos foi achado pelo próprio teste e corrigido (a ordem comparava bytes, e "Água" ia parar depois de "zebra").
- O teste "a lista de despesas leva aos favoritos" foi descartado por não verificar o link de verdade; o link é verificado no navegador.

**Revisão automática**
- Corrigido: título e Cancelar da tela de uso do favorito, e teste de que usar o favorito não o altera.
- Pontos não corrigidos estão em `../pontos-para-revisao-final.md`.
