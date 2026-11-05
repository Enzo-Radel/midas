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
- [x] **Descrições truncadas no celular** — resolvido na etapa 07: as ações viraram um menu de três pontos. Vale só confirmar no seu celular.
- [ ] **Diálogo de confirmação sem teste automatizado** — confirmação e cancelamento verificados só no navegador (sem teste de componente no projeto).

## Etapa 05 — Categoria no gasto
- [ ] **Categoria apagada sem destino** — a chave de `expenses.category_id` está como `nullOnDelete`: se uma categoria for apagada direto no banco, os gastos ficam sem categoria em silêncio. A análise diz que categoria em uso nunca é apagada sem destino; as etapas 12 a 14 garantem isso na tela. Decidir se vale trocar por `restrictOnDelete` (mais fiel à regra) na etapa 14.
- [ ] **Sem índice único em nome de categoria** — duas primeiras visitas simultâneas podem duplicar as categorias iniciais, e nada impede nomes repetidos no banco (a regra é só na tela, etapa 11). Decidir se vale um índice único `(user_id, nome)`.
- [ ] **Migração de preenchimento sem teste automatizado** — verificada manualmente em banco com dois usuários; a suíte roda as migrações sem usuários.
- [ ] **Teste da lista inicial compara com texto fixo** — quando a lista provisória mudar, o teste muda junto. Decidir se vale comparar com `config('midas.default_categories')`.
- [x] **Truncamento no celular** — resolvido pelo menu de ações (etapa 07).

## Etapa 06 — Tags no gasto
- [ ] **Tag duplicada em salvamentos simultâneos** — a regra "sem tags de mesmo nome" é feita no código (ler e depois criar), sem índice único no banco; dois salvamentos ao mesmo tempo (clique duplo, duas abas) com a mesma tag nova podem criar duas. Mesmo ponto das categorias. Decidir se vale um índice único `(user_id, nome)` (no MySQL costuma ignorar maiúsculas; no SQLite dos testes não).
- [ ] **Limite de 20 tags só no servidor** — a 21ª tag é aceita na tela e o erro só aparece ao salvar. Decidir se a tela deve impedir ou avisar antes.
- [ ] **Ordem das tags pode variar entre telas** — a lista ordena em PHP (ignorando maiúsculas), a edição e as sugestões ordenam no banco; com acentos a ordem pode diferir. Baixa prioridade.
- [ ] **Erro do campo de tags sem `aria-describedby`** — como nos outros campos do formulário; decidir se vale ligar as mensagens de erro aos campos em todo o formulário.
- [ ] **Índice em `expense_tag.tag_id`** — as etapas 14 e 15 vão renomear e excluir tags; vale conferir se a consulta por tag precisa de índice.
- [ ] **Atualizar um gasto sem enviar `tags` remove todas as tags** — é o comportamento adotado (campo ausente = nenhuma tag); confirmar que é o desejado se um dia houver outro cliente além da tela.

## Etapa 07 — Repetir um gasto
- [ ] **Menu de ações sempre abre para baixo** — no último gasto de uma lista longa, ou perto do fim da tela, o menu pode exigir rolagem. Hoje cabe (menu de 3 itens); fica mais apertado com "Favoritar". Decidir se vale abrir para cima quando faltar espaço.
- [ ] **Foco após excluir** — depois de confirmar a exclusão o foco cai no corpo da página (o botão some com o gasto). Decidir se vale levá-lo ao gasto seguinte ou ao título da lista.
- [x] **Montagem dos dados do gasto repetida no controller** — resolvido na etapa 09 (`ExpenseFormData`).
- [ ] **Menu e repetir sem teste automatizado** — verificados só no navegador (sem teste de componente no projeto).

