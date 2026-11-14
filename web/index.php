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
	
	$lien_ctfd = getenv("LIEN_CTFD");
	$nom_ctf = getenv("NOM_CTF");
	
	if (isset($_SESSION['equipe']))
	{
		header('Location: /dashboard.php', true, 302);
		exit;
	}
	
	$equipes = json_decode(file_get_contents(getenv("RACINE_SERVEUR")."/equipes.json"), true)['equipes_ctfd'];
?>

<!DOCTYPE html>

<html>
<head>
	<meta encoding=UTF-8>
	<title>GDDD</title>
	<link rel=stylesheet href=/style.css>
	<link rel=icon href=/<?php echo getenv("ICONE_SERVEUR") ?>>
	
	<style>
		#conteneur-portail
		{
			display: flex;
			justify-content: center;
			margin: 40px 0px;
		}
		
		#portail
		{
			background-color: #505055;
			color: white;
			padding: 20px;
			width: 40%;
		}
		
		#portail div
		{
			margin: 10px;
		}
		
		#portail div label
		{
			margin-right: 10px;
			width: 35%;
			display: inline-block;
			text-align: right;
		}
		
		#portail div input, #portail div select
		{
			width: 50%;
		}
	</style>
</head>

<body>
	<nav>
		<a href=/><img src=/<?php echo getenv("LOGO_SERVEUR") ?> style="width: 180px"></a>
	</nav>
	
	<page>
		<h1>GDDD, le Gestionnaire de Défis à Déploiement Dynamique</h1>
		
		<strong>Bienvenue au <?php echo $nom_ctf ?>!</strong>
		<p>Ce portail vous permet de démarrer des instances de défis à déploiement dynamique directement depuis votre navigateur!</p>
		<p>Vous devez tout de même entrer vos flags dans <a href="<?php echo $lien_ctfd ?>">CTFd</a>.</p>
		
		<div id=conteneur-portail>
			<form id=portail method=POST action=/dashboard.php>
				<h3>Connexion</h3>
				<div>
					<label for=equipe>Équipe</label>
					<select name=num id=equipe>
						<?php
							for ($compteur = 0; $compteur < count($equipes); $compteur++)
							{echo "<option value=$compteur>" . $equipes[$compteur]['nom'] . "</option>";}
						?>
					</select>
				</div>
				<div>
					<label for=mdp>Mot de passe</label>
					<input type=password name=mdp id=mdp>
				</div>
				<div style="margin: 20px 0 0 0">
					<input type=submit value="Se connecter" style="width: 180px">
				</div>
			</form>
		</div>
		
		<p>Ne partagez jamais votre mot de passe d'équipe avec les autres!</p>
		<p>En cas de problème de connexion, avertissez un admin.</p>
	</page>
	
	<footer>
		<p><strong>GDDD vD0.2</strong>&nbsp;&nbsp;(<a href=https://github.com/nico64-64/gddd/tree/version-D#readme>code source</a>)</p>
	</footer>
</body>
</html>
