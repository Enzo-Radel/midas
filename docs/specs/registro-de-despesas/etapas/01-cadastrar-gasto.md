# 01 — Cadastrar um gasto

**Status:** concluída · **Atualizada em:** 2026-11-05
**Depende de:** nenhuma

## O que esta etapa entrega
Passa a existir a tela de novo gasto, em que basta informar o valor para salvar. A data vem com hoje e a descrição é opcional. O gasto salvo aparece numa lista simples, do mais recente para o mais antigo.

## Critérios de aceite
- [x] Dá para salvar um gasto informando só o valor; a data vem preenchida com hoje.
- [x] Ao abrir o cadastro, o foco vai direto para o campo de valor.
- [x] A data pode ser alterada para um dia passado ou futuro, e o gasto é salvo com a data escolhida.
- [x] A descrição é opcional e, quando informada, é salva com o gasto.
- [x] O sistema não aceita salvar sem valor, com valor zero ou negativo, nem sem data, e mostra o motivo.
- [x] Cancelar ou voltar no meio do cadastro não salva nada.
- [x] Depois de salvar, o gasto aparece na lista, que mostra do mais recente para o mais antigo.
- [x] Gasto sem descrição aparece na lista com um texto neutro (ex.: "Sem descrição").
- [x] A tela é confortável de usar no celular.

## Fora desta etapa
- Separadores por dia e total do dia (etapa 02).
- Categoria e tags (etapas 05 e 06).

## Registro da implementação
**O que foi alterado**
- Passa a existir a tela "Novo gasto" (`/despesas/nova`), com o foco já no valor, a data preenchida com hoje (do dispositivo) e a descrição opcional. O valor aceita "25,50", "25.50" e "1.000,50".
- Salvar leva para a lista de despesas (`/despesas`), do mais recente para o mais antigo (empate no dia: o cadastrado por último aparece primeiro). Os erros aparecem sob o campo, em português. Cancelar volta para a lista sem salvar.
- Gasto sem descrição aparece como "Sem descrição". Lista vazia mostra convite para registrar o primeiro gasto (ele pertence ao critério da etapa 02, mas a lista já nasce com ele).
- O item "Expenses" do menu lateral passou a abrir a lista de despesas.
- Valores são guardados em centavos (inteiro), para não ter erro de arredondamento nas somas das próximas etapas.

**Testes**
- `tests/Feature/Expenses/RegisterExpenseTest.php` — tela de cadastro disponível; salvar só com valor e data; usuário padrão como dono; descrição salva; datas passadas e futuras aceitas; recusa de valor ausente, zero, negativo, não numérico, com mais de 2 casas, data ausente e inválida (nada é salvo); ordem da lista; lista ignora gastos de outros usuários.

**Verificação manual** (feita em navegador com viewport de celular, banco temporário)
- Foco no valor ao abrir, data de hoje preenchida, mensagens de erro (vazio e zero), salvar "25,50" e com descrição e data futura, cancelar sem salvar, texto "Sem descrição" e lista vazia: tudo conforme esperado.
- Para o usuário conferir: aparência e conforto de uso no próprio celular.

**Desvios do planejado**
- Como o sistema não tem login, foi criado um usuário padrão (decisão do usuário), usado por todas as telas de despesas via `UseDefaultUser`, com dados em `config/midas.php`. É provisório: quando houver login, troca-se esse middleware pela autenticação.
- A tabela `expenses` existente foi alterada na própria migration (valor em centavos e descrição opcional), pois o usuário confirmou que ela não tem dados reais. Quem já rodou a migration original localmente precisa de `php artisan migrate:fresh`.
- Os testes passam a rodar em SQLite em memória (`phpunit.xml`), porque não há MySQL no ambiente de desenvolvimento. O aplicativo continua usando MySQL.
- Foi publicado `config/inertia.php`, só com o caminho das páginas ajustado para `js/Pages`, para os testes encontrarem as telas.
- `composer.lock` atualizou `nette/schema` (1.3.4 para 1.4.0), porque a versão antiga não podia mais ser baixada.

**Revisão automática**
- Corrigido: valor com separador de milhar ("1.000,50"), hover do botão desabilitado e contraste do texto "Sem descrição".
- Os pontos não corrigidos estão em `../pontos-para-revisao-final.md`, para a revisão final.
