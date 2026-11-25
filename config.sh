#! /bin/bash

cwd="$(realpath "$0" | sed -E "s/\/$(basename "$0")$//gm")" # Current Working Directory

# Lecture des arguments reçus par le programme:
# Les variables d'environnement doivent être lues avant pour que les paramètres les réécrivent

for arg in "$@"; do
	case "$arg"
	in
		--help | -h)
			echo -e "Utilitaire de configuration de GDDD (version D).\n"
			echo -e "Utilisez comme suit:\n./config.sh [ -h | -v ] [ -c ] [ -f .env ] [ -P port ] [ -P minimum ] [ -d defis/ ] [ -e equipes.json -m machines.json ] [ -r /racine ] [ -M 1 ] [ -n nom -L ctfd.ctf ] [ -i icone.svg -l logo.png ]\n"
			echo    "-h Affiche ce message d'aide, puis quitte sans rien faire"
			echo    "-v Affiche le numéro de version de ce script, puis quitte sans rien faire"
			echo    "-c Copie les fichiers nécessaires au lieu de créer des symlinks"
			echo -e "-d Indique que le prochain argument sera le dossier où se trouvent les catégories de défis (un dossier\n   par catégorie, chacun contenant un dossier par défi)"
			echo    "-e Indique que le prochain argument sera le fichier JSON où sont enregistrées les équipes"
			echo    "-f Sécifie l'emplacement du fichier de configuration"
			echo    "-i Indique que le prochain argument sera l'icone du CTF (petite image carée)"
			echo    "-l Indique que le prochain argument sera le logo du CTF (image rectangulaire en format paysage)"
			echo    "-L Indique que le prochain argument sera le lien vers l'instance de CTFd de l'évènement"
			echo	"-m Indique que le prochain argument sera le fichier JSON où sont enregistrées les machines où devront être déployés les défis"
			echo	"-M Indique que le prochain argument sera le nombre maximal d'instances de défi exécutées simultanément par équipe, tous défis confondus"
			echo    "-n Indique que le prochain argument sera le nom du CTF (entre guillemets s'il contient des espaces)"
			echo 	"-p Indique que le prochain argument sera le numéro de port utilisé pour déployer GDDD"
			echo    "-P Indique que le prochain argument sera le numéro de port minimal utilisé par GDDD pour déployer les défis dynamiques"
			echo	"-r Indique que le prochain argument sera le dossier racine de GDDD, relatif à celui du serveur HTTP"
			echo    "--pwd     Indique le dossier contenant le CTF (Project Working Directory)"
			echo    "--domaine Indique le nom de domaine global du CTF"
			echo -e "\nTous ces arguments sont facultatifs et la valeur par défaut (inscrite dans .env.exemple) sera utilisée si vous ne la spécifiez pas.\nL'ordre des arguments n'a pas d'importance.\n"
			echo -e "Vous devez absolument exécuter ce script dans son dossier d'origine (c'est-à-dire la racine du repo).\n"
			exit
			;;
		
		--version | -v)
			echo "config.sh version 1.2"
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

if ! [[ -v "$fichier_env" ]]
then
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
		
		-m)
			aLire=FICHIER_MACHINES
			;;
		
		-M)
			aLire=MAX_INSTANCES
			;;
		
		-n)
			aLire=NOM_CTF
			;;
			
		-p)
			aLire=PORT
			;;
		
		-P)
			aLire=PORT_MINIMUM
			;;
		
		-r)
			aLire=RACINE_SERVEUR
			;;
		
		-f | --pwd | --domaine)
			#TODO: Skipper les valeurs de --pwd, --domaine et -f aussi (BUG)
			continue
			;;
		
		*)
			if [[ -v aLire ]]
			then
				declare "$aLire=$arg"
				unset aLire
			else
				echo -e "\"$arg\" n'est pas un argument valide.\nUtilisez --help pour afficher la liste des arguments supportés\n"
				#exit #temporairement commenté pour contourner le bug de --pwd, --domaine et --env
			fi
			;;
	esac
done


# Enregistrement des variables pour les autres scripts

env_deploiement="# Ce fichier est généré automatiquement. Veuillez ne pas le modifier.\n"
for var in $(grep -oE "^[a-zA-Z0-9_\-]+=" "${cwd}/.env.exemple"); do
	env_deploiement="${env_deploiement}$(eval "echo \"${var}\\\"\$${var//=/}\\\"\n\"")"
done
mkdir -p "${cwd}/env"
printf "$env_deploiement" > "${cwd}/env/.env"


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

jq . "$FICHIER_MACHINES" &> /dev/null
if ! [ $? -eq 0 ]
then
	echo "ERREUR: $FICHIER_MACHINES n'est pas un fichier JSON valide."
	echo "Veuillez fournir un fichier JSON valide contenant notamment les informations de toutes les machines où seront déployés les défis dynamiques."
	echo "Consultez $0 -h pour en savoir plus."
	exit
fi

if [[ -v ICONE ]] && ! [ -f "$ICONE" ]
then
	echo "ERREUR: $ICONE n'est pas un fichier."
	echo "Veuillez fournir une petite image carrée en tant qu'icone."
	echo "Consultez $0 -h pour en savoir plus."
	exit
fi

if [[ -v LOGO ]] && ! [ -f "$LOGO" ]
then
	echo "ERREUR: $LOGO n'est pas un fichier."
	echo "Veuillez fournir une image rectangulaire au format paysage en tant que logo."
	echo "Consultez $0 -h pour en savoir plus."
	exit
fi


# Création des symlinks (si nécessaire):

if [[ -v SYMLINKS ]]
then
	if [[ -L ./defis ]]
	then
		rm ./defis
	fi
	ln -s "$DOSSIER_DEFIS" ./defis &> /dev/null
	ln -s "$FICHIER_EQUIPES" ./equipes.json &> /dev/null
	ln -s "$FICHIER_MACHINES" ./machines.json &> /dev/null
	ln -s "$ICONE" "./web/$(basename "$ICONE")" &> /dev/null
	ln -s "$LOGO" "./web/$(basename "$LOGO")" &> /dev/null
fi

echo -e "\nGDDD a été configuré avec succès."
echo -e "Démarrer le docker et naviguez au port $PORT de l'ip de cette machine pour vérifier."
echo -e "N'oubliez pas de vous connecter en tant qu'admin pour installer le CTF sur les machines distantes.\n"
