# 03 — Editar um gasto

**Status:** concluída · **Atualizada em:** 2026-11-05
**Depende de:** etapa 02

## O que esta etapa entrega
Cada gasto da lista ganha a ação de editar, que abre o cadastro com os dados atuais para corrigir.

## Critérios de aceite
- [x] Da lista, a ação editar abre o cadastro com os dados atuais do gasto.
- [x] Alterações salvas aparecem na lista, e o gasto muda de dia quando a data é alterada.
- [x] Os totais dos dias afetados são atualizados.
- [x] As mesmas regras de validação do cadastro valem na edição.
- [x] Cancelar a edição não altera o gasto.
- [x] A tela é confortável de usar no celular.

## Fora desta etapa
- Editar categoria e tags (entram nas etapas 05 e 06).

## Registro da implementação
**O que foi alterado**
- Cada gasto da lista ganhou um botão de editar (ícone de lápis), que abre a tela "Editar gasto" (`/despesas/{id}/editar`) com valor, data e descrição atuais. O foco não vai automaticamente para o campo, para não abrir o teclado do celular.
- Salvar volta para a lista com o gasto atualizado; mudar a data move o gasto para o outro dia e os totais dos dois dias são recalculados. Cancelar volta para a lista sem alterar nada.
- As regras de validação são as mesmas do cadastro, e só é possível editar gastos do próprio usuário (os de outros dão "não encontrado").
- O formulário de cadastro foi extraído para um componente (`ExpenseForm.vue`) que cadastro e edição compartilham; o cadastro continua se comportando como antes.

**Testes**
- `tests/Feature/Expenses/EditExpenseTest.php` — tela abre com os dados atuais; alterações salvas e vistas na lista; mudar a data move o gasto e atualiza os totais dos dias; descrição pode ser apagada; mesmas validações do cadastro sem alterar o gasto; gasto de outro usuário não abre nem altera.

**Verificação manual** (navegador, tela de celular, banco temporário)
- O botão de lápis abre a edição com "6,00", data e descrição corretas; cancelar mantém a lista igual; trocar valor para "7,50" e data para ontem move o gasto de "Hoje" para "Ontem" e atualiza os totais; valor negativo mostra o erro.
- Para o usuário conferir no próprio celular: conforto de toque no botão de editar e truncamento de descrições longas.

**Desvios do planejado**
- O rótulo do botão de editar inclui a descrição do gasto, para leitores de tela.
- `StoreExpenseRequest` foi renomeado para `ExpenseRequest`, porque agora serve cadastro e edição.

**Revisão automática**
- Corrigido: foco automático só no cadastro, rótulo do botão de editar, área de toque de 44px.
- Pontos não corrigidos estão em `../pontos-para-revisao-final.md`.
