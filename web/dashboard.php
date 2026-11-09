<?php
	session_start();
	
	
	function filtre_dossiers($dossier)
	{
		global $path_actuel;
		if ($dossier == "." || $dossier == "..")
		{return false;}
		return is_dir($path_actuel . $dossier);
	}
	
	
	function charger_defis ()
	{
		global $path_actuel;
		$defis = [];
		$path_actuel = "../defis/";
		$categories = scandir("../defis");
		$categories = array_filter($categories, "filtre_dossiers");
		foreach ($categories as $categorie)
		{
			$path_actuel = "../defis/$categorie/";
			$liste_defis = scandir("../defis/$categorie/");
			$liste_defis = array_filter($liste_defis, "filtre_dossiers");
			
			foreach ($liste_defis as $defi)
			{
				$defis[] = json_decode(shell_exec("yq . ../defis/$categorie/$defi/description.yaml"), true);
			}
		}
		return $defis;
	}
	
	
	$equipes = json_decode(file_get_contents("../equipes.json"), true)['equipes_ctfd'];
	
	if (!isset($_SESSION['equipe']))
	//Le participant n'a pas encore été identifié
	{
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
		{$_SESSION['equipe'] = $equipes[$_POST['num']]['nom'];}
	}
	//var_dump($equipes);
	
	if (isset($_GET['reload']) || time() - $_SESSION['dernier_reload'] > 300)
	{
		$_SESSION['defis'] = charger_defis();
		$_SESSION['dernier_reload'] = time();
		header('Location: /dashboard.php', true, 302);
		exit;
	}
?>

<!DOCTYPE html>

<html>
<head>
	<meta encoding=UTF-8>
	<title>GDDD</title>
	<link rel=stylesheet href=/style.css>
	<link rel=icon href=/logo-small.svg>
	
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
		
		.defi a
		{
			background-color: green;
			color: white;
			font-size: 15px;
			font-weight: bold;
			padding: 5px 10px;
			border-radius: 8px;
			text-decoration: none;
			margin-left: auto;
			user-select: none;
		}
		
		.defi a:hover
		{
			cursor: pointer;
			text-decoration: underline;
		}
		
		.defi .bloque
		{
			background-color: darkred;
			color: white;
			font-size: 15px;
			font-weight: bold;
			padding: 5px 10px;
			border-radius: 8px;
			margin-left: auto;
			user-select: none;
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
	</style>
</head>

<body>
	<nav>
		<a href=/><img src=/logo-large.svg style="width: 180px"></a>
	</nav>
	
	<page>
		<h1>Tableau de bord GDDD</h1>
		
		<?php echo "<h2>$_SESSION[equipe]</h2>"; ?>
		
		<p id=timer-refresh>Svp activer javascript</p>
		<a class=bouton href=/dashboard.php?reload=1>Recharger</a>
		
		<div id=conteneur-defis>
			<div class=defi>
				<h4>Le portique de l'antre de Tux</h4>
				<div class="tag non-resolu">Non-résolu</div>
				<div class=valeur>135 points</div>
				<div class=bloque>Occupé</div>
			</div>
			<div class=defi>
				<h4>Le portique de l'antre de Tux</h4>
				<div class="tag nouveau">Nouveau</div>
				<div class=valeur>135 points</div>
				<a>Démarrer</a>
			</div>
			<div class=defi>
				<h4>Le portique de l'antre de Tux</h4>
				<div class="tag resolu">Résolu</div>
				<div class=valeur>135 points</div>
				<div class=txt-resolu>Résolu il y a X minutes</div>
			</div>
			<?php
				foreach ($_SESSION['defis'] as $defi)
				{
					echo "<div class=defi><h4>$defi[titre]</h4><div class=\"tag nouveau\">Nouveau</div><div class=valeur>" . $defi['pointage']['initial'] . " points</div><a>Démarrer</a></div>";
				}
			?>
		</div>
		
		<?php
			if ($_SESSION['equipe'] == "admins")
			{
				echo "<details><summary>Détails de débogage</summary><pre>" . print_r($_SESSION['defis'], true) . "</pre></details>";
			}
		?>
	</page>
	
	<footer>
		<p><strong>GDDD vD0.2</strong>&nbsp;&nbsp;(<a href=https://github.com/nico64-64/gddd/tree/version-D#readme>code source</a>)</p>
	</footer>
	
	<script>
		timer = document.getElementById('timer-refresh');
		const dernier_reload = "<?php echo $_SESSION['dernier_reload'] * 1000; ?>";
		
		function verifierReload ()
		{
			timer.innerHTML = "Dernière mise à jour il y a " + Math.floor((Date.now() - dernier_reload) / 60000) + " minute(s) " + Math.floor(((Date.now() - dernier_reload) % 60000) / 1000) + " secondes";
			if (Date.now() - dernier_reload > 300000)
			{
				clearInterval(intervalId);
				window.location.href = "/dashboard.php?reload=1";
			}
		}
		
		const intervalID = setInterval(verifierReload, 1000);
		verifierReload();
	</script>
</body>
</html>