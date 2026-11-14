<?php
	//Attention, PHP cryptique, usages multiples et injection de fichiers.
	
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
	
	function filtre_dossiers ($dossier)
	{
		global $path_actuel;
		if ($dossier == "." || $dossier == "..")
		{return false;}
		return is_dir($path_actuel . $dossier);
	}
	
	
	function filtre_defis ($defi)
	{
		if ($defi['type'] == "dynamique" && $defi['etat'] == "visible")
		{return true;}
		else
		{return false;}
	}
	
	
	function charger_defis ()
	{
		global $path_actuel;
		$defis = [];
		$path_actuel = getenv("RACINE_SERVEUR","..")."/defis/";
		$categories = scandir(getenv("RACINE_SERVEUR","..")."/defis");
		$categories = array_filter($categories, "filtre_dossiers");
		foreach ($categories as $categorie)
		{
			$path_actuel = getenv("RACINE_SERVEUR","..")."/defis/$categorie/";
			$liste_defis = scandir(getenv("RACINE_SERVEUR","..")."/defis/$categorie/");
			$liste_defis = array_filter($liste_defis, "filtre_dossiers");
			
			foreach ($liste_defis as $defi)
			{
				$defis[] = json_decode(shell_exec("yq . \"".getenv("RACINE_SERVEUR","..")."/defis/$categorie/$defi/description.yaml\""), true);
				$defis[count($defis) - 1]['path'] = "\"".getenv("RACINE_SERVEUR","..")."/defis/$categorie/$defi/\"";
				
				if (file_exists(str_replace("\"", "", $defis[count($defis) - 1]['path'] . $_SESSION['num_equipe'] . ".lock")) || file_exists(str_replace("\"", "", $defis[count($defis) - 1]['path'] . "all.lock")))
				{$defis[count($defis) - 1]['statut'] = "occupe";}
				else
				{$defis[count($defis) - 1]['statut'] = "pret";}
			}
		}
		return array_values(array_filter($defis, "filtre_defis"));
	}
	
	
	if (!isset($_SESSION['equipe']))
	//Le participant n'a pas encore été identifié
	{
		$equipes = json_decode(file_get_contents(getenv("RACINE_SERVEUR","..")."/equipes.json"), true)['equipes_ctfd'];
		
		if (!$_POST)
		//Un petit finfinaud essaie d'accéder au portail sans se loguer
		{
			http_response_code(418);
			echo str_replace("ERR_CODE", "418 I'm a teapot", file_get_contents("erreur.html"));
			exit;
		}
		
		if ($_POST['mdp'] != $equipes[$_POST['num']]['mdp'])
		//Ou bien un grand perdu a entré le mauvais mot de passe...
		//...ou bien un petit finfinaud essaie de nous avoir en forgeant ses propres requêtes HTTP
		{
			http_response_code(401);
			echo str_replace("ERR_CODE", "401 Unauthorized", file_get_contents("erreur.html"));
			exit;
		}
		else
		//Un participant normal a réussi à se connecter
		{
			$_SESSION['equipe'] = $equipes[$_POST['num']]['nom']; //nom de l'équipe enregistré dans $_SESSION['equipe'] et utilisé pour vérifier si l'usager est connecté ou pas
			$_SESSION['num_equipe'] = $_POST['num']; //numéro d'équipe enregitré dans $_SESSION['num_equipe'] et utilisé pour créer un lockfile au nom de l'équipe
		}
	}
	
	if (!isset($_SESSION['dernier_reload']) || isset($_GET['reload']) || time() - $_SESSION['dernier_reload'] > 300)
	//Relecture des défis
	{
		$_SESSION['defis'] = charger_defis();
		$_SESSION['dernier_reload'] = time();
		header('Location: /dashboard.php', true, 302);
		exit;
	}
	
	if ((isset($_GET['defi']) && !isset($_SESSION['defis'][$_GET['defi']])) || (isset($_GET['demarre']) && !isset($_SESSION['defis'][$_GET['demarre']])))
	//Un petit finfinaud essaie d'accéder à un défi inexistant
	{
		http_response_code(422);
		echo str_replace("ERR_CODE", "422 Unprocessable Content", file_get_contents("erreur.html"));
		exit;
	}
?>

<!DOCTYPE html>

