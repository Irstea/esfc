<?php
/**
 * @author Eric Quinton
 * @copyright Copyright (c) 2014, IRSTEA / Eric Quinton
 *  Creation 18 févr. 2014
 */
include_once 'modules/classes/categorie.class.php';
include_once 'modules/classes/evenement.class.php';
include_once 'modules/classes/documentSturio.class.php';

/**
 * ORM de gestion de la table poisson
 *
 * @author quinton
 *        
 */
class Poisson extends ObjetBDD
{

    /**
     * Constructeur de la classe
     *
     * @param
     *            instance ADODB $bdd
     * @param array $param
     */
    function __construct($bdd, $param = null)
    {
        $this->param = $param;
        $this->paramori = $param;
        $this->table = "poisson";
        $this->id_auto = "1";
        $this->colonnes = array(
            "poisson_id" => array(
                "type" => 1,
                "key" => 1,
                "requis" => 1,
                "defaultValue" => 0
            ),
            "poisson_statut_id" => array(
                "type" => 1,
                "requis" => 1
            ),
            "sexe_id" => array(
                "type" => 1,
                "requis" => 1,
                "defaultValue" => 3
            ),
            "matricule" => array(
                "type" => 0
            ),
            "prenom" => array(
                "type" => 0
            ),
            "cohorte" => array(
                "type" => 0
            ),
            "capture_date" => array(
                "type" => 2
            ),
            "date_naissance" => array(
                "type" => 2
            ),
            "categorie_id" => array(
                "type" => 1,
                "requis" => 1,
                "defaultValue" => 2
            ),
            "commentaire" => array(
                "type" => 0
            ),
            "vie_modele_id" => array(
                "type" => 1
            )
        );
        if (! is_array($param))
            $param = array();
        $param["fullDescription"] = 1;
        parent::__construct($bdd, $param);
    }

    /**
     * Fonction permettant de retourner une liste de poissons selon les criteres specifies
     *
     * @param array $dataSearch
     * @return array
     */
    function getListeSearch($dataSearch)
    {
        if (is_array($dataSearch)) {
            $dataSearch = $this->encodeData($dataSearch);
            $sql = "select poisson_id, sexe_id, matricule, prenom, cohorte, capture_date, sexe_libelle, sexe_libelle_court, poisson_statut_libelle,commentaire,
					pittag_valeur,
					mortalite_date,
					categorie_id, categorie_libelle";
            $from = " from " . $this->table . " natural join sexe
					  natural join poisson_statut
					  natural join categorie
					  left outer join mortalite using (poisson_id)					  
					  left outer join v_pittag_by_poisson using (poisson_id)";
            if ($dataSearch["displayMorpho"] == 1) {
                $sql .= ", longueur_fourche, longueur_totale, masse";
                $from .= " left outer join v_poisson_last_lf using (poisson_id)
					  left outer join v_poisson_last_lt using (poisson_id)
					  left outer join v_poisson_last_masse using (poisson_id) ";
            }
            if ($dataSearch["displayBassin"] == 1 || $dataSearch["site_id"]> 0) {
                $sql .= ", bassin_id, bassin_nom, site_id, site_name";
                $from .= " left outer join v_poisson_last_bassin using (poisson_id)
                            left outer join site using (site_id)";
            }
            /*
             * Preparation de la clause group by
             */
            /*
             * $group = " group by poisson_id, sexe_id, matricule, prenom,
             * cohorte, capture_date, sexe_libelle, sexe_libelle_court, poisson_statut_libelle, mortalite_date,
             * categorie_id, categorie_libelle, commentaire ";
             */
            /*
             * Preparation de la clause order
             */
            $order = " order by matricule ";
            /*
             * Preparation de la clause where
             */
            $where = " where ";
            $and = "";
            if ($dataSearch["statut"] > 0 && is_numeric($dataSearch["statut"])) {
                $where .= $and . " poisson_statut_id = " . $dataSearch["statut"];
                $and = " and ";
            }
            if ($dataSearch["categorie"] > 0 && is_numeric($dataSearch["categorie"])) {
                $where .= $and . " categorie_id = " . $dataSearch["categorie"];
                $and = " and ";
            }
            if ($dataSearch["sexe"] > 0 && is_numeric($dataSearch["sexe"])) {
                $where .= $and . " sexe_id = " . $dataSearch["sexe"];
                $and = " and ";
            }
            if (strlen($dataSearch["texte"]) > 0) {
                $texte = "%" . mb_strtoupper($dataSearch["texte"], 'UTF-8') . "%";
                $where .= $and . " (upper(matricule) like '" . $texte . "' 
						or upper(prenom) like '" . $texte . "' 
						or cohorte like '" . $texte . "' 
						or upper(pittag_valeur) like '" . $texte . "'";
                if (is_numeric($dataSearch["texte"])) {
                    $where .= " or poisson_id = " . $dataSearch["texte"];
                }
                $where .= ")";
                $and = " and ";
            }
            if ($dataSearch["site_id"] > 0 && is_numeric($dataSearch["site_id"])) {
                $where .= $and . " site_id = " . $dataSearch["site_id"];
                $and = " and ";
            }
            if (strlen($where) > 7)
                $data = $this->getListeParam($sql . $from . $where . /*$group .*/ $order);
            /*
             * Mise en forme des dates
             */
            foreach ($data as $key => $value) {
                if (strlen($value["mortalite_date"]) > 0)
                    $data[$key]["mortalite_date"] = $this->formatDateDBversLocal($value["mortalite_date"]);
            }
            /*
             * Recherche des temperatures cumulees
             */
            if ($dataSearch["displayCumulTemp"] == 1) {
                foreach ($data as $key => $value) {
                    $data[$key]["temperature"] = $this->calcul_temperature($value["poisson_id"], $dataSearch["dateDebutTemp"], $dataSearch["dateFinTemp"]);
                }
            }
            return ($data);
        }
    }

    /**
     * Retourne le detail d'un poisson
     *
     * @param int $poisson_id
     * @return array
     */
    function getDetail($poisson_id)
    {
        if ($poisson_id > 0 && is_numeric($poisson_id)) {
            $sql = "select p.poisson_id, sexe_id, matricule, prenom, cohorte, capture_date, sexe_libelle, sexe_libelle_court, poisson_statut_libelle,
					pittag_valeur, p.poisson_statut_id, date_naissance,
					bassin_nom, b.bassin_id, b.site_id, site_name, 
					categorie_id, categorie_libelle, commentaire
                    from poisson p 
                     join sexe using (sexe_id)
					  join poisson_statut using (poisson_statut_id)
					  join categorie using (categorie_id)
					  left outer join v_pittag_by_poisson using (poisson_id)
					  /*left outer join v_transfert_last_bassin_for_poisson vlast on (vlast.poisson_id = p.poisson_id)
					  left outer join transfert t on (vlast.poisson_id = t.poisson_id and transfert_date_last = transfert_date)
                      left outer join bassin b on (b.bassin_id = (case when t.bassin_destination is not null then t.bassin_destination else t.bassin_origine end))*/
                      left outer join v_poisson_last_bassin b using (poisson_id)
                      left outer join site on (b.site_id = site.site_id)
							";
            $where = " where p.poisson_id = " . $poisson_id;
            return $this->lireParam($sql . $where);
        }
    }

    /**
     * Fonction retournant la liste des poissons correspondant au libellé fourni
     *
     * @param string $libelle
     * @return array
     */
    function getListPoissonFromName($libelle)
    {
        if (strlen($libelle) > 0) {
            $libelle = $this->encodeData($libelle);
            $sql = "select poisson.poisson_id, matricule, prenom, pittag_valeur 
					from " . $this->table . "
					left outer join v_pittag_by_poisson using (poisson_id)
					where upper(matricule) like upper('%" . $libelle . "%') 
					or upper(prenom) like upper('%" . $libelle . "%')
					or upper(pittag_valeur) like upper('%" . $libelle . "%')
					order by matricule, pittag_valeur, prenom";
            return $this->getListeParam($sql);
        }
    }

