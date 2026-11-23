<?php
	/*  panneauadmin.php
	 * ==================
	 * Interface visuelle d'administration de GDDD.
	 * Cette page fournit aux administrateurs du CTF un moyen de voir et modifier l'état de GDDD en direct, en faisant notamment usage de requêtes SSE (Server-Side Events, ou EventSource) pour appeler cmd.php.
	 * Cette page n'est accessible qu'à l'équipe des administrateurs du CTF.
	 */
	
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
	
	if (!isset($_SESSION['equipe']) || $_SESSION['equipe'] != "admins")
	//Qqun qui n'est pas supposé sait que cette page existe
	{
		http_response_code(404);
		exit;
	}
	
	$equipes = json_decode(file_get_contents(getenv("RACINE_SERVEUR","..")."/equipes.json"), true)['equipes_ctfd'];
	$machines = json_decode(file_get_contents(getenv("RACINE_SERVEUR", "..") . "/machines.json"), true)['defis-dynamiques'];
?>

<!DOCTYPE html>

<html>
<head>
	<meta encoding=UTF-8>
	<title>GDDD</title>
	<link rel=stylesheet href=/style.css>
	<link rel=icon href=/logo-small.svg>
	
	<style>
		table
		{
			border-collapse: collapse;
			margin: 30px 20px;
		}
		
		td, th
		{
			border: 1px solid black;
			padding: 5px 10px;
		}
		
		th
		{
			background-color: #CFCFCF;
		}
		
		td.rouge
		{
			background-color: #FFB5B5;
		}
		
		td.vert
		{
			background-color: #B1FEAF;
		}
		
		form
		{
			margin: 20px;
			padding: 20px;
			background-color: lightgray;
		}
		
		#conteneur-modale
		{
			background-color: rgba(0,0,0,0.4);
			position: absolute;
			top: 0;
			left: 0;
			width: 100%;
			height: 100%;
			align-items: center;
			justify-content: center;
			display: none;
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
		
		#modale #header-modale
		{
			display: flex;
			margin: 0px 0px 10px 0px;
		}
		
		#modale h2
		{
			margin: 0px;
		}
		
		#modale .loading
		{
			margin-top: 10px;
			text-align: center;
			display: inline-block;
		}
		
		#msg-modale-2
		{
			font-weight: bold;
		}
		
		#x-modale
		{
			display: none;
			font-size: 30px;
			font-weight: bold;
			margin-left: auto;
			margin-top: -15px;
			height: 30px;
			cursor: pointer;
			text-decoration: none;
			color: black;
		}
	</style>
</head>

<body>
	<nav>
		<a href=/><img src=/logo-large.svg style="width: 180px"></a>
	</nav>
	
	<page style="position: relative">
		<h1>Panneau Admin</h1>
		
		<p>Dernier reload des défis à <?php date_default_timezone_set('America/Montreal'); echo date('H:i:s', $_SESSION['dernier_reload']); ?>, heure de l'Est.</p>
		
		<?php
			echo "<table>\n<th>Défi</th>";
			
			foreach ($equipes as $equipe)
			{echo "<th>$equipe[nom]</th>";}
			
			echo "<tr>\n<td>Connexion à GDDD</td>";
			for ($i = 0; $i < count($equipes); $i++)
			{
				if ($equipes[$i]['nom'] == "admins")
				{echo "<td style='background-color: #FFFB96'>Root</td>";}
				else if (file_exists(getenv("RACINE_SERVEUR", "..") . "/lockfiles/nologin.$i.lock"))
				{echo "<td class=rouge>Bloqué</td>";}
				else
				{echo "<td>Possible</td>";}
			}
			echo "</tr>";
			
			foreach ($_SESSION['defis'] as $defi)
			{
				echo "<tr>\n<td>$defi[titre]</td>";
				for ($i = 0; $i < count($equipes); $i++)
				{
					if (file_exists(getenv("RACINE_SERVEUR", "..") . "/lockfiles/" . basename($defi['path']) . ".$i.lock"))
					{echo "<td class=vert>Démarré</td>";}
					else if (file_exists(getenv("RACINE_SERVEUR", "..") . "/lockfiles/" . basename($defi['path']) . ".all.lock"))
					{echo "<td class=rouge>Barré</td>";}
					else
					{echo "<td>Arrêté</td>";}
				}
				echo "\n</tr>";
			}
			echo "</table>";
		?>
		
		<h2>Exécuter une commande</h2>
		<form id=cmd-defi action=/cmd.php method=get>
			<h3>Actions de défi</h3>
			<div>
				<select name=cmd>
					<option value=fermer>Fermer</option>
					<option value=debarrer>Débarrer</option>
					<option value=barrer>Barrer</option>
				</select>
				<span>le défi</span>
				<select name=defi>
					<?php
						for ($i = 0; $i < count($_SESSION['defis']); $i++)
						{echo "<option value=$i>" . $_SESSION['defis'][$i]['titre'] . "</option>";}
					?>
				</select>
				<span>pour</span>
				<select name=equipe>
					<?php
						for ($i = 0; $i < count($equipes); $i++)
						{echo "<option value=$i>" . $equipes[$i]['nom'] . "</option>";}
					?>
					<option value=all>tous</option>
				</select>
			</div>
			<input type=submit value=Exécuter style="margin-top: 10px;">
		</form>
		
		<form id=cmd-equipe action=/cmd.php method=get>
			<h3>Actions d'équipe</h3>
			<div>
				<select name=cmd>
					<option value=login>Se connecter en tant que</option>
					<option value=bloquer>Bloquer</option>
					<option value=debloquer>Débloquer</option>
				</select>
				<select name=equipe>
					<?php
						for ($i = 0; $i < count($equipes); $i++)
						{echo "<option value=$i>" . $equipes[$i]['nom'] . "</option>";}
					?>
				</select>
			</div>
			<input type=submit value=Exécuter style="margin-top: 10px;">
		</form>
		
		<form id=cmd-globale action=/cmd.php method=get>
			<h3>Actions globales</h3>
			<div>
				<select name=cmd>
					<option value=installer>Configurer les VMs et installer les défis</option>
					<option value=quitter>Fermer les VMs et GDDD</option>
				</select>
			</div>
			<input type=submit value=Exécuter style="margin-top: 10px;">
		</form>
		
		<a class=bouton href=/dashboard.php>Retour au dashboard</a>
		
		<div id=conteneur-modale>
			<div id=modale>
				<div id=header-modale>
					<h2>Exécution de la commande</h2>
					<a href=/panneauadmin.php id=x-modale>&times;</a>
				</div>
				<p id=msg-modale>Connexion au serveur...</p>
				<p id=msg-modale-2>Ne fermez et ne rechargez pas cette page!</p>
				<div class=loading id=loading-modale></div>
			</div>
		</div>
	</page>
	
	<footer>
		<p><strong>GDDD vD0.4</strong>&nbsp;&nbsp;(<a href=https://github.com/nico64-64/gddd/tree/version-D#readme>code source</a>)</p>
	</footer>
	
	<script>
		cmdDefi = document.getElementById('cmd-defi');
		cmdEquipe = document.getElementById('cmd-equipe');
		cmdGlobale = document.getElementById('cmd-globale');
		loadingModale = document.getElementById('loading-modale');
		msgModale = document.getElementById('msg-modale');
		msgModale2 = document.getElementById('msg-modale-2');
		btnFermer = document.getElementById('x-modale');
		modale = document.getElementById('conteneur-modale');
		
		
		async function gererSSE (event, cmdGlobale)
		//Démarre, gère et termine la connexion SSE (Server-Side Events, ou EventSource) avec cmd.php
		//On doit lui fournir l'évènement de soumission du formulaire en 1er paramètre et le formulaire HTML concerné en 2e
		{
			event.preventDefault(); //ne pas soumettre le form normalement
			modale.style.display = 'flex';
			params = new URLSearchParams(new FormData(cmdGlobale));
			eventSource = new EventSource(cmdGlobale.action + '?' + params.toString()); //envoi de la requête SSE
			
			eventSource.onmessage = function (ev, form)
			{
				donnees = JSON.parse(ev.data);
				switch (donnees.statut)
				{
					case 100:
						msgModale.innerHTML = "Exécution de la commande en cours...";
						break;
					
					case 102:
						msgModale.innerHTML = donnees.msg;
						break;
					
					case 204:
						msgModale.innerHTML = "Commande exécutée avec succès.<br>Vous pouvez fermer cette fenêtre ou recharger la page.";
						eventSource.close();
						loadingModale.style.display = "none";
						msgModale2.style.display = "none";
						btnFermer.style.display = "block";
						break;
					
					case 303:
						msgModale.innerHTML = "Commande exécutée avec succès.<br>Si votre navigateur ne vous redirige pas lui-même, cliquez <a href=" + donnees.msg + ">ici</a> pour accéder à la ressource demandée.";
						eventSource.close();
						window.location.href = donnees.msg;
						break;
					
					default:
						msgModale.innerHTML = donnees.msg;
						eventSource.close();
						loadingModale.style.display = "none";
						msgModale2.style.display = "none";
						btnFermer.style.display = "block";
						break;
				}
			};
			
			eventSource.onerror = function (erreur)
			{
				console.error("Erreur SSE", erreur);
				msgModale.innerHTML = "Une erreur SSE est survenue lors de l'exécution de la commande.<br>Les détails sont affichés dans la console.";
				eventSource.close();
				loadingModale.style.display = "inline";
			};
		}
		
		cmdDefi.addEventListener('submit', (event) => {gererSSE(event, cmdDefi)});
		cmdEquipe.addEventListener('submit', (event) => {gererSSE(event, cmdEquipe)});
		cmdGlobale.addEventListener('submit', (event) => {gererSSE(event, cmdGlobale)});
	</script>
</body>
</html>