<html>
<head>
	<meta encoding=UTF-8>
	<title>GDDD</title>
	<link rel=stylesheet href=/style.css>
	<link rel=icon href="/<?php echo getenv("ICONE_SERVEUR") ?>">
	
	<style>
		.defi
		{
			text-align: left;
			border-radius: 10px;
			box-shadow: 0px 7px 10px 0px rgba(0,0,0,0.3);
			padding: 20px 30px;
			display: flex;
			gap: 20px;
			align-items: center;
			margin: 20px 0px;
			background-color: white;
			height: 40px;
		}
		
		.defi h4
		{
			width: 30%;
		}
		
		a.demarrer, a.arreter
		{
			color: white;
			font-size: 15px;
			font-weight: bold;
			padding: 5px 10px;
			border-radius: 8px;
			text-decoration: none;
			margin-left: auto;
			user-select: none;
			cursor: pointer;
			box-shadow: 0px 5px 10px 0px rgba(0,0,0,0.3);
		}
		
		a.demarrer
		{
			background-color: green;
		}
		
		a.arreter
		{
			background-color: darkred;
		}
		
		a.demarrer:hover, a.arreter:hover
		{
			box-shadow: 0px 3px 10px 0px rgba(0,0,0,0.3);
		}
		
		.defi .txt-resolu
		{
			margin-left: auto;
		}
		
		.tag
		{
			color: white;
			font-weight: bold;
			font-size: 15px;
			border: 1px solid black;
			padding: 0px 5px;
			border-radius: 10px;
			width: 100px;
			text-align: center;
		}
		
		.non-resolu
		{
			background-color: #E67102;
		}
		
		.nouveau
		{
			background-color: blue;
		}
		
		.resolu
		{
			background-color: green;
		}
		
		#conteneur-modale
		{
			background-color: rgba(0,0,0,0.4);
			position: absolute;
			top: 0;
			left: 0;
			width: 100%;
			height: 100%;
			<?php
				if (isset($_GET['defi']) || isset($_GET['demarre']))
				{echo "display: flex;";}
				else
				{echo "display: none;";}
			?>
			align-items: center;
			justify-content: center;
		}
		
		#modale
		{
			background-color: #F0F0F0;
			padding: 30px;
			border-radius: 15px;
			max-width: 80%;
			max-height: 80%;
			overflow-y: scroll;
		}
		
		#modale h2
		{
			margin: 0px 0px 30px 0px;
		}
		
		#modale #header-modale
		{
			display: flex;
		}
		
		#modale #header-modale a
		{
			font-size: 30px;
			font-weight: bold;
			margin-left: auto;
			margin-top: -15px;
			height: 30px;
			display: block;
			cursor: pointer;
			text-decoration: none;
			color: black;
		}
		
		.loading
		{
			text-align: center;
			display: inline-block;
		}
	</style>
</head>

