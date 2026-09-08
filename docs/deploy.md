# Deploy e ambiente de homologação

Guia de referência para os dois ambientes do Midas no servidor (Apache + MySQL no host, sem Docker):

| Item | Produção | Homologação |
|---|---|---|
| Domínio | `midas.enzoradel.com.br` | `midas-homolog.enzoradel.com.br` |
| Diretório | `/var/www/midas` | `/var/www/midas-homolog` |
| Branch | `main` | qualquer uma, escolhida no disparo |
| Banco | `midas` | `midas_homolog` |
| Usuário MySQL | restrito ao schema de produção | restrito **apenas** a `midas_homolog` |
| `APP_ENV` | `production` | `staging` |
| E-mail | mailer real | `MAIL_MAILER=log` |
| Acesso | público | HTTP Basic + `noindex` |

O deploy dos dois ambientes é feito pelo mesmo script (`deploy/midas-deploy.sh`), disparado por dois workflows do GitHub Actions (`deploy-homolog.yml` e `deploy-prod.yml`) via `workflow_dispatch` — ou seja, sem precisar de terminal, inclusive pelo celular.

## 1. Configuração no servidor (feita uma vez)

### 1.1 Usuário de deploy

Crie um usuário dedicado, sem `sudo`, dono apenas dos dois diretórios da aplicação e membro do grupo do Apache (para poder ajustar permissões de `storage/` e `bootstrap/cache/`):

```bash
sudo useradd -m -s /bin/bash deploy
sudo usermod -aG www-data deploy
sudo mkdir -p /var/www/midas /var/www/midas-homolog
sudo chown -R deploy:deploy /var/www/midas /var/www/midas-homolog
```

### 1.2 Chave SSH do GitHub Actions

Gere um par de chaves dedicado ao deploy (não reaproveite sua chave pessoal):

```bash
ssh-keygen -t ed25519 -f ~/midas-deploy-key -C "github-actions-midas" -N ""
```

Autorize a chave **pública** para o usuário `deploy`:

```bash
sudo -u deploy mkdir -p /home/deploy/.ssh
sudo -u deploy tee -a /home/deploy/.ssh/authorized_keys < ~/midas-deploy-key.pub
sudo chmod 700 /home/deploy/.ssh
sudo chmod 600 /home/deploy/.ssh/authorized_keys
```

A chave **privada** (`~/midas-deploy-key`) vai para o secret `SSH_PRIVATE_KEY` no GitHub (passo 2).

### 1.3 Banco de dados

```sql
CREATE DATABASE midas_homolog CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'midas_homolog'@'localhost' IDENTIFIED BY 'senha-forte-aqui';
GRANT ALL PRIVILEGES ON midas_homolog.* TO 'midas_homolog'@'localhost';
FLUSH PRIVILEGES;
```

Aproveite para checar se a produção já usa um usuário restrito ao próprio schema (não `root`). Se não usa, vale criar um `midas`@`localhost` com `GRANT` só em `midas.*` — mesmo princípio, mesmo benefício: um `.env` errado falha a conexão em vez de alcançar o banco errado.

### 1.4 Clonar os repositórios

```bash
sudo -u deploy git clone https://github.com/Enzo-Radel/midas.git /var/www/midas
sudo -u deploy git clone https://github.com/Enzo-Radel/midas.git /var/www/midas-homolog
```

Em cada diretório, configure o `.env`:

```bash
cd /var/www/midas-homolog
sudo -u deploy cp .env.example .env
sudo -u deploy php artisan key:generate
```

Ajuste no `.env` de homologação (produção segue o padrão normal do projeto):

```
APP_ENV=staging
APP_URL=https://midas-homolog.enzoradel.com.br
DB_DATABASE=midas_homolog
DB_USERNAME=midas_homolog
DB_PASSWORD=senha-forte-aqui
MAIL_MAILER=log
```

Se for usar a API de ações/cripto (`docs/apis-financas.md`) em homologação, considere um `BRAPI_TOKEN` de uma conta separada da produção — a cota gratuita (15k req/mês) é por conta, não por token.

Permissões graváveis pelo Apache sem abrir mão do dono ser o `deploy`:

```bash
sudo chgrp -R www-data storage bootstrap/cache
sudo chmod -R 2775 storage bootstrap/cache
```

### 1.5 Instalar o script de deploy

```bash
sudo cp /var/www/midas-homolog/deploy/midas-deploy.sh /usr/local/bin/midas-deploy.sh
sudo chmod +x /usr/local/bin/midas-deploy.sh
```

Teste manualmente antes de depender do CI:

```bash
sudo -u deploy midas-deploy.sh homolog main
```

### 1.6 Vhosts e TLS

```bash
sudo a2enmod ssl headers rewrite
sudo cp /var/www/midas-homolog/deploy/apache/midas-homolog.conf /etc/apache2/sites-available/
sudo htpasswd -c /etc/apache2/.htpasswd-midas-homolog seu-usuario
sudo a2ensite midas-homolog
sudo certbot --apache -d midas-homolog.enzoradel.com.br
sudo systemctl reload apache2
```

O vhost de produção (`deploy/apache/midas.conf`) segue o mesmo padrão, sem a Basic Auth e sem o header `noindex`.

## 2. Configuração no GitHub (feita uma vez)

Em **Settings → Secrets and variables → Actions**, cadastre:

| Secret | Valor |
|---|---|
| `SSH_HOST` | endereço do servidor |
| `SSH_USER` | `deploy` |
| `SSH_PRIVATE_KEY` | conteúdo de `~/midas-deploy-key` (a chave privada) |
| `SSH_PORT` | porta do SSH (só se não for a 22) |

Em **Settings → Environments**, crie `homolog` e `production`. Em `production`, considere marcar **Required reviewers** — assim o workflow de produção fica esperando uma aprovação manual antes de rodar, mesmo já tendo sido disparado.

## 3. Uso do dia a dia

Três formas de disparar, todas sem terminal:

1. **Pelo GitHub** — aba *Actions* → `Deploy homologação` → *Run workflow* → escolhe a branch.
2. **Pedindo aqui** — "sobe a branch X pra homolog": a sessão do Claude Code dispara o workflow e acompanha o resultado.
3. **Produção** — aba *Actions* → `Deploy produção` → *Run workflow*, digitando `deploy` no campo de confirmação. Sempre a partir da `main`, depois do merge.

Para zerar os dados de homologação (ex: depois de testar uma migration destrutiva), marque `fresh_db` ao disparar o workflow de homologação — nunca disponível em produção, o script recusa.

## 4. Pontos de atenção

- **Migrations destrutivas** aparecem primeiro em homologação — é o propósito do ambiente.
- **`APP_KEY`** deve ser gerada uma vez por ambiente e nunca versionada.
- **Cota da brapi** é por conta (15k req/mês): não reaproveite o token de produção em homologação.
- **Dados de homologação são descartáveis** — nunca copie um dump da produção para lá.
