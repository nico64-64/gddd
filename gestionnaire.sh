#! /bin/bash

##############################################################
## gddd, le Gestionnaire de Déploiement de Défis Dynamiques ##
##############################################################

PASSWD_FILE="./passwd.txt" # fichier contenant les mots de passes de chaque équipe
FICHIER_DEFIS="./defis.txt" # fichier contenant les informations des défis
VERSION=C0.4 # version de gddd
SIMULTANEITE=0 # simultanéité permise par cette version (autre que pour les différentes équipes)


. $FICHIER_DEFIS
echo -e "Bienvenue au $NOM_CTF!\n"
echo "Voici la liste des défis offert via GDDD:"
for ((i=0; i<$NBRE_DEFIS; i++))
do
	echo "$i - ${NOMS_DEFIS[$i]}"
done

echo -e -n "\nEntre le numéro du défi que tu souhaites essayer: "
read defi
if [ -z "$defi" ] || ! [[ "$defi" =~ ^([1-9][0-9]*|0)$ ]] || [ $defi -ge $NBRE_DEFIS ]
then
	echo "numéro invalide!"
	exit
fi

DEFI=${ID_DEFIS[$defi]} # nom du conteneur docker tel que préalablement construit avec docker build
BEAU_NOM=${NOMS_DEFIS[$defi]} # nom du défi tel qu'affiché aux participants

echo -e "Bienvenue au défi $BEAU_NOM.\n"
. $PASSWD_FILE

echo "S'il vous plaît entrez votre numéro d'équipe:"
read num
if [ -z "$num" ] || ! [[ "$num" =~ ^([1-9][0-9]*|0)$ ]]
# $num n'est pas un nombre entier ou bien il a été préfixé par des 0
then
	echo "Numéro d'équipe invalide!"
	echo "Vous devez entrer le numéro d'équipe écrit sur votre table."
	exit
elif [ $num -eq 0 ]
# login admin (camouflé)
then
	echo "Error: not found."
	echo "Press Enter to exit."
	read input
	if [ -n "$input" ] && [ "$input" = "adm" ]
	then
		login
	fi
	exit
elif [ $num -lt 0 ] || [ $num -gt $NBRE_EQUIPES ]
# $num > $NBRE_EQUIPES ou négatif
then
	echo "Numéro d'équipe invalide!"
	echo "Vous devez entrer le numéro d'équipe écrit sur votre table."
	exit
fi


if ! [ -f $PASSWD_FILE ]
# le fichier de mots de passe n'existe pas
then
	echo "ERREUR: Fichier de mots de passes manquants!"
	echo "Avertissez un Admin!"
	exit
fi

# Lecture des mots de passe:
if [ -z "$PASSWD" ]
# Le fichier est mal formatté
then
	echo "ERREUR: Fichier de mots de passe mal formatté."
	echo "Avertissez un Admin!"
	exit
fi
if [ -z "${PASSWD[$num]}" ]
# Pas de mot de passe enregistré pour cette équipe
then
	echo "ERREUR: Le mot de passe de ton équipe n'a pas encore été défini."
	echo "Va voir un admin pour régler ça."
	exit
fi

# Demande et vérification du mot de passe:
echo "Entre le mot de passe de ton équipe pour te connecter au défi:"
read passwd
if [ -z "$passwd" ] || [ "$passwd" != "${PASSWD[$num]}" ]
then
	echo "Mot de passe invalide!"
	exit
fi

# Si on s'est rendu jusqu'ici, c'est que le numéro d'équipe est vraiment le bon.

nom_instance="$DEFI"_$num
id_instance="$(docker ps --format {{.ID}} --filter name=$nom_instance)"

# Vérifions si cette équipe est déjà connectée:
if ! [ -z "$id_instance" ]
then
	echo -e "\nDésolé, un membre de votre équipe est déjà en train de résoudre ce défi."
	echo -e "Un seul participant par équipe peut se connecter simultanément à ce défi.\n"
	echo "Si un admin le demande, donnez le code suivant:"
	echo "\"Défi $DEFI: instance $id_instance occupée, gddd v$VERSION (sim$SIMULTANEITE)\""
	exit
fi

# Démarrage du défi et Nettoyage quand c'est fini
docker run -it --name $nom_instance $DEFI
docker container prune -f >> /dev/null

clear
echo -e "\n\e[1mDéfi terminé!\e[0m"
