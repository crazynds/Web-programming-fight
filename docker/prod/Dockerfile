FROM dunglas/frankenphp:1.2-php8.3 AS php_base

LABEL maintainer="Crazynds"

ENV DEBIAN_FRONTEND noninteractive
ENV TZ=UTC-3
ENV NODE_VERSION=18

# Install necessary libs
#RUN apk add --no-cache tzdata dos2unix npm bash

# Install node UBUNTU
RUN curl -fsSL https://deb.nodesource.com/setup_21.x | tap | bash
RUN apt-get update && \
    apt-get install -y tzdata dos2unix npm bash supervisor \
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
    python3.11 python3-pip

RUN mkdir /langs

# Prepare AI environment
RUN pip install torch torchvision torchaudio --index-url https://download.pytorch.org/whl/cpu --break-system-packages
RUN pip install transformers optimum[onnxruntime] optimum --break-system-packages
RUN mkdir /var/scripts
RUN chmod 0777 /var/scripts
COPY ./docker/scripts /var/scripts
## Run one time to load the model
RUN python3 /var/scripts/autolang.py < /var/scripts/autolang.py

# Download and install pypy
RUN wget https://buildbot.pypy.org/nightly/py3.10/pypy-c-jit-184945-79a57c5ea141-linux64.tar.bz2 -P /langs
RUN tar -xf /langs/pypy-c-jit-184945-79a57c5ea141-linux64.tar.bz2 -C /langs && \
    mv /langs/pypy-c-jit-184945-79a57c5ea141-linux64 /langs/pypy3.10 && \
    rm /langs/pypy-c-jit-184945-79a57c5ea141-linux64.tar.bz2 && \
    rm /langs/pypy3.10/bin/python* && \
    cp /langs/pypy3.10/bin/* /usr/bin -r && \
    cp /langs/pypy3.10/lib/* /usr/lib -r && \
    cp /langs/pypy3.10/include/* /usr/include -r


# Download and install NSJail
RUN git clone https://github.com/google/nsjail.git  /nsjail
RUN cd /nsjail && make && mv /nsjail/nsjail /bin && rm -rf -- /nsjail

# Copy nsjail config
RUN mkdir /var/nsjail
RUN chmod 0777 /var/nsjail

COPY ./docker/config/ /var/nsjail/
RUN chmod +x /var/nsjail/runPypy3.10.sh
RUN chmod +x /var/nsjail/runPython3.11.sh
RUN chmod +x /var/nsjail/runBinary.sh


# Add php additional extensions 
RUN install-php-extensions redis pdo_mysql pdo_pgsql mbstring exif pcntl curl zip intl sockets xml gd

# Install Composer
RUN php -r "readfile('http://getcomposer.org/installer');" | php -- --install-dir=/usr/bin/ --filename=composer 

# Set timezone
#RUN ln -s /usr/share/zoneinfo/America/Sao_Paulo /etc/localtime
RUN cp /usr/share/zoneinfo/America/Sao_Paulo /etc/localtime

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
RUN dos2unix /usr/local/bin/start-container
RUN chmod 0777 /usr/local/bin/start-container
RUN chmod +x /usr/local/bin/start-container

# Copy src code
COPY . /app/
RUN rm -rf /app/{.devcontainer,.github,.vscode,doc,docker}/

# Build frontend
RUN npm run build

# Clear apt and tmp files
#RUN apk del --no-cache nodejs npm
#RUN rm -rf /var/cache/apk/*
RUN apt-get clean autoclean
RUN apt-get autoremove --yes
RUN rm -rf /var/lib/{apt,dpkg,cache,log}/

# Delete not used code
RUN rm -rf /app/{client,node_modules,stubs}/

ENTRYPOINT ["start-container"]