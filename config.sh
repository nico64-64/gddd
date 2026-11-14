#! /bin/bash
set -a

if ! [ -v _PWD ];then
	printf "Veuillez spécifier le répertoire du CTF : "
	read "_PWD"
fi

if ! [ -v DOMAINE_GLOBAL ];then
	source ${_PWD}/coordination/nom_domaine.env
fi

source .env

# Lecture des arguments reçus par le programme:

for arg in "$@"
do
	case "$arg"
	in
		--help | -h)
			echo -e "Utilitaire de configuration de GDDD (version D).\n"
			echo -e "Utilisez comme suit:\n./config.sh [ -h | -v ] [ -c ] [ -a IP -p #port ] [ -n nom ] [ -L ctfd.ctf ] [ -d dir/ ] [ -e equipes.json ] [ -i icone.svg -l logo.png ]\n"
			echo "-h Affiche ce message d'aide, puis quitte sans rien faire"
			echo "-v Affiche le numéro de version de ce script, puis quitte sans rien faire"
			echo "-a Indique que le prochain argument sera l'addresse IP de GDDD"
			echo "-c Copie les fichiers nécessaires au lieu de créer des symlinks"
			echo -e "-d Indique que le prochain argument sera le dossier où se trouvent les catégories de défis (un dossier\n   par catégorie, chacun contenant un dossier par défi)"
			echo "-e Indique que le prochain argument sera le fichier JSON où sont enregitrées les équipes"
			echo "-i Indique que le prochain argument sera l'icone du CTF (petite image carée)"
			echo "-l Indique que le prochain argument sera le logo du CTF (image rectangulaire en format paysage)"
			echo "-L Indique que le prochain argument sera le lien vers l'instance de CTFd de l'évènement"
			echo "-n Indique que le prochain argument sera le nom du CTF (entre guillemets s'il contient des espaces)"
			echo "-p Indique que le prochain argument sera le numéro de port utilisé par GDDD"
			echo -e "\nTous ces arguments sont facultatifs et viennent modifier les valeurs inscrites dans le script.\nLeur ordre n'a pas d'importance.\n"
			echo -e "Vous devez absolument exécuter ce script dans son dossier d'origine (c'est-à-dire la racine du repo).\n"
			exit
			;;
		
		--version | -v)
			echo "config.sh version 1.0"
			echo "Compatible avec GDDD version D (0.2 et plus récent)"
			exit
			;;
		
		-a)
			aLire=IP
			;;
		
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


# Modification du site web:
for fichier in ./web/*.{php,html}
do
	sed -i "s|<link rel=icon href=[^>]*>|<link rel=icon href=/$(basename "$ICONE")>|g" $fichier
	sed -i "s|<a href=/><img src=[^>]* style=\"width: 180px\"></a>|<a href=/><img src=/$(basename "$LOGO") style=\"width: 180px\"></a>|g" $fichier
	sed -i "s|\$nom_ctf = \"[^>]*\";|\$nom_ctf = \"$NOM_CTF\";|g" $fichier
	sed -i "s|\$lien_ctfd = \"[^>]*\";|\$lien_ctfd = \"$LIEN_CTFD\";|g" $fichier
done

echo -e "\nGDDD a été configuré avec succès."
echo -e "Démarrer le docker et naviguez à la bonne adresse pour vérifier.\n"
