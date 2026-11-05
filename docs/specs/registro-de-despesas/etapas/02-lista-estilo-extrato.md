# 02 — Lista de gastos no estilo extrato

**Status:** concluída · **Atualizada em:** 2026-11-05
**Depende de:** etapa 01

## O que esta etapa entrega
A lista de gastos passa a ser agrupada por dia, como num extrato bancário, com um separador que mostra a data e o total gasto naquele dia.

## Critérios de aceite
- [x] Os gastos aparecem agrupados por dia, do dia mais recente para o mais antigo.
- [x] Cada separador mostra a data ("Hoje", "Ontem" ou dia da semana e data, ex.: "Seg, 2 nov") e o total gasto naquele dia.
- [x] O total do dia é a soma exata dos valores dos gastos daquele dia.
- [x] Gastos com data futura aparecem no separador do seu dia, acima de hoje.
- [x] Com a lista vazia, aparece uma mensagem convidando a cadastrar o primeiro gasto.
- [x] A tela é confortável de usar no celular.

## Registro da implementação
**O que foi alterado**
- A lista de despesas passa a ser agrupada por dia, como um extrato: cada dia tem um separador com o rótulo e o total gasto, e a data deixou de aparecer em cada gasto.
- O rótulo é "Hoje", "Ontem" ou dia da semana e data (ex.: "Ter, 10 nov"), calculado com a data do dispositivo. Dias futuros aparecem acima de hoje.
- Dentro do dia, o gasto cadastrado por último aparece primeiro. O total do dia é a soma exata em centavos.

**Testes**
- `tests/Feature/Expenses/ExpenseListByDayTest.php` — agrupamento e ordem dos dias; total exato de cada dia; ordem dentro do dia; dia futuro acima de hoje; total ignora gastos de outros usuários; lista vazia.
- `tests/Feature/Expenses/RegisterExpenseTest.php` — os dois testes de lista da etapa 01 foram adaptados ao novo formato (`days` no lugar de `expenses`), sem mudar o que verificam.

**Verificação manual** (navegador, tela de celular, banco temporário com gastos de hoje, ontem, 6 dias atrás e 5 dias à frente)
- Separadores "Ter, 10 nov", "Hoje", "Ontem" e "Sex, 30 out", com os totais certos e na ordem esperada. Mensagem de lista vazia já verificada na etapa 01.

**Desvios do planejado**
- O rótulo usa maiúscula na primeira letra ("Seg, 2 nov"), para ficar igual a "Hoje" e "Ontem"; o exemplo do critério foi ajustado.
- A mensagem de lista vazia já existia desde a etapa 01.

**Revisão automática**
- Corrigido: indentação do `Index.vue`.
- Pontos não corrigidos estão em `../pontos-para-revisao-final.md`.
