FROM nginx:latest

RUN apt-get update
RUN apt-get install -y php-fpm yq

COPY entrypoint.sh /entrypoint.sh
RUN mkdir -pv /var/www/{web,defis}
COPY web /var/www/web
COPY defis /var/www/defis
COPY equipes.json /var/www
COPY nginx.conf /etc/nginx/conf.d/default.conf
RUN sed -i 's/listen.owner = www-data/listen.owner = nginx/g' /etc/php/8.4/fpm/pool.d/www.conf
RUN sed -i 's/listen.group = www-data/listen.group = nginx/g' /etc/php/8.4/fpm/pool.d/www.conf
RUN /sbin/php-fpm8.4

RUN sed -i 's|\.\./|/var/www/|g' /var/www/web/*.php

EXPOSE 8000
CMD ["/entrypoint.sh"]
