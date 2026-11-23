# Lockfiles

Ce répertoire contient les fichiers de verrous ("lockfiles" en anglais) de GDDD.

Ces fichiers définissent par leur présence ou leur absence l'état de GDDD et sont donc ignorés par `git` et supprimés par `config.sh`.

Tous ces fichiers devrait être supprimés lors de la fermeture de GDDD.

## Types de verrous

GDDD utilise 3 types de verrous:
- `nologin.X.lock` signifie que l'équipe numéro X ne peut pas se connecter à GDDD
- `defi.all.lock` signifie que personne ne peut démarrer une instance du défi "defi"
- `defi.X.lock` signifie que l'équipe numéro X est présentement en train de travailler sur le défi "defi" (ou du moins, leur instance de ce défi est encore en cours d'exécution)

## Interface
Tous ces fichiers doivent normalement être créés et supprimés exclusivement par GDDD lui-même. On peut utiliser le tableau de bord utilisateur et le panneau d'administration pour cela.