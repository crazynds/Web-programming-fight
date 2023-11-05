FROM ubuntu:22.04 as php_base

ARG WWWGROUP

WORKDIR /var/www/html

ENV DEBIAN_FRONTEND noninteractive
ENV TZ=UTC

RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

RUN apt-get update \
    && mkdir -p /etc/apt/keyrings \
    && apt-get install -y gnupg gosu curl ca-certificates git zip unzip libcap2-bin librsvg2-bin fswatch \
    && curl -sS 'https://keyserver.ubuntu.com/pks/lookup?op=get&search=0x14aa40ec0831756756d7f66c4f4ea0aae5267a6c' | gpg --dearmor | tee /etc/apt/keyrings/ppa_ondrej_php.gpg > /dev/null \
    && echo "deb [signed-by=/etc/apt/keyrings/ppa_ondrej_php.gpg] https://ppa.launchpadcontent.net/ondrej/php/ubuntu jammy main" > /etc/apt/sources.list.d/ppa_ondrej_php.list

RUN apt-get update \
    && apt-get install -y php8.2-cli php8.2-dev \
       php8.2-gd \
       php8.2-curl \
       php8.2-imap php8.2-mysql php8.2-mbstring \
       php8.2-zip \
       php8.2-intl php8.2-readline \
       php8.2-ldap \
       php8.2-redis php8.2-swoole \
    && curl -sLS https://getcomposer.org/installer | php -- --install-dir=/usr/bin/ --filename=composer

RUN setcap "cap_net_bind_service=+ep" /usr/bin/php8.2

RUN groupadd --force -g $WWWGROUP sail
RUN useradd -ms /bin/bash --no-user-group -g $WWWGROUP -u 1337 sail

RUN git config --global --add safe.directory /var/www/html

FROM php_base AS php_supervisor

RUN apt-get install -y supervisor \
    && apt-get -y autoremove \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

COPY start-container /usr/local/bin/start-container
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY php.ini /etc/php/8.2/cli/conf.d/99-sail.ini
RUN chmod +x /usr/local/bin/start-container

ENTRYPOINT ["start-container"]


FROM php_base AS php_worker

RUN apt-get install -y \
    nano time \
    autoconf \
    bison \
    flex \
    gcc \
    g++ \
    libprotobuf-dev \
    libnl-route-3-dev \
    libtool \
    make \
    pkg-config \
    protobuf-compiler \
    && apt-get -y autoremove \
    && apt-get clean 

RUN git clone https://github.com/google/nsjail.git  /nsjail

RUN cd /nsjail && make && mv /nsjail/nsjail /bin && rm -rf -- /nsjail

RUN rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

RUN mkdir /var/nsjail
RUN chmod 0777 /var/nsjail
COPY basic.conf /var/nsjail/basic.conf

COPY start-container-worker /usr/local/bin/start-container
RUN chmod +x /usr/local/bin/start-container


ENTRYPOINT ["start-container"]