    /**
     * Surcharge de la fonction ecrire pour generer les parents et la date de naissance
     * si indication du modele de marque VIE utilise
     * (non-PHPdoc)
     *
     * @see ObjetBDD::ecrire()
     */
    function ecrire($data)
    {
        /*
         * Recuperation des donnees de naissance, si vie_modele_id est renseigne
         */
        $dataLot = array();
        if ($data["vie_modele_id"] > 0) {
            require_once 'modules/classes/lot.class.php';
            $lot = new Lot($this->connection, $this->paramori);
            $dataLot = $lot->getFromVieModele($data["vie_modele_id"]);
            if ($dataLot["lot_id"] > 0) {
                /*
                 * Mise a jour de la date de naissance
                 */
                if (strlen($dataLot["eclosion_date"]) > 0) {
                    $data["date_naissance"] = $dataLot["eclosion_date"];
                    $date = explode("/", $dataLot["eclosion_date"]);
                    $data["cohorte"] = $date[2];
                }
            }
        }
        /*
         * Ecriture de l'enregistrement
         */
        $id = parent::ecrire($data);
        if ($id > 0 && $dataLot["lot_id"] > 0) {
            /*
             * Recuperation et ecriture des parents
             */
            $parents = $lot->getParents($dataLot["lot_id"]);
            $parentArray = array();
            /*
             * Formatage de la liste en tableau simple, pour prise en compte par la fonction ad-hoc
             */
            foreach ($parents as $key => $value)
                $parentArray[] = $value["poisson_id"];
            /*
             * Ecriture des parents
             */
            $this->ecrireTableNN("parent_poisson", "poisson_id", "parent_id", $id, $parentArray);
        }
        return $id;
    }

    /**
     * Réécriture de la fonction supprimer, pour vérifier si c'est possible, et supprimer les enregistrements
     * dans les tables liées
     * (non-PHPdoc)
     *
     * @see ObjetBDD::supprimer()
     */
    function supprimer($id)
    {
        if ($id > 0 && is_numeric($id)) {
            $retour = 0;
            /*
             * Vérification des liens non supprimables
             */
            /*
             * Recherche des poissons "enfants"
             */
            $parent_poisson = new Parent_poisson($this->connection, $this->paramori);
            $listeEnfant = $parent_poisson->lireEnfant($id);
            if (is_array($listeEnfant) == true && count($listeEnfant) > 0) {
                $detailEnfant = "";
                foreach ($listeEnfant as $key => $value)
                    $detailEnfant .= $value["matricule"] . " ";
                $retour = - 1;
                $this->errorData[] = array(
                    "code" => 0,
                    "message" => "Le poisson est défini comme le parent d'autres poissons (" . $detailEnfant . ")"
                );
            }
            if ($retour == 0) {
                /*
                 * Vérification des événements
                 */
                $evenement = new Evenement($this->connection, $this->paramori);
                $listeEvenement = $evenement->getEvenementByPoisson($id);
                if (is_array($listeEvenement) && count($listeEvenement) > 0) {
                    $retour = - 1;
                    $this->errorData[] = array(
                        "code" => 0,
                        "message" => "Le poisson contient des événements qui doivent être supprimés préalablement"
                    );
                }
            }
            if ($retour == 0) {
                /*
                 * Suppression dans les tables liées
                 */
                /*
                 * Documents
                 */
                $documentLie = new DocumentLie($this->connection, $this->paramori, "poisson");
                $listeDocument = $documentLie->getListeDocument($id);
                $documentSturio = new DocumentSturio($this->connection, $this->paramori);
                foreach ($listeDocument as $key => $value) {
                    if ($value["document_id"] > 0)
                        $documentSturio->supprimer($value["document_id"]);
                }
                /*
                 * Pittag
                 */
                $pittag = new Pittag($this->connection, $this->paramori);
                $pittag->supprimerChamp($id, "poisson_id");
                /*
                 * Suppression du poisson
                 */
                $retour = parent::supprimer($id);
            }
        }
        return $retour;
    }

    /**
     * Fonction permettant de calculer le cumul de temperature recu par un poisson
     * entre deux dates, en fonction des bassins frequentes
     *
     * @param int $poisson_id
     * @param date $date_debut
     * @param date $date_fin
     * @return numeric
     */
    function calcul_temperature($poisson_id, $date_debut, $date_fin)
    {
        $date_debut = $this->encodeData($date_debut);
        $date_debut = $this->formatDateLocaleVersDB($date_debut);
        $date_fin = $this->encodeData($date_fin);
        $date_fin = $this->formatDateLocaleVersDB($date_fin);
        if (is_numeric($poisson_id) && $poisson_id > 0) {
            /*
             * Recherche des bassins frequentes
             */
            $sql = "select pb.* from v_poisson_bassins pb
				where (date_debut <= '" . $date_debut . "' and ( date_fin > '" . $date_fin . "' or date_fin is null)
				or (date_debut <= '" . $date_debut . "' and date_fin > '" . $date_debut . "')
				or (date_debut > '" . $date_debut . "' and date_fin <= '" . $date_fin . "')
				or (date_debut between '" . $date_debut . "' and '" . $date_fin . "' and (date_fin > '" . $date_fin . "' or date_fin is null))
				)
				and poisson_id = " . $poisson_id . "
				order by date_debut, date_fin";
            $bassins = $this->getListeParam($sql);
            $temperature = 0;
            foreach ($bassins as $bassin) {
                if ($bassin["date_debut"] < $date_debut)
                    $bassin["date_debut"] = $date_debut;
                if (strlen($bassin["date_fin"]) == 0)
                    $bassin["date_fin"] = $date_fin;
                /*
                 * Calcul du total de la temperature
                 */
                $sqltemp = "with gs as (
				select generate_series('" . $bassin["date_debut"] . "'::date, '" . $bassin["date_fin"] . "'::date, interval ' 1 day') as date_jour
				)
				select  sum( ae.temperature) as temperature
				from gs, analyse_eau ae, circuit_eau ce, bassin b
				where b.circuit_eau_id = ce.circuit_eau_id 
				and b.bassin_id = " . $bassin["bassin_id"] . "
				and ae.circuit_eau_id = ce.circuit_eau_id
				and ae.analyse_eau_id = 
				(select a2.analyse_eau_id from analyse_eau a2
				join circuit_eau ce2 using (circuit_eau_id)
				where a2.temperature is not null
				and a2.analyse_eau_date <= gs.date_jour
				and ce.circuit_eau_id = ce2.circuit_eau_id
				order by a2.analyse_eau_date desc limit 1)
				";
                $dataTotal = $this->lireParam($sqltemp);
                if ($dataTotal["temperature"] > 0)
                    $temperature += $dataTotal["temperature"];
            }
            return $temperature;
        }
    }
}

/**
 * ORM de la table poisson_statut
 *
 * @author quinton
 *        
 */
class Poisson_statut extends ObjetBDD
{

    /**
     * Constructeur de la classe
     *
     * @param
     *            instance ADODB $bdd
     * @param array $param
     */
    function __construct($bdd, $param = null)
    {
        $this->param = $param;
        $this->table = "poisson_statut";
        $this->id_auto = "1";
        $this->colonnes = array(
            "poisson_statut_id" => array(
                "type" => 1,
                "key" => 1,
                "requis" => 1,
                "defaultValue" => 0
            ),
            "poisson_statut_libelle" => array(
                "type" => 0,
                "requis" => 1
            )
        );
        if (! is_array($param))
            $param = array();
        $param["fullDescription"] = 1;
        parent::__construct($bdd, $param);
    }
}

/**
 * ORM de la table pittag_type
 *
 * @author quinton
 *        
 */
class Pittag_type extends ObjetBDD
{

    /**
     * Constructeur de la classe
     *
     * @param
     *            instance ADODB $bdd
     * @param array $param
     */
    function __construct($bdd, $param = null)
    {
        $this->param = $param;
        $this->table = "pittag_type";
        $this->id_auto = "1";
        $this->colonnes = array(
            "pittag_type_id" => array(
                "type" => 1,
                "key" => 1,
                "requis" => 1,
                "defaultValue" => 0
            ),
            "pittag_type_libelle" => array(
                "type" => 0,
                "requis" => 1
            )
        );
        if (! is_array($param))
            $param = array();
        $param["fullDescription"] = 1;
        parent::__construct($bdd, $param);
    }
}

/**
 * ORM de gestion de la table pittag
 *
 * @author quinton
 *        
 */
class Pittag extends ObjetBDD
{

    /**
     * Constructeur de la classe
     *
     * @param
     *            instance ADODB $bdd
     * @param array $param
     */
    function __construct($bdd, $param = null)
    {
        $this->param = $param;
        $this->table = "pittag";
        $this->id_auto = "1";
        $this->colonnes = array(
            "pittag_id" => array(
                "type" => 1,
                "key" => 1,
                "requis" => 1,
                "defaultValue" => 0
            ),
            "poisson_id" => array(
                "type" => 1,
                "requis" => 1,
                "parentAttrib" => 1
            ),
            "pittag_date_pose" => array(
                "type" => 2
            ),
            "pittag_type_id" => array(
                "type" => 1
            ),
            "pittag_valeur" => array(
                "type" => 0
            ),
            "pittag_commentaire" => array(
                "type" => 0
            )
        );
        if (! is_array($param))
            $param = array();
        $param["fullDescription"] = 1;
        parent::__construct($bdd, $param);
    }

