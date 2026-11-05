# 04 — Excluir um gasto

**Status:** concluída · **Atualizada em:** 2026-11-05
**Depende de:** etapa 02

## O que esta etapa entrega
Cada gasto da lista ganha a ação de excluir, com confirmação.

## Critérios de aceite
- [x] Da lista, a ação excluir pede confirmação antes de apagar.
- [x] Confirmando, o gasto some da lista e o total do dia é atualizado; um dia que fica sem gastos deixa de aparecer.
- [x] Cancelando a confirmação, nada muda.
- [x] A tela é confortável de usar no celular.

## Registro da implementação
**O que foi alterado**
- Cada gasto da lista ganhou um botão de excluir (ícone de lixeira). Ele abre um diálogo "Excluir este gasto?" com o nome e o valor do gasto, e os botões Cancelar e Excluir.
- Confirmar remove o gasto, atualiza o total do dia e faz o dia desaparecer quando fica sem gastos. Cancelar, Esc ou clicar fora do diálogo não alteram nada.
- O diálogo é um componente genérico (`ConfirmDialog.vue`), pensado para as próximas exclusões (favoritos, categorias, tags). O foco começa em "Cancelar", fica preso dentro do diálogo e volta ao botão que o abriu; durante a exclusão ele não pode ser fechado.
- Só é possível excluir gastos do próprio usuário (os de outros dão "não encontrado").

**Testes**
- `tests/Feature/Expenses/DeleteExpenseTest.php` — excluir remove e volta para a lista; total do dia atualizado; dia sem gastos some; só o gasto escolhido é removido; gasto de outro usuário não pode ser excluído.

**Verificação manual** (navegador, tela de celular, banco temporário)
- O diálogo mostra o gasto certo; Esc e Cancelar mantêm a lista igual; confirmar remove o gasto e ajusta o total ("Ontem" de R$ 32,50 para R$ 25,00); excluir o único gasto de um dia faz o separador sumir.
- Teclado: Esc fecha mesmo depois de clicar no texto do diálogo; Tab e Shift+Tab ficam dentro do diálogo; o foco volta ao botão de excluir.

**Desvios do planejado**
- Nenhum desvio no que a etapa entrega. O diálogo ganhou acessibilidade de teclado além do mínimo, por ser reutilizado nas próximas etapas.

**Revisão automática**
- Corrigido: Esc em qualquer ponto, foco preso e devolvido, `aria-describedby`, cancelamento bloqueado durante a exclusão.
- Pontos não corrigidos estão em `../pontos-para-revisao-final.md`.
