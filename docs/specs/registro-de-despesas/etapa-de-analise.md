# Registro rápido de despesas

**Tipo:** funcionalidade · **Status:** em andamento · **Atualizada em:** 2026-11-05

## Problema
Quem controla os próprios gastos registra a maioria deles pelo celular, logo após pagar, e o restante no fim do dia ou numa revisão no fim de semana. Boa parte dos gastos se repete (o mesmo almoço, o mesmo refrigerante da tarde), mas os sistemas existentes obrigam a redigitar descrição, valor, categoria e tags a cada vez. Esse esforço repetido faz a pessoa pular registros, depois parar de registrar, e perder a clareza sobre onde poderia reduzir gastos.

## Solução
Um cadastro curto, em que só o valor é obrigatório e a data já vem como hoje. A partir dele, qualquer gasto pode ser **repetido**: abre o cadastro já preenchido com os dados dele e a data de hoje, para ajustar e salvar. Os gastos mais frequentes podem ser **favoritados** e repetidos a partir de uma lista de favoritos. Os gastos aparecem numa lista no estilo de extrato bancário, agrupados por dia, com o total gasto em cada dia.
**Motivo da escolha:** ataca diretamente a redigitação dos gastos repetidos sem pedir que a pessoa configure nada antes, e aceita variações (o mesmo almoço com bebida, por exemplo).

## Soluções consideradas
- **Atalhos criados à parte** — cadastrar modelos fixos antes de usar — criar um atalho do zero é pouco intuitivo; favoritar algo que já existe é mais natural.
- **Sugestão automática ao digitar a descrição** — preenche com os dados da última vez — fica como evolução possível; favoritar e repetir já cobrem o problema com menos incerteza.
- **Só o cadastro mínimo** — não resolve a redigitação, que é o problema central.

## Escopo
**Inclui:**
- Cadastrar, editar e excluir gastos (valor, data, descrição, categoria, tags).
- Repetir qualquer gasto, favorito ou não.
- Favoritar gastos no cadastro, na edição ou a partir da lista; gerenciar a lista de favoritos (editar, renomear, remover).
- Lista de gastos no estilo extrato, com separadores por dia e o total do dia.
- Gerenciar categorias (já vêm cadastradas; podem ser criadas, editadas, desativadas, reativadas e excluídas) e tags (criadas livremente).
- Uso confortável no celular.

**Não inclui:**
- Análise de gastos: totais por categoria, quanto ainda dá para gastar no mês (depende de entradas e histórico; fica com a pessoa por enquanto).
- Forma de pagamento (virá numa evolução futura).
- Receitas/entradas e planejamento de gastos futuros (a data futura é aceita, mas não há nenhuma funcionalidade de planejamento).
- Sugestão automática ao digitar.

## Critérios de aceite
- [ ] Dá para salvar um gasto informando só o valor; a data vem preenchida com hoje e pode ser alterada.
- [ ] O sistema não aceita salvar sem valor, com valor zero ou negativo, nem sem data.
- [ ] Descrição, categoria (no máximo uma) e tags (várias) são opcionais.
- [ ] Datas passadas, a de hoje e datas futuras são aceitas.
- [ ] Repetir um gasto da lista abre o cadastro com os mesmos dados e a data de hoje; nada é salvo até a pessoa confirmar, e qualquer campo pode ser alterado antes.
- [ ] Repetir um gasto não altera o gasto original.
- [ ] Dá para favoritar um gasto no cadastro, na edição ou direto da lista, inclusive um gasto que veio de um favorito.
- [ ] Na lista de favoritos, tocar num favorito abre o cadastro preenchido com os dados dele e a data de hoje, como no repetir.
- [ ] Editar ou excluir o gasto que deu origem a um favorito não altera nem remove o favorito.
- [ ] Um favorito pode ser renomeado, editado e removido sem afetar nenhum gasto.
- [ ] A lista de gastos mostra do mais recente para o mais antigo, com um separador por dia exibindo a data e o total gasto naquele dia.
- [ ] Da lista, cada gasto pode ser editado, excluído (com confirmação), repetido e favoritado.
- [ ] Na primeira vez, já existem as categorias Alimentação, Transporte, Moradia, Saúde, Lazer, Educação, Compras e Outros; nenhuma tag existe.
- [ ] Categorias podem ser criadas e renomeadas; tags podem ser criadas na hora de atribuir e também excluídas.
- [ ] Uma categoria sem gastos é excluída normalmente; uma categoria com gastos oferece desativar ou excluir trocando-a por outra (ou deixando sem categoria) nos gastos que a usam.
- [ ] Uma categoria desativada não aparece para escolha em gastos novos, continua visível nos gastos antigos e pode ser reativada.

## Decisões
- Só o valor é obrigatório; a data vem com hoje — valor e data são o mínimo que faz sentido; na correria o resto pode ficar para a revisão.
- Repetir abre o cadastro preenchido em vez de salvar direto — um toque a mais, mas fica claro o que será salvo e é natural ajustar variações.
- O favorito é uma cópia independente do gasto — excluir ou corrigir um gasto antigo na revisão não deve apagar ou mudar um favorito.
- "Repetir" funciona sem favoritar — gastos frequentes só por uma semana não devem poluir a lista de favoritos.
- Data futura é aceita — não atrapalha e prepara para o planejamento que virá depois.
- Categoria em uso é desativada ou trocada, nunca apagada sem destino — evita bagunçar gastos já registrados.

## Em aberto
- Lista inicial definitiva de categorias — a pessoa ainda não tem certeza — **Como seguir:** usar a lista provisória dos critérios, definida num único lugar para ser fácil trocar.

## Detalhes
- [fluxo-do-usuario.md](fluxo-do-usuario.md) — telas e passos de cadastrar, repetir, favoritar e revisar; abra ao implementar ou testar as telas.
- [categorias-e-tags.md](categorias-e-tags.md) — regras completas de criar, desativar e excluir categorias e tags, e o efeito em gastos e favoritos; abra ao implementar essa gestão.
- [pontos-para-revisao-final.md](pontos-para-revisao-final.md) — pontos do revisor automático não corrigidos nas etapas; abra na revisão final.
- [etapas/](etapas/) — etapas de desenvolvimento, uma por arquivo, em ordem; abra ao implementar.
