# Fluxo do usuário

Telas e passos do registro de despesas, do ponto de vista de quem usa, pensados primeiro para o celular.

## Lista de gastos (tela inicial)
- Estilo extrato bancário: do mais recente para o mais antigo.
- Um separador por dia, com a data (ex.: "Hoje", "Ontem", "seg, 2 nov") e o total gasto naquele dia.
- Cada gasto mostra descrição (ou um texto neutro como "Sem descrição"), valor, categoria e tags.
- Ações em cada gasto: editar, excluir (pede confirmação), repetir, favoritar/desfavoritar.
- Acesso visível a: novo gasto, favoritos.
- Lista vazia: mensagem convidando a cadastrar o primeiro gasto.

## Cadastrar um gasto
1. A pessoa toca em "novo gasto".
2. O foco vai direto para o valor (o caso mais comum é registrar logo após pagar).
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

## Favoritos
- Favoritar cria uma cópia dos dados do gasto (descrição, valor, categoria, tags) na lista de favoritos. O nome exibido começa sendo a descrição.
- Lista de favoritos: tocar num favorito funciona como o repetir (abre o cadastro preenchido com a data de hoje).
- No favorito dá para renomear, editar os dados e remover.
- Gasto sem descrição favoritado: o favorito exibe algo identificável, como o valor e a categoria, até ser renomeado.
- Favoritar duas vezes o mesmo gasto não deve criar dois favoritos iguais; o mesmo gasto aparece como já favoritado.
- Editar ou excluir o gasto de origem não afeta o favorito.
- Lista de favoritos vazia: mensagem explicando que dá para favoritar a partir de qualquer gasto.

## Revisão do fim de semana
- A pessoa percorre a lista por dia, completa descrição, categoria e tags dos gastos registrados às pressas, corrige valores e cadastra o que esqueceu, escolhendo a data do dia em que gastou.
