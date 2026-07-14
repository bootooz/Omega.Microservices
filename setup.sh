#!/bin/bash
set -e

## 1. Обновление системы
apt-get update && apt-get upgrade -y

## 2. Настройка SSH
echo "Configuring SSH..."
sed -i 's/^#\?PubkeyAuthentication.*/PubkeyAuthentication yes/' /etc/ssh/sshd_config
sed -i 's/^#\?PermitRootLogin.*/PermitRootLogin prohibit-password/' /etc/ssh/sshd_config
systemctl reload sshd

## 3. Установка git, curl, sudo, wget и nano
apt-get install -y git curl sudo wget nano

## 4. Установка nvm и nodejs (через nvm)
export NVM_DIR="$HOME/.nvm"
if [ ! -d "$NVM_DIR" ]; then
  curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.7/install.sh | bash
fi
export NVM_DIR="$HOME/.nvm"
source "$NVM_DIR/nvm.sh"
nvm install 22
nvm use 22

## 5. Клонирование и подготовка репозитория
mkdir -p /var/www/skiwatch.ru
cd /var/www/skiwatch.ru

if [ ! -d ".git" ]; then
  git init
  git remote add origin https://github.com/bootooz/Omega.Microservices.git
  git pull origin master
else
  git pull origin master
fi

## 6. Сборка frontend
cd frontend
npm install
npm run build
cd ..

## 7. Установка Docker и Docker Compose
if ! command -v docker &> /dev/null; then
  echo "Installing Docker..."
  apt-get remove -y docker docker-engine docker.io containerd runc || true
  apt-get install -y ca-certificates curl gnupg lsb-release
  mkdir -p /etc/apt/keyrings
  curl -fsSL https://download.docker.com/linux/ubuntu/gpg | gpg --dearmor -o /etc/apt/keyrings/docker.gpg
  echo \
    "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu \
    $(lsb_release -cs) stable" | tee /etc/apt/sources.list.d/docker.list > /dev/null
  apt-get update
  apt-get install -y docker-ce docker-ce-cli containerd.io docker-compose-plugin
fi

if ! command -v docker-compose &> /dev/null; then
  echo "Installing Docker Compose..."
  curl -L "https://github.com/docker/compose/releases/download/v2.27.0/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
  chmod +x /usr/local/bin/docker-compose
fi

## 8. Установка websocat
if ! command -v websocat &> /dev/null; then
  echo "Installing websocat..."
  wget https://github.com/vi/websocat/releases/download/v4.0.0-alpha3/websocat.x86_64-unknown-linux-musl -O /usr/local/bin/websocat
  chmod +x /usr/local/bin/websocat
  if ! websocat --version; then
    echo "Websocat installation failed!"
    exit 1
  fi
else
  echo "websocat already installed: $(websocat --version)"
fi

## 9. Создание docker-сети
docker network create zalupa || echo "docker network exists"

## 10. Запуск Docker Compose сервисов
cd /var/www/skiwatch.ru

docker compose -f ./frontend/docker-compose.yml up -d
docker compose -f ./ntrip/docker-compose.yml up -d

chmod 600 ./traefik/acme.json || true
docker compose -f ./traefik/docker-compose.yml up -d
docker compose -f ./websocket/docker-compose.yml up -d

echo "=== Готово! Все контейнеры запущены. websocat установлен! ==="