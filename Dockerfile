FROM php:7.4-apache

RUN docker-php-ext-install pdo pdo_mysql mysqli
RUN a2enmod rewrite
#Iniciar Apache mode Detached
CMD /usr/sbin/apache2ctl -D FOREGROUND

#Copiar todo de la ruta actual a carpeta /var/www/html
COPY . /var/www/html/

#Exponer el puerto 80
EXPOSE 80
