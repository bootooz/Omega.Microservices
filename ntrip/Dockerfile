FROM ubuntu:22.04

RUN apt-get update && apt-get install -y wget build-essential bzip2 git

WORKDIR /var/www/skiwatch.ru/ntrip
# Скачиваем исходники кастера прямо при сборке контейнера
# Актуальная версия тут: https://igs.bkg.bund.de/root_ftp/NTRIP/software/caster/
# Инструкция: https://igs.bkg.bund.de/root_ftp/NTRIP/software/caster/ntripcaster_manual.html#c2
RUN wget https://igs.bkg.bund.de/root_ftp/NTRIP/software/caster/ntripcaster-2.0.47.tar.bz2 && \
    tar xjf ntripcaster-2.0.47.tar.bz2 --no-same-owner

WORKDIR /var/www/skiwatch.ru/ntrip/ntripcaster-2.0.47

RUN ./configure && make && make install

# Копируем свою конфигурацию если нужно (их структура в ntripcaster 2.x: *.conf, *.aut, *.dat)
COPY config/ntripcaster.conf /usr/local/ntripcaster/conf/ntripcaster.conf
COPY config/clientmounts.aut /usr/local/ntripcaster/conf/clientmounts.aut
COPY config/sourcemounts.aut /usr/local/ntripcaster/conf/sourcemounts.aut
COPY config/users.aut /usr/local/ntripcaster/conf/users.aut
COPY config/groups.aut /usr/local/ntripcaster/conf/groups.aut
COPY config/sourcetable.dat /usr/local/ntripcaster/conf/sourcetable.dat

RUN chmod -R 755 /usr/local/ntripcaster/conf/ && \
    chmod -R 644 /usr/local/ntripcaster/conf/*

#EXPOSE 2101

# Запускаем кастер в foreground-режиме (режим обычного консольного сервера)
CMD ["/usr/local/ntripcaster/bin/casterwatch"]