## Etapa 08 — Favoritar um gasto
- [ ] **Etapas 12 a 15 precisam tratar favoritos** — o favorito aponta para as mesmas categorias e tags do usuário: renomear aparece nele (bom), mas excluir uma tag apaga o vínculo em silêncio e excluir uma categoria deixa o favorito sem categoria. A exclusão com troca (etapa 14) e a de tags (etapa 15) devem reatribuir também os favoritos e ter teste para isso; as contagens de uso vão precisar de `Tag::favorites` e `Category::favorites`.
- [ ] **Exibição do favorito sem descrição** — o nome fica vazio e o valor e a categoria são guardados; a regra de exibição ("valor e categoria") fica para a lista de favoritos (etapa 09). Conferir lá.
- [ ] **Desfavoritar apaga o favorito mesmo se ele foi renomeado/editado** (a partir da etapa 10) — bate com "remover", mas vale confirmar se um aviso é desejado.
- [ ] **Texto da opção "Salvar nos favoritos"** — não diz que editar o gasto depois não atualiza o favorito. Decidir se vale esclarecer.
- [ ] **Sem aviso do resultado de favoritar/desfavoritar** — o menu fecha e só a estrela muda; não há anúncio para leitor de tela nem aviso de falha da requisição (mesmo ponto da exclusão, etapa 04).
- [ ] **Menu com 4 itens** — o ponto "menu abre sempre para baixo" (etapa 07) fica mais relevante; olhar o último gasto de uma lista longa no celular.
- [ ] **Estrela lida por leitor de tela** — o ícone usa `title="Favorito"`; confirmar num leitor de tela, ou trocar por texto visualmente oculto.

## Etapa 09 — Lista de favoritos
- [ ] **Acesso aos favoritos só pelo cabeçalho da lista de despesas** — não há entrada no menu lateral. Decidir se vale uma entrada lá (o menu lateral hoje tem só itens de exemplo).
- [ ] **Teste de "salvar com dados alterados não muda o favorito" é fraco** — o cadastro não conhece o favorito, então o teste mostra pouco; a garantia real está em "usar não grava nada" (que agora confere os campos do favorito). Decidir se mantém ou remove.
- [ ] **CSS de página, botão e cartão repetido** — a tela de favoritos é mais uma cópia dos estilos de botão e título (ver o ponto da etapa 01); vale extrair componentes compartilhados antes de a tela de categorias (etapa 11) e a de tags (etapa 15) entrarem.
- [ ] **Valor aparece duas vezes no favorito sem nome** — o título é "R$ 12,00 · Transporte" e o valor também aparece à direita. Decidir se o título deve ser só a categoria.
- [ ] **Mensagem de lista vazia e acesso sem teste automatizado** — verificados só no navegador.

## Etapa 10 — Gerenciar favoritos
- [ ] **Validação antes da checagem de dono** — ao alterar um favorito de outro usuário com dados inválidos a resposta é o erro de validação, e só com dados válidos é "não encontrado" (não vaza dados). Decidir se a checagem de dono deve vir antes (em `authorize()` da requisição), valendo também para gastos.
- [ ] **Formulário com três papéis** — `ExpenseForm` serve a gasto novo, edição de gasto e edição de favorito, e o ajuste dos campos enviados fica num `transform` verificado só no navegador. Decidir se vale separar ou cobrir com teste de componente.
- [ ] **Remover favorito herda dois pontos** — falha de rede fecha o diálogo sem aviso (etapa 04) e o foco cai no corpo da página depois da remoção (etapa 07).
- [ ] **Estilo dos itens do menu depende de classes combinadas** — `menu-item`, `menu-icon` e `menu-item-danger` precisam ser usadas por quem usa o `ActionMenu` (já documentado no componente).
- [ ] **Nome do favorito não acompanha a descrição** — depois de criado, mudar a descrição não muda o nome (e vice-versa). Decidir se, ao editar, o nome deveria acompanhar a descrição enquanto ele for igual a ela.

