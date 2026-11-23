<?php
	/*  SSE.php
	 * =========
	 * Outils pour gérer une connexion SSE (Server-Side Events, ou EventSource).
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
?>
