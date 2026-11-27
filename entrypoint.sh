#! /bin/bash

ssh-keygen -f /root/.ssh/id_ed25519 -P ""

jq -c ".equipes[]" "${RACINE_SERVEUR}/${FICHIER_EQUIPES}" | while read equipe
do
	if [ "$(echo $equipe | jq -r ".nom")" = "admins" ]; then
		continue # Les admins n'ont pas de machine
	fi

	nom="$(echo $equipe | jq -r ".nom")"
	mdp="$(echo $equipe | jq -r ".mdp")"
	if [ "$(echo $equipe | jq -r ".machine.est_locale")" = "true" ]
	then
		useradd "$nom"  -m -s "/home/$nom/shell_defaut.sh"
		cp "/root/shell_defaut.sh" "/home/$nom/shell_defaut.sh"
	else
		mip="$(echo $equipe | jq -r ".machine.ip")"
		#musr="$(echo $equipe | jq -r ".machine.usr")" # pas la job de ce script
		#mmdp="$(echo $equipe | jq -r ".machine.mdp")" # idem, et pas un mot de passe qu'on veut utiliser sur les machines anyway
		
		sshpass -p "ctf" ssh-copy-id ctf@$mip # j'ai des doutes sur la syntaxe de cette commande...
		ssh ctf@$mip -t "sudo echo 'PermitRootLogin yes' >> /etc/ssh/sshd_config" # problème: j'ai besoin de root access...
		ssh ctf@$mip -t "sudo systemctl restart sshd" # et ici aussi (quoique je pourrais aussi reboot)
		
		sshpass -p "adminPass123" ssh-copy-id root@$mip
		ssh root@$mip -t "userdel ctf" # ctf était un usager temporaire; on n'en a plus besoin maintenant qu'on peut ssh en root
		ssh root@$mip -t "rm -r /home/ctf"
		
		
		# Les actions suivantes sont la job de GDDD, pas de ce script.
		
		#sshpass -p "$mmdp" ssh-copy-id "$musr@$mip"
		#scp "/${RACINE_SERVEUR}/configVM.sh" "$musr@mip:configVM.sh"
        #ssh "echo '$mmdp' | sudo -sS -t 'chmod +x configVM.sh'"
		#ssh "echo '$mmdp' | sudo -sS -t 'export NOM=\"$nom\"; export MDP=\"$mdp\"; ./configVM.sh'"

		#scp -r "${RACINE_SERVEUR}/${DOSSIER_DEFIS}" "$musr@mip:defis/"
		#scp "/root/configDefis.sh" "$musr@$mip:configDefis.sh"
		#ssh "echo '$mmdp' | sudo -sS -t 'export NOM=$nom; ./configDefis.sh'"
	fi
done

chown -R www-data:www-data /var/www/lockfiles

php-fpm8.4

nginx -g "daemon off;"
