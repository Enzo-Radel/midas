#!/usr/bin/env bash
#
# Deploy do Midas em produção ou homologação.
# Instalado no servidor em /usr/local/bin/midas-deploy.sh (ver docs/deploy.md).
#
# Uso: midas-deploy.sh <prod|homolog> <branch> [--fresh]
#   --fresh  roda migrate:fresh --seed em vez de migrate (recusado em prod)

set -euo pipefail

ambiente="${1:-}"
branch="${2:-}"
fresh="${3:-}"

if [[ -z "$ambiente" || -z "$branch" ]]; then
  echo "uso: midas-deploy.sh <prod|homolog> <branch> [--fresh]" >&2
  exit 1
fi

case "$ambiente" in
  prod)    dir=/var/www/midas ;;
  homolog) dir=/var/www/midas-homolog ;;
  *)
    echo "ambiente inválido: $ambiente (use prod ou homolog)" >&2
    exit 1
    ;;
esac

if [[ ! -d "$dir/.git" ]]; then
  echo "repositório não encontrado em $dir (ver docs/deploy.md, passo de clone inicial)" >&2
  exit 1
fi

if [[ "$fresh" == "--fresh" && "$ambiente" == "prod" ]]; then
  echo "recusando --fresh em produção" >&2
  exit 1
fi

cd "$dir"

echo "==> [$ambiente] atualizando código para a branch $branch"
git fetch --prune origin
git checkout "$branch"
git reset --hard "origin/$branch"

echo "==> [$ambiente] instalando dependências PHP"
composer install --no-dev --optimize-autoloader --no-interaction

echo "==> [$ambiente] rodando migrations"
if [[ "$fresh" == "--fresh" ]]; then
  php artisan migrate:fresh --seed --force
else
  php artisan migrate --force
fi

echo "==> [$ambiente] recriando cache de configuração"
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> [$ambiente] deploy concluído: $branch"
