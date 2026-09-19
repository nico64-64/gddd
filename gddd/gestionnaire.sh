#! /bin/bash

##############################################################
## gddd, le Gestionnaire de Déploiement de Défis Dynamiques ##
##############################################################


# Valeurs par défaut (modifiables via les options d'invocation)
UTILISER_CONFIGS_UCTF=1 # indique qu'on veut utiliser les fichiers de configuration json et yaml de l'uctf (commenter pour revenir aux arrays Bash de GDDD)
JSON_EQUIPES="/gddd/comptes_utilisateurs.json" # fichier contenant les mots de passes de chaque équipe
FICHIER_MDP="/gddd/passwd" # fichier contenant les mots de passes de chaque équipe
FICHIER_DEFIS="/gddd/defis" # fichier contenant les informations des défis

# Valeurs fixes (non modifiables)
VERSION="C1.1" # version de gddd
SIMULTANEITE=0 # simultanéité permise par cette version (autre que pour les différentes équipes)


# Lecture des arguments reçus par le programme
for arg in "$@"
do
	case "$arg"
	in
		--help | -h)
			echo -e "\e[1mGDDD, le Gestionnaire de Déploiement de Défis Dynamiques\e[0m\n"
			echo -e "Usage: $0 [ -h | -v ] [ -d FICHIER_DEFIS ] [ -m FICHIER_MDP | -j JSON_EQUIPES ]\n"
			echo "-h Affiche ce message, puis quitte"
			echo "-v Affiche les informations de version de GDDD, puis quitte"
			echo "-d Indique que le prochain argument sera le nom du fichier listant les informations"
			echo "     des défis, sous forme d'array Bash"
			echo "   Ce fichier peut être généré par reload_defis.sh à partir des descriptions yaml"
			echo "     des défis, tels que présentes à l'UCTF"
			echo "-m Indique que le prochain argument sera le nom du fichier listant les mots de passes"
			echo "     des équipes, sous forme d'array Bash"
			echo "-j Indique que le prochain argument sera le nom du fichier json de configuration des"
			echo "     équipes, tel qu'utilisé par CTFd dans le cadre de l'UCTF"
			echo -e "   Notez que cette option et la précédente sont mutuellement exclusives.\n"
			exit
			;;
		
		--version | -v)
			echo "GDDD version $VERSION"
			echo "Cette version supporte les configs de l'UCTF!"
			echo "Consultez https://github.com/nico64-64/gddd pour en savoir plus."
			exit
			;;
		
		-d)
			aLire=FICHIER_DEFIS
			;;
		
		-m)
			unset UTILISER_CONFIGS_UCTF
			aLire=FICHIER_MDP
			;;
		
		-j)
			UTILISER_CONFIGS_UCTF=1
			aLire=JSON_EQUIPES
			;;
		
		*)
			if [[ -v aLire ]]
			then
				declare "$aLire=$arg"
				unset aLire
			else
				echo "Erreur: $arg n'est pas une option acceptée par ce programme."
				echo "Entrez $0 -h pour consulter la liste des options acceptées."
				exit
			fi
			;;
	esac
done


if [[ -v UTILISER_CONFIGS_UCTF ]]
# Lecture des informations de configuration depuis les fichiers JSON de l'UCTF
then
	if ! [ -f "$JSON_EQUIPES" ]
	then
		echo "ERREUR: Fichier JSON de définition des équipes manquant!"
		echo "Avertissez un admin!"
		exit
	fi
	
	readarray -t PASSWD < <(jq -r '.equipes_ctfd[].mdp' $JSON_EQUIPES) # lecture des mots de passes des équipes depuis le fichier JSON
	PASSWD=("ADMIN" "${PASSWD[@]}") # décale toutes les entrées pour qu'elles commencent à 1, rejoignant le comportement des arrays Bash de GDDD

else
# Lecture des informations de configuration depuis les arrays Bash de GDDD
	if ! [ -f $FICHIER_MDP ]
	# le fichier de mots de passe n'existe pas
	then
		echo "ERREUR: Fichier de mots de passes manquant!"
		echo "Avertissez un Admin!"
		exit
	fi
	. $FICHIER_MDP # Lecture des informations des équipes
fi


if ! [ -f $FICHIER_DEFIS ]
# le fichier de définition des défis n'existe pas
then
	echo "ERREUR: Fichier de définition des défis manquant!"
	echo "Avertissez un Admin!"
	exit
fi
. $FICHIER_DEFIS # Lecture des informations des défis


if [ -z "$PASSWD" ]
# le fichier de mots de passe est mal formatté
then
	echo "ERREUR: Fichier de mots de passe mal formatté."
	echo "Avertissez un Admin!"
	exit
elif [ -z "$ID_DEFIS" ] || [ -z "$NOMS_DEFIS" ] || [ ${#ID_DEFIS[@]} -ne ${#NOMS_DEFIS[@]} ]
# le fichier de définition des défis est mal formatté
then
	echo "ERREUR: Fichier de définition des défis mal formatté."
	echo "Avertissez un Admin!"
	exit
fi

NBRE_DEFIS=${#ID_DEFIS[@]}
((NBRE_EQUIPES=${#PASSWD[@]}-1))


# Mot de bienvenue et affichage de la liste des défis
echo -e "Bienvenue au $NOM_CTF!\n"
echo "Voici la liste des défis offerts via GDDD:"
for ((i=0; i<$NBRE_DEFIS; i++))
do
	echo "$i - ${NOMS_DEFIS[$i]}"
done

# Choix du défi
echo -e -n "\nEntrez le numéro du défi que vous souhaitez essayer: "
read defi
if [ -z "$defi" ] || ! [[ "$defi" =~ ^([1-9][0-9]*|0)$ ]] || [ $defi -ge $NBRE_DEFIS ]
then
	echo "Ce numéro de défi est invalide!"
	exit
fi


DEFI=${ID_DEFIS[$defi]} # nom du conteneur docker tel que préalablement construit avec docker build
BEAU_NOM=${NOMS_DEFIS[$defi]} # nom du défi tel qu'affiché aux participants

# Login d'équipe
echo -e "Bienvenue au défi $BEAU_NOM.\n"
echo -n "S'il vous plaît entrez votre numéro d'équipe: "
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
		su
	fi
	exit
elif [ $num -lt 0 ] || [ $num -gt $NBRE_EQUIPES ]
# $num > $NBRE_EQUIPES ou négatif
then
	echo "Numéro d'équipe invalide!"
	echo "Vous devez entrer le numéro d'équipe écrit sur votre table."
	exit
fi

if [ -z "${PASSWD[$num]}" ]
# Pas de mot de passe enregistré pour cette équipe
then
	echo "ERREUR: Le mot de passe de ton équipe n'a pas encore été défini."
	echo "Va voir un admin pour régler ça."
	exit
fi

# Demande et vérification du mot de passe
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
	echo "\"Défi #$defi ($DEFI), équipe #$num: instance $id_instance occupée, gddd v$VERSION (sim$SIMULTANEITE)\""
	exit
fi

# Démarrage du défi et Nettoyage quand c'est fini
docker run -it --name $nom_instance $DEFI
docker container prune -f >> /dev/null

clear
echo -e "\n\e[1mDéfi terminé!\e[0m"
