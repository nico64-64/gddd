#!/bin/bash
cwd="$(realpath "$0" | sed -E "s/\/$(basename "$0")$//gm")"
source "${cwd}/.env"
docker build -t gddd "${cwd}"
docker run --rm --name gddd -d -p "${PORT}:80" \
    --env-file "${cwd}/.env" \
    -v /var/run/docker.sock:/var/run/docker.sock `# Permettre le déploiement de défis docker en local` \
    -v "${cwd}/.env:/var/www/.env" \
    -v "${cwd}/entrypoint.sh:/entrypoint.sh" \
    -v "${cwd}/configVM.sh:/root/configVM.sh" \
    -v "${cwd}/configDefis.sh:${RACINE_SERVEUR}/configDefis.sh" \
    -v "${cwd}/gererDefis.sh:${RACINE_SERVEUR}/gererDefis.sh" \
    -v "${cwd}/web:/var/www/web" \
    -v "${cwd}/${DOSSIER_DEFIS}:${RACINE_SERVEUR}/defis" \
    -v "${cwd}/${FICHIER_EQUIPES}:${RACINE_SERVEUR}/equipes.json" \
    -v "${cwd}/lockfiles/:${RACINE_SERVEUR}/lockfiles/" \
    -v "${cwd}/${ICONE}:${RACINE_SERVEUR}/web/$(basename "${ICONE}")" \
    -v "${cwd}/${LOGO}:${RACINE_SERVEUR}/web/$(basename "${LOGO}")" \
    -v "${cwd}/nginx.conf:/etc/nginx/conf.d/default.conf" \
    gddd
