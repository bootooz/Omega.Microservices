# Используем официальный Node-образ
FROM nginx:1.25-alpine
# Устанавливаем рабочую директорию внутри контейнера
WORKDIR /frontend
# Копируем всё остальное приложение внутрь контейнера
COPY dist .
# Копируем nginx конфиг
COPY nginx/frontend.conf /etc/nginx/conf.d/default.conf
# Открываем порт (по умолчанию 8080)
#EXPOSE 9876
# Запускаем режим разработки
CMD ["nginx", "-g", "daemon off;"]