<?php
/**
 * Code execute systematiquement a chaque appel, apres demarrage de la session
 * Utilise notamment pour recuperer les instances de classes stockees en 
 * variables de session
 */

/*
 * Declaration de la classe de recherche des poissons
 */
if (! isset ( $_SESSION ["searchPoisson"] )) {
	$searchPoisson = new SearchPoisson ();
	$_SESSION ["searchPoisson"] = $searchPoisson;
} else {
	$searchPoisson = $_SESSION ["searchPoisson"];
}
/*
 * Declaration de la classe de recherche des bassins
 */
if (! isset ( $_SESSION ["searchBassin"] )) {
	$searchBassin = new SearchBassin ();
	$_SESSION ["searchBassin"] = $searchBassin;
} else {
	$searchBassin = $_SESSION ["searchBassin"];
}
/*
 * Declaration de la classe de recherche des circuits d'eau
 */
if (! isset ( $_SESSION ["searchCircuitEau"] )) {
	$searchCircuitEau = new SearchCircuitEau ();
	$_SESSION ["searchCircuitEau"] = $searchCircuitEau;
} else {
	$searchCircuitEau = $_SESSION ["searchCircuitEau"];
}
/*
 * Déclaration de la classe de recherche des anomalies
 */
if (! isset ( $_SESSION ["searchAnomalie"] )) {
	$searchAnomalie = new SearchAnomalie ();
	$_SESSION ["searchAnomalie"] = $searchAnomalie;
} else {
	$searchAnomalie = $_SESSION ["searchAnomalie"];
}
/*
 * Déclaration de la classe de recherche des modèles d'alimentation
 */
if (! isset ( $_SESSION ["searchRepartTemplate"] )) {
	$searchRepartTemplate = new SearchRepartTemplate ();
	$_SESSION ["searchRepartTemplate"] = $searchRepartTemplate;
} else {
	$searchRepartTemplate = $_SESSION ["searchRepartTemplate"];
}
/*
 * Déclaration de la classe de recherche des répartitions d'aliments
*/
if (! isset ( $_SESSION ["searchRepartition"] )) {
	$searchRepartition = new SearchRepartition ();
	$_SESSION ["searchRepartition"] = $searchRepartition;
} else {
	$searchRepartition = $_SESSION ["searchRepartition"];
}
/*
 * Déclaration de la classe de recherche des consommations d'aliments
 */
if (! isset ( $_SESSION ["searchAlimentation"] )) {
	$searchAlimentation = new SearchAlimentation();
	$_SESSION ["searchAlimentation"] = $searchAlimentation;
} else {
	$searchAlimentation = $_SESSION ["searchAlimentation"];
}
/*
 * Déclaration de la classe de recherche des poissons sélectionnés pour les repros
 */
if (!isset ($_SESSION["searchRepro"])) {
	$searchRepro = new SearchRepro();
	$_SESSION ["searchRepro"] = $searchRepro;
} else {
	$searchRepro = $_SESSION["searchRepro"];
}
/*
 * Declaration de la classe contenant les parametres de l'alimentation des juveniles-lots
 */
if (!isset($_SESSION["alimJuv"])) {
	$alimJuv = new AlimJuv();
	$_SESSION["alimJuv"] = $alimJuv;
} else {
	$alimJuv = $_SESSION["alimJuv"];
}
/**
 * Gestion du site
 */
if (isset($_REQUEST["site_id"]) && $_REQUEST["site_id"] != $_SESSION["site_id"]) {
	if (is_numeric($_REQUEST["site_id"])) {
		$_SESSION["site_id"] = $_REQUEST["site_id"];
		setcookie( "site_id", $_REQUEST["site_id"], strtotime( '+30 days' ), "", "", true, true );
		/**
		 * Affectation de site_id dans les modules de recherche
		 */
		$search = array("searchPoisson", "searchBassin", "searchCircuitEau", "searchRepartition", "searchRepro");
		foreach ($search as $s) {
			$$s->param["site_id"] = $_SESSION["site_id"];
		}
	}
}
$smarty->assign("site_id", $_SESSION["site_id"]);
?>