    /**
     * Retourne la liste des pittag attribués à un poisson
     *
     * @param int $poisson_id
     * @param int $limit
     * @return array
     */
    function getListByPoisson($poisson_id, $limit = 0)
    {
        if ($poisson_id > 0 && is_numeric($poisson_id)) {
            $sql = "select pittag_id, poisson_id, pittag_date_pose, pittag_valeur, pittag_type_libelle,
					pittag_commentaire
					from pittag
					left outer join pittag_type using (pittag_type_id)
					where poisson_id = " . $poisson_id . " order by pittag_date_pose desc, pittag_id desc";
            if ($limit > 0 && is_numeric($limit)) {
                $sql .= " limit " . $limit;
            }
            if ($limit == 1) {
                return $this->lireParam($sql);
            } else {
                return $this->getListeParam($sql);
            }
        }
    }
}

/**
 * ORM de gestion de la table morphologie
 *
 * @author quinton
 *        
 */
class Morphologie extends ObjetBDD
{

    /**
     * Constructeur de la classe
     *
     * @param
     *            instance ADODB $bdd
     * @param array $param
     */
    function __construct($bdd, $param = null)
    {
        $this->param = $param;
        $this->table = "morphologie";
        $this->id_auto = "1";
        $this->colonnes = array(
            "morphologie_id" => array(
                "type" => 1,
                "key" => 1,
                "requis" => 1,
                "defaultValue" => 0
            ),
            "poisson_id" => array(
                "type" => 1,
                "requis" => 1,
                "parentAttrib" => 1
            ),
            "longueur_fourche" => array(
                "type" => 1
            ),
            "longueur_totale" => array(
                "type" => 1
            ),
            "masse" => array(
                "type" => 1
            ),
            "morphologie_date" => array(
                "type" => 2
            ),
            "evenement_id" => array(
                "type" => 1
            ),
            "morphologie_commentaire" => array(
                "type" => 0
            ),
            "circonference" => array(
                "type" => 1
            )
        );
        if (! is_array($param))
            $param = array();
        $param["fullDescription"] = 1;
        parent::__construct($bdd, $param);
    }

    /**
     * Fonction retournant la liste des donnees morphologiques pour un poisson
     *
     * @param int $poisson_id
     * @return array
     */
    function getListeByPoisson($poisson_id)
    {
        if ($poisson_id > 0 && is_numeric($poisson_id)) {
            $sql = "select morphologie_id, m.poisson_id, longueur_fourche, longueur_totale, masse, circonference, morphologie_date, morphologie_commentaire, 
					m.evenement_id, evenement_type_libelle
					from morphologie m
					left outer join evenement using (evenement_id)
					left outer join evenement_type using (evenement_type_id)
					where m.poisson_id = " . $poisson_id . " order by morphologie_date desc";
            return $this->getListeParam($sql);
        }
    }

    /**
     * Retourne la dernière masse connue pour un poisson
     *
     * @param int $poisson_id
     * @return array
     */
    function getMasseLast($poisson_id)
    {
        if ($poisson_id > 0 && is_numeric($poisson_id)) {
            $sql = "select masse from v_poisson_last_masse
					where  poisson_id = " . $poisson_id;
            return $this->lireParam($sql);
        }
    }

    /**
     * Retourne la masse d'un poisson entre deux dates
     *
     * @param int $poisson_id
     * @param string $date_from
     * @param string $date_to
     * @return tableau|NULL
     */
    function getListMasseFromPoisson($poisson_id, $date_from, $date_to)
    {
        if ($poisson_id > 0 && is_numeric($poisson_id) && strlen($date_from) > 0 && strlen($date_to) > 0) {
            $date_from = $this->encodeData($date_from);
            $date_to = $this->encodeData($date_to);
            $sql = "select poisson_id, morphologie_date, masse 
					from morphologie
					where poisson_id = " . $poisson_id . "
					and morphologie_date between '" . $date_from . "' and '" . $date_to . "' 
					order by morphologie_date";
            // printr ( $sql );
            return $this->getListeParam($sql);
        } else
            return null;
    }

    /**
     * Retourne la masse d'un poissson après le 1er juin (post-repro)
     *
     * @param unknown $poisson_id
     * @param unknown $annee
     * @return array
     */
    function getMasseBeforeDate($poisson_id, $date)
    {
        if ($poisson_id > 0 && is_numeric($poisson_id) && strlen($date) > 0) {
            $date = $this->encodeData($date);
            $sql = "select masse, morphologie_date from " . $this->table . "
					where morphologie_date < '" . $date . "' 
					and poisson_id = " . $poisson_id . " 
					order by morphologie_date desc
					limit 1";
            return $this->lireParam($sql);
        }
    }

    /**
     * Retourne la masse d'un poisson avant le 1er juin pré-repro
     *
     * @param unknown $poisson_id
     * @param unknown $annee
     * @return array
     */
    function getMasseBeforeRepro($poisson_id, $annee)
    {
        if ($poisson_id > 0 && is_numeric($poisson_id) && $annee > 0 && is_numeric($annee)) {
            $sql = "select masse, morphologie_date from " . $this->table . "
					where morphologie_date between '" . $annee . "-01-01' and '" . $annee . "-05-31' 
					and poisson_id = " . $poisson_id . " 
					order by morphologie_date asc
					limit 1";
            return $this->lireParam($sql);
        }
    }

    /**
     * Lit un enregistrement à partir de l'événement
     *
     * @param int $evenement_id
     * @return array
     */
    function getDataByEvenement($evenement_id)
    {
        if ($evenement_id > 0 && is_numeric($evenement_id)) {
            $sql = "select * from morphologie where evenement_id = " . $evenement_id;
            return $this->lireParam($sql);
        }
    }
}

/**
 * ORM de gestion de la table pathologie
 *
 * @author quinton
 *        
 */
class Pathologie extends ObjetBDD
{

    /**
     * Constructeur de la classe
     *
     * @param
     *            instance ADODB $bdd
     * @param array $param
     */
    function __construct($bdd, $param = null)
    {
        $this->paramori = $param;
        $this->param = $param;
        $this->table = "pathologie";
        $this->id_auto = "1";
        $this->colonnes = array(
            "pathologie_id" => array(
                "type" => 1,
                "key" => 1,
                "requis" => 1,
                "defaultValue" => 0
            ),
            "poisson_id" => array(
                "type" => 1,
                "requis" => 1,
                "parentAttrib" => 1
            ),
            "pathologie_type_id" => array(
                "type" => 1,
                "requis" => 1
            ),
            "pathologie_date" => array(
                "type" => 2
            ),
            "pathologie_commentaire" => array(
                "type" => 0
            ),
            "evenement_id" => array(
                "type" => 1
            ),
            "pathologie_valeur" => array(
                "type" => 1
            )
        );
        if (! is_array($param))
            $param = array();
        $param["fullDescription"] = 1;
        parent::__construct($bdd, $param);
    }

    /**
     * Retourne la liste des pathologies pour un poisson
     *
     * @param unknown $poisson_id
     * @return Ambigous <tableau, boolean, $data, string>
     */
    function getListByPoisson($poisson_id)
    {
        if ($poisson_id > 0 && is_numeric($poisson_id)) {
            $sql = "select pathologie_id, patho.poisson_id, pathologie_date, pathologie_commentaire,
					pathologie_type_libelle, evenement_type_libelle, patho.evenement_id
					from pathologie patho
					left outer join pathologie_type using (pathologie_type_id)
					left outer join evenement using (evenement_id)
					left outer join evenement_type using (evenement_type_id)
					where patho.poisson_id = " . $poisson_id . " order by pathologie_date desc";
            return $this->getListeParam($sql);
        }
    }

    /**
     * Lit un enregistrement à partir de l'événement
     *
     * @param unknown $evenement_id
     * @return Ambigous <multitype:, boolean, $data, string>
     */
    function getDataByEvenement($evenement_id)
    {
        if ($evenement_id > 0 && is_numeric($evenement_id)) {
            $sql = "select * from pathologie where evenement_id = " . $evenement_id;
            return $this->lireParam($sql);
        }
    }
}

/**
 * ORM de la table pathologie_type
 *
 * @author quinton
 *        
 */
class Pathologie_type extends ObjetBDD
{

    /**
     * Constructeur de la classe
     *
     * @param
     *            instance ADODB $bdd
     * @param array $param
     */
    function __construct($bdd, $param = null)
    {
        $this->param = $param;
        $this->table = "pathologie_type";
        $this->id_auto = "1";
        $this->colonnes = array(
            "pathologie_type_id" => array(
                "type" => 1,
                "key" => 1,
                "requis" => 1,
                "defaultValue" => 0
            ),
            "pathologie_type_libelle" => array(
                "type" => 0,
                "requis" => 1
            ),
            "pathologie_type_libelle_court" => array(
                "type" => 0
            )
        );
        if (! is_array($param))
            $param = array();
        $param["fullDescription"] = 1;
        parent::__construct($bdd, $param);
    }
}

