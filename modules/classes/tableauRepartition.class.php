<?php
/**
 * @author Eric Quinton
 * @copyright Copyright (c) 2014, IRSTEA / Eric Quinton
 * @license http://www.cecill.info/licences/Licence_CeCILL-C_V1-fr.html LICENCE DE LOGICIEL LIBRE CeCILL-C
 *  Creation 24 mars 2014
 */
include_once 'plugins/tcpdf_6_0_062/tcpdf.php';
/**
 * Classe générique, à hériter pour chaque modèle de document
 * 
 * @author quinton
 *        
 */
class TableauRepartition extends TCPDF {
	public $dataRepartition;
	public $dataDistribution;
	public $dataAliment;
	/**
	 * Parametres par defaut
	 * 
	 * @var array
	 */
	public $param = array (
			"orientation" => "P",
			"unit" => "mm",
			"format" => "A4",
			"unicode" => true,
			"encoding" => "UTF-8",
			"nomFichier" => "sturio",
			"hl" => 10  // Hauteur de ligne
		);
	public $nbLigne = 0;
	/**
	 * Jeu de couleur pour les fonds
	 * 
	 * @var array
	 */
	public $color = array (
			0 => array (
					"nom" => "bleu",
					"R" => 200,
					"G" => 235,
					"B" => 254 
			),
			1 => array (
					"nom" => "olive",
					"R" => 209,
					"G" => 199,
					"B" => 86 
			),
			2 => array (
					"nom" => "orange",
					"R" => 252,
					"G" => 164,
					"B" => 75 
			),
			3 => array (
					"nom" => "jaune",
					"R" => 255,
					"G" => 216,
					"B" => 67 
			),
			4 => array (
					"nom" => "violet",
					"R" => 224,
					"G" => 209,
					"B" => 204 
			),
			5 => array (
					"nom" => "vert",
					"R" => 208,
					"G" => 232,
					"B" => 213 
			),
			6 => array (
					"nom" => "bleu",
					"R" => 200,
					"G" => 235,
					"B" => 254 
			),
			7 => array (
					"nom" => "olive",
					"R" => 209,
					"G" => 199,
					"B" => 86 
			),
			8 => array (
					"nom" => "orange",
					"R" => 252,
					"G" => 164,
					"B" => 75 
			),
			9 => array (
					"nom" => "jaune",
					"R" => 255,
					"G" => 216,
					"B" => 67 
			),
			10 => array (
					"nom" => "violet",
					"R" => 224,
					"G" => 209,
					"B" => 204 
			),
			11 => array (
					"nom" => "vert",
					"R" => 208,
					"G" => 232,
					"B" => 213 
			) 
	);
	public $jourSemaine = array (
				0 => "dimanche",
				1 => "lundi",
				2 => "mardi",
				3 => "mercredi",
				4 => "jeudi",
				5 => "vendredi",
				6 => "samedi",
				7 => "dimanche" 
		);
	public $nbJour = 7;
	/**
	 * Constructeur de la classe
	 */
	function __construct($param = null) {
		if (is_array ( $param )) {
			foreach ( $param as $key => $value ) {
				$this->param [$key] = $value;
			}
		}
		parent::__construct ( $this->param ["orientation"], $this->param ["unit"], $this->param ["format"], $this->param ["unicode"], $this->param ["encoding"] );
	}
	/**
	 * Recupere les donnees necessaires a la generation de la feuille de repartition
	 *
	 * @param array $dataRepartition        	
	 * @param array $dataDistribution        	
	 * @param array $dataAliment        	
	 */
	function setData($dataRepartition, $dataDistribution, $dataAliment) {
		$this->dataRepartition = $dataRepartition;
		$this->dataDistribution = $dataDistribution;
		$this->dataAliment = $dataAliment;
	}
	/**
	 * Definition du pied de page
	 * (non­PHPdoc)
	 *
	 * @see FPDF::Footer()
	 */
	function Footer() {
		$this->SetY ( - 15 );
		// Set font
		$this->SetFont ( 'helvetica', 'I', 8 );
		// Page number
		$this->Cell ( 0, 10, 'Page ' . $this->getAliasNumPage () . '/' . $this->getAliasNbPages (), 0, false, 'C', 0, '', 0, false, 'T', 'M' );
	}
	/**
	 * Definition de l'entete de page
	 */
	function entete() {
		/*
		 * Definition de la marge
		 */
		$this->SetMargins ( 10, 10, 10 );
		$this->SetFont ( "helvetica" );
		$this->setPrintHeader ( false );
		$this->AddPage ($this->param["orientation"]);
		/*
		 * Impression du titre
		 */
		$this->SetFont ( "", "B" );
		$this->SetFontSize ( 10 );
		$this->Write ( 0, $this->param ["title"], "", 0, "C" );
		$this->Ln ();
		/*
		 * Titre du document
		 */
		$dateDebut = date_create_from_format ( "d/m/Y", $this->dataRepartition ["date_debut_periode"] );
		$this->dateDebut = $dateDebut;
		$jourDebut = date_format ( $dateDebut, "w" );
		$dateFin = date_create_from_format ( "d/m/Y", $this->dataRepartition ["date_fin_periode"] );
		$interval = date_diff ( $dateDebut, $dateFin );
		$this->nbJour = $interval->format ( '%a' ) + 1;
		$jourFin = date_format ( $dateFin, "w" );
		$titre = $this->dataRepartition["repartition_name"]. " " . $this->jourSemaine [$jourDebut] . " ".  $this->dataRepartition ["date_debut_periode"] . " au " . $this->jourSemaine [$jourFin] . " " . $this->dataRepartition ["date_fin_periode"];
		$this->SetFont ( "", "B" );
		$this->SetFontSize ( 14 );
		$this->Write ( 0, $titre, "", 0, "C" );
		$this->ln ();
	}
	/**
	 * Envoie le document au navigateur
	 */
	function sent($dateDebut="") {
		if ($dateDebut == "") {
			$dateDebut = date('d-m-Y');
		}
		$nomFichier = $this->param ["nomFichier"] . "_" . $this->dateDebut->format ( "d-m-Y" ) . ".pdf";
		$this->Output ( $nomFichier, "I" );
	}
}
/**
 * Affichage de l'état de répartition pour les adultes
 * 
 * @author quinton
 *        
 */
