FM php:8.2-cli

# Install system dependencies required for mysqli
RUN apt-get update && apt-get install -y \
    default-mysql-client \
    && docker-php-ext-install mysqli pdo pdo_mysql

WORKDIR /api

COPY . /api

# IMPORTANT: Railway expects $PORT, so we keep this
EXPOSE 8080

CMD sh -c "php -S 0.0.0.0:$PORT index.php"