/**
 * ORM de la table sexe
 *
 * @author quinton
 *        
 */
class Sexe extends ObjetBDD
{

    /**
     * Constructeur de la classe
     *
     * @param
     *            instance ADODB $bdd
     * @param array $param
     */
    function __construct($bdd, $param = null)
    {
        $this->param = $param;
        $this->table = "sexe";
        $this->id_auto = "1";
        $this->colonnes = array(
            "sexe_id" => array(
                "type" => 1,
                "key" => 1,
                "requis" => 1,
                "defaultValue" => 0
            ),
            "sexe_libelle" => array(
                "type" => 0,
                "requis" => 1
            ),
            "sexe_libelle_court" => array(
                "type" => 0
            )
        );
        if (! is_array($param))
            $param = array();
        $param["fullDescription"] = 1;
        parent::__construct($bdd, $param);
    }
}

/**
 * ORM de la table gender_methode
 *
 * @author quinton
 *        
 */
class Gender_methode extends ObjetBDD
{

    /**
     * Constructeur de la classe
     *
     * @param
     *            instance ADODB $bdd
     * @param array $param
     */
    function __construct($bdd, $param = null)
    {
        $this->param = $param;
        $this->table = "gender_methode";
        $this->id_auto = 1;
        $this->colonnes = array(
            "gender_methode_id" => array(
                "type" => 1,
                "key" => 1,
                "requis" => 1,
                "defaultValue" => 0
            ),
            "gender_methode_libelle" => array(
                "type" => 0,
                "requis" => 1
            )
        );
        if (! is_array($param))
            $param = array();
        $param["fullDescription"] = 1;
        parent::__construct($bdd, $param);
    }
}

/**
 * ORM de gestion de la table gender_selection
 *
 * @author quinton
 *        
 */
class Gender_selection extends ObjetBDD
{

    /**
     * Constructeur de la classe
     *
     * @param
     *            instance ADODB $bdd
     * @param array $param
     */
    function __construct($bdd, $param = null)
    {
        $this->param = $param;
        $this->paramori = $param;
        $this->table = "gender_selection";
        $this->id_auto = "1";
        $this->colonnes = array(
            "gender_selection_id" => array(
                "type" => 1,
                "key" => 1,
                "requis" => 1,
                "defaultValue" => 0
            ),
            "poisson_id" => array(
                "type" => 1,
                "requis" => 1,
                "parentAttrib" => 1
            ),
            "gender_methode_id" => array(
                "type" => 1
            ),
            "sexe_id" => array(
                "type" => 1
            ),
            "gender_selection_date" => array(
                "type" => 2
            ),
            "evenement_id" => array(
                "type" => 1
            ),
            "gender_selection_commentaire" => array(
                "type" => 0
            )
        );
        if (! is_array($param))
            $param = array();
        $param["fullDescription"] = 1;
        parent::__construct($bdd, $param);
    }

    /**
     * Surcharge de la fonction ecrire, pour mettre a jour le sexe dans l'enregistrement poisson, le cas echeant
     * (non-PHPdoc)
     *
     * @see ObjetBDD::ecrire()
     */
    function ecrire($data)
    {
        $ret = parent::ecrire($data);
        if ($ret > 0 && $data["poisson_id"] > 0) {
            /*
             * S'il s'agit d'une determination expert ou par échographie, on force le sexe
             */
            if ($data["gender_methode_id"] == 1 || $data["gender_methode_id"] == 4) {
                $poisson = new Poisson($this->connection, $this->paramori);
                $dataPoisson = $poisson->lire($data["poisson_id"]);
                $dataPoisson["sexe_id"] = $data["sexe_id"];
                $poisson->ecrire($dataPoisson);
            }
        }
        return $ret;
    }

    /**
     * Recupère la liste des déterminations sexuelles pour un poisson
     *
     * @param int $poisson_id
     * @return array
     */
    function getListByPoisson($poisson_id)
    {
        if ($poisson_id > 0 && is_numeric($poisson_id)) {
            $sql = "select gender_selection_id, g.poisson_id, gender_selection_date, gender_selection_commentaire,
					gender_methode_libelle, sexe_libelle_court, sexe_libelle, g.evenement_id,
					evenement_type_libelle
					from gender_selection g
					left outer join gender_methode using (gender_methode_id)
					left outer join sexe using (sexe_id)
					left outer join evenement using (evenement_id)
					left outer join evenement_type using (evenement_type_id)
					where g.poisson_id = " . $poisson_id . " order by gender_selection_date desc";
            return $this->getListeParam($sql);
        }
    }

    /**
     * Lit un enregistrement à partir de l'événement
     *
     * @param int $evenement_id
     * @return array
     */
    function getDataByEvenement($evenement_id)
    {
        if ($evenement_id > 0 && is_numeric($evenement_id)) {
            $sql = "select * from gender_selection where evenement_id = " . $evenement_id;
            return $this->lireParam($sql);
        }
    }
}

/**
 * ORM de gestion de la table Transfert
 *
 * @author quinton
 *        
 */
class Transfert extends ObjetBDD
{

    /**
     * Constructeur de la classe
     *
     * @param
     *            instance ADODB $bdd
     * @param array $param
     */
    function __construct($bdd, $param = null)
    {
        $this->param = $param;
        $this->paramori = $param;
        $this->table = "transfert";
        $this->id_auto = "1";
        $this->colonnes = array(
            "transfert_id" => array(
                "type" => 1,
                "key" => 1,
                "requis" => 1,
                "defaultValue" => 0
            ),
            "poisson_id" => array(
                "type" => 1,
                "requis" => 1,
                "parentAttrib" => 1
            ),
            "bassin_origine" => array(
                "type" => 1
            ),
            "bassin_destination" => array(
                "type" => 1
            ),
            "transfert_date" => array(
                "type" => 2,
                "requis" => 1
            ),
            "evenement_id" => array(
                "type" => 1
            ),
            "transfert_commentaire" => array(
                "type" => 0
            )
        );
        if (! is_array($param))
            $param = array();
        $param["fullDescription"] = 1;
        parent::__construct($bdd, $param);
    }

    /**
     * Retourne la liste des transferts pour un poisson
     *
     * @param int $poisson_id
     * @return array
     */
    function getListByPoisson($poisson_id, $annee = 0)
    {
        if ($poisson_id > 0 && is_numeric($poisson_id)) {
            $sql = 'select transfert_id, transfert.poisson_id, bassin_origine, bassin_destination, transfert_date, evenement_id,
					ori.bassin_nom as "bassin_origine_nom", dest.bassin_nom as "bassin_destination_nom",
					evenement_id, evenement_type_libelle, transfert_commentaire
					from transfert
					join poisson using (poisson_id)
					left outer join bassin ori on (bassin_origine = ori.bassin_id)
					left outer join bassin dest on (bassin_destination = dest.bassin_id)
					left outer join evenement using (evenement_id)
					left outer join evenement_type using (evenement_type_id)';
            $where = ' where transfert.poisson_id = ' . $poisson_id;
            if ($annee > 0 && is_numeric($annee))
                $where .= " and extract(year from transfert_date) = " . $annee;
            $order = " order by transfert_date desc";
            return $this->getListeParam($sql . $where . $order);
        }
    }

    /**
     * Calcule la liste des poissons presents dans un bassin
     *
     * @param int $bassin_id
     * @return array
     */
    function getListPoissonPresentByBassin($bassin_id)
    {
        if ($bassin_id > 0 && is_numeric($bassin_id)) {
            $sql = 'select distinct t.poisson_id,matricule, prenom, cohorte, t.transfert_date, 
					(case when t.bassin_destination is not null then t.bassin_destination else t.bassin_origine end) as "bassin_id",
					bassin_nom, sexe_libelle_court,
					pittag_valeur, masse
 					from transfert t
 					join v_transfert_last_bassin_for_poisson v on (v.poisson_id = t.poisson_id and transfert_date_last = transfert_date)
					join bassin on (bassin.bassin_id = (case when t.bassin_destination is not null then t.bassin_destination else t.bassin_origine end))
					join poisson on (t.poisson_id = poisson.poisson_id)
					left outer join v_pittag_by_poisson pittag on (pittag.poisson_id = poisson.poisson_id)
					left outer join v_poisson_last_masse vmasse on (t.poisson_id = vmasse.poisson_id)
					left outer join sexe using (sexe_id)
					where  poisson_statut_id = 1 and bassin.bassin_id = ' . $bassin_id . "
 					order by matricule";
        }
        return ($this->getListeParam($sql));
    }

