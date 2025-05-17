FROM ubuntu:22.04 AS php_base

ARG WWWGROUP

WORKDIR /var/www/html

ENV DEBIAN_FRONTEND noninteractive
ENV TZ=UTC

RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

RUN apt-get update \
    && mkdir -p /etc/apt/keyrings \
    && apt-get install -y gnupg gosu curl ca-certificates git zip unzip libcap2-bin librsvg2-bin fswatch software-properties-common libcap-dev \
    && curl -sS 'https://keyserver.ubuntu.com/pks/lookup?op=get&search=0x14aa40ec0831756756d7f66c4f4ea0aae5267a6c' | gpg --dearmor | tee /etc/apt/keyrings/ppa_ondrej_php.gpg > /dev/null \
    && echo "deb [signed-by=/etc/apt/keyrings/ppa_ondrej_php.gpg] https://ppa.launchpadcontent.net/ondrej/php/ubuntu jammy main" > /etc/apt/sources.list.d/ppa_ondrej_php.list

RUN add-apt-repository ppa:deadsnakes/ppa 

RUN apt-get update \
    && apt-get install -y php8.2-cli php8.2-dev \
    php8.2-gd \
    php8.2-curl \
    php8.2-imap php8.2-mysql php8.2-mbstring \
    php8.2-zip \
    php8.2-intl php8.2-readline php8.2-fpm \
    php8.2-ldap \
    php8.2-xml \
    php8.2-redis php8.2-swoole \
    && curl -sLS https://getcomposer.org/installer | php -- --install-dir=/usr/bin/ --filename=composer


RUN setcap "cap_net_bind_service=+ep" /usr/bin/php8.2

RUN apt-get install -y dos2unix mariadb-client python3.11 python3-pip

RUN groupadd --force -g $WWWGROUP sail
RUN useradd -ms /bin/bash --no-user-group -g $WWWGROUP -u 1337 sail

RUN git config --global --add safe.directory /var/www/html

FROM php_base AS php_supervisor

RUN apt-get install -y supervisor \
    && apt-get -y autoremove \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

RUN pip install cloudscraper

COPY docker/start-container /usr/local/bin/start-container
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/php.ini /etc/php/8.2/cli/conf.d/99-sail.ini
RUN mkdir /var/scripts
RUN chmod 0777 /var/scripts
COPY docker/scripts /var/scripts


RUN dos2unix /usr/local/bin/start-container
RUN chmod +x /usr/local/bin/start-container

ENTRYPOINT ["start-container"]


FROM php_base AS php_schedule 

#Copy Starter
COPY ./docker/start-container-scheduler /usr/local/bin/start-container

# Set permissions in folders
RUN dos2unix /usr/local/bin/start-container
RUN chmod +x /usr/local/bin/start-container

ENTRYPOINT [ "start-container" ]

FROM php_base AS php_worker

RUN apt-get install -y \
    nano time wget \
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
    poppler-utils \
    python3.13 \
    && apt-get -y autoremove \
    && apt-get clean 

RUN mkdir /langs

# Copy Scripts
RUN mkdir /var/scripts
RUN chmod 0777 /var/scripts
COPY ./docker/scripts /var/scripts


# Download and install pypy
RUN wget https://buildbot.pypy.org/nightly/py3.10/pypy-c-jit-186045-8708da3ee078-linux64.tar.bz2 -P /langs
RUN tar -xf /langs/pypy-c-jit-186045-8708da3ee078-linux64.tar.bz2 -C /langs
RUN mv /langs/pypy-c-jit-186045-8708da3ee078-linux64 /langs/pypy3.10
RUN rm /langs/pypy-c-jit-186045-8708da3ee078-linux64.tar.bz2
RUN rm /langs/pypy3.10/bin/python* && \
    cp /langs/pypy3.10/bin/* /usr/bin -r && \
    cp /langs/pypy3.10/lib/* /usr/lib -r && \
    cp /langs/pypy3.10/include/* /usr/include -r

# Download and install pypy 3.11
RUN wget https://buildbot.pypy.org/nightly/py3.11/pypy-c-jit-186687-ff94ca720f36-linux64.tar.bz2 -P /langs
RUN tar -xf /langs/pypy-c-jit-186687-ff94ca720f36-linux64.tar.bz2 -C /langs && \
    mv /langs/pypy-c-jit-186687-ff94ca720f36-linux64 /langs/pypy3.11 && \
    rm /langs/pypy-c-jit-186687-ff94ca720f36-linux64.tar.bz2 && \
    rm /langs/pypy3.11/bin/python* && \
    cp /langs/pypy3.11/bin/* /usr/bin -r && \
    cp /langs/pypy3.11/lib/* /usr/lib -r && \
    cp /langs/pypy3.11/include/* /usr/include -r


# Download and install NSJail
RUN git clone https://github.com/google/nsjail.git  /nsjail
RUN cd /nsjail && make && mv /nsjail/nsjail /bin && rm -rf -- /nsjail

RUN rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# Copy nsjail confign
RUN mkdir /var/config
RUN chmod 0777 /var/config


# RUN git clone https://github.com/ioi/isolate.git /isolate
# RUN cd /isolate && make isolate && mv /isolate/isolate /bin && rm -rf /isolate
# COPY ./docker/config/isolate.conf /usr/local/etc/isolate

COPY ./docker/config/ /var/config
RUN chmod +x /var/config/*.sh

ENTRYPOINT ["php", "/var/www/html/artisan", "queue:work", "--queue=high,contest,submit,rank,low","--sleep=2","--tries=2","--backoff=15,30,60","--max-jobs=300"]

