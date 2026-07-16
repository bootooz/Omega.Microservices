#!/bin/bash
set -e

## 1. Создание docker-сети
docker network create zalupa || echo "docker network exists"

## 2. Запуск Docker Compose сервисов
docker compose -f ./frontend/docker-compose.yml up -d
docker compose -f ./ntrip/docker-compose.yml up -d

chmod 600 ./traefik/acme.json || true
docker compose -f ./traefik/docker-compose.yml up -d
docker compose -f ./websocket/docker-compose.yml up -d

echo "=== Готово! Все контейнеры запущены. websocat установлен! ==="
echo "=== Не забудьте прописать домен в hosts роутера ==="