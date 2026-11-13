# GDDD, le Gestionnaire de Déploiement de Défis Dynamiques, version D

`/!\ Ce projet est encore en cours de développement! Des changements majeurs pourraient arriver à tout moment. /!\`

## But du projet

Le but de GDDD est de fournir une manière de déployer des défis (challenges) de CTF de manière dynamique et économe sur un serveur.

Par cela, j'entend que chaque participant devrait pouvoir se connecter au serveur d'une certaines manière, et celui-ci lui fournira une instance du défi demandé. Le nombre d'instances de défis fonctionnant simultanément devrait être réduit au maximum afin d'utiliser le moins de ressources possibles.

## Exigences

Pour être déployé par GDDD, un défi doit respecter les contraintes suivantes:

- Le défi doit être fourni sous la forme d'un conteneur Docker (pas docker compose)
- L'image docker du défi doit avoir été chargée sur le serveur
- La version C de GDDD exige que les défis fournissent eux-même leur flag statique
- La version D de GDDD fournit le flag (dynamique ou statique) au participant à la place du défi
- Il n'est pas souhaitable d'utiliser GDDD pour des défis que le participant peut télécharger lui-même ou pour des défis web

Il faut également s'assurer de donner un numéro (entier supérieur à 0) et un mot de passe à chaque équipe.
Ces données, ainsi que le nombre d'équipess participantes, peuvent être entrées dans le fichier `gddd/passwd` ou lues directement dans le fichier JSON qui définit les comptes utilisateurs et les équipes pour CTFd dans le cadre de l'UCTF.

Le fichier `gddd/defis` doit aussi être rempli avec le nom du CTF, le nombre de défis ainsi que le nom du conteneur docker et le nom d'affichage de chaque défi.
Il peut toutefois être rempli automatiquement par le script `reload_defis.sh`, qui lit les fichiers yaml de description des défis exigés par l'UCTF.

## Versions

GDDD a été développé sous différentes formes, soit les versions A, B, C et D.

Chaque version est complétement indépendante l'une de l'autre et fonctionne différemment.

- Version A: Plugin CTFD (plus ou moins abandonné)
- Version B: Donneur de Flag (abandonné - Ne pas utiliser)
- Version C: Gestionnaire ssh (fonctionnel)
- Version D: Gestionnaire http (en cours de développement)

## Fonctionnement de cette version (D)

Cette version utilise un serveur HTTP et un serveur PHP pour présenter une interface HTML conviviale aux usager.

DOCUMENTATION À VENIR! (d'ici la semaine des 4 jeudis...)

### Guide de déploiement

#### Pour tester et développer

Le développement se fait majoritairement avec le serveur http fourni avec PHP.

Entrez `php -S 127.0.0.1:8000` pour le démarrer en local sur le port 8000 de votre machine. Vous devez exécuter cette commande à la racine du site web, donc dans le dossier /web.

Le tout est ensuite vérifié avec le déploiement docker une fois stable.

#### Déploiement docker

On peut aussi déployer GDDD version D par docker, mais cela n'est pas idéal, puisque cela rend le partage des fichiers avec CTFd plus complexe.

Cela permet toutefois d'avoir un déploiement rapide et préconfiguré avec un serveur plus stable et robuste que le serveur de développement de PHP. Ce docker utilise nginx avec php-fpm, tous deux à leur dernière version.

Il se peut que ce déploiement ne fonctionne pas avec la toute dernière version. Les patchs sont les bienvenues.

### Guide pour les participants

1. Ouvrez votre navigateur web et rendez-vous à l'adresse `http://ADRESSE_IP:8000`.
2. Choisissez votre équipe et entrez votre mot de passe.
3. La liste des défis déployés par GDDD apparaissent devant vous.
4. Choisissez un défi et cliquez sur "Démarrer". Vous ne pouvez avoir qu'une seule instance par équipe.
5. Votre tableau de bord est rafraichi à toutes les 5 minutes. Pensez à le rafraichir manuellement avant de démarrer un défi.

### Limites actuelles (appelées à changer)

Pour l'instant, GDDD permet à chaque équipe d'exécuter une instance de chaque défi à la fois seulement, dans le but (entre autres) de réduire les ressources utilisées. Cette limite sera probablement configurable dans le futur.

### Dépendances

Cette version de GDDD utilise les programmes suivants:

- un serveur http (php -S ou nginx)
- un serveur php (php ou php-fpm)
- yq et jq
- bash
- sed
- docker (pour le déploiement docker)

GDDD ne fonctionne pas nativement sur Windows et n'est supporté que sur Linux.
