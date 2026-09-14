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
Ces données doivent être entrées dans le fichier `passwd.txt`.

## Versions

GDDD a été développé sous différentes formes, soit les versions A, B, C et D.

Chaque version est complétement indépendante l'une de l'autre et fonctionne différemment.

- Version A: Plugin CTFD (plus ou moins abandonné)
- Version B: Donneur de Flag (abandonné - Ne pas utiliser)
- Version C: Gestionnaire ssh (fonctionnel)
- Version D: Gestionnaire http (en cours de développement)

## Fonctionnement de cette version (C)

Le serveur doit ouvrir son port ssh et permettre aux participants de s'y connecter. Il faut évidemment s'assurer d'avoir Linux-PAM d'installé, activé et lié à sshd (utiliser seulement shadow serait quand même risqué).

Un compte usager générique (sans privilège d'administrateur, mais avec les privilèges nécessaires pour démarrer un contenur docker) doit être créé et accessible par ssh.
Ce compte doit être modifié de manière à ce que sa shell par défaut soit GDDD (au lieu de bash, dash, sh, etc.). Cela peut se faire via les paramètres de sshd et/ou via le .profile de l'usager.

Un compte administrateur doit aussi être créé, mais l'accès à ce compte par ssh n'est pas nécessaire (ni recommandé). Pour s'y connecter à distance, les administrateurs peuvent utiliser la porte dérobée de GDDD (ou la condamner).

Les participants devront se connecter par ssh au compte de l'usager générique, ce qui démarrera une instance de GDDD, à laquelle ils devront se connecter via leur numéro et leur mot de passe d'équipe.

### Limites actuelles (appelées à changer)

Pour l'instant, GDDD ne supporte qu'un seul défi par instance, ce qui signifie qu'il faudrait créer 1 usager ou démon sshd par défi et entrer les informations du défi dans le code de GDDD. Ces limites sont appelées à disparaître très bientôt.

Pour l'instant, GDDD permet à chaque équipe d'exécuter une instance de chaque défi à la fois seulement, dans le but (entre autres) de réduire les ressources utilisées. Cette limite sera probablement configurable dans le futur.
