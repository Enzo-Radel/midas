# Fluxo do usuário

Telas e passos do registro de despesas, do ponto de vista de quem usa, pensados primeiro para o celular.

## Lista de gastos (tela inicial)
- Estilo extrato bancário: do mais recente para o mais antigo.
- Um separador por dia, com a data (ex.: "Hoje", "Ontem", "seg, 2 nov") e o total gasto naquele dia.
- Cada gasto mostra descrição (ou um texto neutro como "Sem descrição"), valor, categoria e tags.
- Ações em cada gasto, num menu de três pontos: editar, repetir, favoritar/desfavoritar e excluir (pede confirmação). Gasto favoritado mostra uma estrela.
- Acesso visível a: novo gasto (botão em destaque) e, numa linha de atalhos logo abaixo do título, favoritos, categorias e tags.
- Lista vazia: mensagem convidando a cadastrar o primeiro gasto.

## Cadastrar um gasto
1. A pessoa toca em "novo gasto".
2. O foco vai direto para o valor (o caso mais comum é registrar logo após pagar). Na edição e na repetição o foco não vai para o campo, para não abrir o teclado sem necessidade.
3. A data já vem com hoje e pode ser trocada (passada ou futura).
4. Opcionais: descrição, categoria (uma, só entre as ativas), tags (várias; dá para criar uma nova digitando o nome).
5. Opção "favoritar" no próprio cadastro.
6. Salvar. Volta para a lista, com o gasto aparecendo no dia certo.
- Desistir no meio (voltar ou cancelar) não salva nada.

## Repetir um gasto
1. Da lista, a pessoa toca em "repetir" num gasto qualquer.
2. Abre o cadastro preenchido com descrição, valor, categoria e tags daquele gasto, e a data de hoje.
3. A pessoa ajusta o que quiser (ex.: valor maior porque pegou bebida) e salva.
- Um novo gasto é criado; o original não muda.
- O novo gasto também pode ser favoritado ali.
- Se a categoria do gasto original está desativada, o cadastro abre sem categoria e avisa isso.

## Favoritos
- Favoritar cria uma cópia dos dados do gasto (descrição, valor, categoria, tags) na lista de favoritos. O nome exibido começa sendo a descrição.
- Lista de favoritos (atalho "Favoritos" na lista de despesas): em ordem alfabética, com os sem nome por último. Tocar num favorito abre o cadastro ("Usar favorito") preenchido com a data de hoje, como o repetir; Cancelar volta para a lista de favoritos.
- Cada favorito tem um menu de três pontos: **Editar** (tela com nome, valor, descrição, categoria e tags; sem data) e **Remover** (pede confirmação e lembra que os gastos já registrados não são afetados).
- Gasto sem descrição favoritado: o favorito exibe algo identificável, como o valor e a categoria, até ser renomeado.
- Favoritar duas vezes o mesmo gasto não deve criar dois favoritos iguais; o mesmo gasto aparece como já favoritado.
- Editar ou excluir o gasto de origem não afeta o favorito.
- Lista de favoritos vazia: mensagem explicando que dá para favoritar a partir de qualquer gasto.
- Se a categoria do favorito está desativada, usá-lo abre o cadastro sem categoria e avisa isso.

## Categorias (atalho "Categorias")
- Lista com o uso de cada categoria ("3 gastos · 1 favorito" ou "Não está em uso"); campo "Nova categoria" no topo.
- Cada categoria tem um menu: **Renomear** (no próprio item), **Desativar/Reativar** e **Excluir**.
- Excluir categoria sem uso pede uma confirmação simples. Excluir categoria **em uso** abre um diálogo que diz quantos gastos e favoritos a usam e pede o destino deles: "Sem categoria" ou outra categoria ativa ("Excluir e trocar"); categoria ativa também pode só ser desativada ("Só desativar").
- Categoria desativada mostra o selo "Desativada", não é oferecida para escolha e continua nos gastos e favoritos antigos; ao editar um gasto ou favorito que a usa, ela continua selecionada (marcada como "desativada") até ser trocada.
- Mensagem de erro junto do campo quando o nome repete o de outra categoria (sem diferenciar maiúsculas de minúsculas).

## Tags (atalho "Tags")
- As tags são criadas ao registrar ou editar um gasto (digitando o nome); esta tela só renomeia e exclui.
- Lista em ordem alfabética com o uso de cada tag; cada uma tem um menu com **Renomear** (no próprio item, sem repetir o nome de outra tag) e **Excluir** (confirmação que diz de quantos gastos e favoritos a tag será retirada; eles não são excluídos).
- Sem tags: mensagem explicando como criá-las.

## Revisão do fim de semana
- A pessoa percorre a lista por dia, completa descrição, categoria e tags dos gastos registrados às pressas, corrige valores e cadastra o que esqueceu, escolhendo a data do dia em que gastou.
