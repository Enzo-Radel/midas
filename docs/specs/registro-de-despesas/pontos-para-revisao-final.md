# Pontos para revisão final

Itens levantados pelo revisor automático que não foram corrigidos na etapa e ficam para o usuário avaliar no fim da tarefa. Cada item diz de que etapa veio e o que decidir.

## Etapa 01 — Cadastrar um gasto
- [ ] **CSS repetido entre telas** — os estilos de botão e título de página estão copiados em `Expenses/Create.vue` e `Expenses/Index.vue`. Decidir se vira um componente compartilhado (botão, cabeçalho de página) antes de as telas crescerem.
- [ ] **URLs escritas à mão** — `/despesas` e `/despesas/nova` aparecem direto nas telas e no menu lateral, embora as rotas tenham nome. O projeto não usa Ziggy; decidir se vale adotá-lo ou manter assim.
- [ ] **Migration editada no lugar** — a tabela `expenses` foi alterada na própria migration original, com a sua autorização (sem dados reais). Quem já rodou a original precisa de `migrate:fresh`. Confirmar que é o que se quer, ou trocar por uma migration de alteração.
- [ ] **Testes em SQLite em memória** — pode esconder diferenças em relação ao MySQL usado no aplicativo. Decidir se vale rodar a suíte também contra MySQL (por exemplo, no Sail ou na CI).
- [ ] **Usuário padrão criado em qualquer requisição** — `UseDefaultUser` cria o usuário na primeira visita, até em GET. É provisório: trocar por autenticação quando o login existir.
- [ ] **Telas sem testes automatizados de componente** — foco no valor, data de hoje, "Sem descrição" e o conforto no celular só têm verificação manual (o projeto não tem Vitest nem Jest). Decidir se vale montar essa estrutura.

## Etapa 02 — Lista no estilo extrato
- [ ] **Rótulo do dia sem o ano** — gastos de anos diferentes aparecem como "Sex, 7 nov" sem distinção. Decidir se o ano aparece quando for diferente do atual.
- [ ] **Parâmetro `today` de `formatDayLabel`** — existe para facilitar testes, mas nenhum teste o usa (o projeto não tem Vitest/Jest). Manter ou remover.
- [ ] **Peso visual do separador** — título do dia e total usam o mesmo cinza e tamanho; olhar no celular se o total precisa de mais destaque.
