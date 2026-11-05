# 07 — Repetir um gasto

**Status:** concluída · **Atualizada em:** 2026-11-05
**Depende de:** etapas 05, 06

## O que esta etapa entrega
Cada gasto da lista ganha a ação de repetir, que abre o cadastro preenchido com os dados daquele gasto e a data de hoje.

## Critérios de aceite
- [x] Da lista, a ação repetir abre o cadastro com descrição, valor, categoria e tags do gasto, e a data de hoje.
- [x] Nada é salvo até a pessoa confirmar.
- [x] Qualquer campo pode ser alterado antes de salvar.
- [x] Salvar cria um novo gasto; o gasto original não muda.
- [x] Cancelar não cria nada.
- [x] A tela é confortável de usar no celular.

## Registro da implementação
**O que foi alterado**
- Cada gasto da lista passa a ter um menu de três pontos com **Editar**, **Repetir** e **Excluir** (no lugar dos botões soltos de editar e excluir). A ação "Repetir" abre a tela "Repetir gasto": o cadastro preenchido com descrição, valor, categoria e tags do gasto e com a data de hoje.
- Qualquer campo pode ser alterado antes de salvar. Nada é gravado ao abrir a tela; Cancelar volta para a lista sem criar nada. Salvar cria um novo gasto e o original não muda (as tags são reaproveitadas, não duplicadas).
- Na repetição o teclado do celular não abre sozinho (o foco só vai para o valor no cadastro em branco), porque a pessoa costuma só conferir e salvar.
- Só é possível repetir gastos do próprio usuário (os de outros dão "não encontrado").
- O menu abre com o foco no primeiro item, fecha com Esc, Tab ou clique fora, e navega com setas, Home e End.

**Testes**
- `tests/Feature/Expenses/RepeatExpenseTest.php` — a tela abre com valor, descrição, categoria e tags do gasto; a data original não é levada; abrir a tela não grava nada; salvar cria um gasto novo e preserva o original (e as tags); gasto de outro usuário não pode ser repetido; gasto sem descrição, categoria e tags também pode ser repetido. O teste de "salvar cria um novo gasto" já passava antes da etapa (comportamento do cadastro) e fica como proteção contra regressão.

**Verificação manual** (navegador, tela de celular, banco temporário)
- Menu com as três ações; "Repetir" abre com "30,00", a descrição, as tags e a data de hoje; Cancelar mantém a lista; trocar o valor para 35 e salvar cria um novo gasto e deixa o original intacto.
- Regressão das etapas 03 e 04 pelo menu: editar abre a edição; excluir abre o diálogo com o gasto certo, cancelar mantém e confirmar remove.
- Teclado: setas, Home e End percorrem o menu; Esc, Tab e clique fora fecham.
- Para o usuário conferir no próprio celular: o conforto do menu e da tela de repetir.

**Desvios do planejado**
- As ações de editar e excluir viraram um menu de três pontos. Com "Repetir" (e, na próxima etapa, "Favoritar") os botões soltos tomariam quase toda a linha no celular e esconderiam a descrição. O menu também resolve o problema de descrições truncadas anotado na etapa 04.
- A lista deixou de cortar o conteúdo que passa da borda (`overflow: hidden`), para o menu do último gasto não ser cortado.

**Revisão automática**
- Corrigido: navegação por setas, Home e End; Tab fecha o menu; `aria-controls`.
- Pontos não corrigidos estão em `../pontos-para-revisao-final.md`.