    /**
     * Lit un enregistrement à partir de l'événement
     *
     * @param int $evenement_id
     * @return array
     */
    function getDataByEvenement($evenement_id)
    {
        if ($evenement_id > 0 && is_numeric($evenement_id)) {
            $sql = "select * from transfert where evenement_id = " . $evenement_id;
            return $this->lireParam($sql);
        }
    }

    /**
     * Complement de la fonction ecrire pour mettre a jour le statut de l'animal,
     * en cas de transfert dans un bassin adulte
     * (non-PHPdoc)
     *
     * @see ObjetBDD::ecrire()
     */
    function ecrire($data)
    {
        $transfert_id = parent::ecrire($data);
        if ($transfert_id > 0 && $data["bassin_destination"] > 0 && $data["poisson_id"] > 0) {
            /*
             * Recuperation de l'usage du bassin
             */
            $bassin = new Bassin($this->connection, $this->paramori);
            $dataBassin = $bassin->lire($data["bassin_destination"]);
            if ($dataBassin["bassin_usage_id"] == 1) {
                /*
                 * Recuperation du poisson
                 */
                $poisson = new Poisson($this->connection, $this->paramori);
                $dataPoisson = $poisson->lire($data["poisson_id"]);
                if ($dataPoisson["poisson_categorie_id"] == 2) {
                    $dataPoisson["poisson_categorie_id"] = 1;
                    $poisson->ecrire($dataPoisson);
                }
            }
        }
        return $transfert_id;
    }
}

/**
 * ORM de gestion de la table mime_type
 *
 * @author quinton
 *        
 */
class Mime_type extends ObjetBDD
{

    /**
     * Constructeur de la classe
     *
     * @param
     *            instance ADODB $bdd
     * @param array $param
     */
    function __construct($bdd, $param = null)
    {
        $this->param = $param;
        $this->table = "mime_type";
        $this->id_auto = "1";
        $this->colonnes = array(
            "mime_type_id" => array(
                "type" => 1,
                "key" => 1,
                "requis" => 1,
                "defaultValue" => 0
            ),
            "content_type" => array(
                "type" => 0,
                "requis" => 1
            ),
            "extension" => array(
                "type" => 0,
                "requis" => 1
            )
        );
        if (! is_array($param))
            $param = array();
        $param["fullDescription"] = 1;
        parent::__construct($bdd, $param);
    }

    /**
     * retourne la liste des types mimes triés par extension
     * (non-PHPdoc)
     *
     * @see ObjetBDD::getListe()
     */
    function getListe()
    {
        $sql = "select * from mime_type order by extension";
        return ($this->getListeParam($sql));
    }
}

// class Document extends ObjetBDD {
/**
 * Constructeur de la classe
 *
 * @param
 *            instance ADODB $bdd
 * @param array $param
 */
/*
 * function __construct($bdd, $param = null) { $this->param = $param; $this->table = "document"; $this->id_auto = "1"; $this->colonnes = array ( "document_id" => array ( "type" => 1, "key" => 1, "requis" => 1, "defaultValue" => 0 ), "mime_type_id" => array ( "type" => 1, "requis" => 1 ), "poisson_id" => array ( "type" => 1 ), "evenement_id" => array ( "type" => 1 ), "document_date_import" => array ( "type" => 2, "requis" => 1, "defaultValue" => "dateJour" ), "document_nom" => array ( "requis" => 1 ), "document_description" => array ( "type" => 0 ) ); if (! is_array ( $param )) $param = array(); $param ["fullDescription"] = 1; parent::__construct ( $bdd, $param ); } }
 */
class Cohorte extends ObjetBDD
{

    /**
     * Constructeur de la classe
     *
     * @param
     *            instance ADODB $bdd
     * @param array $param
     */
    function __construct($bdd, $param = null)
    {
        $this->paramori = $param;
        $this->param = $param;
        $this->table = "cohorte";
        $this->id_auto = "1";
        $this->colonnes = array(
            "cohorte_id" => array(
                "type" => 1,
                "key" => 1,
                "requis" => 1,
                "defaultValue" => 0
            ),
            "poisson_id" => array(
                "type" => 1,
                "requis" => 1,
                "parentAttrib" => 1
            ),
            "cohorte_date" => array(
                "type" => 2
            ),
            "cohorte_commentaire" => array(
                "type" => 0
            ),
            "evenement_id" => array(
                "type" => 1
            ),
            "cohorte_determination" => array(
                "type" => 0
            ),
            "cohorte_type_id" => array(
                "type" => 1
            )
        );
        if (! is_array($param))
            $param = array();
        $param["fullDescription"] = 1;
        parent::__construct($bdd, $param);
    }

    /**
     * Retourne la liste des déterminations de cohortes pour un poisson
     *
     * @param int $poisson_id
     * @return array <tableau, boolean, $data, string>
     */
    function getListByPoisson($poisson_id)
    {
        if ($poisson_id > 0 && is_numeric($poisson_id)) {
            $sql = "select cohorte_id, cohorte.poisson_id, cohorte_date, cohorte_commentaire,
					cohorte_determination, evenement_type_libelle, cohorte.evenement_id,
					cohorte_type_id, cohorte_type_libelle
					from cohorte
					left outer join cohorte_type using (cohorte_type_id)
					left outer join evenement using (evenement_id)
					left outer join evenement_type using (evenement_type_id)
					where cohorte.poisson_id = " . $poisson_id . " order by cohorte_date desc";
            return $this->getListeParam($sql);
        }
    }

    /**
     * Lit un enregistrement à partir de l'événement
     *
     * @param unknown $evenement_id
     * @return Ambigous <multitype:, boolean, $data, string>
     */
    function getDataByEvenement($evenement_id)
    {
        if ($evenement_id > 0 && is_numeric($evenement_id)) {
            $sql = "select * from " . $this->table . " where evenement_id = " . $evenement_id;
            return $this->lireParam($sql);
        }
    }

    /**
     * rajout de l'ecriture de la cohorte
     * (non-PHPdoc)
     *
     * @see ObjetBDD::ecrire()
     */
    function ecrire($data)
    {
        $ret = parent::ecrire($data);
        if ($ret > 0 && $data["poisson_id"] > 0 && strlen($data["cohorte_determination"]) > 0) {
            /*
             * S'il s'agit d'une determination expert, on force le sexe
             */
            $poisson = new Poisson($this->connection, $this->paramori);
            $dataPoisson = $poisson->lire($data["poisson_id"]);
            $dataPoisson["cohorte"] = $data["cohorte_determination"];
            $poisson->ecrire($dataPoisson);
        }
        return $ret;
    }
}

/**
 * ORM de la table cohorte_type
 *
 * @author quinton
 *        
 */
class Cohorte_type extends ObjetBDD
{

    /**
     * Constructeur de la classe
     *
     * @param
     *            instance ADODB $bdd
     * @param array $param
     */
    function __construct($bdd, $param = null)
    {
        $this->param = $param;
        $this->table = "cohorte_type";
        $this->id_auto = 1;
        $this->colonnes = array(
            "cohorte_type_id" => array(
                "type" => 1,
                "key" => 1,
                "requis" => 1,
                "defaultValue" => 0
            ),
            "cohorte_type_libelle" => array(
                "type" => 0,
                "requis" => 1
            )
        );
        if (! is_array($param))
            $param = array();
        $param["fullDescription"] = 1;
        parent::__construct($bdd, $param);
    }
}

/**
 * ORM de la table mortalite_type
 *
 * @author quinton
 *        
 */
class Mortalite_type extends ObjetBDD
{

    /**
     * Constructeur de la classe
     *
     * @param
     *            instance ADODB $bdd
     * @param array $param
     */
    function __construct($bdd, $param = null)
    {
        $this->param = $param;
        $this->table = "mortalite_type";
        $this->id_auto = 1;
        $this->colonnes = array(
            "mortalite_type_id" => array(
                "type" => 1,
                "key" => 1,
                "requis" => 1,
                "defaultValue" => 0
            ),
            "mortalite_type_libelle" => array(
                "type" => 0,
                "requis" => 1
            )
        );
        if (! is_array($param))
            $param = array();
        $param["fullDescription"] = 1;
        parent::__construct($bdd, $param);
    }
}

/**
 * ORM de gestion de la table mortalite
 *
 * @author quinton
 *        
 */
class Mortalite extends ObjetBDD
{

