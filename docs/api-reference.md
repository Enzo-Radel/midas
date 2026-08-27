# Referência da API — Midas

Documentação das rotas HTTP expostas pela aplicação para consulta de dados financeiros. Todas as rotas ficam sob o prefixo `/api`, respondem em JSON e usam cache local (ver `docs/apis-financas.md` para os detalhes de TTL e limites de cada fonte externa).

Implementação: `routes/api.php`, controllers em `app/Http/Controllers/Api/`, services em `app/Services/Finance/`.

## Formato de erro

Quando a fonte externa falha ou o dado pedido não existe, a resposta segue o padrão:

```json
{
  "message": "Descrição do erro"
}
```

| Status | Quando ocorre |
|---|---|
| `422` | Parâmetros de query inválidos (falha de validação do Laravel) |
| `404` | Índice de renda fixa desconhecido (`{indice}` fora da lista suportada) |
| `502` | Falha ao consultar a API externa (BCB, brapi, Yahoo Finance, CoinGecko ou Binance) |

---

## Renda fixa

Fonte: SGS do Banco Central (`App\Services\Finance\BcbSgsService`).

### `GET /api/renda-fixa`

Lista os índices suportados.

**Resposta 200**
```json
{
  "indices": [
    "selic-meta", "selic-diaria", "cdi", "ipca", "ipca-12-meses",
    "igpm", "inpc", "tr", "usd-brl", "ibovespa"
  ]
}
```

### `GET /api/renda-fixa/{indice}`

Retorna a série do índice informado. `{indice}` deve ser uma das chaves listadas acima.

**Query params** (mutuamente exclusivos)

| Param | Tipo | Descrição |
|---|---|---|
| `ultimos` | int (1–20) | Retorna os últimos N valores. Padrão: `1`. |
| `data_inicial` + `data_final` | `dd/MM/yyyy` | Retorna os valores no período. Os dois são obrigatórios juntos. |

**Exemplos**

```
GET /api/renda-fixa/cdi?ultimos=5
GET /api/renda-fixa/ipca?data_inicial=01/01/2025&data_final=30/06/2025
```

**Resposta 200** (formato repassado da API do BCB)
```json
[
  { "data": "01/08/2026", "valor": "0.04" }
]
```

**Erros**
- `404` — `{indice}` não está na lista suportada.
- `502` — API do BCB indisponível.

---

## Ações e FIIs (B3)

Fonte principal: brapi.dev, com fallback automático para Yahoo Finance na cotação atual (`App\Services\Finance\BrapiService`). As rotas de FIIs têm o mesmo comportamento das de ações — a B3 não distingue os endpoints por tipo de ativo.

### `GET /api/acoes/{ticker}` · `GET /api/fiis/{ticker}`

Cotação atual do ticker (ex: `PETR4`, `MXRF11`). Sem `BRAPI_TOKEN` configurado, apenas os tickers gratuitos da brapi funcionam (`PETR4`, `VALE3`, `MGLU3`, `ITUB4`); qualquer outro cai automaticamente no fallback do Yahoo Finance.

```
GET /api/acoes/PETR4
GET /api/fiis/MXRF11
```

**Resposta 200** — formato da brapi (`GET /quote/{ticker}`) ou, no fallback, formato do Yahoo Finance `chart` endpoint.

**Erros**
- `502` — brapi e Yahoo Finance falharam.

### `GET /api/acoes/{ticker}/historico` · `GET /api/fiis/{ticker}/historico`

Histórico de cotações via brapi.

**Query params**

| Param | Valores aceitos | Padrão |
|---|---|---|
| `range` | `1d,5d,1mo,3mo,6mo,1y,2y,5y,10y,ytd,max` | `3mo` |
| `interval` | `1m,2m,5m,15m,30m,60m,90m,1h,1d,5d,1wk,1mo,3mo` | `1d` |

```
GET /api/acoes/VALE3/historico?range=1y&interval=1mo
```

**Erros**
- `422` — `range` ou `interval` fora da lista aceita.
- `502` — brapi indisponível (esta rota não tem fallback no Yahoo Finance).

---

## Criptomoedas

Fonte principal: CoinGecko; complemento sem cadastro: Binance (`App\Services\Finance\CriptoService`).

### `GET /api/cripto/precos`

Preço atual de uma ou mais moedas (ids da CoinGecko, ex: `bitcoin`, `ethereum`).

**Query params**

| Param | Tipo | Obrigatório | Descrição |
|---|---|---|---|
| `moedas` | string | sim | Lista separada por vírgula, ex: `bitcoin,ethereum` |
| `vs` | string | não | Moeda de cotação. Padrão: `brl` |

```
GET /api/cripto/precos?moedas=bitcoin,ethereum&vs=brl
```

**Resposta 200**
```json
{
  "bitcoin": { "brl": 350000 },
  "ethereum": { "brl": 18000 }
}
```

**Erros**
- `422` — `moedas` ausente.
- `502` — CoinGecko indisponível.

### `GET /api/cripto/{moeda}/historico`

Histórico de preços de uma moeda (id da CoinGecko).

**Query params**

| Param | Tipo | Padrão |
|---|---|---|
| `dias` | int (1–365) | `30` |
| `vs` | string | `brl` |

```
GET /api/cripto/bitcoin/historico?dias=90&vs=brl
```

**Resposta 200** — formato `market_chart` da CoinGecko (`prices`, `market_caps`, `total_volumes`).

**Erros**
- `422` — `dias` fora do intervalo 1–365.
- `502` — CoinGecko indisponível.

### `GET /api/cripto/binance/{par}`

Preço em tempo real de um par específico na Binance, sem necessidade de chave (ex: `BTCBRL`, `ETHBRL`).

```
GET /api/cripto/binance/BTCBRL
```

**Resposta 200**
```json
{ "symbol": "BTCBRL", "price": "350000.00000000" }
```

**Erros**
- `502` — par inexistente na Binance ou API indisponível.

---

## Variáveis de ambiente

| Variável | Obrigatória | Efeito |
|---|---|---|
| `BRAPI_TOKEN` | não | Libera tickers além dos gratuitos (`PETR4`, `VALE3`, `MGLU3`, `ITUB4`) nas rotas de ações/FIIs. |
| `COINGECKO_API_KEY` | não | Eleva o limite de requisições à CoinGecko de ~30 para 100 req/min (10.000/mês). |

Sem nenhuma das duas, todas as rotas funcionam dentro dos limites gratuitos descritos em `docs/apis-financas.md`.
