# 13 — Desativar e reativar categoria

**Status:** concluída · **Atualizada em:** 2026-11-05
**Depende de:** etapa 12

## O que esta etapa entrega
Uma categoria em uso pode ser desativada: deixa de aparecer para escolha, mas continua nos gastos que já a têm, e pode ser reativada.

## Critérios de aceite
- [x] Ao tentar excluir uma categoria com gastos, o sistema informa quantos gastos a usam e oferece desativá-la.
- [x] Uma categoria desativada não aparece para escolha no cadastro nem na edição.
- [x] Gastos antigos continuam exibindo a categoria desativada, e ela permanece ao editar o gasto enquanto não for trocada.
- [x] Repetir um gasto ou favorito com categoria desativada abre o cadastro sem categoria, com um aviso de que a categoria original está desativada.
- [x] Uma categoria desativada pode ser reativada e volta a aparecer para escolha.

## Registro da implementação
**O que foi alterado**
- Na tela "Categorias", o menu de cada categoria ganhou **Desativar** e, para uma desativada, **Reativar**. Uma categoria desativada mostra o selo "Desativada" e o nome em cinza, e continua com a contagem de uso.
- Ao tentar **Excluir** uma categoria ativa que está em uso, aparece o diálogo "Esta categoria está em uso", que diz quantos gastos e favoritos a usam, explica que ela não pode ser excluída e oferece **Desativar** (botão roxo, por ser reversível). Cancelar não muda nada. O servidor continua recusando a exclusão de categoria em uso.
- Uma categoria desativada deixa de ser oferecida para escolha no cadastro de gasto, na edição e no cadastro/edição de favorito, e não pode ser escolhida por um gasto novo. Reativar a oferece de novo.
- Gastos e favoritos antigos continuam mostrando a categoria desativada. Ao editar um gasto ou favorito que já a usa, ela continua selecionada (marcada como "(desativada)") até ser trocada, e depois de trocada não pode ser escolhida de novo.
- Ao repetir um gasto ou usar um favorito cuja categoria está desativada, o cadastro abre sem categoria e mostra o aviso: `A categoria "X" está desativada e não foi aplicada. Escolha outra, se quiser.` O gasto ou favorito original não muda.
- Desativar e reativar não alteram nenhum gasto nem favorito. Renomear uma categoria desativada é permitido e ela continua desativada. Só o dono acessa as próprias categorias.

**Testes**
- `tests/Feature/Expenses/CategoryActivationTest.php` — todas começam ativas; desativar categoria em uso (e desativar duas vezes); desativada não é oferecida no cadastro nem na edição; gasto novo não pode usá-la; gastos e favoritos antigos continuam mostrando o nome; edição de gasto e de favorito mantém a categoria desativada, permite trocá-la e não permite voltar a ela; outro gasto não pode "pegar emprestada" a categoria desativada; repetir e usar favorito abrem sem a categoria e com o aviso (e sem aviso quando ela está ativa); reativar e voltar a ser oferecida; renomear categoria desativada; desativar e reativar não mexem em gastos e favoritos; categoria desativada em uso continua sem poder ser excluída; categorias de outro usuário dão "não encontrado".

**Verificação manual** (navegador, tela de celular, banco temporário)
- Menu de uma categoria ativa em uso: Renomear, Desativar, Excluir; "Excluir" abre o diálogo com "1 gasto · 1 favorito" e a opção Desativar; Cancelar mantém; confirmar mostra o selo "Desativada" e o menu passa a ter Renomear e Reativar. O cadastro de gasto deixa de oferecer a categoria; a lista mantém "Ração / Animais"; a edição abre com "Animais (desativada)" selecionada e salva mantendo; repetir abre "Sem categoria" com o aviso; reativar faz a categoria voltar a ser oferecida.
- O diálogo de exclusão de gasto continua com o botão vermelho; o de categoria em uso, com "Desativar" em roxo.
- Para o usuário conferir no próprio celular: o diálogo e o selo na tela de categorias.

**Desvios do planejado**
- A etapa 12 dizia que "Excluir" não aparece para categoria em uso; agora aparece para categoria **ativa** em uso, como porta de entrada do diálogo que oferece desativar (critério desta etapa). A exclusão em si continua recusada. Para categoria desativada em uso o item "Excluir" não aparece (não há o que oferecer até a etapa 14).
- O diálogo de confirmação ganhou uma variante neutra (botão roxo) para ações reversíveis.

**Revisão automática**
- Corrigido: botão "Desativar" deixou de ser vermelho, aviso ao repetir passa a ser anunciado, teste de que categoria desativada em uso não pode ser excluída.
- Pontos não corrigidos estão em `../pontos-para-revisao-final.md`.
