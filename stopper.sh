#! /bin/bash

if [[ $1 == '-h' ]] || [[ $1 == '--help' ]]
then
	echo "Script de fermeture et nettoyage de GDDD"
	echo -e "Usage: $0 [ -h ] [ -f ]\n"
	echo "-h affiche ce message, puis quitte"
	echo "-f indique qu'il faut aussi supprimer l'image docker de GDDD"
	echo -e "\nPour construire et démarrer GDDD, utilisez demarrer.sh."
	exit
fi

echo "Fermeture de GDDD..."
docker stop gddd > /dev/null # ferme le docker
docker container prune -f # décharge le docker
if [[ $1 == '-f' ]]
then
	docker rmi gddd # suppression de l'image docker
fi
echo "Terminé!"