<body>
	<nav>
		<a href=/><img src=/<?php echo getenv("LOGO_SERVEUR") ?> style="width: 180px"></a>
	</nav>
	
	<page style="position: relative">
		<h1>Tableau de bord GDDD</h1>
		
		<?php echo "<h2>$_SESSION[equipe]</h2>"; ?>
		
		<p id=timer-refresh>Svp activer javascript</p>
		<div class=loading id=loading-reload style="display: none"></div>
		<a class=bouton id=btn-reload onclick=recharger()>Recharger</a>
		
		<div id=conteneur-defis>
			<?php
				if (count($_SESSION['defis']) == 0)
				{echo "<p>Aucun défi n'est actuellement disponible :(<br>Reviens plus tard ou va pousser dans le dos les admins.</p>";}
				for ($compteur = 0; $compteur < count($_SESSION['defis']); $compteur++)
				{
					echo "<div class=defi><h4>" . $_SESSION['defis'][$compteur]['titre'] . "</h4><div class=\"tag nouveau\">Nouveau</div><div class=valeur>" . $_SESSION['defis'][$compteur]['pointage']['initial'] . " points</div>";
					if ($_SESSION['defis'][$compteur]['statut'] == "pret")
					{echo "<a class=demarrer href=/dashboard.php?defi=$compteur>Démarrer</a></div>";}
					else if ($_SESSION['defis'][$compteur]['statut'] == "occupe")
					{echo "<a class=arreter href=/dashboard.php?defi=$compteur>Arrêter</a></div>";}
					else
					{echo "<div class=txt-resolu>Erreur: état inconnu</div>";}
				}
			?>
		</div>
		
		<?php
			if ($_SESSION['equipe'] == "admins")
			{
				echo "<details><summary>Détails de débogage</summary><pre>" . print_r($_SESSION['defis'], true) . "</pre></details>";
			}
		?>
		
		<div id=conteneur-modale>
			<div id=modale>
				<?php
					if (isset($_GET['defi']))
					{
						echo "<div id=header-modale><h2>" . $_SESSION['defis'][$_GET['defi']]['titre'] . "</h2><a href=/dashboard.php id=x-modale>&times;</a></div>";
						
						if ($_SESSION['defis'][$_GET['defi']]['statut'] == "pret")
						//défi à démarrer
						{
							
							echo "<p style=\"text-align: left\" id=msg-modale>" . str_replace("\n", "<br>", $_SESSION['defis'][$_GET['defi']]['description']) . "</p>";
							echo "<a class=bouton id=bouton-modale onclick=demarrerDefi()>Démarrer le défi</a>";
							echo "<div class=loading style=\"display: none\"></div>";
						}
						else if ($_SESSION['defis'][$_GET['defi']]['statut'] == "occupe")
						//défi à arrêter
						{
							echo "<p style\"text-align: left\" id=msg-modale>Une instance de ce défi est présentement en cours d'exécution.<br>Êtes-vous sûr de vraiment vouloir arrêter cette instance?</p>";
							echo "<a class=bouton id=bouton-modale onclick=arreterDefi() style=\"background-color: darkred\">Arrêter le défi</a>";
							echo "<div class=loading style=\"display: none\"></div>";
						}
						else
						//défi au statut inconnu
						{
							echo "<p>Erreur!<br>Le défi se trouve dans un état inconnu.<br>Veuillez contacter un admin et lui fournir les renseignements suivants:</p>";
							echo "<pre style=\"text-align: left\">GET & POST:\n" . print_r($_REQUEST, true) . "\nDéfi:\nid=" . print_r($_SESSION['defis'][$_GET['defi']]['id'], true)  . "\ntitre=" . print_r($_SESSION['defis'][$_GET['defi']]['titre'], true) . "\nstatut=" . print_r($_SESSION['defis'][$_GET['defi']]['statut'], true) . "</pre>";
						}
					}
					else if (isset($_GET['demarre']))
					{
						echo "<div id=header-modale><h2>" . $_SESSION['defis'][$_GET['demarre']]['titre'] . "</h2><a href=/dashboard.php id=x-modale>&times;</a></div>";
						
						echo "<p>Connectez-vous à " . $_SESSION['defis'][$_GET['demarre']]['statiques-dynamiques']['domaine'] . " par " . $_SESSION['defis'][$_GET['demarre']]['statiques-dynamiques']['protocole'] . ".</p>";
						echo "<a class=bouton href=/dashboard.php?reload=1>Ok</a>";
					}
				?>
			</div>
		</div>
	</page>
	
	<footer>
		<p><strong>GDDD vD0.2</strong>&nbsp;&nbsp;(<a href=https://github.com/nico64-64/gddd/tree/version-D#readme>code source</a>)</p>
	</footer>
	
	<script>
		// Gestion du reload:
		
		timer = document.getElementById('timer-refresh');
		const dernier_reload = "<?php echo $_SESSION['dernier_reload'] * 1000; ?>";
		
		function recharger ()
		{
			clearInterval(intervalId);
			document.getElementById('btn-reload').style.display = "none";
			document.getElementById('loading-reload').style.display = "inline-block";
			window.location.href = "/dashboard.php?reload=1";
		}
		
		function verifierReload ()
		{
			timer.innerHTML = "Dernière mise à jour il y a " + Math.floor((Date.now() - dernier_reload) / 60000) + " minute(s) " + Math.floor(((Date.now() - dernier_reload) % 60000) / 1000) + " secondes";
			if (Date.now() - dernier_reload > 300000)
			{recharger();}
		}
		
		const intervalId = setInterval(verifierReload, 1000);
		verifierReload();
		
		<?php
			if (isset($_GET['defi']))
			{
				echo "defi = $_GET[defi];";
				echo preg_replace("/\/\*\*\*[\s\S]*?\*\*\*\//", "", file_get_contents('deploiement.js'));
				//preg_replace agit comme str_replace, mais en utilisant des regex PCRE
				//ici, je supprime les commentaires de format /*** commentaire ***/
			}
		?>
	</script>
</body>
</html>
