<?php
	session_start();
	
	//Charger les variables d'environnements
	$env = file_get_contents("/.env");
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
	
	//Configuration SSE:
	header('Content-Type: text/event-stream');
	header('Cache-Control: no-cache, no-store, must-revalidate');
	header('Pragma: no-cache');
	header('Expires: 0');
	header('Connection: keep-alive');
	
	if ((!file_exists(str_replace("\"", "", $_SESSION['defis'][$_GET['defi']]['path'] . $_SESSION['num_equipe'] . ".lock")) && $_GET['type'] == "arreter") || ($_GET['type'] == "demarrer" && (file_exists(str_replace("\"", "", $_SESSION['defis'][$_GET['defi']]['path'] . $_SESSION['num_equipe'] . ".lock")) || file_exists(str_replace("\"", "", $_SESSION['defis'][$_GET['defi']]['path'] . "all.lock")))))
	//Vérifie si le défi est actuellement bloqué ou occupé et qu'on veut le démarrer ou s'il est déjà arrêté et qu'on veut l'arrêter
	{
		repondre(str_replace("ERR_CODE", "503 Service Unavailable", file_get_contents("erreur.html")), 503);
		error_log("[503 Service Unavailable]");
		exit;
	}
	
	if ($_GET['type'] == "demarrer")
	//Démarrage du défi
	{
		if ($_SESSION['defis'][$_GET['defi']]['statiques-dynamiques']['protocole'] == "SSH")
		{$port = 2200 + $_SESSION['num_equipe'];}
		else
		{$port = 8000 + $_SESSION['num_equipe'];}
		repondre('Démarrage...', 100); //seul le statut est utilisé
		shell_exec("cd " . $_SESSION['defis'][$_GET['defi']]['path'] . " && EQUIPE=$_SESSION[num_equipe] PORT=$port \"./" . $_SESSION['defis'][$_GET['defi']]['script_instanciation'] . "\"");
		error_log("Lancement du défi " . $_SESSION['defis'][$_GET['defi']]['id'] . ": cd " . $_SESSION['defis'][$_GET['defi']]['path'] . " && EQUIPE=$_SESSION[num_equipe] \"./" . $_SESSION['defis'][$_GET['defi']]['script_instanciation'] . "\"");
		error_log("Port $port à l'adresse " . $_SESSION['defis'][$_GET['defi']]['statiques-dynamiques']['domaine']);
		repondre('Prêt!', 200); //seul le statut est utilisé
	}
	else
	{
		repondre('Démarrage...', 100); //seul le statut est utilisé
		shell_exec("cd " . $_SESSION['defis'][$_GET['defi']]['path'] . " && EQUIPE=$_SESSION[num_equipe] \"./" . $_SESSION['defis'][$_GET['defi']]['script_destruction'] . "\"");
		repondre('Terminé!', 200); //seul le statut est utilisé
	}
?>
