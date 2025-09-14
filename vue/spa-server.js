import express from 'express';
import https from 'https';
import http from 'http';
import fs from 'fs';
import { WebSocketServer } from 'ws';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const app = express();
const dist = path.join(__dirname, 'dist');
app.use(express.static(dist));
app.use((req, res) => {
  res.sendFile(path.join(dist, 'index.html'));
});

// HTTPS сервер для клиентов (SPA, WSS)
const httpsOptions = {
  key: fs.readFileSync('C:/192.168.1.2-key.pem'),
  cert: fs.readFileSync('C:/192.168.1.2.pem')
};
const httpsServer = https.createServer(httpsOptions, app);
// HTTP сервер для ws (ESP32)
const httpServer = http.createServer();

// Запуск HTTPS SPA сервера и WS-сервера без шифрования
httpsServer.listen(8080, '0.0.0.0', () => {
  console.log('SPA + WSS сервер на https://192.168.1.2:8080');
});
httpServer.listen(8081, '0.0.0.0', () => {
  console.log('Сервер для ESP32 на ws://192.168.1.2:8081');
});

// WSS: для клиентов
const wss = new WebSocketServer({ server: httpsServer });
// WS: для esp32
const wsEsp = new WebSocketServer({ server: httpServer });

let latestData = null;

// Когда esp32 присылает данные - рассылаем WSS-клиентам
wsEsp.on('connection', (ws) => {
  ws.on('message', (message) => {
    latestData = message.toString();
    // отправить всем WSS-клиентам
    wss.clients.forEach(client => {
      if (client.readyState === client.OPEN) {
        client.send(latestData);
      }
    });
  });
});

// wss для браузеров: при подключении, присылаем актуальные данные
wss.on('connection', (client) => {
  if (latestData) {
    client.send(latestData);
  }
  // можно добавить дополнительные обработчики сообщений от браузеров
});
