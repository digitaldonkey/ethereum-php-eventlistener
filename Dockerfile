FROM php:7.2-cli

# Prepare basic deps
RUN apt-get update && apt-get install -y apt-utils wget curl build-essential libevent-dev libssl-dev libzmq3-dev

# Install PHP5.6
RUN pecl config-set preferred_state beta
RUN docker-php-ext-install sockets bcmath

# allow manipulation with ENV variables
RUN touch /usr/local/etc/php/php.ini

# Install PHP Libevent
ENV EVENT_VERSION 2.4.0
# export EVENT_VERSION=2.4.0
RUN cd /usr/local/src && \
    wget https://pecl.php.net/get/event-$EVENT_VERSION.tgz && tar -xvzf event-$EVENT_VERSION.tgz && rm event-$EVENT_VERSION.tgz && \
    cd event-$EVENT_VERSION && /usr/local/bin/phpize && ./configure && make && make install && \
    printf "\n" | pecl install event && echo "extension=event.so" > /usr/local/etc/php/conf.d/event.ini

## Install ZeroMQ
RUN pecl install zmq-beta && echo "extension=zmq.so" > /usr/local/etc/php/conf.d/zeromq.ini

##Install PHP Zlib
ENV ZLIB_VERSION 1.2.11
RUN cd /usr/local/src && wget http://zlib.net/zlib-$ZLIB_VERSION.tar.gz && tar -xvzf zlib-$ZLIB_VERSION.tar.gz && rm *.gz && cd zlib-$ZLIB_VERSION && ./configure && make && make install && docker-php-ext-install zip

RUN curl -sS http://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

ADD docker-run.sh /opt/local/bin/docker-run.sh

