FROM node:22-alpine
WORKDIR /websocket
# Копируем spa-server.js
COPY spa-server.js .
# Ставим только express и ws (или только ws, если express не нужен)
RUN npm init -y && npm install express ws
#EXPOSE 7771
CMD ["node", "spa-server.js"]