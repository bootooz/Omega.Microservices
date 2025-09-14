/*
  WebSocket Bridge for skiwatch.ru
  - ESP32 подключается через ws://skiwatch.ru:9001
  - Ваш Vue3/SPA фронтенд через wss://skiwatch.ru/wsbridge/
  (nginx проксирует /wsbridge/ -> 9443)
*/

const WebSocket = require('ws');
const https = require('https');
const fs = require('fs');

// Читаем пути к SSL из env (см. docker-compose.yml)
const SSL_CERT = process.env.SSL_CERT || "/certs/live/skiwatch.ru/fullchain.pem";
const SSL_KEY = process.env.SSL_KEY || "/certs/live/skiwatch.ru/privkey.pem";
const PORT_WSS = process.env.PORT || 9443;
const PORT_WS = 9001; // стандартный локальный порт для ESP32

// WSS сервер для фронта (через SSL)
const server = https.createServer({
    cert: fs.readFileSync(SSL_CERT),
    key: fs.readFileSync(SSL_KEY)
});
const wss = new WebSocket.Server({ server });
server.listen(PORT_WSS, () => {
    console.log('Frontend WSS (secure WS) on port', PORT_WSS);
});

// WS сервер для esp32
const wsEsp32 = new WebSocket.Server({ port: PORT_WS }, () => {
    console.log('ESP32 WS (plain WS) on port', PORT_WS);
});

// Список подключённых фронтендов
let frontends = new Set();

// Пришёл новый фронт-клиент
wss.on('connection', ws => {
    console.log('Frontend connected');
    frontends.add(ws);
    ws.on('close', () => {
        frontends.delete(ws);
        console.log('Frontend disconnected');
    });
});

// Когда esp32 подключилась
wsEsp32.on('connection', esp32 => {
    console.log('ESP32 connected');

    // Все сообщения от esp32 — рассылаем фронтам
    esp32.on('message', msg => {
        console.log('Message from ESP32:', msg.toString());

        frontends.forEach(ws => {
            if (ws.readyState === WebSocket.OPEN) {
                ws.send(msg);
            }
        });
    });

    esp32.on('close', () => {
        console.log('ESP32 disconnected');
    });
});

// (по желанию) Можете реализовать приём сообщений с фронта и ответ на esp32, если нужно!