const http = require('http');
const WebSocket = require('ws');

const server = http.createServer();
const wss = new WebSocket.Server({ server });

const devices = new Set();
const frontends = new Set();

wss.on('connection', (ws, req) => {
  // Слабое определение: фронт или устройство (лучше использовать query param)
  let isFrontend = false;
  let isRover = false;
  if (
      req.headers['sec-websocket-protocol'] === 'frontend' ||
      (req.url && req.url.includes('frontend'))
  ) {
    isFrontend = true;
  }

  if (
      req.headers['sec-websocket-protocol'] === 'rover' ||
      (req.url && req.url.includes('rover'))
  ) {
    isRover = true;
  }

  if (isFrontend) {
    frontends.add(ws);
    console.log('Подключился фронтенд');
  } else {
    isRover ? console.log('Подключился ровер') : console.log('Подключено устройство');
    devices.add(ws);
  }

  ws.on('message', (message) => {
    if (isFrontend) {
      for (const dev of devices) {
        if (dev.readyState === WebSocket.OPEN) {
          console.log('dev.send message: ' + message);
          dev.send(message);
        }
      }
    } else {
      for (const fe of frontends) {
        if (fe.readyState === WebSocket.OPEN) {
          console.log('fe.send message: ' + message);
          fe.send(message);
        }
      }
    }
  });

  ws.on('close', () => {
    if (isFrontend) {
      frontends.delete(ws);
      console.log('Фронтенд отключён');
    } else {
      devices.delete(ws);
      isRover ? console.log('Ровер отключен') : console.log('Устройство отключено');
    }
  });
});

server.listen(7771, () => {
  console.log('WebSocket server (bridge) на порту 7771');
});