FROM dunglas/frankenphp:1.4-php8.3 AS php_base

LABEL maintainer="Crazynds"

ENV DEBIAN_FRONTEND=noninteractive
ENV TZ=UTC-3
ENV NODE_VERSION=18

# Install node UBUNTU
RUN curl -fsSL https://deb.nodesource.com/setup_21.x | tap | bash && \
    apt-get update && \
    apt-get install -y tzdata dos2unix npm bash supervisor mariadb-client \
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
    python3.11 python3-pip && \ 
    install-php-extensions redis pdo_mysql pdo_pgsql mbstring exif pcntl curl zip intl sockets xml gd && \
    php -r "readfile('http://getcomposer.org/installer');" | php -- --install-dir=/usr/bin/ --filename=composer && \
    cp /usr/share/zoneinfo/America/Sao_Paulo /etc/localtime

RUN mkdir /langs

# Download and install python 
RUN wget https://www.python.org/ftp/python/3.13.3/Python-3.13.3.tar.xz -P /langs && \
    tar -xf /langs/Python-3.13.3.tar.xz -C /langs && \
    apt-get install -y build-essential libreadline-dev libncursesw5-dev libssl-dev libsqlite3-dev tk-dev libgdbm-dev libc6-dev libbz2-dev libffi-dev zlib1g-dev && \
    mv /langs/Python-3.13.3 /langs/python3.13 && \
    rm /langs/Python-3.13.3.tar.xz && \
    cd /langs/python3.13 && \
    ./configure --enable-optimizations --without-ensurepip --disable-ipv6 && \
    make altinstall && \
    apt-get purge -y build-essential libreadline-dev libncursesw5-dev libssl-dev libsqlite3-dev tk-dev libgdbm-dev libc6-dev libbz2-dev libffi-dev zlib1g-dev

# Download and install pypy 3.10
RUN wget https://buildbot.pypy.org/nightly/py3.10/pypy-c-jit-186045-8708da3ee078-linux64.tar.bz2 -P /langs && \
    tar -xf /langs/pypy-c-jit-186045-8708da3ee078-linux64.tar.bz2 -C /langs && \
    mv /langs/pypy-c-jit-186045-8708da3ee078-linux64 /langs/pypy3.10 && \
    rm /langs/pypy-c-jit-186045-8708da3ee078-linux64.tar.bz2 && \
    rm /langs/pypy3.10/bin/python* && \
    cp /langs/pypy3.10/bin/* /usr/bin -r && \
    cp /langs/pypy3.10/lib/* /usr/lib -r && \
    cp /langs/pypy3.10/include/* /usr/include -r

# Download and install pypy 3.11
RUN wget https://buildbot.pypy.org/nightly/py3.11/pypy-c-jit-186687-ff94ca720f36-linux64.tar.bz2 -P /langs && \
    tar -xf /langs/pypy-c-jit-186687-ff94ca720f36-linux64.tar.bz2 -C /langs && \
    mv /langs/pypy-c-jit-186687-ff94ca720f36-linux64 /langs/pypy3.11 && \
    rm /langs/pypy-c-jit-186687-ff94ca720f36-linux64.tar.bz2 && \
    rm /langs/pypy3.11/bin/python* && \
    cp /langs/pypy3.11/bin/* /usr/bin -r && \
    cp /langs/pypy3.11/lib/* /usr/lib -r && \
    cp /langs/pypy3.11/include/* /usr/include -r

# Download and install NSJail
RUN git clone https://github.com/google/nsjail.git  /nsjail
RUN cd /nsjail && make && mv /nsjail/nsjail /bin && rm -rf -- /nsjail

# Copy nsjail config
RUN mkdir /var/config && chmod 0777 /var/config
COPY ./docker/config/ /var/config/
RUN chmod +x /var/config/*.sh

# Copy Scripts
RUN mkdir /var/scripts && chmod 0777 /var/scripts
COPY ./docker/scripts /var/scripts


# Copy code to /app
RUN mkdir -p /app
RUN mkdir /.npm
RUN chmod 0777 /.npm
RUN mkdir /.composer
RUN chmod 0777 /.composer
RUN chmod 0777 /root
COPY composer.json /app/
COPY package.json /app/
COPY package-lock.json /app/

# Setup the libraries
RUN composer install --no-interaction --no-dev --optimize-autoloader --no-scripts
RUN npm install

# Copy php config
COPY ./docker/prod/php.ini /usr/local/etc/php/conf.d/local.ini

# Copy supervisord config
COPY docker/prod/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Copy startup
COPY ./docker/prod/start-container /usr/local/bin/start-container
RUN dos2unix /usr/local/bin/start-container && chmod 0777 /usr/local/bin/start-container && chmod +x /usr/local/bin/start-container

# Copy src code
COPY . /app/
RUN rm -rf /app/{.devcontainer,docker}/

# Build frontend
RUN npm run build

# Clear apt and tmp files
RUN apt-get clean autoclean && \
    apt-get autoremove --yes && \
    rm -rf /var/lib/{apt,dpkg,cache,log}/ && \
    rm -rf /app/{client,node_modules,stubs}/ 

ENTRYPOINT ["start-container"]