#! /bin/bash

ssh-keygen -f /root/.ssh/id_ed25519 -P ""

machines="$(cat ${RACINE_SERVEUR}/${FICHIER_MACHINES})"

chmod +x "${RACINE_SERVEUR}/configDefis.sh" "${RACINE_SERVEUR}/gererDefis.sh"

jq -c ".equipes[]" "${RACINE_SERVEUR}/${FICHIER_EQUIPES}" | while read equipe; do
	if [ "$(echo $equipe | jq -r ".nom")" = "admins" ]; then
		continue # Les admins n'ont pas de machine
	fi

	nom="$(echo $equipe | jq -r ".nom")"
	mdp="$(echo $equipe | jq -r ".mdp")"
	if [ "$(echo $equipe | jq -r ".machine.est_locale")" = "true" ]; then
		useradd "$nom"  -m -s "/home/$nom/shell_defaut.sh"
		cp "/root/shell_defaut.sh" "/home/$nom/shell_defaut.sh"
	else
		mip="$(echo $equipe | jq -r ".machine.ip")"
		musr="$(echo $equipe | jq -r ".machine.usr")"
		mmdp="$(echo $equipe | jq -r ".machine.mdp")"

		ssh-keygen -f "/root/.ssh/id_ed25519" -P ""
		sshpass -p "$mmdp" ssh-copy-id "$musr@mip"
		scp "/root/configVM.sh" "$musr@mip:configVM.sh"
                ssh "echo '$mmdp' | sudo -sS -t 'chmod +x configVM.sh'"
		ssh "echo '$mmdp' | sudo -sS -t 'export NOM=\"$nom\"; export MDP=\"$mdp\"; ./configVM.sh'"

		scp -r "${RACINE_SERVEUR}/${DOSSIER_DEFIS}" "$musr@mip:defis/"
		scp "/root/configDefis.sh" "$musr@$mip:configDefis.sh"
		ssh "echo '$mmdp' | sudo -sS -t 'export NOM=$nom; ./configDefis.sh'"
	fi
done

chown -R www-data:www-data /var/www/lockfiles

php-fpm8.4

nginx -g "daemon off;"