class RepartitionAdulte extends TableauRepartition {
	function __construct() {
		$this->param ["title"] = "Répartition des aliments dans les bassins - ADULTES";
		$this->param ["nomFichier"] = "adulte";
		parent::__construct ();
	}
	/**
	 * Lancement de la generation du document
	 */
	function exec() {
		$this->entete ();
		/*
		 * Preparation de l'entete du tableau
		 */
		$nbAlim = count ( $this->dataAliment );
		$alimColumnSize = intval ( 130 / $nbAlim );
		$alimColumnSizeMatin = intval ( $alimColumnSize / 2 );
		$alimColumnSizeSoir = $alimColumnSize - $alimColumnSizeMatin;
		$largeurTotaleAlim = $nbAlim * $alimColumnSize;
		$this->enteteTableau ( $nbAlim, $alimColumnSize );
		/*
		 * Impression des données
		 */
		/*
		 * Preparation du tableau general des aliments
		 * En absisse : le numero d'aliment
		 * En ordonnee : le numéro de colonne
		 */
		$aliment = array ();
		$alimentTotal = array();
		$i = 0;
		// printr($this->dataAliment);
		foreach ( $this->dataAliment as $key => $value ) {
			$aliment [$value ["aliment_id"]] = $i;
			$i ++;
		}
		// printr($aliment);
		/*
		 * preparation du tableau de distribution
		 */
		$distrib = array ();
		$i = 0;
		foreach ( $this->dataDistribution as $key => $value ) {
			if (strlen ( $distrib [$i] ["bassin_nom"] ) > 0) {
				if ($value ["bassin_nom"] != $distrib [$i] ["bassin_nom"]) {
					/*
					 * On incremente le compteur
					 */
					$i ++;
					$distrib [$i] ["bassin_nom"] = $value ["bassin_nom"];
				}
			} else
				$distrib [0] ["bassin_nom"] = $value ["bassin_nom"];
				/*
			 * Integration des donnees generales
			 */
			$distrib [$i] ["taux_nourrissage"] = $value ["taux_nourrissage"];
			$distrib [$i] ["evol_taux_nourrissage"] = $value ["evol_taux_nourrissage"];
			$distrib [$i] ["total_distribue"] = $value ["total_distribue"];
			$distrib [$i] ["distribution_consigne"] = $value ["distribution_consigne"];
			$distrib [$i] ["distribution_masse"] = $value ["distribution_masse"];
			/*
			 * Integration des donnees d'aliment
			 */
			$distrib [$i] [$aliment [$value ["aliment_id"]]] ["repart_alim_taux"] = $value ["repart_alim_taux"];
			$distrib [$i] [$aliment [$value ["aliment_id"]]] ["quantiteMatin"] = $value ["quantiteMatin"];
			$distrib [$i] [$aliment [$value ["aliment_id"]]] ["quantiteSoir"] = $value ["quantiteSoir"];
		}
		/*
		 * Impression du tableau
		 */
		$nbOccur = 0;
		/*
		 * Traitement du tableau de distribution
		 */
		foreach ( $distrib as $key => $value ) {
			/*
			 * Traitement du saut de page
			 */
			if ($nbOccur > 9) {
				$this->AddPage ();
				$this->enteteTableau ( $nbAlim, $alimColumnSize );
				$nbOccur = 0;
			}
			/*
			 * Preparation de la ligne supérieure
			 */
			$this->SetFillColor ( 255, 255, 255 );
			$this->SetFontSize ( 6 );
			$this->SetFont ( "", "" );
			$this->Cell ( 20, 4, "% de la ration", 1, 0, 'C', true );
			/*
			 * Recuperation des taux pour chaque aliment
			 */
			for($i = 0; $i < $nbAlim; $i ++) {
				$this->SetFillColor ( $this->color [$i] ["R"], $this->color [$i] ["G"], $this->color [$i] ["B"] );
				$this->SetFont ( "", "B" );
				$this->Cell ( $alimColumnSize, 4, $value [$i] ["repart_alim_taux"], 1, 0, 'C', true );
			}
			/*
			 * Ecriture de la fin de ligne
			 */
			$this->SetFillColor ( 255, 255, 255 );
			$this->SetFont ("");
			$this->Cell ( 40, 4, "", 1, 0, 'C', true );
			$this->Ln ();
			/*
			 * Preparation de la ligne de donnees
			 */
			$this->SetFont ( "", "B" );
			$this->SetFontSize ( 16 );
			$this->Cell ( 20, $this->param ["hl"], $value ["bassin_nom"], 1, 0, 'C', true );
			/*
			 * Traitement des aliments
			 */
			$this->SetFontSize ( 16 );
			for($i = 0; $i < $nbAlim; $i ++) {
				$this->SetFillColor ( $this->color [$i] ["R"], $this->color [$i] ["G"], $this->color [$i] ["B"] );
				$this->Cell ( $alimColumnSizeMatin, $this->param ["hl"], $value [$i] ["quantiteMatin"], 1, 0, 'C', true );
				$this->Cell ( $alimColumnSizeSoir, $this->param ["hl"], $value [$i] ["quantiteSoir"], 1, 0, 'C', true );
				$alimentTotal [$i] ["matin"] += $value [$i] ["quantiteMatin"];
				$alimentTotal [$i] ["soir"] += $value [$i] ["quantiteSoir"];
			}
			/*
			 * Fin de ligne
			 */
			$this->SetFillColor ( 255, 255, 255 );
			$this->SetFontSize ( 10 );
			$this->Cell ( 15, $this->param ["hl"], $value ["distribution_masse"], 1, 0, 'C', true );
			$this->Cell ( 10, $this->param ["hl"], $value ["taux_nourrissage"], 1, 0, 'C', true );
			$this->Cell ( 15, $this->param ["hl"], $value ["total_distribue"], 1, 0, 'C', true );
			$this->Ln ();
			/*
			 * Troisième ligne
			 */
			$this->Cell ( 20 + $largeurTotaleAlim + 15, 8, $value ["distribution_consigne"], 1, 0, 'L', true );
			$this->Cell ( 10, 8, $value ["evol_taux_nourrissage"], 1, 0, 'C', true );
			$this->Cell ( 15, 8, "", 1, 0, 'C', true );
			$this->Ln ();
			$nbOccur ++;
		}
		/*
		 * Preparation de la fin du tableau - totalisations
		 */
		$this->SetFont ( "", "B" );
		$this->Cell ( 20, 6, "Total/jour", 1, 0, 'C', true );
		$this->SetFont ("");
		/*
		 * Recuperation des totaux pour chaque aliment
		 */
		for($i = 0; $i < $nbAlim; $i ++) {
			$this->Cell ( $alimColumnSizeMatin, 6, $alimentTotal [$i] ["matin"], 1, 0, 'C', true );
			$this->Cell ( $alimColumnSizeSoir, 6, $alimentTotal [$i] ["soir"], 1, 0, 'C', true );
		}
		$this->Cell ( 40, 6, "", 1, 0, 'C', true );
		$this->Ln ();
		/*
		 * Total par semaine
		 */
		$this->SetFont ( "", "B" );
		$this->Cell ( 20, 6, "Total/sem", 1, 0, 'C', true );
		$this->SetFont ("");
		/*
		 * Recuperation des totaux pour chaque aliment
		 */
		for($i = 0; $i < $nbAlim; $i ++) {
			$this->Cell ( $alimColumnSize, 6, ($alimentTotal [$i] ["matin"] + $alimentTotal [$i] ["soir"]) * $this->nbJour, 1, 0, 'C', true );
		}
		$this->Cell ( 40, 6, "", 1, 0, 'C', true );
		/*
		 * envoi au navigateur
		 */
		$this->sent ();
	}
	/**
	 * Imprime l'entete du tableau
	 * 
	 * @param int $nbAlim        	
	 * @param int $alimColumnSize        	
	 */
	function enteteTableau($nbAlim, $alimColumnSize) {
		/*
		 * Ecriture du contenu de l'entete
		 */
		$this->SetFillColor ( 255, 255, 255 );
		$this->SetFont ( "", "B" );
		$this->Cell ( 20, 10, "Bassin", 1, 0, 'C', true );
		/*
		 * Ecriture du nom des aliments
		 */
		$i = 0;
		foreach ( $this->dataAliment as $key => $value ) {
			$this->SetFontSize ( 14 );
			$this->SetFillColor ( $this->color [$i] ["R"], $this->color [$i] ["G"], $this->color [$i] ["B"] );
			$this->Cell ( $alimColumnSize, $this->param ["hl"], $value ["aliment_libelle_court"], 1, 0, 'C', true );
			$i ++;
		}
		$this->SetFillColor ( 255, 255, 255 );
		$this->SetFontSize ( 8 );
		$this->Cell ( 15, $this->param ["hl"], "Biomasse", 1, 0, 'C', true );
		$this->Cell ( 10, $this->param ["hl"], "Taux", 1, 0, 'C', true );
		$this->Cell ( 15, $this->param ["hl"], "Total/jour", 1, 0, 'C', true );
		$this->Ln ();
	}
}
class RepartitionJuvenile extends TableauRepartition {
	function __construct() {
		$this->param ["title"] = "Répartition des aliments dans les bassins - JUVENILES";
		$this->param ["nomFichier"] = "juvenile";
		$this->param ["hl"] = 6;
		$this->param["orientation"] = "L";
		parent::__construct ();
	}
	/**
	 * Lancement de la generation du document
	 */
	function exec() {
		$this->entete ();
		/*
		 * Preparation de l'entete du tableau
		 */
		$nbAlim = count ( $this->dataAliment );
		$alimColumnSize = intval ( 240 / $nbAlim );
		$alimColumnSizeMatin = intval ( $alimColumnSize / 2 );
		$alimColumnSizeSoir = $alimColumnSize - $alimColumnSizeMatin;
		$largeurTotaleAlim = $nbAlim * $alimColumnSize;
		$this->enteteTableau ( $nbAlim, $alimColumnSize );
		/*
		 * Preparation du tableau general des aliments En absisse : le numero d'aliment En ordonnee : le numéro de colonne
		 */
		$aliment = array ();
		$alimentTotal = array();
		$i = 0;
		// printr($this->dataAliment,1);
		foreach ( $this->dataAliment as $key => $value ) {
			$aliment [$value ["aliment_id"]] = $i;
			$i ++;
		}
		// printr($aliment);
		/*
		 * preparation du tableau de distribution
		 */
		$distrib = array ();
		$i = 0;
		foreach ( $this->dataDistribution as $key => $value ) {
			if (strlen ( $distrib [$i] ["bassin_nom"] ) > 0) {
				if ($value ["bassin_nom"] != $distrib [$i] ["bassin_nom"]) {
					/*
					 * On incremente le compteur
					 */
					$i ++;
					$distrib [$i] ["bassin_nom"] = $value ["bassin_nom"];
				}
			} else
				$distrib [0] ["bassin_nom"] = $value ["bassin_nom"];
			/*
			 * Integration des donnees generales
			 */
			// printr($value, 1);
			$distrib [$i] ["taux_nourrissage"] = $value ["taux_nourrissage"];
			$distrib [$i] ["evol_taux_nourrissage"] = $value ["evol_taux_nourrissage"];
			$distrib [$i] ["total_distribue"] = $value ["total_distribue"];
			$distrib [$i] ["distribution_consigne"] = $value ["distribution_consigne"];
			$distrib [$i] ["distribution_masse"] = $value ["distribution_masse"];
			$distrib [$i] ["distribution_jour"] = $value ["distribution_jour"];
			/*
			 * Integration des donnees d'aliment
			 */
			$distrib [$i] [$aliment [$value ["aliment_id"]]] ["repart_alim_taux"] = $value ["repart_alim_taux"];
			$distrib [$i] [$aliment [$value ["aliment_id"]]] ["quantiteMatin"] = $value ["quantiteMatin"];
			$distrib [$i] [$aliment [$value ["aliment_id"]]] ["quantiteSoir"] = $value ["quantiteSoir"];
			$distrib [$i] [$aliment [$value ["aliment_id"]]] ["aliment_type_id"] = $value ["aliment_type_id"];
		}
		/*
		* Impression du tableau
		*/
		$nbOccur = 0;
		/*
		 * Traitement du tableau de distribution
		*/
		foreach ( $distrib as $key => $value ) {
			/*
			 * Traitement du saut de page
			*/
			if ($this->nbLigne > 35) {
				$this->nbLigne = 0;
				$this->AddPage ();
				$this->enteteTableau ( $nbAlim, $alimColumnSize );
				$nbOccur = 0;
			}
			/*
			 * Preparation de la ligne de donnees
			*/
			$this->SetFont ( "", "B" );
			$this->SetFontSize ( 12 );
			$this->SetFillColor ( 255, 255, 255 );
			$this->Cell ( 30, $this->param ["hl"], $value ["bassin_nom"], 1, 0, 'C', true );
			/*
			 * Traitement des aliments
			*/
			$this->SetFontSize ( 12 );
			for($i = 0; $i < $nbAlim; $i ++) {
				if($this->dataAliment[$i]["aliment_type_id"] == 1) $colorId = 4 ; else $colorId = 5;
				$this->SetFillColor ( $this->color [$colorId] ["R"], $this->color [$colorId] ["G"], $this->color [$colorId] ["B"] );
				$this->Cell ( $alimColumnSizeMatin, $this->param ["hl"], $value [$i] ["quantiteMatin"], 1, 0, 'C', true );
				$this->Cell ( $alimColumnSizeSoir, $this->param ["hl"], $value [$i] ["quantiteSoir"], 1, 0, 'C', true );
				$alimentTotal [$i] ["matin"] += $value [$i] ["quantiteMatin"];
				$alimentTotal [$i] ["soir"] += $value [$i] ["quantiteSoir"];
			}
			$this->ln();
			$this->nbLigne ++;
			/*
			 * Recherche des jours sans distribution
			 */
			$jourDistrib = explode(",", $value["distribution_jour"]);
			$messageJour = "";
			for ($i = 0; $i < 7 ; $i ++ ) {
				if ($jourDistrib[$i] != 1) {
					$messageJour .= " ".$this->jourSemaine[($i + 1)];
				}
			}
			if (strlen ($messageJour) > 1) $messageJour = 'À jeun le '.$messageJour;
			if (strlen($value["distribution_consigne"])> 1 ) $messageJour .= " ". $value["distribution_consigne"];
			if (strlen($messageJour) > 1) {
				$this->SetTextColor(255,0,0);
				$this->SetFillColor ( 255, 255, 255 );
				$this->Cell ($largeurTotaleAlim + 30, $this->param["hl"], $messageJour, 1, 0, "L", true);
				$this->SetTextColor(0,0,0);
				$this->nbLigne ++;
				$this->ln();
			}
		}
		/*
		 * Preparation de la fin du tableau - totalisations
		*/
		$this->SetFillColor ( 255, 255, 255 );
		$this->SetFont ( "", "B" );
		$this->Cell ( 30, $this->param["hl"], "Total/jour", 1, 0, 'C', true );
		$this->SetFont ("");
		/*
		 * Total par jour
		 */
		for($i = 0; $i < $nbAlim; $i ++) {
			$this->Cell ( $alimColumnSizeMatin, 6, $alimentTotal [$i] ["matin"], 1, 0, 'C', true );
			$this->Cell ( $alimColumnSizeSoir, 6, $alimentTotal [$i] ["soir"], 1, 0, 'C', true );
		}
		$this->Ln ();
		/*
		 * Total par semaine
		*/
		$this->SetFont ( "", "B" );
		$this->Cell ( 30, $this->param["hl"], "Total/sem", 1, 0, 'C', true );
		$this->SetFont ("");
		/*
		 * Recuperation des totaux pour chaque aliment
		*/
		for($i = 0; $i < $nbAlim; $i ++) {
			$this->Cell ( $alimColumnSize, $this->param["hl"], ($alimentTotal [$i] ["matin"] + $alimentTotal [$i] ["soir"]) * 7, 1, 0, 'C', true );
		}
		/*
		 * Envoi au navigateur
		 */
		$this->sent ();
	}
	/**
	 * Imprime l'entete du tableau
	 * 
	 * @param int $nbAlim        	
	 * @param int $alimColumnSize        	
	 */
	function enteteTableau($nbAlim, $alimColumnSize) {
		/*
		 * Ecriture du contenu de l'entete
		 */
		$this->SetFillColor ( 255, 255, 255 );
		$this->SetFont ( "", "B" );
		$this->Cell ( 30, $this->param ["hl"], "Bassin", 1, 0, 'C', true );
		/*
		 * Ecriture du nom des aliments
		 */
		foreach ( $this->dataAliment as $key => $value ) {
			$this->SetFontSize ( 14 );
			if ($value ["aliment_type_id"] == 1)
				$i = 4;
			else
				$i = 5;
			$this->SetFillColor ( $this->color [$i] ["R"], $this->color [$i] ["G"], $this->color [$i] ["B"] );
			$this->Cell ( $alimColumnSize, $this->param ["hl"], $value ["aliment_libelle_court"], 1, 0, 'C', true );
		}
		$this->Ln ();
		$this->nbLigne ++;
	}
}

