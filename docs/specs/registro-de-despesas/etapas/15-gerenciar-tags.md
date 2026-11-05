# 15 — Gerenciar tags

**Status:** concluída · **Atualizada em:** 2026-11-05
**Depende de:** etapas 06, 08

## O que esta etapa entrega
Passa a existir uma tela de tags em que é possível renomear e excluir tags.

## Critérios de aceite
- [x] É possível renomear uma tag, e o novo nome aparece nos gastos e favoritos que a têm.
- [x] Não é possível renomear para um nome igual a outra tag, sem diferenciar maiúsculas de minúsculas.
- [x] É possível excluir uma tag, com confirmação; ela é apenas retirada dos gastos e favoritos que a tinham.
- [x] A tela é confortável de usar no celular.

## Registro da implementação
**O que foi alterado**
- Passa a existir a tela "Tags" (`/tags`), acessada pelo atalho "Tags" na lista de despesas. Ela lista as tags do usuário em ordem alfabética (sem diferenciar maiúsculas nem acentos), cada uma com o seu uso ("3 gastos · 1 favorito" ou "Não está em uso"). Sem tags, mostra uma mensagem explicando que elas são criadas ao registrar ou editar um gasto (esta tela não cria tags).
- Cada tag tem um menu de três pontos com **Renomear** e **Excluir**. Renomear acontece no próprio item (Salvar, Cancelar, Esc); o novo nome aparece nos gastos e favoritos que têm a tag e nas sugestões do campo de tags. Não pode haver duas tags com o mesmo nome (sem diferenciar maiúsculas de minúsculas), com a mensagem "Já existe uma tag com esse nome."; manter o próprio nome ou mudar só a caixa é permitido. O nome é obrigatório e tem até 50 caracteres.
- **Excluir** abre um diálogo de confirmação que diz de quantos gastos e favoritos a tag será retirada e lembra que eles não são excluídos. Confirmando, a tag some e é retirada de todos os gastos e favoritos que a tinham; nada mais neles muda. O nome de uma tag excluída pode ser usado de novo. Cada usuário só vê e altera as próprias tags.
- A lista de despesas teve o cabeçalho reorganizado: "Novo gasto" continua em destaque e uma linha de atalhos (Favoritos, Categorias, Tags) vem logo abaixo, cabendo numa linha em celulares de 390px e quebrando com elegância em telas menores.

**Testes**
- `tests/Feature/Expenses/TagManageTest.php` — tela vazia; lista em ordem alfabética com acentos e com o uso de cada tag; só as tags do usuário; renomear; novo nome nos gastos, favoritos, edição e sugestões; renomear não muda quais gastos e favoritos usam a tag; manter o nome ou mudar só a caixa; nome igual ao de outra tag recusado com o motivo; nomes inválidos (em branco, só espaços, longo, não texto) e o limite exato de 50 caracteres; outro usuário pode ter tag com o mesmo nome; excluir retira a tag dos gastos e favoritos (e dos vínculos) sem excluir nem alterar nada neles; outras tags não são tocadas; a tag some das listas e das sugestões; o nome pode ser usado de novo; excluir tag sem uso; tag de outro usuário dá "não encontrado".

**Verificação manual** (navegador, tela de celular, banco temporário)
- O atalho "Tags" abre a tela com as tags e o uso de cada uma; renomear para o nome de outra tag ("REFEIÇÃO" contra "refeição") mostra o erro junto do campo, com o foco de volta ao campo e o Esc cancelando; renomear "trabalho" para "escritório" atualiza a lista e o novo nome aparece num gasto e num favorito; excluir pede confirmação ("será retirada de 3 gastos e 1 favorito"), Cancelar mantém, confirmar remove e o gasto continua sem a tag; sem tags aparece a mensagem de lista vazia.
- Os atalhos ficam numa linha a 390px de largura e quebram a 320px.
- Para o usuário conferir no próprio celular: a tela e o diálogo de exclusão.

**Desvios do planejado**
- O cabeçalho da lista de despesas foi reorganizado (atalhos em uma linha própria) para acomodar o acesso às tags sem apertar o cabeçalho, já que "Favoritos" e "Categorias" ocupavam o lugar do botão principal.
- A regra de nome único foi extraída para um trecho compartilhado (`UniqueNameForUser`) usado por categorias e tags; o comportamento das categorias não mudou.
- Foi corrigido, também na tela de categorias, o foco depois de um erro de renomeação: o botão Salvar fica desabilitado durante o envio e o foco se perdia (o Esc deixava de cancelar); agora o foco volta ao campo com o erro.
- As mensagens dos diálogos de tags e de categorias passaram a ler "2 gastos e 1 favorito" e a citar o nome entre aspas.

**Revisão automática**
- Corrigido: texto das mensagens dos diálogos (frase corrida e nome entre aspas).
- Pontos não corrigidos estão em `../pontos-para-revisao-final.md`.
