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
	
	function repondre ($msg, $statut)
	//Répond au client via HTTP SSE.
	//Envoie dans l'objet JSON "data" un JSON comportant un code HTTP de statut ($statut) et un message (pas toujours utilisé côté client).
	{
		echo "data: " . json_encode(['msg' => $msg, 'statut' => $statut]) . "\n\n";
		if (function_exists('ob_flush'))
		{@ob_flush();}
		flush();
	}
	
	
	if (!isset($_SESSION['equipe']))
	//Un petit finfinaud essaie d'accéder à la page sans s'être connecté
	{
		http_response_code(401);
		echo str_replace("ERR_CODE", "401 Unauthorized", file_get_contents("erreur.html"));
		exit;
	}
	
	if (!isset($_GET['defi']) || !isset($_SESSION['defis'][$_GET['defi']]))
	//Un petit finfinaud essaie d'accéder à un défi inexistant
	{
		http_response_code(422);
		echo str_replace("ERR_CODE", "422 Unprocessable Content", file_get_contents("erreur.html"));
		exit;
	}
	
	//Lecture du fichier de configuration des machines:
	$machines = json_decode(file_get_contents(getenv("RACINE_SERVEUR", "..") . "/machines.json"), true)['defis-dynamiques'];
	
	//Configuration SSE:
	header('Content-Type: text/event-stream');
	header('Cache-Control: no-cache, no-store, must-revalidate');
	header('Pragma: no-cache');
	header('Expires: 0');
	header('Connection: keep-alive');
	
	
	if ($_GET['type'] == "demarrer")
	//Démarrage du défi
	{		
		if (file_exists(getenv("RACINE_SERVEUR", "..") . "/lockfiles/" . basename($_SESSION['defis'][$_GET['defi']]['path']) . ".$_SESSION[num_equipe].lock") || file_exists(getenv("RACINE_SERVEUR", "..") . "/lockfiles/" . basename($_SESSION['defis'][$_GET['defi']]['path']) . ".all.lock"))
		{
			repondre(str_replace("ERR_CODE", "503 Service Unavailable", file_get_contents("erreur.html")), 503);
			exit;
		}
		
		if (!file_put_contents(getenv("RACINE_SERVEUR", "..") . "/lockfiles/" . basename($_SESSION['defis'][$_GET['defi']]['path']) . ".$_SESSION[num_equipe].lock", "...", LOCK_EX))
		{repondre(str_replace("ERR_CODE", "409 Conflict", file_get_contents("erreur.html")), 409);}
		else
		{
			$_SESSION['defis'][$_GET['defi']]['port'] = 2000 + $_SESSION['num_equipe'] * 1000 + rand(1, 999);
			session_write_close(); //On ne peut plus ÉCRIRE dans $_SESSION après ça, mais ça m'assure que le port y est bien ajouté
			repondre('Démarrage...', 100); //seul le statut est utilisé
			shell_exec("ssh root@" . $machines[$_SESSION['num_equipe']]['ip'] . " -t \"NOM='" . $_SESSION['equipe'] . "' DEFI='" . basename($_SESSION['defis'][$_GET['defi']]['path']) . "' PORT=" . $_SESSION['defis'][$_GET['defi']]['port'] . " /home/" . $_SESSION['equipe'] . "/gererDefi.sh -d &> /home/$_SESSION[equipe]/gererDefi.log\"");
			error_log("Service en ligne au port " . $_SESSION['defis'][$_GET['defi']]['port'] . " de l'adresse " . $machines[$_SESSION['num_equipe']]['ip']);
			sleep(1); //Laisse un peu de temps pour être sûr que tout est bien parti
			repondre('Terminé!', 204); //seul le statut est utilisé
		}
	}
	else
	//Suppression de l'instance
	{
		if (!file_exists(getenv("RACINE_SERVEUR", "..") . "/lockfiles/" . basename($_SESSION['defis'][$_GET['defi']]['path']) . ".$_SESSION[num_equipe].lock") || file_exists(getenv("RACINE_SERVEUR", "..") . "/lockfiles/" . basename($_SESSION['defis'][$_GET['defi']]['path']) . ".all.lock"))
		{
			repondre(str_replace("ERR_CODE", "503 Service Unavailable", file_get_contents("erreur.html")), 503);
			exit;
		}
		
		repondre('Supression...', 100); //seul le statut est utilisé
		shell_exec("ssh root@" . $machines[$_SESSION['num_equipe']]['ip'] . " -t \"NOM='" . $_SESSION['equipe'] . "' DEFI='" . basename($_SESSION['defis'][$_GET['defi']]['path']) . "' PORT=" . $_SESSION['defis'][$_GET['defi']]['port'] . " /home/" . $_SESSION['equipe'] . "/gererDefi.sh -a &> /home/$_SESSION[equipe]/gererDefi.log\"");
		sleep(1); //Laisse un peu de temps pour être sûr que tout est bien fermé
		unlink(getenv("RACINE_SERVEUR", "..") . "/lockfiles/" . basename($_SESSION['defis'][$_GET['defi']]['path']) . ".$_SESSION[num_equipe].lock");
		repondre('Terminé!', 204); //seul le statut est utilisé
	}
?>