    /**
     * Constructeur de la classe
     *
     * @param
     *            instance ADODB $bdd
     * @param array $param
     */
    function __construct($bdd, $param = null)
    {
        $this->paramori = $param;
        $this->param = $param;
        $this->table = "mortalite";
        $this->id_auto = "1";
        $this->colonnes = array(
            "mortalite_id" => array(
                "type" => 1,
                "key" => 1,
                "requis" => 1,
                "defaultValue" => 0
            ),
            "poisson_id" => array(
                "type" => 1,
                "requis" => 1,
                "parentAttrib" => 1
            ),
            "mortalite_type_id" => array(
                "type" => 1,
                "requis" => 1
            ),
            "mortalite_date" => array(
                "type" => 2
            ),
            "mortalite_commentaire" => array(
                "type" => 0
            ),
            "evenement_id" => array(
                "type" => 1
            )
        );
        if (! is_array($param))
            $param = array();
        $param["fullDescription"] = 1;
        parent::__construct($bdd, $param);
    }

    /**
     * Surcharge de la fonction ecrire
     * pour mettre a jour le statut du poisson
     * (non-PHPdoc)
     *
     * @see ObjetBDD::ecrire()
     */
    function ecrire($data)
    {
        $mortalite_id = parent::ecrire($data);
        if ($mortalite_id > 0 && $data["poisson_id"] > 0) {
            /*
             * Lecture du poisson
             */
            $poisson = new Poisson($this->connection, $this->paramori);
            $dataPoisson = $poisson->lire($data["poisson_id"]);
            if ($dataPoisson["poisson_id"] > 0 && $dataPoisson["poisson_statut_id"] == 1) {
                /*
                 * Mise a niveau du statut : le poisson est mort
                 */
                $dataPoisson["poisson_statut_id"] = 2;
                $poisson->ecrire($dataPoisson);
            }
        }
        return $mortalite_id;
    }

    /**
     * Retourne la liste des mortalites pour un poisson
     *
     * @param unknown $poisson_id
     * @return Ambigous <tableau, boolean, $data, string>
     */
    function getListByPoisson($poisson_id)
    {
        if ($poisson_id > 0 && is_numeric($poisson_id)) {
            $sql = "select mortalite_id, mortalite.poisson_id, mortalite_date, mortalite_commentaire,
					mortalite_type_libelle, evenement_type_libelle, mortalite.evenement_id
					from mortalite 
					left outer join mortalite_type using (mortalite_type_id)
					left outer join evenement using (evenement_id)
					left outer join evenement_type using (evenement_type_id)
					where mortalite.poisson_id = " . $poisson_id . " order by mortalite_date desc";
            return $this->getListeParam($sql);
        }
    }

    /**
     * Lit un enregistrement à partir de l'événement
     *
     * @param int $evenement_id
     * @return array
     */
    function getDataByEvenement($evenement_id)
    {
        if ($evenement_id > 0 && is_numeric($evenement_id)) {
            $sql = "select * from mortalite where evenement_id = " . $evenement_id;
            return $this->lireParam($sql);
        }
    }
}

/**
 * ORM de gestion de la table parent_poisson
 *
 * @author quinton
 *        
 */
class Parent_poisson extends ObjetBDD
{

    /**
     * Constructeur de la classe
     *
     * @param
     *            instance ADODB $bdd
     * @param array $param
     */
    function __construct($bdd, $param = null)
    {
        $this->param = $param;
        $this->table = "parent_poisson";
        $this->id_auto = 1;
        $this->colonnes = array(
            "parent_poisson_id" => array(
                "type" => 1,
                "key" => 1,
                "requis" => 1,
                "defaultValue" => 0
            ),
            "poisson_id" => array(
                "type" => 1,
                "requis" => 1,
                "parentAttrib" => 1
            ),
            "parent_id" => array(
                "type" => 1,
                "requis" => 1
            )
        );
        if (! is_array($param))
            $param = array();
        $param["fullDescription"] = 1;
        parent::__construct($bdd, $param);
    }

    /**
     * Retourne la liste des poissons parents
     *
     * @param int $poisson_id
     * @return array
     */
    function getListParent($poisson_id)
    {
        if ($poisson_id > 0 && is_numeric($poisson_id)) {
            $sql = "select parent_poisson_id, par.poisson_id, parent_id, matricule, pittag_valeur, prenom, sexe_libelle, cohorte
					from " . $this->table . " par
					join poisson pois on (par.parent_id = pois.poisson_id)
					left outer join sexe using (sexe_id)
					left outer join v_pittag_by_poisson pit on (pois.poisson_id = pit.poisson_id)
					where par.poisson_id = " . $poisson_id . " order by matricule, pittag_valeur, prenom ";
            return $this->getListeParam($sql);
        }
    }

    /**
     * Retourne les parents
     *
     * @param int $id
     * @return array
     */
    function lireAvecParent($id)
    {
        if ($id > 0 && is_numeric($id)) {
            $sql = "select parent_poisson_id, parent_poisson.poisson_id, parent_id,
				matricule, prenom, pittag_valeur
				from " . $this->table . "
				join poisson on (parent_poisson.parent_id = poisson.poisson_id)
				left outer join v_pittag_by_poisson pit on (poisson.poisson_id = pit.poisson_id)
				where parent_poisson_id = " . $id;
            return $this->lireParam($sql);
        }
    }

    /**
     * Retourne la liste des enfants attaches a un parent
     *
     * @param int $parent_id
     * @return array
     */
    function lireEnfant($parent_id)
    {
        if ($parent_id > 0 && is_numeric($parent_id)) {
            $sql = "select parent_poisson_id, parent_poisson.poisson_id, parent_id,
				matricule, prenom, pittag_valeur
				from " . $this->table . "
				join poisson on (parent_poisson.poisson_id = poisson.poisson_id)
				left outer join v_pittag_by_poisson pit on (poisson.poisson_id = pit.poisson_id)
				where parent_id = " . $parent_id;
        }
        return $this->getListeParam($sql);
    }
}

class Sortie extends ObjetBDD
{

    /**
     * Constructeur de la classe
     *
     * @param
     *            instance ADODB $bdd
     * @param array $param
     */
    function __construct($bdd, $param = null)
    {
        $this->paramori = $param;
        $this->param = $param;
        $this->table = "sortie";
        $this->id_auto = "1";
        $this->colonnes = array(
            "sortie_id" => array(
                "type" => 1,
                "key" => 1,
                "requis" => 1,
                "defaultValue" => 0
            ),
            "poisson_id" => array(
                "type" => 1,
                "requis" => 1,
                "parentAttrib" => 1
            ),
            "evenement_id" => array(
                "type" => 1
            ),
            "sortie_lieu_id" => array(
                "type" => 1
            ),
            "sortie_date" => array(
                "type" => 2
            ),
            "sortie_commentaire" => array(
                "type" => 0
            ),
            "sevre" => array(
                "type" => 0
            )
        );
        if (! is_array($param))
            $param = array();
        $param["fullDescription"] = 1;
        parent::__construct($bdd, $param);
    }

    /**
     * Surcharge de la fonction ecrire
     * pour mettre a jour le statut du poisson
     * (non-PHPdoc)
     *
     * @see ObjetBDD::ecrire()
     */
    function ecrire($data)
    {
        $sortie_id = parent::ecrire($data);
        if ($sortie_id > 0 && $data["poisson_id"] > 0) {
            /*
             * Lecture du poisson
             */
            $poisson = new Poisson($this->connection, $this->paramori);
            $dataPoisson = $poisson->lire($data["poisson_id"]);
            if ($dataPoisson["poisson_id"] > 0 && $dataPoisson["poisson_statut_id"] == 1) {
                /*
                 * Mise a niveau du statut : le poisson a quitte l'elevage
                 */
                $dataPoisson["poisson_statut_id"] = 4;
                $poisson->ecrire($dataPoisson);
            }
        }
        return $sortie_id;
    }

    /**
     * Retourne la liste des sorties pour un poisson
     *
     * @param unknown $poisson_id
     * @return Ambigous <tableau, boolean, $data, string>
     */
    function getListByPoisson($poisson_id)
    {
        if ($poisson_id > 0 && is_numeric($poisson_id)) {
            $sql = "select sortie_id, sortie.poisson_id, sortie_date, sortie_commentaire,
					localisation, evenement_type_libelle, sortie.evenement_id, sevre
					from sortie
					left outer join sortie_lieu using (sortie_lieu_id)
					left outer join evenement using (evenement_id)
					left outer join evenement_type using (evenement_type_id)
					where sortie.poisson_id = " . $poisson_id . " order by sortie_date desc";
            return $this->getListeParam($sql);
        }
    }

    /**
     * Lit un enregistrement à partir de l'événement
     *
     * @param int $evenement_id
     * @return array
     */
    function getDataByEvenement($evenement_id)
    {
        if ($evenement_id > 0 && is_numeric($evenement_id)) {
            $sql = "select sortie_id, poisson_id, sortie_date, sortie_commentaire,
					localisation, evenement_id, sortie_lieu_id, sevre
					from sortie
					left outer join sortie_lieu using (sortie_lieu_id)
					where evenement_id = " . $evenement_id;
            return $this->lireParam($sql);
        }
    }
}

