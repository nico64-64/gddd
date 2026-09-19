#! /bin/bash

if [[ "$1" == "-h" ]] || [[ "$1" == "--help" ]]
then
	echo "Script de construction et de démarrage de GDDD"
	echo -e "Usage: $0 [ -h ] [ -b ] [ -p port ]\n"
	echo "-h affiche ce message, puis quitte"
	echo "-b indique qu'il faut construire l'image docker de GDDD"
	echo "-p indique que le prochain argument est le port à mapper au port ssh du conteneur"
	echo -e "\nUtilisez stopper.sh pour fermer GDDD."
	exit
fi

if [[ "$1" == "-b" ]] || [[ "$3" == "-b" ]]
then
	build=1
	if [[ "$2" == "-p" ]]
	then
		port="$3"
	fi
fi
if [[ "$1" == "-p" ]]
then
	port="$2"
fi

if [[ ! -z "$build" ]]
then
	echo "Construction de l'image docker..."
	docker build --network=host --rm -t gddd .
fi
if [[ -z "$port" ]]
then
	port=2208
fi

echo "Démarrage du conteneur docker..."
docker run -dp $port:22 -v /var/run/docker.sock:/var/run/docker.sock --stop-timeout 10 --name gddd gddd
echo "Terminé!"
echo "Vous pouvez ssh à l'usager uctf sur cet appareil au port $port pour vous connecter à gddd."
