#!/bin/bash
# Uso: ./deploy.sh
# Executa no VPS após "git pull" para aplicar atualizações de código.
set -e

echo "[1/5] Atualizando código..."
git pull

echo "[2/5] Buildando frontend..."
cd frontend && npm ci && npm run build && cd ..

echo "[3/5] Instalando dependências PHP..."
docker compose -f docker-compose.prod.yml exec app composer install --no-dev --optimize-autoloader

echo "[4/5] Rodando migrations e caches..."
docker compose -f docker-compose.prod.yml exec app php artisan migrate --force
docker compose -f docker-compose.prod.yml exec app php artisan config:cache
docker compose -f docker-compose.prod.yml exec app php artisan route:cache

echo "[5/5] Reiniciando worker e nginx..."
docker compose -f docker-compose.prod.yml restart worker nginx

echo "Deploy concluído."