/**
 * Generation du fichier de distribution pour les lots de juveniles
 * @author quinton
 *
 */
class RepartitionJuvenileLot extends TableauRepartition {
	public $dataBassin;
	public $isArtemia = false;
	public $horaire = array("8h","14h", "18h", "22h");
	
	function __construct() {
		$this->param ["title"] = "Répartition des aliments dans les bassins - JUVENILES LOTS";
		$this->param ["nomFichier"] = "juvenileLot";
		$this->param ["hl"] = 6;
		$this->param["orientation"] = "P";
		parent::__construct ();
	}

	/**
	 * Affectation des donnees
	 * @param $dataRepartition : tableau correspondant a la table repartition
	 * @param $dataBassin : donnees de distribution par bassin
	 * @isArtemia : booleen pour indiquer s'il y a ou non distribution d'artemies
	 * (non-PHPdoc)
	 * @see TableauRepartition::setData()
	 */
	function setData($dataRepartition, $dataBassin, $isArtemia) {
		$this->dataBassin = $dataBassin;
		$this->dataRepartition = $dataRepartition;
	}

	/**
	 * Creation du PDF
	 */
	function exec() {
		$this->entete();
		/*
		 * Affichage de la densité d'artemia
		 */
		$this->SetFillColor ( 255, 255, 255 );
		$this->SetFont("");
		$this->Cell ( 50, $this->param ["hl"], "Nb artémies/ml : ", 0, 0, 'R', true );
		$this->SetFont ( "", "B" );
		$this->Cell(50, $this->param ["hl"], $this->dataRepartition["densite_artemia"], 0, 0, 'L', true );
		$this->Ln();
		$this->nbLigne ++;
		$total = array();
		/*
		 * Appel de l'entete
		 */
		$this->enteteTableau();	
	/*
	 * Traitement de chaque bassin
	 */
		foreach ($this->dataBassin as $key => $bassin) {
			/*
			 * Traitement du saut de page
			 */
			if ($this->nbLigne > 35) {
				$this->nbLigne = 0;
				$this->AddPage ();
				$this->enteteTableau ();
			}
				
			$this->Cell ( 30, $this->param ["hl"], $bassin["bassin_nom"], 1, 0, 'C', true );
			/*
			 * Traitement des artemies
			 */
			if ($bassin["artemia"] > 0) {
				$taux = array (0.2,0.2,0.2,0.4);
				$this->Cell ( 25, $this->param ["hl"], $bassin["artemia"], 1, 0, 'C', true );
				$total[0] += $bassin["artemia"];
				for ($i = 0 ; $i < 4 ; $i ++) {
					$val = intval($bassin["volumeArtemia"] * $taux[$i]);
					$this->Cell ( 15, $this->param ["hl"], $val, 1, 0, 'C', true );
					$total[$i+1] += $val;
				}
			} else {
				/*
				 * mise a vide
				 */
				$this->Cell ( 25, $this->param ["hl"], "-", 1, 0, 'C', true );
				for ($i = 0 ; $i < 4 ; $i ++) {
					$this->Cell ( 15, $this->param ["hl"], "", 1, 0, 'L', true );
				}
			}
			/*
			 * Traitement des chironomes
			 */
			if ($bassin["chironome"] > 0) {
				if ($bassin["artemia"] > 0) {
					/*
					 * 4 distributions par jour
					 */
					$taux = array (0.2,0.2,0.2,0.4);
				} else 
					$taux = array (0.25,0.25,0.50,0);
				for ($i = 0 ; $i < 4 ; $i ++) {
					$val = intval($bassin["chironome"] * $taux[$i]);
					if ($val == 0 ) $val = "";
					$this->Cell ( 15, $this->param ["hl"], $val, 1, 0, 'C', true );
					$total[$i + 5] += $val;
				}
			} else {
				/*
				 * Mise a vide
				 */
				for ($i = 0 ; $i < 4 ; $i ++) {
					$this->Cell ( 15, $this->param ["hl"], "", 1, 0, 'L', true );
				}
			}
			$this->Ln();
			$this->nbLigne ++;
		}
		/*
		 * Creation de la ligne de total
		 */
		$this->Cell ( 30, $this->param ["hl"], "Totaux", 1, 0, 'C', true );
		$this->Cell ( 25, $this->param ["hl"], $total[0], 1, 0, 'C', true );
		for ($i = 1 ; $i < 9; $i ++) {
			$this->Cell ( 15, $this->param ["hl"], $total[$i], 1, 0, 'C', true );
		}
		$this->Ln();
		/*
		 * Envoi au navigateur
		 */
		$this->sent ();
	}

	/**
	 * Generation de l'entete du tableau
	 */
	function enteteTableau () {
		/*
		 * Ecriture du contenu de l'entete
		 */
		$this->SetFillColor ( 255, 255, 255 );
		$this->SetFont ( "", "B" );
		$this->Cell ( 30, $this->param ["hl"], "Bassin", 'LTR', 0, 'C', true );
		$this->Cell ( 85, $this->param ["hl"], "Artémies (ml)", 1, 0, 'C', true );
		$this->Cell ( 60, $this->param ["hl"], "Chironomes (gramme)", 1, 0, 'C', true );
		$this->Ln();
		$this->nbLigne ++;
		/*
		 * Seconde ligne
		 */
		$this->Cell ( 30, $this->param ["hl"], "", 'LBR', 0, 'C', true );
		$this->Cell ( 25, $this->param ["hl"], "Total", 1, 0, 'C', true );
		/*
		 * Generation des cellules horaires
		 */
		for ($i=1;$i <= 2; $i++) {
			for($j=0; $j < 4 ; $j ++) {
				$this->Cell ( 15, $this->param ["hl"], $this->horaire[$j], 1, 0, 'C', true );
			}
		}
		$this->Ln();
		$this->nbLigne++;
	}
}
?>