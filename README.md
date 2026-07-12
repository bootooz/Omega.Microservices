# Установка

1. Создаем сервер-приложение "docker"
2. Переходим в консоль сервера через веб-интерфейс
3. `passwd root` задаем пароль. Выходим из веб-консоли.
4. Подключаемся по ssh через IDE
    - `nano /etc/ssh/sshd_config` раскомментировать `PubkeyAuthentication yes` - разрешаем входить по ssh-ключам
    - `PermitRootLogin: prohibit-password` - запрещаем доступ по паролю для root
    - `systemctl restart sshd`
5. Проверяем подключение по ключу.
6. `apt-get update` и `apt-get upgrade`
7. Создаем каталог: `mkdir -p /var/www/skiwatch.ru && cd /var/www/skiwatch.ru`
8. Репозиторий:
   - `git init`
   - `git remote add origin https://github.com/bootooz/Omega.Microservices.git`
   - `git pull origin master`
Инструменты для сборки:
   - nvm
     - `curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.7/install.sh | bash`
     - `source ~/.nvm/nvm.sh`
   - node js
     - `nvm install 22`
     - `nvm use 22`
   - npm:
      - `apt install npm`
   - vite:
     - `npm install --save-dev vite`
     - `npm install @vitejs/plugin-vue --save-dev`
9. Сборка:
   - frontend
     - `cd ./frontend` 
     - `npm run build`
10. Проверяем наличие докера:
    - `docker`
    - `docker-compose --version` 
11. Создаем сеть docker: `docker network create zalupa`
12. `docker-compose -v` - если не установлен docker-compose выполнить следующее:
    - `sudo curl -L "https://github.com/docker/compose/releases/download/v2.27.0/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose`
    - `sudo chmod +x /usr/local/bin/docker-compose`
13. Запускаем контейнеры:
    - `docker-compose -f ./frontend/docker-compose.yml up -d`
    - `docker-compose -f ./ntrip/docker-compose.yml up -d`
    - `chmod 600 ./traefik/acme.json` + `docker-compose -f ./traefik/docker-compose.yml up -d`
    - `docker-compose -f ./websocket/docker-compose.yml up -d`
10. Возможные ошибки при запуске docker-compose:
    - /frontend/: если не собран фронт - будет ошибка об отсутствующей папке dist
    - /ntrip/: ERROR [ntrip  4/13] RUN wget https://igs.bkg.bund.de/root_ftp/NTRIP/software/caster/ntripcaster-2.0.47.tar.bz2 && tar xjf ntripcaster-2.0.47.tar.bz2 --no-same-owner (это ошибка версии. Нужно проверить доступность файла. Поменять версию)