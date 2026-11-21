/*** Ce script est inséré dans /dashboard.php par PHP lorsque nécessaire et ne devrait pas être accédé directement. ***/
/*** Ce format de commentaire est préalablement retiré par PHP, mais gardez en tête que ce fichier est tout de même accessible au public même s'il n'est pas indexé ***/

// Gestion du démarrage et de l'arrêt:

btnDemarrer = document.getElementById('bouton-modale');
btnFermerModale = document.getElementById('x-modale');
msgModale = document.getElementById('msg-modale');
loadingModale = document.getElementById('loading-modale');
/*** defi = numéro du défi (loadé par PHP) ***/


function demarrerDefi()
/*** Créé et monitore une connexion SSE avec le serveur pour démarrer un défi. ***/
{
	clearInterval(intervalId); //pas de reload quand on démarre un défi!
	eventSource = new EventSource('/deploiement.php?type=demarrer&defi=' + defi); //envoi de la requête SSE
	msgModale.innerHTML = "Connexion au serveur...";
	btnDemarrer.style.display = "none";
	btnFermerModale.style.display = "none";
	loadingModale.style.display = "inline-block";
	
	eventSource.onmessage = function (ev)
	{
		donnees = JSON.parse(ev.data);
		if (donnees.statut == 100) //Continue (le serveur spawn l'instance du défi)
		{msgModale.innerHTML = "Démarrage du défi...<br>Svp patienter quelques instants.<br><strong>NE FERMEZ PAS CETTE PAGE!<strong>";}
		else if (donnees.statut == 204) //No Content (défi démarré avec succès)
		{
			msgModale.innerHTML = "Démarrage terminé!<br>Vous serez redirigés dans un instant...";
			eventSource.close();
			window.location.replace('/dashboard.php?demarre=' + defi);
		}
		else if (donnees.statut == 503) //Service Unavailable (défi déjà déamrré)
		{
			msgModale.innerHTML = "<h3 style=\"color: red\">Ce défi est déjà démarré.</h3><br><br>" + donnees.msg;
			loadingModale.style.display = "none";
			eventSource.close();
		}
		else //Autre code d'erreur (409, 500, etc.)
		{
			msgModale.innerHTML = donnees.msg;
			loadingModale.style.display = "none";
			eventSource.close();
		}
	};
	
	eventSource.onerror = function (erreur)
	{
		console.error("Erreur SSE", erreur);
		msgModale.innerHTML = "Une erreur SSE est survenue lors du démarrage du défi.<br>Veuillez contacter un admin et lui fournir les détails affichés dans la console.";
		eventSource.close();
		loadingModale.style.display = "none";
	};
}

function arreterDefi()
/*** Créé et monitore une connexion SSE avec le serveur pour fermer un défi. ***/
{
	clearInterval(intervalId); //pas de reload quand on arrête un défi!
	eventSource = new EventSource('/deploiement.php?type=arreter&defi=' + defi); //envoi de la requête SSE
	msgModale.innerHTML = "Connexion au serveur...";
	btnDemarrer.style.display = "none";
	btnFermerModale.style.display = "none";
	loadingModale.style.display = "inline-block";
	
	eventSource.onmessage = function (ev)
	{
		donnees = JSON.parse(ev.data);
		if (donnees.statut == 100) //Continue (le serveur va supprimer l'instance du défi)
		{msgModale.innerHTML = "Arrêt de l'instance du défi...<br>Svp patienter quelques instants.<br><strong>NE FERMEZ PAS CETTE PAGE!<strong>";}
		else if (donnees.statut == 204) //No Content (défi arrêté avec succès)
		{
			msgModale.innerHTML = "Arrêt terminé!<br>Vous serez redirigés dans un instant...";
			eventSource.close();
			window.location.replace('/dashboard.php?reload=1');
		}
		else if (donnees.statut == 503) //Service Unavailable (défi déjà arrêté)
		{
			msgModale.innerHTML = "<h3 style=\"color: red\">Ce défi est déjà arrêté.</h3><br><br>" + donnees.msg;
			loadingModale.style.display = "none";
			eventSource.close();
		}
		else //Autre code d'erreur (409, 500, etc.)
		{
			msgModale.innerHTML = donnees.msg;
			loadingModale.style.display = "none";
			eventSource.close();
		}
	};
	
	eventSource.onerror = function (erreur)
	{
		console.error("Erreur SSE", erreur);
		msgModale.innerHTML = "Une erreur SSE est survenue lors du démarrage du défi.<br>Veuillez contacter un admin et lui fournir les détails affichés dans la console.";
		eventSource.close();
		loadingModale.style.display = "none";
	};
}
