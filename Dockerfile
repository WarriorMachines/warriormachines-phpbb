# warriormachines/warriormachines-phpbb

FROM debian:jessie

MAINTAINER "Austin Maddox" <austin@maddoxbox.com>

WORKDIR /tmp

RUN apt-get update && apt-get install -y \
    curl \
    git \
    php5-cli \
    php5-curl \
    php5-mcrypt \
    php5-mysqlnd

RUN php5enmod mcrypt \
    && php5enmod curl \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && chmod +x /usr/local/bin/composer

# Cleanup
RUN apt-get clean \
    && apt-get autoremove -y \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# Copy the directory from the native host into the container. (Either from the local dev machine or GitHub, depending on where this container is built.)
COPY . /var/www/html/public/discuss

WORKDIR /var/www/html/public/discuss

RUN composer install --prefer-source --no-interaction --no-progress \
    && chown -R www-data:www-data . \
    && chmod -R 777 cache files images/avatars/upload store

VOLUME /var/www/html/public/discuss

CMD ["/bin/sh", "-c", "while true; do sleep 1; done"]
