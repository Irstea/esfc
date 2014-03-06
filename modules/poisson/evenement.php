<?php
/**
 * @author Eric Quinton
 * @copyright Copyright (c) 2014, IRSTEA / Eric Quinton
 * @license http://www.cecill.info/licences/Licence_CeCILL-C_V1-fr.html LICENCE DE LOGICIEL LIBRE CeCILL-C
 *  Creation 25 févr. 2014
 */
include_once 'modules/classes/evenement.class.php';
$dataClass = new Evenement ( $bdd, $ObjetBDDParam );
$keyName = "evenement_id";
$id = $_REQUEST [$keyName];

switch ($t_module ["param"]) {
	case "list" :
		break;
	case "display":
		/*
		 * Display the detail of the record
		 */
		$data = $dataClass->lire ( $id );
		$smarty->assign ( "data", $data );
		$smarty->assign ( "corps", "example/exampleDisplay.tpl" );
		break;
	case "change" :
		if ($_REQUEST ["poisson_id"] > 0) {
			/*
			 * open the form to modify the record If is a new record, generate a new record with default value : $_REQUEST["idParent"] contains the identifiant of the parent record
			 */
		/*
		 * Lecture des tables de parametres necessaires a la saisie
		 */
		include_once 'modules/classes/poisson.class.php';
			include_once 'modules/classes/bassin.class.php';
			$evenement_type = new Evenement_type ( $bdd, $ObjetBDDParam );
			$smarty->assign ( "evntType", $evenement_type->getListe () );
			$pathologie_type = new Pathologie_type ( $bdd, $ObjetBDDParam );
			$smarty->assign ( "pathoType", $pathologie_type->getListe () );
			$gender_methode = new Gender_methode ( $bdd, $ObjetBDDParam );
			$smarty->assign ( "genderMethode", $gender_methode->getListe () );
			$sexe = new Sexe ( $bdd, $ObjetBDDParam );
			$smarty->assign ( "sexe", $sexe->getListe () );
			$bassin = new Bassin ( $bdd, $ObjetBDDParam );
			$smarty->assign ( "bassinList", $bassin->getListe () );
			$smarty->assign ( "bassinListActif", $bassin->getListe ( 1 ) );
			dataRead ( $dataClass, $id, "poisson/evenementChange.tpl", $_REQUEST ["poisson_id"] );
			/*
			 * Lecture du poisson
			 */
			$poisson = new Poisson ( $bdd, $ObjetBDDParam );
			$dataPoisson = $poisson->getDetail ( $_REQUEST ["poisson_id"] );
			$smarty->assign ( "dataPoisson", $dataPoisson );
			/*
			 * Lecture des tables associees
			 */
			if ($id > 0) {
				$morphologie = new Morphologie ( $bdd, $ObjetBDDParam );
				$smarty->assign ( "dataMorpho", $morphologie->getDataByEvenement ( $id ) );
				$pathologie = new Pathologie ( $bdd, $ObjetBDDParam );
				$smarty->assign ( "dataPatho", $pathologie->getDataByEvenement ( $id ) );
				$genderSelection = new Gender_selection ( $bdd, $ObjetBDDParam );
				$smarty->assign ( "dataGender", $genderSelection->getDataByEvenement ( $id ) );
				$transfert = new Transfert ( $bdd, $ObjetBDDParam );
				$smarty->assign ( "dataTransfert", $transfert->getDataByEvenement ( $id ) );
			} else {
				/*
				 * Initialisation des donnees en cas de nouvel evenement
				 */
				if ($dataPoisson ["bassin_id"] > 0)
					$dataTransfert = array (
							"bassin_origine" => $dataPoisson ["bassin_id"] 
					);
			}
		}
		break;
	case "write":
		/*
		 * write record in database
		 */
		$id = dataWrite ( $dataClass, $_REQUEST );
		if ($id > 0) {
			$_REQUEST [$keyName] = $id;
			/*
			 * Ecriture des informations complementaires
			 */
			include_once 'modules/classes/poisson.class.php';
			include_once 'modules/classes/bassin.class.php';
			/*
			 * Morphologie
			 */
			if ($_REQUEST ["longueur_fourche"] > 0 || $_REQUEST ["longueur"] > 0 || $_REQUEST ["masse"] > 0) {
				$morphologie = new Morphologie ( $bdd, $ObjetBDDParam );
				$_REQUEST ["morphologie_date"] = $_REQUEST ["evenement_date"];
				$morpho_id = $morphologie->ecrire ( $_REQUEST );
				if (! $morpho_id > 0) {
					$message .= formatErrorData ( $morphologie->getErrorData () );
					$message .= $LANG ["message"] [12];
					$module_coderetour = - 1;
				}
			}
			/*
			 * Pathologie
			 */
			if ($_REQUEST ["pathologie_type_id"] > 0) {
				$pathologie = new Pathologie ( $bdd, $ObjetBDDParam );
				$_REQUEST ["pathologie_date"] = $_REQUEST ["evenement_date"];
				$patho_id = $pathologie->ecrire ( $_REQUEST );
				if (! $patho_id > 0) {
					$message .= formatErrorData ( $pathologie->getErrorData () );
					$message .= $LANG ["message"] [12];
					$module_coderetour = - 1;
				}
			}
			/*
			 * Sexage
			 */
			if ($_REQUEST ["gender_methode_id"] > 0 && $_REQUEST ["sexe_id"] > 0) {
				$genderSelection = new Gender_selection ( $bdd, $ObjetBDDParam );
				$_REQUEST ["gender_selection_date"] = $_REQUEST ["evenement_date"];
				$gender_id = $genderSelection->ecrire ( $_REQUEST );
				if (! $gender_id > 0) {
					$message .= formatErrorData ( $genderSelection->getErrorData () );
					$message .= $LANG ["message"] [12];
					$module_coderetour = - 1;
				}
			}
			/*
			 * Transfert
			 */
			if ($_REQUEST["bassin_destination"] > 0 ) {
				$transfert = new Transfert($bdd, $ObjetBDDParam);
				$_REQUEST["transfert_date"] = $_REQUEST["evenement_date"];
				$transfert_id = $transfert->ecrire($_REQUEST);
				if (! $transfert_id > 0) {
					$message .= formatErrorData ( $transfert->getErrorData () );
					$message .= $LANG ["message"] [12];
					$module_coderetour = - 1;
				}
			}
		}
		break;
	case "delete":
		/*
		 * delete record
		 */
		include_once "modules/classes/poisson.class.php";
		dataDelete ( $dataClass, $id );
		break;
}

?>