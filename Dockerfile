FROM nginx:latest

ENV_FILE .env

RUN apt-get update
RUN apt-get install -y php-fpm yq sshpass openssh-client openssh-server
RUN curl -fsSL https://get.docker.com | sh

RUN mkdir -pv /var/www/{web,defis}
RUN sed -i 's/listen.owner = www-data/listen.owner = nginx/g' /etc/php/8.4/fpm/pool.d/www.conf
RUN sed -i 's/listen.group = www-data/listen.group = nginx/g' /etc/php/8.4/fpm/pool.d/www.conf
RUN /sbin/php-fpm8.4

CMD ["/entrypoint.sh"]
