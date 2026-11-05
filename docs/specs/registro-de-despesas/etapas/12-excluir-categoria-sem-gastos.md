# 12 — Excluir categoria sem gastos

**Status:** concluída · **Atualizada em:** 2026-11-05
**Depende de:** etapa 11

## O que esta etapa entrega
Na tela de categorias, uma categoria que nenhum gasto usa pode ser excluída com uma confirmação simples.

## Critérios de aceite
- [x] Uma categoria sem gastos ligados pode ser excluída depois de confirmação e some da escolha.
- [x] Cancelando a confirmação, nada muda.
- [x] Para uma categoria com gastos ligados, a exclusão simples não é oferecida.

## Fora desta etapa
- O que fazer com categoria em uso (etapas 13 e 14).

## Registro da implementação
**O que foi alterado**
- Na tela "Categorias", cada categoria passou a mostrar o seu uso ("3 gastos", "1 favorito", "1 gasto · 1 favorito" ou "Não está em uso").
- No menu de três pontos de uma categoria **sem uso** aparece "Excluir", que abre o diálogo "Excluir esta categoria?" com Cancelar e Excluir. Confirmar remove a categoria da lista e das opções de gasto e favorito; Cancelar (ou Esc) não muda nada. O nome de uma categoria excluída pode ser usado de novo.
- Para uma categoria **em uso** o item "Excluir" não aparece (só "Renomear"). Se mesmo assim o pedido de exclusão chegar (tela desatualizada), o servidor recusa, a categoria e o gasto continuam como estavam e a tela mostra o aviso "Esta categoria está em uso em gastos ou favoritos e não pode ser excluída.".
- A checagem de uso e a exclusão acontecem juntas, com a categoria travada, para um gasto que passe a usá-la no meio do caminho não ficar sem categoria em silêncio. Só o dono pode excluir as próprias categorias.

**Testes**
- `tests/Feature/Expenses/CategoryDeleteTest.php` — excluir categoria sem uso; sumir das opções de escolha; não afetar outras categorias nem gastos; reutilizar o nome depois; a tela traz a contagem de gastos e favoritos; categoria usada por gasto ou só por favorito não é excluída (e o gasto/favorito continua com ela); categoria que perdeu o último gasto pode ser excluída; categoria de outro usuário dá "não encontrado".

**Verificação manual** (navegador, tela de celular, banco temporário)
- A lista mostra o uso de cada categoria; o menu de uma categoria em uso só tem "Renomear" e o de uma sem uso tem "Renomear" e "Excluir"; o diálogo cita a categoria, Cancelar mantém, confirmar remove (9 para 8 categorias) e ela some das opções do cadastro de gasto.
- Para o usuário conferir no próprio celular: o conforto do menu e do diálogo.

**Desvios do planejado**
- "Em uso" considera gastos **e favoritos** (a etapa só fala em gastos). Assim um favorito nunca fica sem categoria em silêncio, de acordo com a análise ("a exclusão com troca também reatribui favoritos"). Uma categoria usada só por favoritos precisa ser tratada pelas etapas 13 e 14 (desativar ou trocar), ou o favorito removido antes.

**Revisão automática**
- Corrigido: exclusão numa transação com a categoria travada, asserção que não verificava nada no teste, cor do aviso na paleta de perigo.
- Pontos não corrigidos estão em `../pontos-para-revisao-final.md`.
