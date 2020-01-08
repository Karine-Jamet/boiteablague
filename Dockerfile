FROM composer
FROM php:7.3-fpm-alpine

ENV WORKPATH "/var/www/app"

RUN apk add --no-cache $PHPIZE_DEPS \
    libzip-dev icu-dev libxml2-dev freetype-dev libpng-dev libjpeg-turbo-dev g++ make autoconf \
	&& pecl install apcu mongodb xdebug \
    && docker-php-ext-install pdo_mysql opcache json mysqli intl zip gd \
	&& docker-php-ext-enable apcu mysqli mongodb.so xdebug

RUN apk add --no-cache --repository http://dl-cdn.alpinelinux.org/alpine/edge/community/ --allow-untrusted gnu-libiconv
ENV LD_PRELOAD /usr/lib/preloadable_libiconv.so php

COPY docker/php/conf/php.ini /usr/local/etc/php/php.ini
COPY docker/php/conf/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

# COPY conf/production/php.ini /usr/local/etc/php/php.ini -> Only for production usage.

# Composer
ENV COMPOSER_ALLOW_SUPERUSER 1
COPY --from=0 /usr/bin/composer /usr/bin/composer

# Blackfire (Docker approach) & Blackfire Player
#RUN version=$(php -r "echo PHP_MAJOR_VERSION.PHP_MINOR_VERSION;") \
#    && curl -A "Docker" -o /tmp/blackfire-probe.tar.gz -D - -L -s https://blackfire.io/api/v1/releases/probe/php/alpine/amd64/$version \
#    && tar zxpf /tmp/blackfire-probe.tar.gz -C /tmp \
#    && mv /tmp/blackfire-*.so $(php -r "echo ini_get('extension_dir');")/blackfire.so \
#    && printf "extension=blackfire.so\nblackfire.agent_socket=tcp://blackfire:8707\n" > $PHP_INI_DIR/conf.d/blackfire.ini \
#    && mkdir -p /tmp/blackfire \
#    && curl -A "Docker" -L https://blackfire.io/api/v1/releases/client/linux_static/amd64 | tar zxp -C /tmp/blackfire \
#    && mv /tmp/blackfire/blackfire /usr/bin/blackfire \
#    && rm -Rf /tmp/blackfire

# PHP-CS-FIXER & Deptrac
#RUN wget http://cs.sensiolabs.org/download/php-cs-fixer-v2.phar -O php-cs-fixer \
#    && chmod a+x php-cs-fixer \
#    && mv php-cs-fixer /usr/local/bin/php-cs-fixer \
#    && curl -LS http://get.sensiolabs.de/deptrac.phar -o deptrac.phar \
#    && chmod +x deptrac.phar \
#    && mv deptrac.phar /usr/local/bin/deptrac

RUN mkdir -p ${WORKPATH}

RUN rm -rf ${WORKDIR}/vendor \
    && ls -l ${WORKDIR}

RUN mkdir -p \
		${WORKDIR}/var/cache \
		${WORKDIR}/var/logs \
		${WORKDIR}/var/sessions \
	&& chown -R www-data ${WORKDIR}/var \
	&& chown -R www-data /tmp/

RUN chown www-data:www-data -R ${WORKPATH}
RUN chmod 775 ${WORKPATH}

WORKDIR ${WORKPATH}

COPY docker ./

EXPOSE 9000

CMD ["php-fpm"]
