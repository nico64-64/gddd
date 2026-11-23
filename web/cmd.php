<?php
	/*  cmd.php
	 * =========
	 * API pour exécuter une commande d'administration de GDDD.
	 * Ce fichier ne fournit aucune interface visuelle.
	 * Ce fichier doit être appelé avec les paramètres cmd, equipe et defi (selon ce qui est nécessaire) en paramètre GET.
	 * La manière la plus simple de l'utiliser est de passer par le panneau d'administration de GDDD.
	 * Cette page n'est accessible qu'à l'équipe des administrateurs du CTF.
	 */
	
	
	session_start();
	require_once "SSE.php";
	
	
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
	//Quelqu'un qui n'est pas supposé sait que cette page existe
	{
		http_response_code(404);
		exit;
	}
	
	if (!isset($_GET['cmd']))
	//Quelqu'un essaie d'afficher cette page dans son browser
	{
		echo str_replace("ERR_CODE", "400 Bad Request", file_get_contents("erreur.html"));
		exit;
	}
	
	//Lecture des informations nécessaires
	$equipes = json_decode(file_get_contents(getenv("RACINE_SERVEUR","..")."/equipes.json"), true)['equipes_ctfd'];
	$machines = json_decode(file_get_contents(getenv("RACINE_SERVEUR", "..") . "/machines.json"), true)['defis-dynamiques'];
	
	
	//Configuration SSE:
	header('Content-Type: text/event-stream');
	header('Cache-Control: no-cache, no-store, must-revalidate');
	header('Pragma: no-cache');
	header('Expires: 0');
	header('Connection: keep-alive');
	
	@error_log("cmd: $_GET[cmd]; defi: $_GET[defi]; equipe: $_GET[equipe];");
	switch ($_GET['cmd'])
	{
		case "installer":
			repondre("tranfert", 100);
			session_write_close(); //permet à installer.php d'utiliser $_SESSION
			$viaCMD = true; //indique à installer.php qu'il est appelé par ce script
			require "installer.php"; //tout le travail se fait dans installer.php
			repondre("terminé", 204);
			break;
		
		case "barrer":
			if (!isset($_GET['defi']) || !isset($_GET['equipe']))
			{repondre(str_replace("ERR_CODE", "400 Bad Request", file_get_contents("erreur.html")), 400);}
			else if (!file_exists(getenv("RACINE_SERVEUR", "..") . "/lockfiles/" . basename($_SESSION['defis'][$_GET['defi']]['path']) . ".$_GET[equipe].lock"))
			{
				repondre("supression...", 100);
				if (!file_put_contents(getenv("RACINE_SERVEUR", "..") . "/lockfiles/" . basename($_SESSION['defis'][$_GET['defi']]['path']) . ".$_GET[equipe].lock", "...", LOCK_EX))
				{repondre(str_replace("ERR_CODE", "409 Conflict", file_get_contents("erreur.html")), 409);}
				else
				{repondre("terminé", 204);}
			}
			else
			{repondre(str_replace("ERR_CODE", "409 Conflict<br>Lockfile déjà créé", file_get_contents("erreur.html")), 409);}
			break;
		
		case "debarrer":
			if (!isset($_GET['defi']) || !isset($_GET['equipe']))
			{repondre(str_replace("ERR_CODE", "400 Bad Request", file_get_contents("erreur.html")), 400);}
			else if (file_exists(getenv("RACINE_SERVEUR", "..") . "/lockfiles/" . basename($_SESSION['defis'][$_GET['defi']]['path']) . ".$_GET[equipe].lock"))
			{
				repondre("supression...", 100);
				unlink(getenv("RACINE_SERVEUR", "..") . "/lockfiles/" . basename($_SESSION['defis'][$_GET['defi']]['path']) . ".$_GET[equipe].lock");
				repondre("terminé", 204);
			}
			else
			{repondre(str_replace("ERR_CODE", "409 Conflict<br>Lockfile inexistant", file_get_contents("erreur.html")), 409);}
			break;
		
		case "fermer":
			if (!isset($_GET['defi']) || !isset($_GET['equipe']) || $_GET['equipe'] == "all")
			{repondre(str_replace("ERR_CODE", "400 Bad Request", file_get_contents("erreur.html")), 400);}
			else if ($equipes[$_GET['equipe']]['nom'] == "admins")
			{repondre(str_replace("ERR_CODE", "403 Forbidden", file_get_contents("erreur.html")), 403);}
			else if (file_exists(getenv("RACINE_SERVEUR", "..") . "/lockfiles/" . basename($_SESSION['defis'][$_GET['defi']]['path']) . ".$_GET[equipe].lock"))
			{
				repondre("fermeture...", 100);
				shell_exec("ssh root@" . $machines[$_GET['equipe']]['ip'] . " -t \"NOM='" . $equipes[$_GET['equipe']]['nom'] . "' DEFI='" . basename($_SESSION['defis'][$_GET['defi']]['path']) . "' PORT=0 /home/" . $equipes[$_GET['equipe']]['nom'] . "/gererDefi.sh -a &> /home/" . $equipes[$_GET['equipe']]['nom'] . "/gererDefi.log\""); //je me fout du port vu qu'on fait juste détruire l'instance
				sleep(1); //Laisse un peu de temps pour être sûr que tout est bien fermé
				unlink(getenv("RACINE_SERVEUR", "..") . "/lockfiles/" . basename($_SESSION['defis'][$_GET['defi']]['path']) . ".$_GET[equipe].lock");
				repondre("terminé", 204);
			}
			else
			{repondre(str_replace("ERR_CODE", "409 Conflict<br>Défi déjà arrêté", file_get_contents("erreur.html")), 409);}
			break;
		
		case "bloquer":
			if (!isset($_GET['equipe']))
			{repondre(str_replace("ERR_CODE", "400 Bad Request", file_get_contents("erreur.html")), 400);}
			else if ($equipes[$_GET['equipe']]['nom'] == "admins")
			{repondre(str_replace("ERR_CODE", "403 Forbidden", file_get_contents("erreur.html")), 403);}
			else if (!file_exists(getenv("RACINE_SERVEUR", "..") . "/lockfiles/nologin.$_GET[equipe].lock"))
			{
				repondre("bloquage...", 100);
				if (!file_put_contents(getenv("RACINE_SERVEUR", "..") . "/lockfiles/nologin.$_GET[equipe].lock", "...", LOCK_EX))
				{repondre(str_replace("ERR_CODE", "409 Conflict", file_get_contents("erreur.html")), 409);}
				else
				{repondre("terminé", 204);}
			}
			else
			{repondre(str_replace("ERR_CODE", "409 Conflict<br>Équipe déjà bloquée", file_get_contents("erreur.html")), 409);}
			break;
		
		case "debloquer":
			if (!isset($_GET['equipe']))
			{repondre(str_replace("ERR_CODE", "400 Bad Request", file_get_contents("erreur.html")), 400);}
			else if ($equipes[$_GET['equipe']]['nom'] == "admins")
			{repondre(str_replace("ERR_CODE", "403 Forbidden", file_get_contents("erreur.html")), 403);}
			else if (file_exists(getenv("RACINE_SERVEUR", "..") . "/lockfiles/nologin.$_GET[equipe].lock"))
			{
				repondre("débloquage...", 100);
				unlink(getenv("RACINE_SERVEUR", "..") . "/lockfiles/nologin.$_GET[equipe].lock");
				repondre("terminé", 204);
			}
			else
			{repondre(str_replace("ERR_CODE", "409 Conflict<br>Équipe déjà débloquée", file_get_contents("erreur.html")), 409);}
			break;
		
		case "login":
			if (!isset($_GET['equipe']))
			{repondre(str_replace("ERR_CODE", "400 Bad Request", file_get_contents("erreur.html")), 400);}
			else if ($equipes[$_GET['equipe']]['nom'] == "admins")
			{repondre(str_replace("ERR_CODE", "418 I'm a teapot", file_get_contents("erreur.html")), 418);}
			else
			{
				$_SESSION['num_equipe'] = $_GET['equipe'];
				$_SESSION['equipe'] = $equipes[$_GET['equipe']]['nom'];
				repondre("/dashboard.php?reload=1", 303);
			}
			break;
		
		case "quitter":
			//TODO: Code de vérification à venir ici...
			repondre("fermeture...", 100);
			foreach ($machines as $machine)
			{
				if (!isset($machine['est_admin']) || $machine['est_admin'] != true)
				{shell_exec("ssh root@$machine[ip] -t \"shutdown now\"");}
			}
			//TODO: Plus de code de sauvegarde et de fermeture à venir ici...
			repondre("terminé", 204);
			break;
		
		default:
			repondre(str_replace("ERR_CODE", "400 Bad Request<br>Commande non implémentée", file_get_contents("erreur.html")), 400);
			break;
	}
?>
