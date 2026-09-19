#! /bin/bash

FICHIER_DEFIS=~/uctf/2026/gddd/gddd/defis # fichier où enregistrer les défis déployés par GDDD
DOSSIER_DEFIS=~/uctf/2026 # dossier où se trouvent les défis du CTF (chaque défi doit se trouver dans un sous-dossier)
DEFIS_CATEGORISES=1 # indique que les défis se trouvent dans des sous-dossiers (1 par catégorie)
NOM_CTF="UCTF 2026" # nom du CTF


# Lecture des arguments reçus par le programme
for arg in "$@"
do
	case "$arg"
	in
		--help | -h)
			echo -e "reload_defis.sh\nUsage: $0 [ -h | -v ] [ -c | -C ] [ -f FICHIER_DEFIS ] [ -d DOSSIER_DEFIS ] [ -n NOM_CTF ]\n"
			echo "-h Affiche ce message d'aide, puis quitte sans rien faire"
			echo "-v Affiche le numéro de version de ce script, puis quitte sans rien faire"
			echo "-c Indique que les défis sont catégorisés"
			echo "-C Indique que les défis ne sont pas catégorisés"
			echo "-f Indique que le prochain argument sera le nom du fichier de paramètres des défis de gddd qui sera créé ou remplacé"
			echo "-d Indique que le prochain argument sera le dossier où se trouvent les défis (un dossier par défi)"
			echo "-n Indique que le prochain argument sera le nom du CTF (entre guillemets s'il contient des espaces)"
			echo -e "\nTous ces arguments sont facultatifs et viennent modifier les valeurs inscrites dans le script\n"
			exit
			;;
		
		--version | -v)
			echo "reload_defis.sh version 1.0"
			echo "Compatible avec GDDD version C (1.1 et plus récent)"
			exit
			;;
		
		-c)
			DEFIS_CATEGORISES=1
			;;
		
		-C)
			unset DEFIS_CATEGORISES
			;;
		
		-f)
			aLire=FICHIER_DEFIS
			;;
		
		-d)
			aLire=DOSSIER_DEFIS
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


if ! [ -d "$DOSSIER_DEFIS" ]
then
	echo "ERREUR: $DOSSIER_DEFIS n'est pas un dossier."
	echo "Veuillez fournir un dossier valide et contenant l'ensemble des défis du CTF."
	exit
fi

if [ -f "$FICHIER_DEFIS" ]
then
	cp "$FICHIER_DEFIS" "$FICHIER_DEFIS.backup"
	echo "ATTENTION: La liste interne des défis de GDDD sera écrasée par ce programme!"
	echo "Un backup a été fait, nommé \"$FICHIER_DEFIS.backup\"."
fi

if [[ -v DEFIS_CATEGORISES ]]
then
	for dossier in "$DOSSIER_DEFIS"/*
	do
		if [[ ! -v categories ]] && [ -d "$dossier" ]
		then
			categories=("$dossier")
			echo -e "\nCatégories:\n- $dossier"
		elif [ -d $dossier ]
		then
			categories=("${categories[@]}" "dossier")
			echo "- $dossier"
		fi
	done
else
	categories="$DOSSIER_DEFIS"
	echo -e "\nLes défis ne sont pas catégorisés: Lecture des défis directement."
fi

for categorie in "$categories"
do
	for defi in "$categorie"/*
	do
		if [ -f "$defi/description.yaml" ] && [[ "$(yq '.deploiement' "$defi/description.yaml")" == "\"gddd\"" ]]
		then
			if [[ ! -v id_defis ]] && [ -d "$defi" ]
			then
				id_defis=("$(yq '.id' "$defi/description.yaml")")
				noms_defis=("$(yq '.titre' "$defi/description.yaml")")
				echo -e "\nDéfis déployés par gddd:\n- $defi"
			elif [ -d "$defi" ]
			then
				id_defis+=("$(yq '.id' "$defi/description.yaml")")
				noms_defis+=("$(yq '.titre' "$defi/description.yaml")")
				echo "- $defi"
			fi
		fi
	done
done

echo -e "# PARAMÈTRES DE GDDD\n# Fichier généré automatiquement\n# Modifiez à vos propres risques\n\n" > "$FICHIER_DEFIS"
echo -n -e "NOM_CTF=\"$NOM_CTF\"\n\nID_DEFIS=(" >> "$FICHIER_DEFIS"
for id_defi in ${id_defis[@]}
do
	echo -n "$id_defi " >> "$FICHIER_DEFIS"
done
echo -n -e ")\nNOMS_DEFIS=(" >> "$FICHIER_DEFIS"
for nom_defi in ${noms_defis[@]}
do
	echo -n "$nom_defi " >> "$FICHIER_DEFIS"
done
echo ")" >> "$FICHIER_DEFIS"
