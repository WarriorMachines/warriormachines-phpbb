# warriormachines/warriormachines-phpbb

FROM debian:jessie

MAINTAINER "Austin Maddox" <austin@maddoxbox.com>

# Copy the directory from the native host into the container. (Either from the local dev machine or GitHub, depending on where this container is built.)
COPY . /var/www/html/public/discuss

WORKDIR /var/www/html/public/discuss

RUN chown -R www-data:www-data . \
    && chmod -R 777 cache files images/avatars/upload store

VOLUME /var/www/html/public/discuss

CMD ["/bin/sh", "-c", "while true; do sleep 1; done"]
