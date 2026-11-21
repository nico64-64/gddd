#!/bin/bash
cwd="$(realpath "$0" | sed -E "s/\/$(basename "$0")$//gm")"
source "${cwd}/env/.env"
docker build -t gddd "${cwd}"
docker run --rm --name gddd -d -p "${PORT}:80" \
    -v "${cwd}/env/:/var/www/env/" \
    -v "${cwd}/entrypoint.sh:/entrypoint.sh" \
    -v "${cwd}/web:/var/www/web" \
    -v "${cwd}/${DOSSIER_DEFIS}:/var/www/defis" \
    -v "${cwd}/${FICHIER_EQUIPES}:/var/www/equipes.json" \
    -v "${cwd}/${ICONE}:/var/www/web/$(basename "${ICONE}")" \
    -v "${cwd}/${LOGO}:/var/www/web/$(basename "${LOGO}")" \
    -v "${cwd}/nginx.conf:/etc/nginx/conf.d/default.conf" \
    gddd
