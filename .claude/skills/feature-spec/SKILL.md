---
name: feature-spec
description: Escreve e refina specs de funcionalidades de produto antes da implementação — problema, escopo, regras de negócio, telas e fluxos, e etapas de entrega testáveis. Use sempre que o usuário quiser planejar, especificar, documentar ou amadurecer uma ideia de feature, mesmo que não diga "spec" — por exemplo "tenho uma ideia de funcionalidade", "o que acha dessa feature", "vamos documentar isso antes de codar", "quebra isso em etapas", "escreve os requisitos", "PRD". Não use para documentar código que já existe nem para implementar a feature.
---

# Spec de funcionalidade

Uma spec existe para ser lida. Se for longa, técnica demais ou especular sobre o futuro, ninguém consulta, e ela perde o sentido. O trabalho aqui é ajudar o usuário a decidir **o que** será feito e **o que se espera alcançar**, da forma mais simples possível, e registrar isso de um jeito que se encontre qualquer informação em segundos.

## Princípios

Cada princípio tem um motivo; entenda o motivo e aplique com critério.

- **O quê, nunca o como.** A spec não fala de tabelas, colunas, rotas, classes, services, bibliotecas ou nomes de arquivo. O "como" é decidido na implementação, validando contra os requisitos da spec. Decidir o "como" cedo trava a implementação em escolhas feitas sem informação e gera retrabalho.
- **Só o que será feito agora.** Nada de V2, roadmap, "preparar para o futuro" ou "o schema já nasce pronto para X". Prever o futuro é a principal fonte de overengineering; quando o futuro chegar, refatora-se. O que não será feito vai para "Fora de escopo", sem prometer data.
- **A solução mais simples para cada problema.** Toda regra, tela e campo precisa responder a um problema listado na spec. Se não responde, corte. Se o usuário não pediu análise automática, veredito ou classificação, não invente: prefira mostrar a informação e deixar a leitura com ele.
- **Concreta.** Exemplos com números reais ("café 500 g por R$ 18,90 → R$ 37,80/kg") valem mais que definições abstratas e já servem de caso de teste. Frases curtas, tabelas para comparações, nada de adjetivo no lugar de número.
- **Você propõe, o usuário decide.** Dê opinião e recomende com motivo. Quando o usuário decidir diferente, registre a decisão dele com o motivo dele, sem reabrir a discussão.

## Fluxo de trabalho

### 1. Entender e opinar (no chat, antes de escrever)

Quando o usuário trouxer a ideia, leia rapidamente o projeto para ter contexto (o que já existe, convenções), mas não leve detalhes técnicos para a spec. Responda no chat com:

- o que é bom na ideia e qual o problema real por trás dela;
- os riscos concretos (ex.: se registrar dá trabalho, ninguém registra e não há dados; se a resposta não chega no momento de uso, a feature não é usada);
- o que você faria diferente, como recomendação, não como lista de opções.

Só crie a spec quando o usuário pedir ou concordar.

### 2. Escrever a spec

Use o formato de documento disponível na sessão: um doc com abas, se houver conector de documentos, ou arquivos markdown se o usuário quiser a spec no repositório (uma pasta `docs/specs/<nome-da-feature>/` com um arquivo por aba). Escreva no idioma do usuário. A estrutura completa, com o que vai em cada aba, está em `references/estrutura.md`. Leia antes de escrever.

Resumo das abas:

1. **Visão geral** — o que a feature responde, problema e objetivos, critérios de sucesso, escopo, fora de escopo, decisões com motivo. Links para as outras abas no topo.
2. **Regras** — as regras de negócio e o que o sistema calcula ou decide, com exemplos numéricos.
3. **Telas e fluxos** — o que o usuário vê e faz em cada fluxo, com exemplos de dados na tela.
4. **Implementação** — as etapas de entrega. Escreva quando o usuário pedir, ou ofereça depois que o escopo estabilizar.

Cada aba deve caber em uma ou duas telas. Se passar disso, corte repetição antes de cortar conteúdo.

### 3. Iterar

A spec vai mudar muito: é para isso que ela existe. A cada mudança pedida:

- Aplique a mudança em **todas** as abas. Depois busque os termos antigos (ex.: "V1", "veredito", o nome de um campo removido) para achar frases que ficaram desatualizadas; uma spec contraditória é pior que uma spec incompleta.
- Se a mudança deixar alguma regra sem sentido ou em contradição, pergunte no chat, com o trecho exato e uma sugestão. Não resolva sozinho e não crie uma seção de "questões em aberto" a menos que o usuário queira.
- Ao terminar, diga no chat o que mudou, em poucas linhas. Separe o que você acrescentou sem ter sido pedido, para o usuário poder recusar.
- Quando o usuário responder uma pergunta, incorpore a resposta como regra ou decisão e remova a pergunta. Se ele disser que algo não importa, não registre nada sobre isso.

### 4. Etapas de implementação

É a parte que mais facilmente sai errada. Cada etapa é uma **fatia vertical**: entrega um comportamento que o usuário percebe, de ponta a ponta, e resolve um problema específico da spec.

Cada etapa tem:

- **Problema:** qual problema da spec ela resolve, em uma frase.
- **Entrega:** o comportamento visível ao final, sem dizer como é construído.
- **Pronto quando:** critérios verificáveis, de preferência com exemplo numérico, que viram testes. Critérios de tempo ("em menos de 10 segundos") são verificados à mão.

Ordene as etapas para que as primeiras já entreguem valor real sozinhas, e diga no topo da aba a partir de qual etapa a feature já é útil.

Nunca crie etapas por camada técnica, como "criar estrutura do banco de dados", "criar a API" ou "criar as telas". Elas não resolvem problema nenhum sozinhas, não são testáveis pelo usuário e obrigam a prever tudo o que as etapas seguintes vão precisar, que é exatamente o overengineering a evitar. Cada etapa constrói só o que ela precisa; se uma etapa posterior exigir mudança, a mudança é feita nela.

Exemplo de etapa bem escrita:

> **3. Saber quanto costumo pagar**
> **Problema:** a lista crua de compras obriga a fazer conta de cabeça na prateleira.
> **Entrega:** no topo do produto, um resumo com mediana, mínimo e máximo, última compra e quantidade de compras.
> **Pronto quando:**
> - Com compras a R$ 10, 10, 10 e 30, o resumo mostra mediana R$ 10,00 e contagem 4.
> - Com uma única compra, o resumo aparece normalmente.

Se, ao escrever as etapas, alguma regra da spec não puder virar critério testável (por ambígua ou contraditória), não invente a interpretação: aponte no chat e pergunte.

## Antes de entregar cada versão

- A spec não menciona tecnologia, estrutura de dados, rotas ou nomes de código.
- Não há V2, roadmap ou "preparar para o futuro".
- Toda regra e tela responde a um problema da Visão geral.
- As abas não se contradizem; termos removidos não sobrevivem em nenhuma frase.
- Os exemplos numéricos estão corretos (confira as contas).
- Cada aba cabe em uma ou duas telas.
- A resposta no chat diz o que mudou e aponta o que foi acrescentado sem pedido.
