<?php
	/*  outils.php
	 * ============
	 * Outils pour gérer une connexion SSE (Server-Side Events, ou EventSource) ainsi que les défis déployés par GDDD.
	 * Ce script ne doit jamais être appelé lui-même (il ne ferait rien de toute façon).
	 * Ce fichier est plutôt inclus (avec "require") dans les scripts gérant des connexions SSE.
	 */
	
	function repondre ($msg, $statut)
	//Répond au client via HTTP SSE.
	//Envoie dans l'objet JSON "data" un JSON comportant un code HTTP de statut ($statut) et un message (pas toujours utilisé côté client).
	{
		echo "data: " . json_encode(['msg' => $msg, 'statut' => $statut]) . "\n\n";
		if (function_exists('ob_flush'))
		{@ob_flush();}
		flush();
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
	//Compte les instances de défi (tous défis confondus) en train de rouler pour cette équipe.
	{
		charger_defis_json(); //revérifie la présence de lockfiles
		
		$nbre = 0;
		foreach ($_SESSION['defis'] as $defi)
		{
			if ($defi['statut'] == "occupe")
			{$nbre++;}
		}
		return $nbre;
	}
	
	
	function charger_defis ()
	//Charge toutes les descriptions.yaml des défis déployées par GDDDvD dans un array renvoyé à la fin.
	//Cette fonction prend du temps à s'éexécuter parce qu'elle entre dans tous les dossiers de défis.
	// !! FONCTION DÉPRÉCIÉE !!
	//Utiliser plutôt charger_defis_json() pour lire le fichier JSON généré par le script de lancement de CTFd amalgamant l'ensemble des descriptions.yaml.
	//Cette fonction reste disponible au cas où le json ne serait pas disponible et fournit exactement les mêmes fonctionnalités que la nouvelle, mais prend plusieurs secondes à s'exécuter, contrairement à la nouvelle.
	{
		global $path_actuel;
		if (isset($_SESSION['defis']))
		{$anciens_defis = array_column($_SESSION['defis'], null, 'path');}
		$defis = [];
		$path_actuel = getenv("RACINE_SERVEUR")."/defis/";
		$categories = scandir(getenv("RACINE_SERVEUR")."/defis");
		$categories = array_filter($categories, "filtre_dossiers");
		foreach ($categories as $categorie)
		{
			$path_actuel = getenv("RACINE_SERVEUR")."/defis/$categorie/";
			$liste_defis = scandir(getenv("RACINE_SERVEUR")."/defis/$categorie/");
			$liste_defis = array_filter($liste_defis, "filtre_dossiers");
			
			foreach ($liste_defis as $defi)
			{
				$defis[] = json_decode(shell_exec("yq . \"".getenv("RACINE_SERVEUR")."/defis/$categorie/$defi/description.yaml\""), true);
				$defis[count($defis) - 1]['path'] = getenv("RACINE_SERVEUR") . "/defis/$categorie/$defi/";
				
				if (file_exists(getenv("RACINE_SERVEUR") . "/lockfiles/" . basename($defis[count($defis) - 1]['path']) . ".$_SESSION[num_equipe].lock") || file_exists(getenv("RACINE_SERVEUR") . "/lockfiles/" . basename($defis[count($defis) - 1]['path']) . ".all.lock"))
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
		$defis = json_decode(file_get_contents(getenv("RACINE_SERVEUR") . "/defis/deploiement/defis.json"), true);
		if (isset($_SESSION['defis']))
		{$anciens_defis = array_column($_SESSION['defis'], null, 'path');}
		
		foreach ($defis as &$defi)
		{
			$defi['path'] = getenv("RACINE_SERVEUR") . $defi['repertoire'] . "/";
			unset($defi['repertoire']);
			if (file_exists(getenv("RACINE_SERVEUR") . "/lockfiles/" . basename($defi['path']) . ".all.lock"))
			{$defi['statut'] = "barre";}
			else if (file_exists(getenv("RACINE_SERVEUR") . "/lockfiles/" . basename($defi['path']) . ".$_SESSION[num_equipe].lock"))
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
?>
