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
		<h1>INFOS ADMINS</h1>
		
		<h2>session</h2>
		<details>
			<pre><?php echo print_r($_SESSION, true) ?></pre>
		</details>
		
		<h2>getenv()</h2>
		<details>
			<pre><?php echo print_r(getenv(), true); ?></pre>
		</details>
		
		<h2>all.lock</h2>
		<details>
			<pre><?php
				echo "Défis barrés globalement (occurences de all.lock):\n";
				foreach ($_SESSION['defis'] as $defi)
				{
					if (file_exists(getenv("RACINE_SERVEUR", "..") . "/lockfiles/" . basename($_SESSION['defis'][$_GET['defi']]['path']) . ".all.lock"))
					{echo $defi['titre'] . "\n";}
				}
			?></pre>
		</details>
		
		<h2>equipes.json</h2>
		<details>
			<pre><?php echo file_get_contents(getenv("RACINE_SERVEUR","..")."/equipes.json"); ?></pre>
		</details>
		
		<h2>defis.json</h2>
		<details>
			<pre><?php
				if (file_exists(getenv("RACINE_SERVEUR","..")."/defis/deploiement/defis.json"))
				{echo file_get_contents(getenv("RACINE_SERVEUR","..")."/defis/deploiement/defis.json");}
				else
				{echo "descriptions.yaml seulement";}
			?></pre>
		</details>
		
		<h2>phpinfo()</h2>
		<details>
			<?php phpinfo(); ?>
		</details>
	</page>
	
	<footer>
		<p><strong>GDDD vD0.4</strong>&nbsp;&nbsp;(<a href=https://github.com/nico64-64/gddd/tree/version-D#readme>code source</a>)</p>
	</footer>
</body>
</html>
