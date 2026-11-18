<?php
	session_start();
	
	//Charger les variables d'environnements
	$env = file_get_contents(realpath("../env/.env"));
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
	$equipes = json_decode(file_get_contents(getenv("RACINE_SERVEUR","..")."/equipes.json"), true)['equipes_ctfd'];
	$machines = json_decode(file_get_contents(getenv("RACINE_SERVEUR", "..") . "/machines.json"), true)['defis-dynamiques'];
	
	if (count($equipes) - 1 != count($machines)) //le -1 sert à compenser pour l'équipe "admins" qui n'a pas de machine de défis dynamiques
	//Il manque une machine de défis dynamique ou on en a de trop
	{
		http_response_code(500);
		echo str_replace("ERR_CODE", "500 Internal Error<br>Il y a " . count($equipes) - 1 . " équipes pour " . count($machines) . " machines de défis dynamiques...", file_get_contents("erreur.html"));
		exit;
	}
	
	//Configuration des VMs et installation des défis dynamiques dessus
	for ($i = 0; $i < count($machines); $i++)
	{
		error_log("Équipe $i:\nConfiguration de la VM...");
		shell_exec("scp " . getenv("RACINE_SERVEUR", "..") . "/configVM.sh" . " scp://root@" . $machines[$i]['ip'] . "/configVM.sh");
		shell_exec("ssh root@" . $machines[$i]['ip'] . " -t \"chmod +x /root/configVM.sh\"");
		shell_exec("ssh root@" . $machines[$i]['ip'] . " -t \"NOM='" . $equipes[$i]['nom'] . "' MDP='" . $equipes[$i]['mdp'] . "' /root/configVM.sh &> /root/configVM.log\"");
		
		error_log("Machine configurée. Envoi des défis...");
		shell_exec("scp " . getenv("RACINE_SERVEUR", "..") . "/configDefis.sh" . " scp://root@" . $machines[$i]['ip'] . "/configDefis.sh");
		shell_exec("ssh root@" . $machines[$i]['ip'] . " -t \"chmod +x /root/configDefis.sh\"");
		foreach ($_SESSION['defis'] as $defi)
		{shell_exec("scp -r $defi[path] scp://root@" . $machines[$i]['ip'] . "/defis/\$(basename '$defi[path]')");}
		shell_exec("ssh root@" . $machines[$i]['ip'] . " -t \"NOM='" . $equipes[$i]['nom'] . "' /root/configDefis.sh &> /root/configDefis.log\"");
		
		error_log("Défis installés. Redémarrage de la VM...");
		shell_exec("ssh root@" . $machines[$i]['ip'] . " -t \"reboot && logout\"");
	}
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
		<p><strong>GDDD vD0.4</strong>&nbsp;&nbsp;(<a href=https://github.com/nico64-64/gddd/tree/version-D#readme>code source</a>)</p>
	</footer>
</body>
</html>
