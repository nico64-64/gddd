#! /bin/bash

cwd="$(realpath "$0" | sed -E "s/\/$(basename "$0")$//gm")"

# Lecture des arguments reçus par le programme:



# Les variables d'environnement doivent être lues avant pour que les paramètres les réécrivent
for arg in "$@"; do
	case "$arg"
	in
		--help | -h)
			echo -e "Utilitaire de configuration de GDDD (version D).\n"
			echo -e "Utilisez comme suit:\n./config.sh [ -h | -v ] [ -f ] [ -c ] [ -a IP -p #port ] [ -n nom ] [ -L ctfd.ctf ] [ -d dir/ ] [ -e equipes.json ] [ -i icone.svg -l logo.png ]\n"
			echo    "-h Affiche ce message d'aide, puis quitte sans rien faire"
			echo    "-v Affiche le numéro de version de ce script, puis quitte sans rien faire"
			echo    "-f Sécifie l'emplacement du fichier de configuration"
			echo    "-c Copie les fichiers nécessaires au lieu de créer des symlinks"
			echo -e "-d Indique que le prochain argument sera le dossier où se trouvent les catégories de défis (un dossier\n   par catégorie, chacun contenant un dossier par défi)"
			echo    "-e Indique que le prochain argument sera le fichier JSON où sont enregistrées les équipes"
			echo    "-i Indique que le prochain argument sera l'icone du CTF (petite image carée)"
			echo    "-l Indique que le prochain argument sera le logo du CTF (image rectangulaire en format paysage)"
			echo    "-L Indique que le prochain argument sera le lien vers l'instance de CTFd de l'évènement"
			echo    "-n Indique que le prochain argument sera le nom du CTF (entre guillemets s'il contient des espaces)"
			echo    "-p Indique que le prochain argument sera le numéro de port utilisé par GDDD"
			echo    "--pwd 		Indique le dossier contenant le CTF"
			echo    "--domaine 	Indique le nom de domaine global du CTF"
			echo -e "\nTous ces arguments sont facultatifs et viennent modifier les valeurs inscrites dans le script.\nLeur ordre n'a pas d'importance.\n"
			echo -e "Vous devez absolument exécuter ce script dans son dossier d'origine (c'est-à-dire la racine du repo).\n"
			exit
			;;
		
		--version | -v)
			echo "config.sh version 1.0"
			echo "Compatible avec GDDD version D (0.2 et plus récent)"
			exit
			;;
		-f)
			aLire=fichier_env
			;;
		--pwd)
			aLire=_PWD
			;;
		--domaine)
			aLire=DOMAINE_GLOBAL
			;;
		*)
			if [[ -v aLire ]]
			then
				declare "$aLire=$arg"
				unset aLire
			fi
			;;
	esac
done

if ! [ -v "$fichier_env" ]; then
	source "${cwd}/.env.exemple"
fi

for arg in "$@"
do
	case "$arg"
	in		
		-c)
			unset SYMLINKS
			;;
		
		-d)
			aLire=DOSSIER_DEFIS
			;;
		
		-e)
			aLire=FICHIER_EQUIPES
			;;
		
		-i)
			aLire=ICONE
			;;
		
		-l)
			aLire=LOGO
			;;
		
		-L)
			aLire=LIEN_CTFD
			;;
		
		-n)
			aLire=NOM_CTF
			;;
		-p)
			aLire=PORT
			;;
		-f | --help | -h | --version | -v | --pwd | --domaine)
			# BUG
			continue
			;;
		*)
			if [[ -v aLire ]]
			then
				declare "$aLire=$arg"
				unset aLire
			else
				echo -e "\"$arg\" n'est pas un argument valide.\nUtilisez --help pour afficher la liste des arguments supportés\n"
				exit
			fi
			;;
	esac
done

# Enregistrement des variables pour les autres scripts

env_deploiement="# Ce fichier est généré automatiquement. Veuillez ne pas le modifier.\n"
for var in $(grep -oE "^[a-zA-Z0-9_\-]+=" "${cwd}/.env.exemple"); do
	env_deploiement="${env_deploiement}$(eval "echo \"${var}\\\"\$${var//=/}\\\"\n\"")"
done
printf "$env_deploiement" > .env.deploiement

# Vérification de la présence des dossiers et fichiers requis:

if ! [ -d ./web ] && ! [ -f ./config.sh ]
then
	echo "ERREUR: Vous devez absolument exécuter ce script dans son dossier d'origine (c'est à dire la racine du repo)."
	echo "Consultez $0 -h pour en savoir plus."
	exit
fi

if ! [ -d "$DOSSIER_DEFIS" ]
then
	echo "ERREUR: $DOSSIER_DEFIS n'est pas un dossier."
	echo "Veuillez fournir un dossier valide et contenant l'ensemble des défis du CTF."
	echo "Consultez $0 -h pour en savoir plus."
	exit
fi

jq . "$FICHIER_EQUIPES" &> /dev/null
if ! [ $? -eq 0 ]
then
	echo "ERREUR: $FICHIER_EQUIPES n'est pas un fichier JSON valide."
	echo "Veuillez fournir un fichier JSON valide contenant notamment la liste de toutes les équipes participant au CTF."
	echo "Consultez $0 -h pour en savoir plus."
	exit
fi

if ! [ -f "$ICONE" ]
then
	echo "ERREUR: $ICONE n'est pas un fichier."
	echo "Veuillez fournir une petite image carrée en tant qu'icone."
	echo "Consultez $0 -h pour en savoir plus."
	exit
fi

if ! [ -f "$LOGO" ]
then
	echo "ERREUR: $LOGO n'est pas un fichier."
	echo "Veuillez fournir une image rectangulaire au format paysage en tant que logo."
	echo "Consultez $0 -h pour en savoir plus."
	exit
fi


# Création des symlinks ou Copie des fichiers:

if [[ -v SYMLINKS ]]
then
	if [[ -L ./defis ]]
	then
		rm ./defis
	fi
	ln -s "$DOSSIER_DEFIS" ./defis &> /dev/null
	ln -s "$FICHIER_EQUIPES" ./equipes.json &> /dev/null
	ln -s "$ICONE" "./web/$(basename "$ICONE")" &> /dev/null
	ln -s "$LOGO" "./web/$(basename "$LOGO")" &> /dev/null
fi

echo -e "\nGDDD a été configuré avec succès."
echo -e "Démarrer le docker et naviguez à la bonne adresse pour vérifier.\n"
