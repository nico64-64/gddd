<?php
	/*  dashboard.php
	 * ===============
	 * Interface utilisateur principale de GDDD.
	 * Cette page fournit aux participants du CTF un moyen d'instancier des défis déployés dynamiquement avec GDDD et de les arrêter.
	 * Cette page permet aussi aux participants de voir quels défis ils ont et peuvent instancier ainsi que quelques autres informations potentiellement utiles.
	 * Cette page est rechargée automatiquement à sa première visite, puis à chaque 5 minutes. Il est recommandé de forcer un rechargement complet en lui passant le paramètre GET reload=1 avant d'effectuer n'importe quelle action.
	 * Le chargement complet peut prendre quelques temps si les défis sont lus directement depuis leur description.yaml plutôt que de defis.json. Assurez-vous d'utiliser la fonction appropriée.
	 * Cette page, bien qu'accessible aux administrateurs du CTF, ne leur est pas d'une très grande utilité sauf pour recharger les défis si leur nombre a changé.
	 */
	
	
	//Attention: PHP cryptique, usages multiples et injection de fichiers.
	
	session_start();
	
	//Charger les variables d'environnements
	$env = file_get_contents(realpath("../.env"));
	$lines = explode("\n",$env);

	foreach($lines as $line){
		preg_match("/([^#]+)\=(.*)/",$line,$matches);
		if(isset($matches[2])){ 
			putenv(preg_replace("/\"$/","", preg_replace("/(^[a-zA-Z0-9_\-]+=)\"/m","$1",trim($line)))); 
		}
	} 
	
	function filtre_dossiers ($dossier)
	//Filtre un array pour n'y conserver que les valeurs qui sont des dossiers dans $path_actuel (variable globale).
	//À utiliser comme callback avec array_filter().
	// !! FONCTION DÉPRÉCIÉE !!
	//Cette fonction est seulement utilisée par charger_defis(), qui est dépréciée.
	{
		global $path_actuel;
		if ($dossier == "." || $dossier == "..")
		{return false;}
		return is_dir($path_actuel . $dossier);
	}
	
	
	function filtre_defis ($defi)
	//Filtre un array pour n'y conserver que les défis qui sont visibles et dynamiques.
	//À utiliser comme callback avec array_filter().
	{
		if ($defi['type'] == "dynamique" && $defi['etat'] == "visible")
		{return true;}
		else
		{return false;}
	}
	
	
	function nombre_instances ()
	//Compte les instances de défi en train de rouler pour cette équipe.
	//TODO: Compléter et vérifier cette fonction, ainsi que la logique qui devrait venir avec.
	{
		$nbre = 0;
		foreach ($_SESSION['defis'] as $defi)
		{
			if (file_exists(getenv("RACINE_SERVEUR", "..") . "/lockfiles/" . basename($_SESSION['defis'][$_GET['defi']]['path']) . ".$_SESSION[num_equipe].lock"))
			{$nbre++;}
		}
		return $nbre;
	}
	
	
	function charger_defis ()
	//Charge toutes les descriptions.yaml des défis déployées par GDDDvD dans un array renvoyé à la fin.
	//Cette fonction prend du temps à s'éexécuter parce qu'elle entre dans tous les dossiers de défis.
	// !! FONCTION DÉPRÉCIÉE !!
	//Utiliser plutôt charger_defis_json() pour lire le fichier JSON généré par le script de lancement de CTFd amalgamant l'ensemble des descriptions.yaml.
	//Cette fonction reste disponible au cas où le json ne serait pas disponible et fournit exactement les mêmes fonctionnalités que la nouvelle, mais prend plusieurs secondes à s'éexécuter, contrairement à la nouvelle.
	{
		global $path_actuel;
		if (isset($_SESSION['defis']))
		{$anciens_defis = array_column($_SESSION['defis'], null, 'path');}
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
				$defis[count($defis) - 1]['path'] = getenv("RACINE_SERVEUR","..") . "/defis/$categorie/$defi/";
				
				if (file_exists(getenv("RACINE_SERVEUR", "..") . "/lockfiles/" . basename($defis[count($defis) - 1]['path']) . ".$_SESSION[num_equipe].lock") || file_exists(getenv("RACINE_SERVEUR", "..") . "/lockfiles/" . basename($defis[count($defis) - 1]['path']) . ".all.lock"))
				{
					$defis[count($defis) - 1]['statut'] = "occupe";
					if (isset($anciens_defis))
					{$defis[count($defis) - 1]['port'] = $anciens_defis[$defis[count($defis) - 1]['path']]['port'];}
					else
					{$defis[count($defis) - 1]['port'] = -1;} //impossible de trouver le port, donc on met un placeholder, mais ça ne devrait jamais arriver anyway...
				}
				else
				{$defis[count($defis) - 1]['statut'] = "pret";}
			}
		}
		return array_values(array_filter($defis, "filtre_defis"));
	}
	
	
	function charger_defis_json ()
	//Charge les descriptions de défis contenues dans defis.json de tous les défis déployés par GDDDvD dans un array renvoyé à la fin.
	//Il s'agit d'une version plus rapide (et sans dépendance envers yq) de charger_defis(), mais qui nécessite une version plus récente du script d'installation de CTFd.
	{
		$defis = json_decode(file_get_contents(getenv("RACINE_SERVEUR", "..") . "/defis/deploiement/defis.json"), true);
		if (isset($_SESSION['defis']))
		{$anciens_defis = array_column($_SESSION['defis'], null, 'path');}
		
		foreach ($defis as &$defi)
		{
			$defi['path'] = getenv("RACINE_SERVEUR", "..") . $defi['repertoire'] . "/";
			unset($defi['repertoire']);
			if (file_exists(getenv("RACINE_SERVEUR", "..") . "/lockfiles/" . basename($defi['path']) . ".$_SESSION[num_equipe].lock") || file_exists(getenv("RACINE_SERVEUR", "..") . "/lockfiles/" . basename($defi['path']) . ".all.lock"))
			{
				$defi['statut'] = "occupe";
				if (isset($anciens_defis))
				{$defi['port'] = $anciens_defis[$defi['path']]['port'];}
				else
				{$defi['port'] = -1;} //impossible de trouver le port, donc on met un placeholder, mais ça ne devrait jamais arriver anyway...
			}
			else
			{$defi['statut'] = "pret";}
		}
		unset($defi); //puisqu'utilisé directement (par référence) dans la foreach loop
		return array_values(array_filter($defis, "filtre_defis"));
	}
	
	
	if (!isset($_SESSION['equipe']))
	//Le participant n'a pas encore été identifié
	{
		$equipes = json_decode(file_get_contents(getenv("RACINE_SERVEUR","..")."/equipes.json"), true)['equipes_ctfd'];
		
		if (!$_POST)
		//Un petit finfinaud essaie d'accéder au portail sans se loguer
		//(ou sa session a juste expiré :P)
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
		else if (file_exists(getenv("RACINE_SERVEUR", "..") . "/lockfiles/nologin.$_POST[num].lock"))
		//Un participant bloqué par un admin essaie de se connecter
		{
			http_response_code(403);
			echo str_replace("ERR_CODE", "403 Forbidden<br>Tu n'as présentement pas le droit de te connecter.", file_get_contents("erreur.html"));
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
		$_SESSION['defis'] = charger_defis_json(); //on peut aussi utiliser charger_defis() (voir la définition de ces fonctions)
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
			flex-shrink: 0;
		}
		
		.defi .valeur
		{
			white-space: nowrap;
		}
		
		a.demarrer, a.arreter
		{
			color: white;
			font-size: 15px;
			font-weight: bold;
			padding: 5px 10px;
			border-radius: 8px;
			text-decoration: none;
			user-select: none;
			cursor: pointer;
			box-shadow: 0px 5px 10px 0px rgba(0,0,0,0.3);
			flex-shrink: 0;
		}
		
		a.demarrer
		{
			background-color: green;
			margin-left: auto;
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
			text-align: right;
			font-size: 14px;
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
			flex-shrink: 0;
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
					{
						echo "<div class=txt-resolu>En ligne au port " . $_SESSION['defis'][$compteur]['port'] . " de l'adresse " . $_SESSION['defis'][$compteur]['statiques-dynamiques']['domaine'] . "</div>";
						echo "<a class=arreter href=/dashboard.php?defi=$compteur>Arrêter</a></div>";
					}
					else
					{echo "<div class=txt-resolu>Erreur: état inconnu</div>";}
				}
			?>
		</div>
		
		<?php
			if ($_SESSION['equipe'] == "admins")
			{
				echo "<a class=bouton href=/panneauadmin.php>Panneau Admin</a>";
				echo "<a class=bouton href=/admininfos.php>Infos Admins</a>";
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
							echo "<div class=loading id=loading-modale style=\"display: none\"></div>";
						}
						else if ($_SESSION['defis'][$_GET['defi']]['statut'] == "occupe")
						//défi à arrêter
						{
							echo "<p style\"text-align: left\" id=msg-modale>Une instance de ce défi est présentement en cours d'exécution.<br>Êtes-vous sûr de vraiment vouloir arrêter cette instance?</p>";
							echo "<a class=bouton id=bouton-modale onclick=arreterDefi() style=\"background-color: darkred\">Arrêter le défi</a>";
							echo "<div class=loading id=loading-modale style=\"display: none\"></div>";
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
						
						echo "<p>Connectez-vous au port " . $_SESSION['defis'][$_GET['demarre']]['port'] . " de " . $_SESSION['defis'][$_GET['demarre']]['statiques-dynamiques']['domaine'] . " par " . $_SESSION['defis'][$_GET['demarre']]['statiques-dynamiques']['protocole'] . ".</p>";
						echo "<a class=bouton id=bouton-modale-ok onclick=reloadPostDemarrage()>Ok</a><div class=loading id=loading-modale style=\"display: none\"></div>";
					}
				?>
			</div>
		</div>
	</page>
	
	<footer>
		<p><strong>GDDD vD0.4</strong>&nbsp;&nbsp;(<a href=https://github.com/nico64-64/gddd/tree/version-D#readme>code source</a>)</p>
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
		
		function reloadPostDemarrage ()
		{
			document.getElementById('bouton-modale-ok').style.display = 'none';
			document.getElementById('loading-modale').style.display = 'inline-block';
			window.location.href = "/dashboard.php?reload=1";
		};
	</script>
</body>
</html>
