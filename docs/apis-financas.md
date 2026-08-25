# APIs para Sistema de Controle Financeiro (Renda Fixa + Renda Variável)

Guia de referência para implementação. Todas as opções são gratuitas ou têm plano free suficiente para uso pessoal.

## 1. Renda Fixa (SELIC, CDI, IPCA, IGP-M, TR)

**Usar:** API SGS do Banco Central do Brasil
**Para quê:** única fonte necessária para índices de renda fixa. Oficial, gratuita, sem chave/cadastro.
**Endpoint base:** `https://api.bcb.gov.br/dados/serie/bcdata.sgs.{codigo}/dados?formato=json`
**Últimos N valores:** `.../dados/ultimos/{N}?formato=json` (máx. N=20)
**Por período:** adicionar `&dataInicial=dd/MM/yyyy&dataFinal=dd/MM/yyyy` (máx. 10 anos por consulta)

Códigos de série mais usados:

| Índice | Código |
|---|---|
| SELIC meta | 432 |
| SELIC efetiva (diária) | 11 |
| CDI (diária) | 12 |
| IPCA (mensal) | 433 |
| IPCA acumulado 12 meses | 13522 |
| IGP-M | 189 |
| INPC | 188 |
| TR | 226 |
| USD/BRL (PTAX) | 1 |
| Ibovespa | 7 |

**Docs completos:** https://dadosabertos.bcb.gov.br/dataset/11-taxa-de-juros---selic (outros datasets: busque pelo código da série em dadosabertos.bcb.gov.br)
**Limites:** sem rate limit documentado; cache diário é suficiente.

## 2. Ações e FIIs (B3)

**Usar (principal):** brapi.dev — cobre ações, FIIs, ETFs, BDRs, e também tem indicadores macro (SELIC/CDI/IPCA) e cripto, tudo em uma API só.
**Para quê:** cotações, histórico, dividendos, dados fundamentalistas de ações e FIIs da B3.
**Plano gratuito:** 15.000 requisições/mês. Tickers PETR4, VALE3, MGLU3, ITUB4 funcionam sem token e sem limite (bom para testes).
**Autenticação:** criar conta grátis em brapi.dev para gerar token e acessar outros tickers.
**Docs:** https://brapi.dev/docs
**Limitação:** delay de 15 min (free/Startup) ou 5 min (Pro); histórico e dados detalhados de FII mais completos só no plano Pro (R$116,66/mês).

**Usar (fallback gratuito, sem cadastro):** Yahoo Finance (endpoint não-oficial)
**Para quê:** cotações e histórico de B3 caso brapi não seja suficiente. Ticker = código + `.SA` (ex: `PETR4.SA`, `MXRF11.SA`).
**Endpoint:** `https://query1.finance.yahoo.com/v8/finance/chart/{TICKER}.SA`
**Cuidado:** API não-oficial, pode quebrar ou bloquear IP se usada agressivamente. Usar cache e evitar mais de ~1 req/segundo.

**Usar (opcional, fundamentos detalhados de FII):** scraping de Fundamentus (fundamentus.com.br) ou StatusInvest (statusinvest.com.br) — sem API oficial, só se brapi Pro não for viável.

## 3. Criptomoedas

**Usar (principal):** CoinGecko — plano Demo gratuito
**Para quê:** preços em BRL, market cap, histórico de até 1 ano.
**Limite:** 10.000 chamadas/mês, 100 req/min (com chave Demo grátis) ou ~30 req/min sem chave.
**Cadastro:** grátis em coingecko.com para gerar chave Demo.
**Docs:** https://docs.coingecko.com/reference/introduction

**Usar (complementar, sem cadastro):** API pública da Binance
**Para quê:** preços em tempo real de pares específicos (ex: BTCBRL), sem chave.
**Endpoint:** `https://api.binance.com/api/v3/ticker/price?symbol=BTCBRL`
**Docs:** https://developers.binance.com/docs/binance-spot-api-docs

## Stack recomendada

1. **Renda fixa:** BCB SGS (curl/JSON direto)
2. **Ações e FIIs:** brapi.dev free tier como principal
3. **Cripto:** CoinGecko Demo como principal, Binance público como complemento
4. Implementar cache local (banco de dados) para os dados: índices de renda fixa mudam no máximo 1x/dia e cotações não precisam ser buscadas a cada request — isso mantém o uso bem abaixo de qualquer limite gratuito.

**Quando migrar para pago:** só se ultrapassar 15k req/mês na brapi, ou precisar de dado intradiário (<15min de delay) ou histórico >1 ano de FIIs — nesse caso, brapi Startup (R$99,99/mês) ou Pro (R$116,66/mês).

## Endpoints implementados na aplicação

As rotas abaixo já estão implementadas em `routes/api.php`, delegando para services em `app/Services/Finance/`. Todas usam cache (`Cache::remember`) para respeitar os limites gratuitos das APIs externas.

### Renda fixa (`App\Services\Finance\BcbSgsService`)

| Rota | Descrição |
|---|---|
| `GET /api/renda-fixa` | Lista os índices disponíveis (chaves usadas nas outras rotas) |
| `GET /api/renda-fixa/{indice}?ultimos=N` | Últimos N valores (máx. 20) |
| `GET /api/renda-fixa/{indice}?data_inicial=dd/MM/yyyy&data_final=dd/MM/yyyy` | Valores por período |

Índices disponíveis: `selic-meta`, `selic-diaria`, `cdi`, `ipca`, `ipca-12-meses`, `igpm`, `inpc`, `tr`, `usd-brl`, `ibovespa`.

Cache: 24h (índices de renda fixa mudam no máximo 1x/dia).

### Ações e FIIs (`App\Services\Finance\BrapiService`)

| Rota | Descrição |
|---|---|
| `GET /api/acoes/{ticker}` | Cotação atual (brapi.dev, com fallback para Yahoo Finance) |
| `GET /api/acoes/{ticker}/historico?range=&interval=` | Histórico de cotações |
| `GET /api/fiis/{ticker}` | Mesmo comportamento de `acoes`, para FIIs |
| `GET /api/fiis/{ticker}/historico?range=&interval=` | Histórico de cotações de FII |

Requer `BRAPI_TOKEN` no `.env` para tickers além dos gratuitos (PETR4, VALE3, MGLU3, ITUB4). Sem o token, apenas esses tickers funcionam.

Cache: 15 min para cotação atual, 6h para histórico.

### Criptomoedas (`App\Services\Finance\CriptoService`)

| Rota | Descrição |
|---|---|
| `GET /api/cripto/precos?moedas=bitcoin,ethereum&vs=brl` | Preços atuais (CoinGecko) |
| `GET /api/cripto/{moeda}/historico?dias=30&vs=brl` | Histórico de preços (CoinGecko, máx. 365 dias) |
| `GET /api/cripto/binance/{par}` | Preço em tempo real de um par (ex: `BTCBRL`), via Binance |

`COINGECKO_API_KEY` é opcional: sem ela, as requisições à CoinGecko usam o limite público (~30 req/min); com a chave Demo, o limite sobe para 100 req/min / 10.000 req/mês.

Cache: 5 min para preços, 1h para histórico (CoinGecko); 1 min para Binance.

### Variáveis de ambiente

```
BRAPI_TOKEN=
COINGECKO_API_KEY=
```

Nenhuma das duas é obrigatória para os endpoints funcionarem com os tickers/limites gratuitos descritos acima.