## Etapa 11 — Criar e renomear categorias
- [ ] **Três botões no cabeçalho da lista de despesas** — "Categorias", "Favoritos" e "Novo gasto" quebram em duas linhas no celular, e gerenciar categorias é uma ação rara com o mesmo peso visual de "Favoritos". Decidir junto com o ponto da etapa 09 (entrada no menu lateral, ou só ícones no celular). Os formulários de gasto também não têm atalho para criar categoria.
- [ ] **Foco depois de salvar ou cancelar a renomeação** — o campo some e o foco cai no corpo da página; o encadeamento "fechar menu e focar o campo" depende da ordem (`close()` e depois `nextTick`). Vale um teste no teclado.
- [ ] **Nomes que só diferem por espaços internos ou normalização Unicode** — "Casa  Nova" e "Casa Nova" contam como diferentes. A análise não pede; só uma observação.
- [ ] **Menu de três pontos com um único item** — custa dois toques por ação até as etapas 12 a 14 acrescentarem Excluir e Desativar; reavaliar se a ordem das etapas mudar.
- [ ] **Tela de categorias mais uma cópia de CSS de botão/campo** — ver o ponto de CSS repetido (etapas 01 e 09); a tela de tags (etapa 15) será a próxima.

## Etapa 12 — Excluir categoria sem gastos
- [ ] **Categoria só de favoritos conta como "em uso"** — a etapa fala em "gastos ligados", mas os favoritos também bloqueiam a exclusão simples (para não ficarem sem categoria em silêncio). Confirmar se é o desejado; a saída é desativar ou trocar (etapas 13 e 14).
- [ ] **Travamento só garantido no MySQL** — a transação com `lockForUpdate` protege contra um gasto usar a categoria durante a exclusão no MySQL; no SQLite dos testes o travamento não tem efeito e não há teste desse cenário. Alternativa mais robusta: `restrictOnDelete` nas duas chaves (ver o ponto da etapa 05, decidir na etapa 14).
- [ ] **Aviso de erro no topo da tela de categorias** — com uma lista longa e a rolagem preservada, o aviso pode ficar fora da vista; só aparece com a tela desatualizada.
- [ ] **"Cancelar não muda nada" e "Excluir oculto" sem teste automatizado** — verificados só no navegador (sem teste de componente no projeto).

## Etapa 13 — Desativar e reativar categoria
- [x] **Categoria desativada e em uso não tem "Excluir" no menu** — resolvido na etapa 14.
- [x] **"Desativar" e "Excluir" levam ao mesmo lugar para categoria ativa em uso** — resolvido na etapa 14 (o diálogo agora oferece excluir com troca; "Só desativar" continua disponível).
- [ ] **Testes dependem de "8 categorias" iniciais** — vários testes contam `has('categories', 8)`/`7`; se a lista inicial mudar, eles quebram. Derivar a contagem do banco.
- [ ] **Desativar e reativar sem estado de processamento** — clique duplo dispara duas requisições (idempotentes, sem efeito); nenhuma falha de rede é avisada.
- [ ] **Aviso ao repetir só aparece na carga da tela** — some se houver erro de validação na categoria depois; aceitável.
- [ ] **Fluxo do diálogo "em uso" e o menu condicional sem teste automatizado** — verificados só no navegador.

## Etapa 14 — Excluir categoria em uso com troca
- [ ] **Destino pode mudar entre a validação e a troca** — só a categoria excluída é travada; se o destino for desativado ou excluído nesse meio-tempo, os gastos vão para uma categoria que acabou de sumir (no MySQL a chave estrangeira recusa e vira erro 500). A janela é mínima num sistema de um usuário; decidir se vale travar o destino também.
- [ ] **Defeito da etapa 13 só apareceria na tela** — o helper chamava `router.delete` com a assinatura errada e nenhum teste de backend pega isso; reforça o ponto de ter teste de componente ou de ponta a ponta.
- [ ] **Esc com o seletor aberto fecha o diálogo inteiro** — é o comportamento padrão do navegador, mas pode surpreender.
- [ ] **Testes dependem da ordem e do número de categorias iniciais** — `test_after_the_swap...` assume 7 categorias e `categories.0` = "Alimentação"; se a lista inicial ou a ordem mudarem, quebra por motivo alheio.
- [ ] **Troca para destino inválido não recarrega a lista** — o aviso aparece, mas a lista de destinos só atualiza na próxima navegação.