class SortieLieu extends ObjetBDD
{

    /**
     * Constructeur de la classe
     *
     * @param
     *            instance ADODB $bdd
     * @param array $param
     */
    function __construct($bdd, $param = null)
    {
        $this->paramori = $param;
        $this->param = $param;
        $this->table = "sortie_lieu";
        $this->id_auto = "1";
        $this->colonnes = array(
            "sortie_lieu_id" => array(
                "type" => 1,
                "key" => 1,
                "requis" => 1,
                "defaultValue" => 0
            ),
            "localisation" => array(
                "type" => 0,
                "requis" => 1
            ),
            "longitude_dd" => array(
                "type" => 1
            ),
            "latitude_dd" => array(
                "type" => 1
            ),
            "point_geom" => array(
                "type" => 4
            ),
            "actif" => array(
                "type" => 1
            ),
            "poisson_statut_id" => array(
                "type" => 1,
                "defaultValue" => 4
            )
        );
        if (! is_array($param))
            $param = array();
        $param["fullDescription"] = 1;
        $param["srid"] = 4326;
        parent::__construct($bdd, $param);
    }

    /**
     * Retourne la liste des lieux de sortie, actifs ou non
     *
     * @param int $actif
     *            [-1 | 0 | 1]
     * @return array
     */
    function getListeActif($actif = -1)
    {
        $sql = "select sortie_lieu_id, localisation, longitude_dd, latitude_dd,
				actif, poisson_statut_id, poisson_statut_libelle
				from sortie_lieu
				left outer join poisson_statut using (poisson_statut_id)
				";
        if ($actif > - 1 && is_numeric($actif)) {
            $where = " where actif = " . $actif;
        } else {
            $where = "";
        }
        $order = " order by localisation";
        return $this->getListeParam($sql . $where . $order);
    }

    /**
     * Surcharge de la fonction ecrire pour rajouter le point geographique
     * (non-PHPdoc)
     *
     * @see ObjetBDD::ecrire()
     */
    function ecrire($data)
    {
        /*
         * Preparation du point geometrique
         */
        if (strlen($data["longitude_dd"]) > 0 && strlen($data["latitude_dd"]) > 0) {
            $data["point_geom"] = "POINT(" . $data["longitude_dd"] . " " . $data["latitude_dd"] . ")";
        }
        return parent::ecrire($data);
    }
}

/**
 * ORM de gestion de la table echographie
 *
 * @author quinton
 *        
 */
class Echographie extends ObjetBDD
{

    public function __construct($p_connection, $param = NULL)
    {
        $this->param = $param;
        $this->paramori = $param;
        $this->table = "echographie";
        $this->id_auto = "1";
        $this->colonnes = array(
            "echographie_id" => array(
                "type" => 1,
                "key" => 1,
                "requis" => 1,
                "defaultValue" => 0
            ),
            "evenement_id" => array(
                "type" => 1,
                "requis" => 1
            ),
            "poisson_id" => array(
                "type" => 1,
                "parentAttrib" => 1,
                "requis" => 1
            ),
            "echographie_date" => array(
                "type" => 2,
                "requis" => 1
            ),
            "echographie_commentaire" => array(
                "type" => 0
            ),
            "cliche_nb" => array(
                "type" => 1
            ),
            "cliche_ref" => array(
                "type" => 0
            ),
            "stade_gonade_id" => array(
                "type" => 1
            ),
            "stade_oeuf_id" => array(
                "type" => 1
            )
        );
        if (! is_array($param))
            $param = array();
        $param["fullDescription"] = 1;
        
        parent::__construct($p_connection, $param);
    }

    /**
     * Retourne la liste des echographies realisees pour un poisson
     *
     * @param int $poisson_id
     * @return tableau
     */
    function getListByPoisson($poisson_id)
    {
        if ($poisson_id > 0 && is_numeric($poisson_id)) {
            $sql = "select echographie_id, evenement_id, e.poisson_id, 
					echographie_date, echographie_commentaire, 
					cliche_nb, cliche_ref, stade_oeuf_libelle, stade_gonade_libelle,
					evenement_type_libelle
					from echographie e
					left outer join evenement using (evenement_id)
					left outer join evenement_type using (evenement_type_id)
					left outer join stade_oeuf using (stade_oeuf_id)
					left outer join stade_gonade using (stade_gonade_id)
					where e.poisson_id = " . $poisson_id . "
					order by echographie_date desc";
            return $this->getListeParam($sql);
        } else
            return null;
    }

    /**
     * Retourne une echographie a partir du numero d'evenement
     *
     * @param unknown $evenement_id
     * @return array|NULL
     */
    function getDataByEvenement($evenement_id)
    {
        if ($evenement_id > 0 && is_numeric($evenement_id)) {
            $sql = "select * from echographie 
				where evenement_id = " . $evenement_id;
            return $this->lireParam($sql);
        } else
            return null;
    }

    /**
     * Retourne la liste des echographies pour l'annee consideree
     *
     * @param int $poisson_id
     * @param int $annee
     * @return tableau|NULL
     */
    function getListByYear($poisson_id, $annee)
    {
        if ($annee > 0 && is_numeric($annee) && is_numeric($poisson_id)) {
            $sql = "select echographie_id, evenement_id, e.poisson_id, 
					echographie_date, echographie_commentaire, 
					cliche_nb, cliche_ref, stade_oeuf_libelle, stade_gonade_libelle,
					evenement_type_libelle
					from echographie e
					left outer join evenement using (evenement_id)
					left outer join evenement_type using (evenement_type_id) 
					left outer join stade_oeuf using (stade_oeuf_id)
					left outer join stade_gonade using (stade_gonade_id)
					where extract(year from echographie_date) = " . $annee . "
					and e.poisson_id = " . $poisson_id . " 
					order by echographie_date desc";
            return $this->getListeParam($sql);
        } else
            return null;
    }
}

/**
 * ORM de gestion de la table anesthesie
 *
 * @author quinton
 *        
 */
class Anesthesie extends ObjetBDD
{

    public function __construct($p_connection, $param = NULL)
    {
        $this->param = $param;
        $this->paramori = $param;
        $this->table = "anesthesie";
        $this->id_auto = "1";
        $this->colonnes = array(
            "anesthesie_id" => array(
                "type" => 1,
                "key" => 1,
                "requis" => 1,
                "defaultValue" => 0
            ),
            "evenement_id" => array(
                "type" => 1,
                "requis" => 1
            ),
            "poisson_id" => array(
                "type" => 1,
                "parentAttrib" => 1,
                "requis" => 1
            ),
            "anesthesie_date" => array(
                "type" => 2,
                "requis" => 1
            ),
            "anesthesie_commentaire" => array(
                "type" => 0,
                "requis" => 1
            ),
            "anesthesie_produit_id" => array(
                "type" => 1,
                "requis" => 1
            ),
            "anesthesie_dosage" => array(
                "type" => 1
            )
        );
        if (! is_array($param))
            $param = array();
        $param["fullDescription"] = 1;
        
        parent::__construct($p_connection, $param);
    }

    /**
     * Retourne une anesthésie a partir du numero d'evenement
     *
     * @param unknown $evenement_id
     * @return array|NULL
     */
    function getDataByEvenement($evenement_id)
    {
        if ($evenement_id > 0 && is_numeric($evenement_id)) {
            $sql = "select * from anesthesie
					natural join anesthesie_produit
				where evenement_id = " . $evenement_id;
            return $this->lireParam($sql);
        } else
            return null;
    }

    /**
     * Retourne la liste des echographies realisees pour un poisson
     *
     * @param int $poisson_id
     * @return tableau
     */
    function getListByPoisson($poisson_id)
    {
        if ($poisson_id > 0 && is_numeric($poisson_id)) {
            $sql = "select anesthesie_id, evenement_id, e.poisson_id,
					anesthesie_date, anesthesie_commentaire,
					evenement_type_libelle,
					anesthesie_produit_libelle,
					anesthesie_dosage
					from anesthesie e
					natural join  evenement
					left outer join evenement_type using (evenement_type_id)
					natural join anesthesie_produit
					where e.poisson_id = " . $poisson_id . "
					order by anesthesie_date desc";
            return $this->getListeParam($sql);
        } else
            return null;
    }
}

/**
 * ORM de la table anesthesie_produit
 *
 * @author quinton
 *        
 */
class Anesthesie_produit extends ObjetBDD
{

