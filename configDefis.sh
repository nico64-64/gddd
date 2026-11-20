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
	if [ -f "$INSTALLATION" ]
	then
		chmod +x "$INSTALLATION"
	else
		echo -e "Erreur: Le script d'installation du défi $defi n'a pas été trouvé.\nOn va essayer de l'exécuter quand même..."
	fi
	"./$INSTALLATION"
	
	INSTANCIATION=$(yq -M -r .\"statiques-dynamiques\".installation.script_instanciation "$defi/description.yaml")
	echo "$INSTANCIATION" > .script_instanciation
	if [ -f "$INSTANCIATION" ]
	then
		chmod +x "$INSTANCIATION"
	else
		echo "Erreur: Le script d'instanciation du défi $defi n'a pas été trouvé."
	fi
	
	DESTRUCTION=$(yq -M -r .\"statiques-dynamiques\".installation.script_destruction "$defi/description.yaml")
	echo "$DESTRUCTION" > .script_destruction
	if [ -f "$DESTRUCTION" ]
	then
		chmod +x "$DESTRUCTION"
	else
		echo "Erreur: Le script de destruction du défi $defi n'a pas été trouvé."
	fi
	
	popd
	chown -R "$NOM:$NOM" "/home/$NOM/defis/$(basename "$defi")"
done


# Terminé!
touch /root/.defis_installes
exit
