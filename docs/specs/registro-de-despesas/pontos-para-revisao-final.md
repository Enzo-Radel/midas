# Pontos para revisão final

Itens levantados pelo revisor automático que não foram corrigidos na etapa e ficam para o usuário avaliar no fim da tarefa. Cada item diz de que etapa veio e o que decidir.

## Etapa 01 — Cadastrar um gasto
- [ ] **CSS repetido entre telas** — os estilos de botão e título de página estão copiados em `Expenses/Create.vue`, `Expenses/Edit.vue` e `Expenses/Index.vue` (o formulário já virou um componente compartilhado na etapa 03). Decidir se vira um componente compartilhado (botão, cabeçalho de página) antes de as telas crescerem.
- [ ] **URLs escritas à mão** — `/despesas` e `/despesas/nova` aparecem direto nas telas e no menu lateral, embora as rotas tenham nome. O projeto não usa Ziggy; decidir se vale adotá-lo ou manter assim.
- [ ] **Migration editada no lugar** — a tabela `expenses` foi alterada na própria migration original, com a sua autorização (sem dados reais). Quem já rodou a original precisa de `migrate:fresh`. Confirmar que é o que se quer, ou trocar por uma migration de alteração.
- [ ] **Testes em SQLite em memória** — pode esconder diferenças em relação ao MySQL usado no aplicativo. Decidir se vale rodar a suíte também contra MySQL (por exemplo, no Sail ou na CI).
- [ ] **Usuário padrão criado em qualquer requisição** — `UseDefaultUser` cria o usuário na primeira visita, até em GET. É provisório: trocar por autenticação quando o login existir.
- [ ] **Telas sem testes automatizados de componente** — foco no valor, data de hoje, "Sem descrição" e o conforto no celular só têm verificação manual (o projeto não tem Vitest nem Jest). Decidir se vale montar essa estrutura.

## Etapa 02 — Lista no estilo extrato
- [ ] **Rótulo do dia sem o ano** — gastos de anos diferentes aparecem como "Sex, 7 nov" sem distinção. Decidir se o ano aparece quando for diferente do atual.
- [ ] **Parâmetro `today` de `formatDayLabel`** — existe para facilitar testes, mas nenhum teste o usa (o projeto não tem Vitest/Jest). Manter ou remover.
- [ ] **Peso visual do separador** — título do dia e total usam o mesmo cinza e tamanho; olhar no celular se o total precisa de mais destaque.

## Etapa 03 — Editar um gasto
- [ ] **Testes de validação sem os limites** — o teste de edição cobre os mesmos casos do de cadastro, que não cobre "valor grande demais" nem "descrição com mais de 255 caracteres". Decidir se vale cobrir esses limites nas duas telas.
- [ ] **Link de editar e cancelar sem teste automatizado** — verificados só no navegador (sem teste de componente no projeto).

## Etapa 04 — Excluir um gasto
- [ ] **Falha na exclusão sem aviso** — se a requisição falhar (rede, sessão expirada), o diálogo fecha e o gasto continua na lista, sem mensagem de erro. Decidir se vale mostrar um aviso.
- [ ] **Descrições truncadas no celular** — com valor e dois botões de 44px na mesma linha, descrições ficam bem curtas em tela estreita (ex.: "Sem descri..."). Decidir se as ações viram um menu, ou vão para uma segunda linha. Vale olhar no seu celular; o problema cresce se entrarem categoria, tags e "repetir"/"favoritar" nas próximas etapas.
- [ ] **Diálogo de confirmação sem teste automatizado** — confirmação e cancelamento verificados só no navegador (sem teste de componente no projeto).
