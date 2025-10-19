FROM php:8.2-apache

# Install system deps and PHP extensions
RUN apt-get update \
  && apt-get install -y --no-install-recommends \
    libzip4 libpng16-16 libjpeg62-turbo libfreetype6 \
    libonig5 zip unzip git \
  && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install mysqli \
  && docker-php-ext-enable mysqli

# GD for image rendering in dompdf
RUN apt-get update \
  && apt-get install -y --no-install-recommends libpng-dev libjpeg-dev libfreetype6-dev \
  && docker-php-ext-configure gd --with-jpeg --with-freetype \
  && docker-php-ext-install gd \
  && rm -rf /var/lib/apt/lists/*

# Enable Apache mod_rewrite
RUN a2enmod rewrite

WORKDIR /var/www/html

# Copy app
COPY . /var/www/html

# Set proper Apache docroot permissions
RUN chown -R www-data:www-data /var/www/html

# Expose default Apache port
EXPOSE 80

# Healthcheck: check Apache is responding
HEALTHCHECK --interval=30s --timeout=3s --start-period=30s --retries=3 \
  CMD curl -fsS http://localhost/ || exit 1