    /**
     * Constructeur de la classe
     *
     * @param
     *            instance ADODB $bdd
     * @param array $param
     */
    function __construct($bdd, $param = null)
    {
        $this->param = $param;
        $this->table = "anesthesie_produit";
        $this->id_auto = "1";
        $this->colonnes = array(
            "anesthesie_produit_id" => array(
                "type" => 1,
                "key" => 1,
                "requis" => 1,
                "defaultValue" => 0
            ),
            "anesthesie_produit_libelle" => array(
                "type" => 0,
                "requis" => 1
            ),
            "anesthesie_produit_actif" => array(
                "type" => 1,
                "requis" => 1,
                "defaultValue" => 1
            )
        );
        if (! is_array($param))
            $param = array();
        $param["fullDescription"] = 1;
        parent::__construct($bdd, $param);
    }

    function getListeActif($actif = -1)
    {
        $sql = "select *
				from " . $this->table;
        if ($actif > - 1 && is_numeric($actif)) {
            $where = " where anesthesie_produit_actif = " . $actif;
        } else {
            $where = "";
        }
        $order = " order by anesthesie_produit_libelle";
        return $this->getListeParam($sql . $where . $order);
    }
}

/**
 * ORM de gestion de la table ventilation
 *
 * @author quinton
 *        
 */
class Ventilation extends ObjetBDD
{

    /**
     * Constructeur de la classe
     *
     * @param
     *            instance ADODB $bdd
     * @param array $param
     */
    private $sql = "select * from ventilation";

    private $order = " order by ventilation_date desc";

    function __construct($bdd, $param = null)
    {
        $this->paramori = $param;
        $this->param = $param;
        $this->table = "ventilation";
        $this->id_auto = "1";
        $this->colonnes = array(
            "ventilation_id" => array(
                "type" => 1,
                "key" => 1,
                "requis" => 1,
                "defaultValue" => 0
            ),
            "poisson_id" => array(
                "type" => 1,
                "requis" => 1,
                "parentAttrib" => 1
            ),
            "battement_nb" => array(
                "type" => 1,
                "requis" => 1
            ),
            "ventilation_date" => array(
                "type" => 3,
                "defaultValue" => "getDateHeure"
            ),
            "ventilation_commentaire" => array(
                "type" => 0
            )
        );
        if (! is_array($param))
            $param = array();
        $param["fullDescription"] = 1;
        parent::__construct($bdd, $param);
    }

    /**
     * Retourne la liste des releves pour un poisson
     *
     * @param int $poisson_id
     * @param
     *            int annee : annee de la campagne de reproduction, si requis
     * @return tableau
     */
    function getListByPoisson($poisson_id, $annee = 0)
    {
        if (is_numeric($poisson_id) && $poisson_id > 0) {
            $where = " where poisson_id = " . $poisson_id;
            if (is_numeric($annee) && $annee > 0) {
                $where .= " and extract(year from ventilation_date) = " . $annee;
            }
            return $this->getListeParam($this->sql . $where . $this->order);
        }
    }
}

/**
 * ORM de gestion de la table ventilation
 *
 * @author quinton
 *        
 */
class Genetique extends ObjetBDD
{

    /**
     * Constructeur de la classe
     *
     * @param
     *            instance ADODB $bdd
     * @param array $param
     */
    private $sql = "select * from genetique g
			left outer join nageoire using (nageoire_id)
			join evenement using (evenement_id)
			left outer join evenement_type using (evenement_type_id)";

    private $order = " order by genetique_date desc";

    function __construct($bdd, $param = null)
    {
        $this->paramori = $param;
        $this->param = $param;
        $this->table = "genetique";
        $this->id_auto = "1";
        $this->colonnes = array(
            "genetique_id" => array(
                "type" => 1,
                "key" => 1,
                "requis" => 1,
                "defaultValue" => 0
            ),
            "poisson_id" => array(
                "type" => 1,
                "requis" => 1,
                "parentAttrib" => 1
            ),
            "nageoire_id" => array(
                "type" => 1
            ),
            "genetique_date" => array(
                "type" => 2,
                "defaultValue" => "getDate"
            ),
            "genetique_commentaire" => array(
                "type" => 0
            ),
            "genetique_reference" => array(
                "type" => 0,
                "requis" => 1
            ),
            "evenement_id" => array(
                "type" => 1,
                "requis" => 1
            )
        );
        if (! is_array($param))
            $param = array();
        $param["fullDescription"] = 1;
        parent::__construct($bdd, $param);
    }

    /**
     * Retourne la liste des releves pour un poisson
     *
     * @param int $poisson_id
     * @param
     *            int annee : annee de la campagne de reproduction, si requis
     * @return tableau
     */
    function getListByPoisson($poisson_id, $annee = 0)
    {
        if (is_numeric($poisson_id) && $poisson_id > 0) {
            $where = " where g.poisson_id = " . $poisson_id;
            if (is_numeric($annee) && $annee > 0) {
                $where .= " and extract(year from genetique_date) = " . $annee;
            }
            return $this->getListeParam($this->sql . $where . $this->order);
        }
    }

    /**
     * Retrouve le prelevement attache a l'evenement
     *
     * @param int $evenement_id
     * @return array|NULL
     */
    function getDataByEvenement($evenement_id)
    {
        if ($evenement_id > 0 && is_numeric($evenement_id)) {
            $where = " where evenement_id = " . $evenement_id;
            return $this->lireParam($this->sql . $where);
        } else
            return null;
    }
}

/**
 * ORM de gestion de la table nageoire
 *
 * @author quinton
 *        
 */
class Nageoire extends ObjetBDD
{

    /**
     * Constructeur de la classe
     *
     * @param
     *            instance ADODB $bdd
     * @param array $param
     */
    function __construct($bdd, $param = null)
    {
        $this->param = $param;
        $this->table = "nageoire";
        $this->id_auto = "1";
        $this->colonnes = array(
            "nageoire_id" => array(
                "type" => 1,
                "key" => 1,
                "requis" => 1,
                "defaultValue" => 0
            ),
            "nageoire_libelle" => array(
                "type" => 0,
                "requis" => 1
            )
        );
        if (! is_array($param))
            $param = array();
        $param["fullDescription"] = 1;
        parent::__construct($bdd, $param);
    }
}

/**
 * Table de determination de la parente
 * @author quinton
 *
 */
class Parente extends ObjetBDD
{
    function __construct($bdd, $param = null)
    {
        $this->param = $param;
        $this->table = "parente";
        $this->id_auto = "1";
        $this->colonnes = array(
            "parente_id" => array(
                "type" => 1,
                "key" => 1,
                "requis" => 1,
                "defaultValue" => 0
            ),
            "evenement_id" => array(
                "type" => 1,
                "requis" => 1
            ),
            "poisson_id" => array(
                "type" => 1,
                "requis" => 1
            ),
            "determination_parente_id" => array(
                "type" => 1,
                "requis" => 1
            ),
            "parente_date" => array(
                "type" => 2,
                "requis" => 1
            ),
            "parente_commentaire" => array(
                "type" => 0
            )
        );
        if (! is_array($param))
            $param = array();
        $param["fullDescription"] = 1;
        parent::__construct($bdd, $param);
    }
    
    function getListByPoisson($poisson_id)
    {
        if ($poisson_id > 0 && is_numeric($poisson_id)) {
            $sql = "select parente_id, parente.poisson_id, parente_date, parente_commentaire,
					determination_parente_libelle, evenement_type_libelle, parente.evenement_id
					from parente
					left outer join determination_parente using (determination_parente_id)
					left outer join evenement using (evenement_id)
					left outer join evenement_type using (evenement_type_id)
					where parente.poisson_id = " . $poisson_id . " order by parente_date desc";
            return $this->getListeParam($sql);
        }
    }
    
    /**
     * Lit un enregistrement à partir de l'événement
     *
     * @param int $evenement_id
     * @return array
     */
    function getDataByEvenement($evenement_id)
    {
        if ($evenement_id > 0 && is_numeric($evenement_id)) {
            $sql = "select * from parente where evenement_id = " . $evenement_id;
            return $this->lireParam($sql);
        }
    }
}

/**
 * Table de parametres
 * methodes de determination de la parente
 * @author quinton
 *
 */
class DeterminationParente extends ObjetBDD
{
    
    /**
     * Constructeur de la classe
     *
     * @param
     *            instance ADODB $bdd
     * @param array $param
     */
    function __construct($bdd, $param = null)
    {
        $this->param = $param;
        $this->table = "determination_parente";
        $this->id_auto = "1";
        $this->colonnes = array(
            "determination_parente_id" => array(
                "type" => 1,
                "key" => 1,
                "requis" => 1,
                "defaultValue" => 0
            ),
            "determination_parente_libelle" => array(
                "type" => 0,
                "requis" => 1
            )
        );
        if (! is_array($param))
            $param = array();
            $param["fullDescription"] = 1;
            parent::__construct($bdd, $param);
    }
}

?>