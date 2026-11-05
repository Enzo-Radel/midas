# 06 — Tags no gasto

**Status:** concluída · **Atualizada em:** 2026-11-05
**Depende de:** etapa 03

## O que esta etapa entrega
O gasto pode ter várias tags. Elas começam vazias e são criadas digitando o nome na hora de atribuir.

## Critérios de aceite
- [x] Na primeira vez não existe nenhuma tag.
- [x] No cadastro e na edição é possível atribuir várias tags, ou nenhuma.
- [x] Digitar um nome que ainda não existe cria a tag e a atribui ao gasto.
- [x] Ao digitar, as tags existentes podem ser escolhidas.
- [x] Não é criada tag com nome igual a outra já existente, sem diferenciar maiúsculas de minúsculas; a existente é usada.
- [x] As tags do gasto aparecem na lista.
- [x] É possível retirar uma tag de um gasto na edição.
- [x] A tela é confortável de usar no celular.

## Fora desta etapa
- Renomear e excluir tags (etapa 15).

## Registro da implementação
**O que foi alterado**
- O cadastro e a edição de gasto ganharam o campo "Tags (opcional)". As tags escolhidas aparecem como etiquetas com um botão para retirar. Para adicionar, digita-se o nome e usa-se Enter, vírgula ou o botão "+"; uma tag digitada e ainda não confirmada também é incluída ao salvar.
- Abaixo do campo aparecem as tags que já existem (filtradas pelo que está sendo digitado) para escolher com um toque. Um gasto pode ter várias tags ou nenhuma, e é possível retirá-las na edição.
- Tags novas são criadas na hora. Se a tag já existe (ignorando maiúsculas, acentos de maiúscula e espaços nas pontas), ela é reaproveitada com a grafia original. Retirar uma tag de um gasto não a apaga para os outros.
- A lista mostra as tags de cada gasto ao lado da categoria. Cada usuário tem as próprias tags. Gasto e tags são gravados juntos: se algo falhar, nada fica salvo pela metade.

**Testes**
- `tests/Feature/Expenses/ExpenseTagTest.php` — nenhuma tag na primeira vez; várias tags ou nenhuma; tag nova é criada e atribuída; tag existente reaproveitada ignorando maiúsculas e espaços (inclusive com acentos); tags existentes oferecidas só as do usuário; tags na lista e na edição; adicionar e retirar tags ao editar sem afetar outros gastos; tags de usuários diferentes independentes; tags inválidas recusadas (em branco, longas, formato errado, mais de 20); tag chamada "0" preservada; gasto e tags gravados juntos (falha ao criar tag não deixa o gasto salvo).

**Verificação manual** (navegador, tela de celular, banco temporário)
- Digitar "trabalho" + Enter, repetir como "Trabalho" (não duplica), "almoço," com vírgula (adiciona), digitar e salvar sem confirmar (inclui). Sugestões aparecem, filtram por "trab" e adicionam com um toque. Na edição, retirar "almoço" e salvar tira a tag do gasto.
- Teclado e toque: o foco volta ao campo após escolher uma sugestão ou retirar uma tag; sugestões têm 44px de altura e o botão de retirar 36px; Backspace com o campo vazio não apaga tags.

**Desvios do planejado**
- O atalho "Backspace apaga a última tag" foi removido, porque no celular apagaria tags sem querer.
- A vírgula é tratada no texto digitado (não na tecla), porque teclados de celular nem sempre informam a tecla.

**Revisão automática**
- Corrigido: tag "0" era descartada, gasto e tags agora na mesma transação, foco devolvido ao campo, áreas de toque maiores, grafia da tag existente, atalho do Backspace.
- Pontos não corrigidos estão em `../pontos-para-revisao-final.md`.
