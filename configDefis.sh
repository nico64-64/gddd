#! /bin/bash

#
# configDefis.sh
# Installe chaque défi dans son répertoire et y exécute le script d'installation fourni.
# Le script a terminé si le fichier /root/.defis_installes existe.
#
# N'EXÉCUTEZ *PAS* CE SCRIPT SUR VOTRE ORDI OU L'HÔTE DE GDDD!
#


# Vérification des privilèges de superutilisateur:
if [ $(id -u) -ne 0 ]
then
	echo "Erreur! Ce script doit être exécuté par root."
	exit
fi

# Vérification des données d'entrée:
if ! [[ -v NOM ]]
then
	echo "Erreur! Vous devez fournir le nom d'usager de l'équipe sous forme de variables d'environnement."
	echo "Exemple: NOM=equipe1 /root/configDefis.sh"
	exit
fi

# Vérification de la présence des défis:
if ! [[ -d /root/defis && -d "/home/$NOM/defis" ]]
then
	echo "Erreur! Aucun défi détecté sur cette machine."
	exit
fi


# Installation des défis
for defi in /root/defis/*
do
	cp -r "$defi" "/home/$NOM/defis/$(basename "$defi")"
	rm "/home/$NOM/defis/$(basename "$defi")/description.yaml"
	pushd "/home/$NOM/defis/$(basename "$defi")"
	
	INSTALLATION=$(yq -M -r .\"statiques-dynamiques\".installation.script_installation "$defi/description.yaml")
	chmod +x "$INSTALLATION"
	"./$INSTALLATION"
	
	popd
	chown -R "$NOM:$NOM" "/home/$NOM/defis/$(basename "$defi")"
done


# Terminé!
touch /root/.defis_installes
exit
