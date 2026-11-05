# 11 — Criar e renomear categorias

**Status:** concluída · **Atualizada em:** 2026-11-05
**Depende de:** etapas 05, 08

## O que esta etapa entrega
Passa a existir uma tela de categorias em que é possível criar novas e renomear as existentes.

## Critérios de aceite
- [x] É possível criar uma categoria, que passa a aparecer para escolha nos gastos.
- [x] É possível renomear uma categoria, e o novo nome aparece nos gastos e favoritos que a usam.
- [x] Não é possível criar nem renomear para um nome igual a outro já existente, sem diferenciar maiúsculas de minúsculas, e o motivo é mostrado.
- [x] A tela é confortável de usar no celular.

## Registro da implementação
**O que foi alterado**
- Passa a existir a tela "Categorias" (`/categorias`), acessada pelo botão "Categorias" no cabeçalho da lista de despesas. Ela mostra um campo "Nova categoria" com o botão "Adicionar" (desabilitado enquanto o campo está vazio) e a lista das categorias do usuário, na ordem de criação.
- Uma categoria nova aparece na hora na lista e passa a ser oferecida no cadastro e na edição de gastos e de favoritos.
- Cada categoria tem um menu de três pontos com "Renomear". O nome vira um campo no próprio item, com Salvar e Cancelar (Esc também cancela). Renomear mantém todos os gastos e favoritos ligados à categoria, e o novo nome aparece neles.
- Não pode haver duas categorias com o mesmo nome, sem diferenciar maiúsculas de minúsculas (nem nas letras acentuadas), ao criar ou renomear; o motivo ("Já existe uma categoria com esse nome.") aparece junto do campo. Mudar só a caixa do próprio nome (Lazer para LAZER) é permitido. O nome é obrigatório e tem até 50 caracteres. Cada usuário tem as próprias categorias.

**Testes**
- `tests/Feature/Expenses/CategoryManageTest.php` — lista de categorias; criar categoria; categoria nova oferecida no cadastro e na edição de gasto e usada por um gasto; nome com espaços nas pontas; nome em branco, só com espaços, longo demais ou que não é texto recusado; limite exato de 50 caracteres aceito; nome igual a outro recusado ignorando maiúsculas e espaços (inclusive com acentos) e com o motivo; renomear; novo nome aparece nos gastos e favoritos; renomear não muda quais gastos usam a categoria; manter o nome ou mudar só a caixa; renomear para o nome de outra recusado; nome inválido na renomeação; outro usuário pode ter categoria com o mesmo nome; categorias de outro usuário não aparecem nem podem ser alteradas.

**Verificação manual** (navegador, tela de celular, banco temporário)
- O botão "Categorias" abre a tela com as 8 categorias; criar "Pets" a adiciona e limpa o campo; criar "pets" de novo mostra o erro; renomear abre o campo com o nome selecionado, Esc cancela; renomear para "saúde" mostra o erro; renomear para "Bichos" atualiza a lista e a opção no cadastro de gasto; um gasto e um favorito com a categoria passam a mostrar "Animais" depois de renomeada de novo.
- Para o usuário conferir no próprio celular: o cabeçalho da lista com três botões e o conforto da renomeação no item.

**Desvios do planejado**
- A comparação de nomes é feita no código (e não no banco), para dar o mesmo resultado em qualquer banco, inclusive com letras acentuadas; como cada usuário tem poucas categorias, o custo é irrelevante.

**Revisão automática**
- Corrigido: a mensagem de erro agora é anunciada e ligada ao campo para leitores de tela (criação e renomeação); teste do limite de 50 caracteres.
- Pontos não corrigidos estão em `../pontos-para-revisao-final.md`.
