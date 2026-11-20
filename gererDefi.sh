#! /bin/bash

#
# gererDefis.sh
# Démarre ou arrête un défi pour une équipe sur sa VM.
# Ce script peut être exécuté en tant que root ou bien sous l'usager de l'équipe.
#


# Vérification des données d'entrée:
if ! [[ -v NOM && -v DEFI && -v PORT && ("$1" == "-d" || "$1" == "-a") ]]
then
	echo "Erreur! Vous devez fournir le nom d'usager de l'équipe sous forme de variables d'environnement."
	echo "Vous devez aussi indique si vous souhaitez démarrer (-d) ou arrêter (-a) le défi avec l'argument positionel approprié."
	echo "Exemple: NOM=equipe1 DEFI=defiPatate PORT=8000 $0 -d"
	exit
fi

# Vérification du compte utilisé:
if [ $(id -u) -ne 0 ] && [ "$(id -u -n)" != "$NOM" ]
then
	echo "Erreur! Ce script doit être exécuté par root ou l'usager de l'équipe."
	exit
fi

# Vérification de la présence du dossier de défi:
if ! [ -d "/home/$NOM/defis/$DEFI" ]
then
	echo "Erreur! Le défi fourni n'existe pas sur cette machine."
	exit
fi

# Vérification de la présence du script d'instanciation:
if [ ! -f "/home/$NOM/defis/$DEFI/.script_instanciation" ] || [ ! -f "/home/$NOM/defis/$DEFI/$(cat "/home/$NOM/defis/$DEFI/.script_instanciation")" ]
then
	echo "Erreur! Le script d'instanciation ($(cat "/home/$NOM/defis/$DEFI/.script_instanciation")) n'a pas pu être trouvé."
	exit
fi


# Exécution sous le compte utilisateur de l'équipe (si nécessaire):
if [ $(id -u) -eq 0 ]
then
	RUNAS="sudo -u $NOM"
fi

# Démarrage ou arrêt du défi:
cd "/home/$NOM/defis/$DEFI"
if [ "$1" == "-d" ]
then
	$RUNAS PORT=$PORT "./$(cat .script_instanciation)" &> instanciation.log
else # "$1" == "-a"
	$RUNAS PORT=$PORT "./$(cat .script_destruction)" &> destruction.log
fi

# Vérification de si ça semble avoir fonctionné:
if [ $? -ne 0 ]
then
	echo "Il semblerait que le défi n'a pas pu être démarré correctement."
	echo "Y a pas vraiment rien que je peux faire pour l'instant..."
fi
