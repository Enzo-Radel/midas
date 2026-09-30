# Estrutura da spec

Modelo das abas. Os títulos podem mudar, mas a ordem e o conteúdo de cada aba se mantêm. Seções sem conteúdo real ficam de fora.

## Aba 1 — Visão geral

A primeira aba precisa responder sozinha: o que é, por que existe, o que entra e o que não entra.

- **Título e data.**
- **Abertura (2 frases):** a pergunta ou tarefa que a feature resolve, dita do ponto de vista do usuário no momento de uso. Em seguida, links para as outras abas.
- **Problema e objetivos:** os objetivos em uma frase, depois as situações concretas que motivam a feature, numeradas, cada uma com um nome curto em negrito e um exemplo real.
- **Critérios de sucesso:** 3 a 5 itens mensuráveis (tempo, quantidade de passos, o que cabe em uma tela).
- **Escopo:** tabela `Entra | Observação`, uma linha por capacidade.
- **Fora de escopo:** lista curta, cada item em negrito com o motivo em uma frase. É aqui que ficam as ideias descartadas ou adiadas, sem prometer versão futura.
- **Decisões:** tabela `Decisão | Motivo`. Registre as decisões do usuário com o motivo dele, sobretudo as que contrariam uma sugestão sua; isso evita reabrir a discussão depois. Uma decisão que precise de mais explicação ganha um parágrafo curto logo abaixo da tabela.

## Aba 2 — Regras

Como os dados se comportam e o que o sistema calcula ou decide. Uma seção curta por conceito.

- Cada regra em linguagem de produto: "cada produto tem uma unidade base", nunca "coluna unidade_base na tabela produtos".
- Uma tabela de exemplos quando a regra envolve conversão ou cálculo: entrada, saída e o exemplo com números.
- Casos de borda explícitos quando mudam o comportamento (uma única observação, campo opcional em branco, empate).
- Se o sistema calcula medidas, uma tabela `Medida | Definição` e um exemplo numérico mostrando por que aquela medida foi escolhida.

## Aba 3 — Telas e fluxos

O que o usuário vê e faz. Uma seção por fluxo, na ordem de importância.

- Uma frase dizendo em que contexto o fluxo é usado (ex.: "de pé, com o celular em uma mão").
- Um diagrama de fluxo simples (mermaid), só quando houver decisões ou ramificações; fluxos lineares ficam em lista.
- Regras do fluxo em tópicos: o que aparece direto, o que fica a um toque, o que não acontece (ex.: "consultar não registra nada").
- Tabelas com exemplos de dados como o usuário os veria na tela.
- Nada de layout, componente ou tecnologia. O design decide a forma; a spec diz o que precisa estar lá.

## Aba 4 — Implementação

- **Abertura:** quantas etapas, a partir de qual a feature já é útil.
- **Regras para todas as etapas** (lista curta): cada etapa entrega um comportamento de ponta a ponta; só o necessário para ela; o "como" fica para a implementação e é validado contra as outras abas; critérios viram testes automatizados quando possível.
- **Uma seção por etapa**, numerada, com título que descreve o resultado para o usuário ("Comparar o preço entre mercados", não "Endpoint de comparação"), e os campos **Problema**, **Entrega** e **Pronto quando** (lista de verificação).

Ordem das etapas: primeiro o mínimo para existir dado e ser consultado, depois o que torna o uso diário viável (rapidez, menos esforço de registro), por último refinamentos e telas secundárias.
