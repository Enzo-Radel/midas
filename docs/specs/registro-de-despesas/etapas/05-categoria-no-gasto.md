# 05 — Categoria no gasto

**Status:** concluída · **Atualizada em:** 2026-11-05
**Depende de:** etapa 03

## O que esta etapa entrega
Passam a existir categorias pré-cadastradas, e o gasto pode ter uma categoria (opcional), escolhida no cadastro e na edição.

## Critérios de aceite
- [x] Na primeira vez já existem as categorias Alimentação, Transporte, Moradia, Saúde, Lazer, Educação, Compras e Outros.
- [x] No cadastro e na edição é possível escolher no máximo uma categoria, ou nenhuma.
- [x] A categoria do gasto aparece na lista.
- [x] É possível trocar ou remover a categoria de um gasto na edição.
- [x] A tela é confortável de usar no celular.

## Valores provisórios
- Lista inicial de categorias: Alimentação, Transporte, Moradia, Saúde, Lazer, Educação, Compras, Outros — definida num único lugar para ser trocada depois.

## Fora desta etapa
- Criar, renomear, desativar e excluir categorias (etapas 11 a 14).

## Registro da implementação
**O que foi alterado**
- Passam a existir as categorias, já cadastradas para o usuário: Alimentação, Transporte, Moradia, Saúde, Lazer, Educação, Compras e Outros. A lista fica só em `config/midas.php` (valor provisório, fácil de trocar). Quem já existia antes desta etapa também recebe as categorias na migração.
- O cadastro e a edição de gasto ganharam o campo "Categoria (opcional)", com a opção "Sem categoria". Um gasto tem no máximo uma categoria, que pode ser trocada ou removida na edição.
- A lista mostra a categoria como uma etiqueta embaixo da descrição.
- Só categorias do próprio usuário são aceitas e oferecidas.

**Testes**
- `tests/Feature/Expenses/ExpenseCategoryTest.php` — as 8 categorias existem na primeira vez e não são recriadas; gasto salvo com uma categoria e sem categoria; categoria inexistente, de outro usuário ou com formato inválido é recusada (no cadastro e na edição); categoria aparece na lista; telas oferecem só as categorias do usuário; trocar e remover a categoria na edição.

**Verificação manual** (navegador, tela de celular, banco temporário)
- Campo com as 9 opções; gasto cadastrado com "Alimentação" aparece com a etiqueta; na edição a categoria atual vem selecionada; trocar para "Lazer" e para "Sem categoria" reflete na lista.
- Migração em banco com dois usuários já existentes: ambos receberam as 8 categorias.

**Desvios do planejado**
- Um teste foi reescrito na forma de checar a lista de nomes, porque o `assertInertia` não aceita `*` no caminho; verifica o mesmo (os 8 nomes, na ordem).
- O usuário padrão e suas categorias agora são criados na mesma transação, para ele nunca ficar sem categorias.

**Revisão automática**
- Corrigido: migration sem depender do model (nem de import sem uso), regra de tipo em `category_id` (evitava erro 500 com valor inválido), criação do usuário e das categorias numa transação, tipo de retorno, e testes de isolamento entre usuários na edição.
- Pontos não corrigidos estão em `../pontos-para-revisao-final.md`.
