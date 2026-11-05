# 10 — Gerenciar favoritos

**Status:** concluída · **Atualizada em:** 2026-11-05
**Depende de:** etapa 09

## O que esta etapa entrega
Na lista de favoritos, cada favorito pode ser renomeado, ter os dados editados e ser removido.

## Critérios de aceite
- [x] É possível renomear um favorito.
- [x] É possível editar descrição, valor, categoria e tags de um favorito, e repetições futuras usam os novos dados.
- [x] É possível remover um favorito, com confirmação.
- [x] Nenhuma dessas ações altera gastos já cadastrados.
- [x] A tela é confortável de usar no celular.

## Registro da implementação
**O que foi alterado**
- Cada cartão da lista de favoritos ganhou um menu de três pontos com **Editar** e **Remover**. Tocar no cartão continua abrindo o cadastro preenchido (etapa 09).
- "Editar" abre a tela "Editar favorito": nome (opcional), valor, descrição, categoria e tags. Não há data nem a opção de favoritar. Salvar volta para a lista; Cancelar também. Repetições futuras já usam os dados novos. Nome e descrição são independentes (mudar um não muda o outro); com o nome vazio, o favorito é identificado pelo valor e pela categoria.
- "Remover" abre um diálogo de confirmação ("Remover este favorito?") que lembra que os gastos já registrados não são afetados. Remover um favorito também faz o gasto de origem deixar de aparecer como favoritado, e ele pode ser favoritado de novo.
- Editar ou remover um favorito não altera gastos, categorias nem tags. Só o dono acessa os próprios favoritos.

**Testes**
- `tests/Feature/Expenses/FavoriteManageTest.php` — tela de edição com os dados do favorito; renomear (a descrição não muda) e limpar o nome; editar valor, descrição, categoria e tags (reaproveitando tags existentes); remover categoria e tags; repetições futuras com os dados novos; dados inválidos recusados sem alterar o favorito (valor, nome, descrição, tag em branco, categoria inexistente ou de outro usuário); editar e remover o favorito não alteram o gasto de origem (valor, descrição, categoria, tags e quantidade de gastos) nem as categorias e tags; remover libera o gasto para ser favoritado de novo; favorito de outro usuário dá "não encontrado" ao abrir, alterar e remover.

**Verificação manual** (navegador, tela de celular, banco temporário)
- Menu do favorito com Editar e Remover; a tela de edição vem com nome, valor e descrição atuais e sem data nem caixa de favoritar; renomear para "Almoço no trabalho", mudar o valor para 28,90, a categoria para Lazer e acrescentar a tag "novo" aparece na lista; valor 0 mostra o erro; Cancelar volta para a lista de favoritos; usar o favorito editado abre com 28,90 e Lazer; remover pede confirmação, Cancelar mantém e confirmar remove (4 para 3 favoritos), e os 5 gastos continuam intactos.
- O menu de gastos (refeito sobre o novo menu genérico) continua com os 4 itens, setas, End e Esc.
- Para o usuário conferir no próprio celular: conforto do menu nos cartões e da tela de edição.

**Desvios do planejado**
- O menu de três pontos virou um componente genérico (`ActionMenu`), usado pelos gastos e pelos favoritos, e o formulário de gasto ganhou um modo "favorito" em vez de uma tela de formulário separada, para não duplicar a lógica de tags e categorias.
- As regras de validação comuns a gasto e favorito foram para um trecho compartilhado, e a resolução de tags por nome foi para um serviço (`TagResolver`). A mensagem do limite de tags passou de "por gasto" para um texto neutro ("Use no máximo 20 tags.").

**Revisão automática**
- Corrigido: os testes de "não altera gastos" agora usam um gasto de origem com categoria e tags e conferem que nada muda; contrato do menu genérico documentado.
- Pontos não corrigidos estão em `../pontos-para-revisao-final.md`.
