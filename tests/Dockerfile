FROM php:8.0

RUN apt-get -y update \
    && apt-get install -y gnupg2 \
    && curl https://packages.microsoft.com/keys/microsoft.asc | apt-key add - \
    && curl https://packages.microsoft.com/config/ubuntu/20.04/prod.list > /etc/apt/sources.list.d/mssql-release.list \
    && apt-get -y update \
    && apt-get upgrade -y \
    && ACCEPT_EULA=Y apt-get -y --no-install-recommends install msodbcsql17 unixodbc-dev libpq-dev  \
    && docker-php-ext-install pdo pdo_mysql pgsql pdo_pgsql

RUN pecl install sqlsrv \
    && pecl install pdo_sqlsrv \
    && docker-php-ext-enable sqlsrv pdo_sqlsrv

ENV TEST_ENV=local

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /app
