# GDDD, le Gestionnaire de Déploiement de Défis Dynamiques, version C

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
Ces données, ainsi que le nombre d'équipess participantes, doivent être entrées dans le fichier `gddd/passwd.txt`.

Le fichier `gddd/defis.txt` doit aussi être rempli avec le nom du CTF, le nombre de défis ainsi que le nom du conteneur docker et le nom d'affichage de chaque défi.

## Versions

GDDD a été développé sous différentes formes, soit les versions A, B, C et D.

Chaque version est complétement indépendante l'une de l'autre et fonctionne différemment.

- Version A: Plugin CTFD (plus ou moins abandonné)
- Version B: Donneur de Flag (abandonné - Ne pas utiliser)
- Version C: Gestionnaire ssh (fonctionnel)
- Version D: Gestionnaire http (en cours de développement)

## Fonctionnement de cette version (C)

Cette version fonctionne dans un conteneur docker. On doit d'abord le construire avec `./demarrer.sh -b`, puis on peut le supprimer avec `./stopper.sh -f`.

Ce conteneur docker contient 2 utilisateurs, root et uctf.
L'usager uctf a GDDD comme shell par défaut, ce qui signifie que le programme s'ouvre tout de suite après le login et que les participants n'ont pas accès à aucun autre programme dans le docker.

Également, le socket de docker est "bridgé" avec la machine hôte, ce qui signifie que GDDD, depuis l'intérieur de son conteneur, a accès à toutes les images docker de la machine hôte. (svp ne pas vous en servir pour nester des instances...)

Un démon sshd roule en permanence dans le docker de GDDD: C'est le moyen autant pour les participants que pour les administrateurs de se connecter à GDDD.
Vous pouvez choisir quel port de la machine hôte utiliser avec `./demarrer.sh -p NUMÉRO_PORT`.
Le port par défaut est 2208.

Les administrateurs peuvent se connecter directement au compte root du docker en utilisant la porte dérobée dans GDDD.
Ceci est très utile pour déboguer GDDD, mais ne devrait pas servir pour autre chose, puisque les images docker des défis doivent être créés sur la machine hôte.
On pourrait toutefois décider d'utiliser cette porte dérobée pour faire un hotfix ou pour ajouter des défis sans redémarrer le docker.

Il est important de mentionner clairement que chaque équipe ne peut exécuter chaque défi qu'une instance à la fois.

### Guide pour les participants

1. Ouvrez votre terminal sur votre machine linux
2. Tapez `ssh uctf@ADDRESSE_IP` puis Enter
3. Entrez le mot de passe `2026` puis Enter (rien ne s'affichera, c'est normal)
4. Choisissez un défi parmis ceux disponibles
5. Entrez votre numéro d'équipe
6. Entrez votre mot de passe d'équipe
7. Résolvez le défi!
8. Vous serez déconnecté une fois le défi réussi ou échoué

### Limites actuelles (appelées à changer)

Pour l'instant, GDDD permet à chaque équipe d'exécuter une instance de chaque défi à la fois seulement, dans le but (entre autres) de réduire les ressources utilisées. Cette limite sera probablement configurable dans le futur.

Dans le futur, il se pourrait que GDDD lise directement ses informations dans les fichiers yaml de notre déploiement de CTFd.

### Autres notes

Il se peut qu'il faille `chmod 700 /var/run/docker.sock` pour que gddd soit capable de démarrer les défis.
