<?php
	/*  installer.php
	 * ===============
	 * API pour configurer les VMs et démarrer un CTF avec GDDD.
	 * Ce fichier fournit une interface visuelle, mais je conseille très fortement de plutôt utiliser l'implémentation SSE du panneau d'administration de GDDD, qui appelera ce script via cmd.php.
	 * Cette page n'est accessible qu'à l'équipe des administrateurs du CTF.
	 * Pour le moment, seules des vérifications de base sont faites, donc svp usez de votre bon sens de sysadmin...
	 */
	
	if (!isset($viaCMD))
	{
		session_start();
		error_log("Connexion à installer.php via GET normal");
		error_log("Je recommande plutôt d'utiliser le panneau admin...");
		
		//Charger les variables d'environnements
		$env = file_get_contents(realpath("../.env"));
		$lines = explode("\n",$env);

		foreach($lines as $line){
			preg_match("/([^#]+)\=(.*)/",$line,$matches);
			if(isset($matches[2])){ 
				putenv(preg_replace("/\"$/","", preg_replace("/(^[a-zA-Z0-9_\-]+=)\"/m","$1",trim($line)))); 
			}
		}
		
		if (!isset($_SESSION['equipe']) || $_SESSION['equipe'] != "admins")
		//Qqun qui n'est pas supposé sait que cette page existe
		{
			http_response_code(404);
			exit;
		}
		
		//Lecture des fichiers de configuration
		$equipes = json_decode(file_get_contents(getenv("RACINE_SERVEUR")."/equipes.json"), true)['equipes_ctfd'];
		$machines = json_decode(file_get_contents(getenv("RACINE_SERVEUR") . "/machines.json"), true)['defis-dynamiques'];
	}
	else
	{
		require_once "SSE.php";
		error_log("Connexion à installer.php via SSE et cmd.php");
	}
	
	
	if (count($equipes) != count($machines))
	//Il manque une machine de défis dynamique ou on en a de trop
	{
		if ($viaCMD)
		{repondre(str_replace("ERR_CODE", "500 Internal Error<br>Pas autant de machines que d'équipes.", file_get_contents("erreur.html")), 500);}
		else
		{
			http_response_code(500);
			echo str_replace("ERR_CODE", "500 Internal Error<br>Il y a " . count($equipes) . " équipes pour " . count($machines) . " machines de défis dynamiques...", file_get_contents("erreur.html"));
		}
		exit;
	}
	
	//TODO: Plus de code de vérification à venir ici...
	
	//Configuration des VMs et installation des défis dynamiques dessus
	for ($i = 0; $i < count($machines); $i++)
	{
		if (!isset($machines[$i]['est_admin']) || $machines[$i]['est_admin'] != true)
		{
			if ($viaCMD)
			{repondre("Configuration de " . $machines[$i]['ip'] . " pour l'équipe $i (" . $equipes[$i]['nom'] . ")...", 102);}
			error_log("Équipe $i:\nConfiguration de la VM...");
			shell_exec("scp " . getenv("RACINE_SERVEUR") . "/configVM.sh" . " scp://root@" . $machines[$i]['ip'] . "/configVM.sh");
			shell_exec("ssh root@" . $machines[$i]['ip'] . " -t \"chmod +x /root/configVM.sh\"");
			shell_exec("ssh root@" . $machines[$i]['ip'] . " -t \"NOM='" . $equipes[$i]['nom'] . "' MDP='" . $equipes[$i]['mdp'] . "' /root/configVM.sh &> /root/configVM.log\"");
			shell_exec("scp " . getenv("RACINE_SERVEUR") . "/gererDefi.sh" . " scp://root@" . $machines[$i]['ip'] . "//home/" . $equipes[$i]['nom'] . "/gererDefi.sh");
			shell_exec("ssh root@" . $machines[$i]['ip'] . " -t \"chmod +x /home/" . $equipes[$i]['nom'] . "/gererDefi.sh\"");
			
			if ($viaCMD)
			{repondre("Envoi des défis sur " . $machines[$i]['ip'] . " pour l'équipe $i (" . $equipes[$i]['nom'] . ")...", 102);}
			error_log("Machine configurée. Envoi des défis...");
			shell_exec("scp " . getenv("RACINE_SERVEUR") . "/configDefis.sh" . " scp://root@" . $machines[$i]['ip'] . "/configDefis.sh");
			shell_exec("ssh root@" . $machines[$i]['ip'] . " -t \"chmod +x /root/configDefis.sh\"");
			foreach ($_SESSION['defis'] as $defi)
			{shell_exec("scp -r $defi[path] scp://root@" . $machines[$i]['ip'] . "/defis/\$(basename '$defi[path]')");}
			shell_exec("ssh root@" . $machines[$i]['ip'] . " -t \"NOM='" . $equipes[$i]['nom'] . "' /root/configDefis.sh &> /root/configDefis.log\"");
			
			if ($viaCMD)
			{repondre("Redémarrage de " . $machines[$i]['ip'] . " pour l'équipe $i (" . $equipes[$i]['nom'] . ")...", 102);}
			error_log("Défis installés. Redémarrage de la VM...");
			shell_exec("ssh root@" . $machines[$i]['ip'] . " -t \"reboot && logout\"");
		}
		else
		{error_log("On skip la machine #$i (machine admin)...");}
	}
	
	//Ce qui suit est l'interface visuelle (HTML) de base fournie par ce script.
	//Son utilisation n'est vraiment pas recommandée, principalement parce que la page va prendre un million d'années à charger sans qu'aucun indice de progression ne soit donné (sauf dans les logs du serveur).
?>

<!DOCTYPE html>

<html>
<head>
	<meta encoding=UTF-8>
	<title>GDDD</title>
	<link rel=stylesheet href=/style.css>
	<link rel=icon href=/logo-small.svg>
</head>

<body>
	<nav>
		<a href=/><img src=/logo-large.svg style="width: 180px"></a>
	</nav>
	
	<page>
		<h1>Défis installés</h1>
		<p>Vérifiez les logs sur une VM pour voir s'il y a eu des erreurs sur celles-ci, puisque GDDD ne s'en rendra probablement pas compte.</p>
	</page>
	
	<footer>
		<p><strong>GDDD vD0.5</strong>&nbsp;&nbsp;(<a href=https://github.com/nico64-64/gddd/tree/version-D#readme>code source</a>)</p>
	</footer>
</body>
</html>
