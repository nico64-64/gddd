#! /bin/bash

#
# configVM.sh
# Configure une VM Debian 13 de base pour qu'elle serve à héberger les défis dynamiques d'une équipe.
# Le script a fonctionné si le fichier /root/.config_complete existe.
#
# N'EXÉCUTEZ *PAS* CE SCRIPT SUR VOTRE ORDI OU L'HÔTE DE GDDD!
#


# Vérification de l'OS:
. /etc/os-release
if [ $ID != "debian" ] || [ $VERSION_ID != "13" ]
then
	echo "Erreur! Ce système n'est pas Debian 13."
	exit
fi

# Vérification des données d'entrée:
if ! [[ -v NOM && -v MDP ]]
then
	echo "Erreur! Vous devez fournir le nom d'usager et le mot de passe de l'équipe sous forme de variables d'environnement."
	echo "Exemple: NOM=equipe1 MDP=passwd123 /root/configVM.sh"
	exit
fi

# Vérification des privilèges de superutilisateur:
if [ $(id -u) -ne 0 ]
then
	echo "Erreur! Ce script doit être exécuté par root."
	exit
fi

# Modification du hostname:
echo "$NOM" > /etc/hostname

# Installation et mise à jour des paquets nécessaires:
export DEBIAN_FRONTEND=noninteractive
apt-get update
apt-get upgrade -y
apt-get install -y curl jq
curl -fsSL https://get.docker.com | sh

# Lecture des informations des équipes:
#equipes=()
#for equipe in $(jq .equipes_ctfd.[].nom /root/equipes.json -r -M)
#do
#	equipe=$(echo \"$equipe\" | sed 's/[^a-zA-Z0-9]/_/g')
#	equipes+=($equipe)
#done
#passwd=()
#for mdp in $(jq .equipes_ctfd.[].mdp /root/equipes.json -r -M)
#do
#	passwd+=($mdp)
#done

# Création du compte de l'équipe
useradd "$NOM" -G docker -m -p "$MDP" -s /bin/bash -U

# Terminé!
touch /root/.config_complete
exit
