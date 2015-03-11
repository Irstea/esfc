<?php
/**
 * @author Eric Quinton
 * @copyright Copyright (c) 2014, IRSTEA / Eric Quinton
 * @license http://www.cecill.info/licences/Licence_CeCILL-C_V1-fr.html LICENCE DE LOGICIEL LIBRE CeCILL-C
 *  Creation 7 avr. 2014
 */
include_once "modules/classes/document.class.php";
 /**
  * Classe adaptée à l'application sturio, surchargeant la classe Document
  * @author quinton
  *
  */
 class DocumentSturio extends DocumentAttach {
 	public $resolution=800;
 	public $modules=array("poisson", "evenement", "bassin", "echographie");
 	/**
 	 * Surcharge de la fonction supprimer pour effacer les enregistrements liés
 	 * (non-PHPdoc)
 	 *
 	 * @see ObjetBDD::supprimer()
 	 */
 	function supprimer($id) {
 		if ($id > 0) {
 			/*
 			 * Suppression dans les tables liées
 			*/
 			foreach ( $this->modules as $value ) {
 				$sql = "delete from " . $value . "_document where document_id = " . $id;
 				$this->executeSQL ( $sql );
 			}
 			return parent::supprimer ( $id );
 		}
 	}
 	/**
 	 * Retourne la liste des documents associes au type (evenement, poisson, bassin) et à la clé correspondante
 	 * @param string $type
 	 * @param int $id
 	 * @return array
 	 */
 	function getListeDocument($type, $id) {
 		if ( in_array($type, $this->modules) && $id > 0) {
 			if ($type == "poisson") {
 				$sql = "select document_id, document_date_import, document_nom,
 						document_description, size, mime_type_id
 						from document
 						join poisson_document using (document_id)
 						where poisson_id = ".$id."
 						union
 						select document_id, document_date_import, document_nom,
 						document_description, size, mime_type_id
 						from document
 						join evenement_document using (document_id)
 						join evenement using (evenement_id)
 						join poisson using (poisson_id)
 						where poisson_id = ".$id."
 						order by document_date_import desc
 						";
 			}else {
 			$sql = "select " . $type . "_id, document_id, document_date_import,
					document_nom, document_description, size, mime_type_id
					from document
					join " . $type . "_document using (document_id)
					where " . $type . "_id = " . $id . "
					order by document_date_import desc";
 			}
  			$liste = $this->getListeParam ( $sql );
 			/*
 			 * Preparation des vignettes
 			*/
 			foreach ( $liste as $key => $value ) {
 				if ($value ["mime_type_id"] == 1 || $value ["mime_type_id"] == 4 || $value ["mime_type_id"] == 5 || $value ["mime_type_id"] == 6) {
 					/*
 					 * Traitement des vignettes
 					 */
 					$liste[$key]["thumbnail_name"] = $this->writeFileImage($value["document_id"], 1);
 				}
 				if ($value ["mime_type_id"] == 4 || $value ["mime_type_id"] == 5 || $value ["mime_type_id"] == 6) {
 					/*
 					 * Traitement des photos
 					 */
 					$liste[$key]["photo_name"] = $this->writeFileImage($value["document_id"], 0, $this->resolution);
 				}
 			}
 			return ($liste);
 		}
 	}
 }
 /**
  * ORM permettant de gérer toutes les tables de liaison avec la table Document
  * @author quinton
  *
  */
 class DocumentLie extends ObjetBDD {
 	public $tableOrigine;
 	/**
 	 * Constructeur de la classe
 	 *
 	 * @param Adodb_instance $bdd
 	 * @param array $param
 	 */
 	function __construct($bdd, $param = null, $nomTable="") {
 		$this->param = $param;
 		$this->paramori = $this->param;
 		$this->tableOrigine = $nomTable;
 		$this->table = $nomTable."_document";
 		$this->id_auto = 0;
 		$this->colonnes = array (
 				$nomTable."_id" => array (
 						"type" => 1,
 						"requis" => 1,
 						"key" => 1
 				),
 				"document_id" => array (
 						"type" => 1,
 						"requis" => 1,
 						"key" => 1
 				)
 		);
 		if (! is_array ( $param ))
 			$param == array ();
 		$param ["fullDescription"] = 1;
 		parent::__construct ( $bdd, $param );
 	}
 	/**
 	 * Reecriture de la fonction ecrire($data)
 	 * (non-PHPdoc)
 	 * @see ObjetBDD::ecrire()
 	 */
 	function ecrire ($data) {
 		$nomChamp = $this->tableOrigine."_id";
 		if ($data["document_id"] > 0 && $data[$nomChamp] > 0) {
 			$sql = "insert into ".$this->table."
 					(document_id, ".$nomChamp.")
 					values 
 					(".$data["document_id"].",".$data[$nomChamp].")";
 			$rs = $this->executeSQL($sql);
 			$test = $this->connection->Affected_Rows ();
 			if ($test > 0) {
 				return 1;
 			} else {
 				return -1;
 			}
 		}
 	}
 	/**
 	 * Retourne la liste des documents associes
 	 * @param int $id
 	 * @return array
 	 */
 	function getListeDocument ($id) {
 		$documentSturio = new DocumentSturio($this->connection, $this->paramori);
 		return $documentSturio->getListeDocument($this->tableOrigine, $id);
 	}
 }
?>