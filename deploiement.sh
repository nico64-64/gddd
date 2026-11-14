#!/bin/bash
cwd="$(realpath "$0" | sed -E "s/\/$(basename "$0")$//gm")"
source "${cwd}/.env.deploiement"
docker build -t gddd .
docker run --rm --name gddd -d -p ${PORT}:80 \
    -v "${cwd}/.env.deploiement:/.env" \
    -v "${cwd}/entrypoint.sh:/entrypoint.sh" \
    -v "${cwd}/web:/var/www/web" \
    -v "${DOSSIER_DEFIS}:/var/www/defis" \
    -v "${FICHIER_EQUIPES}:/var/www/equipes.json" \
    -v "${ICONE}:/var/www/web/$(basename "${ICONE}")" \
    -v "${LOGO}:/var/www/web/$(basename "${LOGO}")" \
    -v "${cwd}/nginx.conf:/etc/nginx/conf.d/default.conf" \
    gddd