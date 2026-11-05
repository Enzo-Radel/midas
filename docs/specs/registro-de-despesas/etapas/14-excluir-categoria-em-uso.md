# 14 — Excluir categoria em uso com troca

**Status:** concluída · **Atualizada em:** 2026-11-05
**Depende de:** etapa 13

## O que esta etapa entrega
Ao excluir uma categoria com gastos, além de desativar, passa a ser possível excluí-la escolhendo outra categoria ativa, ou nenhuma, para os gastos e favoritos que a usavam.

## Critérios de aceite
- [x] Ao tentar excluir uma categoria com gastos, o sistema informa quantos gastos a usam e oferece também excluir com troca.
- [x] Na troca é possível escolher outra categoria ativa ou "sem categoria".
- [x] Confirmando, a categoria some e todos os gastos e favoritos que a usavam passam para a escolhida.
- [x] Cancelando, nada muda.

## Registro da implementação
**O que foi alterado**
- Ao escolher **Excluir** numa categoria em uso (ativa ou desativada), a tela de categorias abre o diálogo "Excluir categoria em uso": ele diz quantos gastos e favoritos a usam ("1 gasto · 1 favorito") e pede para escolher **para onde eles vão**: "Sem categoria" ou qualquer outra categoria **ativa** (a própria categoria e as desativadas não aparecem). O botão "Excluir e trocar" só é liberado depois da escolha. Para categoria ativa há também "Só desativar", que faz o que a etapa 13 oferecia.
- Confirmando, todos os gastos e favoritos que usavam a categoria passam para a escolhida (ou ficam sem categoria), e a categoria some da lista e das opções. Nada mais nos gastos e favoritos muda (valor, data, descrição, tags). A categoria que recebeu os gastos mostra o novo uso na tela. Cancelar (ou Esc) não muda nada.
- No servidor, mover e excluir acontecem juntos, numa transação com a categoria travada; a exclusão de categoria em uso continua recusada se não vier um destino. O destino precisa ser uma categoria ativa do próprio usuário e diferente da excluída; se vierem as duas opções (outra categoria e "sem categoria"), vale a outra categoria. Se o destino escolhido deixar de ser válido, a tela mostra o aviso.
- Categoria sem uso continua sendo excluída com a confirmação simples.

**Testes**
- `tests/Feature/Expenses/CategoryReplaceTest.php` — excluir com troca move gastos e favoritos e remove a categoria; excluir sem categoria deixa gastos e favoritos sem categoria (e eles continuam existindo); o restante dos dados (valor, descrição, data, tags, nome do favorito) não muda; categoria em uso sem destino não é excluída; categoria desativada em uso também pode ser excluída (com troca e sem categoria); destino precisa ser ativo, do usuário e diferente da própria categoria; gastos de outras categorias e de outros usuários não são tocados; categoria sem uso é excluída mesmo se um destino for enviado; a categoria que recebeu mostra o novo uso; categoria de outro usuário dá "não encontrado"; destino prevalece quando vêm as duas opções.

**Verificação manual** (navegador, tela de celular, banco temporário)
- Excluir categoria sem uso: o diálogo fecha sozinho e a categoria some. Categoria em uso: o diálogo mostra a contagem e o seletor, o botão de confirmar começa desabilitado, Cancelar mantém tudo; escolher "Alimentação" e confirmar remove "Animais" e a Alimentação passa de 3 para 4 gastos (o gasto "Ração" aparece em Alimentação); "Sem categoria" deixa o favorito de "Lazer" sem categoria; "Só desativar" mostra o selo "Desativada".
- Para o usuário conferir no próprio celular: o diálogo com três botões e o seletor.

**Desvios do planejado**
- Foi achado e corrigido um **defeito da etapa 13**: o helper da tela de categorias chamava `router.delete(url, {}, opções)`, mas o `delete` do Inertia só aceita `(url, opções)`; as opções (inclusive fechar o diálogo ao terminar) eram ignoradas ao excluir categoria sem uso. Não aparecia nos testes de backend. O helper foi corrigido e a exclusão sem uso foi verificada de novo no navegador.
- "Excluir" passou a aparecer para toda categoria em uso (inclusive desativada), como pedem os pontos anotados nas etapas 12 e 13.
- O diálogo de confirmação ganhou um espaço para conteúdo (o seletor), um botão extra opcional e a opção de desabilitar a confirmação.

**Revisão automática**
- Corrigido: o aviso na tela agora também mostra o erro de destino inválido, comentários e `defineProps` reorganizados, testes dos casos "desativada sem categoria" e "destino prevalece".
- Pontos não corrigidos estão em `../pontos-para-revisao-final.md`.
