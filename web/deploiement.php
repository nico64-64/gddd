<?php
	/*  deploiement.php
	 * =================
	 * API pour démarrer ou arrêter une instance d'un défi pour une équipe.
	 * Ce fichier ne fournit aucune interface visuelle.
	 * Ce fichier doit être appelé avec les paramètres type ("demarrer" ou "arreter") et defi (numéro de défi) en paramètres GET.
	 * La manière la plus simple de l'utiliser est de passer par le tableau de bord de GDDD.
	 * Ce script ne doit être utilisé que par les participants du CTF. Les administrateurs devrait plutôt utiliser cmd.php via le panneau d'administration de GDDD.
	 */
	 
	 session_start();
	 require_once "outils.php";
	
	//Charger les variables d'environnements
	$env = file_get_contents(realpath("../.env"));
	$lines = explode("\n",$env);

	foreach($lines as $line){
		preg_match("/([^#]+)\=(.*)/",$line,$matches);
		if(isset($matches[2])){ 
			putenv(preg_replace("/\"$/","", preg_replace("/(^[a-zA-Z0-9_\-]+=)\"/m","$1",trim($line)))); 
		}
	}
	
	
	if (!isset($_SESSION['equipe']))
	//Un petit finfinaud essaie d'accéder à la page sans s'être connecté
	{
		http_response_code(401);
		echo str_replace("ERR_CODE", "401 Unauthorized", file_get_contents("erreur.html"));
		exit;
	}
	
	if (!isset($_GET['defi']) || !isset($_SESSION['defis'][$_GET['defi']]) || !isset($_GET['type']))
	//Un petit finfinaud essaie d'accéder à un défi inexistant
	{
		http_response_code(422);
		echo str_replace("ERR_CODE", "422 Unprocessable Content", file_get_contents("erreur.html"));
		exit;
	}
	
	//Lecture du fichier de configuration des machines:
	$machines = json_decode(file_get_contents(getenv("RACINE_SERVEUR", "..") . "/equipes.json"), true)['equipes'];

	
	//Configuration SSE:
	header('Content-Type: text/event-stream');
	header('Cache-Control: no-cache, no-store, must-revalidate');
	header('Pragma: no-cache');
	header('Expires: 0');
	header('Connection: keep-alive');
	
	if ($_SESSION['equipe'] == "admins")
	{
		repondre(str_replace("ERR_CODE", "403 Forbidden", file_get_contents("erreur.html")), 403);
		exit;
	}
	
	
	if ($_GET['type'] == "demarrer")
	//Démarrage du défi
	{		
		if (file_exists(getenv("RACINE_SERVEUR") . "/lockfiles/" . basename($_SESSION['defis'][$_GET['defi']]['path']) . ".$_SESSION[num_equipe].lock") || file_exists(getenv("RACINE_SERVEUR") . "/lockfiles/" . basename($_SESSION['defis'][$_GET['defi']]['path']) . ".all.lock"))
		//Le défi est déjà démarré ou bien il est barré pour tous
		{
			repondre(str_replace("ERR_CODE", "503 Service Unavailable", file_get_contents("erreur.html")), 503);
			exit;
		}
		if (nombre_instances() >= getenv("MAX_INSTANCES"))
		{
			repondre(str_replace("ERR_CODE", "424 Failed Dependency", file_get_contents("erreur.html")), 424);
			exit;
		}
		
		if (!file_put_contents(getenv("RACINE_SERVEUR") . "/lockfiles/" . basename($_SESSION['defis'][$_GET['defi']]['path']) . ".$_SESSION[num_equipe].lock", "...", LOCK_EX))
		{repondre(str_replace("ERR_CODE", "409 Conflict", file_get_contents("erreur.html")), 409);}
		else
		{
			$_SESSION['defis'][$_GET['defi']]['port'] = getenv("PORT_MINIMUM") + $_SESSION['num_equipe'] * 1000 + rand(1, 999);
			session_write_close(); //On ne peut plus ÉCRIRE dans $_SESSION après ça, mais ça m'assure que le port y est bien ajouté
			repondre('Démarrage...', 100); //seul le statut est utilisé
			usr=$machines[$_SESSION['num_equipe']]['machine']['usr'];
			ip=$machine[$_SESSION['num_equipe']]['machine']['ip'];
			mdp=$machine[$_SESSION['num_equipe']]['machine']['mdp'];
			shell_exec("ssh ".$usr."@".$ip." echo '".$mdp."' | sudo -sS bash -c \"export NOM='".$_SESSION['equipe']."'; export DEFI='".basename($_SESSION['defis'][$_GET['defi']]['path'])."'; export PORT=".$_SESSION['defis'][$_GET['defi']]['port']."; /home/".$_SESSION['equipe']."/gererDefi.sh -d &> /home/".$_SESSION['equipe']."/gererDefi.log\"");
			error_log("Service en ligne au port " . $_SESSION['defis'][$_GET['defi']]['port'] . " de l'adresse " . $machines[$_SESSION['num_equipe']]['ip']);
			sleep(1); //Laisse un peu de temps pour être sûr que tout est bien parti
			repondre('Terminé!', 204); //seul le statut est utilisé
		}
	}
	else
	//Suppression de l'instance
	{
		if (!file_exists(getenv("RACINE_SERVEUR") . "/lockfiles/" . basename($_SESSION['defis'][$_GET['defi']]['path']) . ".$_SESSION[num_equipe].lock") || file_exists(getenv("RACINE_SERVEUR") . "/lockfiles/" . basename($_SESSION['defis'][$_GET['defi']]['path']) . ".all.lock"))
		{
			repondre(str_replace("ERR_CODE", "503 Service Unavailable", file_get_contents("erreur.html")), 503);
			exit;
		}
		
		repondre('Supression...', 100); //seul le statut est utilisé
		usr=$machines[$_SESSION['num_equipe']]['machine']['usr'];
		ip=$machine[$_SESSION['num_equipe']]['machine']['ip'];
		mdp=$machine[$_SESSION['num_equipe']]['machine']['mdp'];
		shell_exec("ssh ".$usr."@".$ip." echo '".$mdp."' | sudo -sS bash -c \"export NOM='".$_SESSION['equipe']."'; export DEFI='".basename($_SESSION['defis'][$_GET['defi']]['path'])."'; export PORT=".$_SESSION['defis'][$_GET['defi']]['port']."; /home/".$_SESSION['equipe']."/gererDefi.sh -a &> /home/".$_SESSION['equipe']."/gererDefi.log\"");
		sleep(1); //Laisse un peu de temps pour être sûr que tout est bien fermé
		unlink(getenv("RACINE_SERVEUR") . "/lockfiles/" . basename($_SESSION['defis'][$_GET['defi']]['path']) . ".$_SESSION[num_equipe].lock");
		repondre('Terminé!', 204); //seul le statut est utilisé
	}
?>
