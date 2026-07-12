# Тестирование соединений

## Установить на хосте:
- `wget https://github.com/vi/websocat/releases/download/v4.0.0-alpha3/websocat.x86_64-unknown-linux-musl -O /usr/local/bin/websocat`
- `chmod +x /usr/local/bin/websocat` - сделать файл исполняемым
- `websocat --version` - если все ок - выдаст версию

## Проверка WebSocket
- `websocat -H="Sec-WebSocket-Protocol: frontend" wss://ws.skiwatch.ru/`
- Если соединение установлено — увидите отсутствие ошибок (ожидание ввода)
- Попробуйте отправить текст — сообщение уйдет на сервер
- `docker logs websocket` по логам можно понять что успешно подсоединяемся "Подключился фронтенд"