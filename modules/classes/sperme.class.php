<?php

/**
 * @author Eric Quinton
 * @copyright Copyright (c) 2015, IRSTEA / Eric Quinton
 * @license http://www.cecill.info/licences/Licence_CeCILL-C_V1-fr.html LICENCE DE LOGICIEL LIBRE CeCILL-C
 *  Creation 25 mars 2015
 */
class Sperme extends ObjetBDD
{

    private $sql = "select distinct on (sperme_date, sperme_id) sperme_id, poisson_campagne_id, sperme_date,
					sequence_id, sequence_nom,
					 sperme_commentaire, 
					sperme_qualite_libelle,
					sperme_mesure_date,
					motilite_initiale, tx_survie_initial,
					motilite_60, tx_survie_60, temps_survie,
					sperme_ph,
					sperme_aspect_id, sperme_aspect_libelle,
					poisson_campagne.annee, poisson_id, matricule, prenom,
					congelation_dates";

    private $from = " from sperme
					natural join poisson_campagne
					natural join poisson
					left outer join sperme_aspect using (sperme_aspect_id)
					left outer join sperme_mesure using (sperme_id)
					left outer join sperme_qualite using (sperme_qualite_id)
					left outer join sequence using (sequence_id)
					left outer join v_sperme_congelation_date using (sperme_id)";

    function __construct($bdd, $param = null)
    {
        $this->param = $param;
        $this->paramori = $param;
        $this->table = "sperme";
        $this->id_auto = "1";
        $this->colonnes = array(
            "sperme_id" => array(
                "type" => 1,
                "key" => 1,
                "requis" => 1,
                "defaultValue" => 0
            ),
            "poisson_campagne_id" => array(
                "type" => 1,
                "requis" => 1,
                "parentAttrib" => 1
            ),
            "sequence_id" => array(
                "type" => 1,
                "requis" => 1
            ),
            "sperme_aspect_id" => array(
                "type" => 1
            ),
            "sperme_date" => array(
                "type" => 3,
                "requis" => 1,
                "defaultValue" => "getDateHeure"
            ),
            "sperme_volume" => array(
                "type" => 1
            ),

            "sperme_commentaire" => array(
                "type" => 0
            ),
            "sperme_dilueur_id" => array(
                "type" => 1
            )
        );
        if (!is_array($param))
            $param = array();
        $param["fullDescription"] = 1;
        parent::__construct($bdd, $param);
    }

    /**
     * Surcharge pour ecrire la mesure realisee en meme temps que le prelevement
     *
     * @see ObjetBDD::ecrire()
     */
    function ecrire($data)
    {
        $id = parent::ecrire($data);
        if ($id > 0 && strlen($_REQUEST["sperme_mesure_date"]) > 0) {

            $data["sperme_id"] = $id;
            /*
             * Ecriture des caracteristiques
             */
            $this->ecrireTableNN("sperme_caract", "sperme_id", "sperme_caracteristique_id", $data["sperme_id"], $data["sperme_caracteristique_id"]);
            /*
             * Ecriture des mesures realisees lors du prelevement
             */
            $spermeMesure = new SpermeMesure($this->connection, $this->paramori);
            $spermeMesure->ecrire($data);
        }
        return $id;
    }

    /**
     * Surcharge de la fonction supprimer pour effacer les enregistrements lies
     *
     * {@inheritdoc}
     *
     * @see ObjetBDD::supprimer()
     */
    function supprimer($id)
    {
        if (is_numeric($id) && $id > 0) {
            /*
             * Suppression des informations rattachees
             */
            $spermeCaract = new SpermeCaract($this->connection, $this->paramori);
            $spermeCaract->supprimerChamp($id, "sperme_id");
            $spermeMesure = new SpermeMesure($this->connection, $this->paramori);
            $spermeMesure->supprimerChamp($id, "sperme_id");
            return parent::supprimer($id);
        }
    }

    /**
     * Retourne la liste des prelevements de sperme pour un poisson
     *
     * @param int $poisson_campagne_id
     * @return tableau|NULL
     */
    function getListFromPoissonCampagne($poisson_campagne_id)
    {
        if ($poisson_campagne_id > 0 && is_numeric($poisson_campagne_id)) {
            $this->types["sperme_mesure_date"] = 3;
            $this->colonnes["sperme_mesure_date"] = array(
                "type" => 3
            );
            // printr($this->colonnes);
            $where = " where poisson_campagne_id = " . $poisson_campagne_id . "
					order by sperme_date, sperme_id";
            return $this->getListeParam($this->sql . $this->from . $where);
        } else
            return null;
    }

    /**
     * Retourne la liste des spermes disponibles pour la liste des poissons fournis
     *
     * @param array $poissons
     * @return tableau
     */
    function getListPotentielFromPoissons($poissons)
    {
        /*
         * Verification que les poissons contiennent bien des valeurs numeriques
         */
        $ok = true;
        foreach ($poissons as $value) {
            if (!is_numeric($value))
                $ok = false;
        }
        if (count($poissons) == 0)
            $ok = false;
        if ($ok == true) {
            $where = " where poisson_id in (";
            $comma = "";
            foreach ($poissons as $value) {
                $where .= $comma . $value;
                $comma = ",";
            }
            $where .= ")";
            $order = " order by matricule, sperme_date";
            return $this->getListeParam($this->sql . $this->from . $where . $order);
        }
    }

    /**
     * Recherche la liste de tous les spermes potentiels pour un croisement (congeles ou ceux de la sequence)
     *
     * @param int $croisement_id
     * @return tableau
     */
    function getListPotentielFromCroisement($croisement_id)
    {
        if ($croisement_id > 0 && is_numeric($croisement_id)) {
            $sql = $this->sql . " ,congelation_date, sg.sperme_congelation_id, sg.nb_paillette, sg.nb_paillettes_utilisees ";
            $from = $this->from . " left outer join sperme_congelation sg using (sperme_id) ";
            $where = " where poisson_id in (
					select poisson_id from croisement
					join poisson_croisement using (croisement_id)
					join poisson_campagne using (poisson_campagne_id)
					where croisement_id = " . $croisement_id . "
						and (congelation_date is not null
                    or sperme.sequence_id = croisement.sequence_id))
                    ";
            $order = " order by prenom, matricule, sperme_date";
            $sql = "with req as ($sql $from $where ) select * from req $order";
            return $this->getListeParam($sql);
        }
    }

    /**
     * Lit un enregistrement a partir du numero de poisson_campagne et de la sequence
     *
     * @param int $poissonCampagneId
     * @param int $sequenceId
     * @return array
     */
    function lireFromSequence($poissonCampagneId, $sequenceId)
    {
        if (is_numeric($poissonCampagneId) && is_numeric($sequenceId) && $poissonCampagneId > 0 && $sequenceId > 0) {
            /*
             * Recherche de l'identifiant correspondant
             */
            $sql = "select sperme_id from sperme where poisson_campagne_id = " . $poissonCampagneId . "
					and sequence_id = " . $sequenceId;
            $data = $this->lireParam($sql);
            $data["sperme_id"] > 0 ? $id = $data["sperme_id"] : $id = 0;
            return $this->lire($id, false);
        }
    }

    /**
     * Surcharge de la fonction lire, pour recuperer la mesure effectuee le meme jour que le prelevement
     *
     * @see ObjetBDD::lire()
     */
    function lire($id, $getDefault = false, $defaultValue = "")
    {
        $data = parent::lire($id, $getDefault, $defaultValue);
        if ($data["sperme_id"] > 0) {
            $spermeMesure = new SpermeMesure($this->connection, $this->paramori);
            $dataMesure = $spermeMesure->getFromSpermeDate($data["sperme_id"]);
            foreach ($dataMesure as $key => $value)
                $data[$key] = $value;
        }
        return $data;
    }
}

/**
 * ORM de gestion de la table sperme_qualite
 *
 * @author quinton
 *        
 */
class SpermeQualite extends ObjetBDD
{

    function __construct($bdd, $param = null)
    {
        $this->param = $param;
        $this->paramori = $param;
        $this->table = "sperme_qualite";
        $this->id_auto = "1";
        $this->colonnes = array(
            "sperme_qualite_id" => array(
                "type" => 1,
                "key" => 1,
                "requis" => 1,
                "defaultValue" => 0
            ),
            "sperme_qualite_libelle" => array(
                "type" => 0,
                "requis" => 1
            )
        );
        if (!is_array($param))
            $param = array();
        $param["fullDescription"] = 1;
        parent::__construct($bdd, $param);
    }
}

class SpermeCongelation extends ObjetBDD
{

    private $sql = "select sperme_congelation_id, sperme_id, congelation_date, congelation_volume,
			sperme_dilueur_id, sperme_dilueur_libelle, nb_paillette, 
			nb_visiotube, sperme_congelation_commentaire,
            sperme_conservateur_id, sperme_conservateur_libelle,
            volume_sperme, volume_dilueur, volume_conservateur,
            nb_paillettes_utilisees
			from sperme_congelation 
			left outer join sperme_dilueur using (sperme_dilueur_id)
            left outer join sperme_conservateur using (sperme_conservateur_id)
             ";

    function __construct($bdd, $param = null)
    {
        $this->param = $param;
        $this->paramori = $param;
        $this->table = "sperme_congelation";
        $this->id_auto = "1";
        $this->colonnes = array(
            "sperme_congelation_id" => array(
                "type" => 1,
                "key" => 1,
                "requis" => 1,
                "defaultValue" => 0
            ),
            "sperme_id" => array(
                "type" => 1,
                "requis" => 1,
                "parentAttrib" => 1
            ),

            "congelation_date" => array(
                "type" => 2,
                "requis" => 1
            ),
            "congelation_volume" => array(
                "type" => 1
            ),
            "sperme_dilueur_id" => array(
                "type" => 1
            ),
            "nb_paillette" => array(
                "type" => 1
            ),
            "nb_visiotube" => array(
                "type" => 1
            ),
            "sperme_conservateur_id" => array(
                "type" => 1,
                "defaultValue" => 1
            ),
            "volume_sperme" => array(
                "type" => 1
            ),
            "volume_dilueur" => array(
                "type" => 1
            ),
            "volume_conservateur" => array(
                "type" => 1
            ),
            "nb_paillettes_utilisees" => array(
                "type" => 1
            ),
            "sperme_congelation_commentaire" => array(
                "type" => 0
            )
        );
        if (!is_array($param))
            $param = array();
        $param["fullDescription"] = 1;
        parent::__construct($bdd, $param);
    }

    /**
     * Retourne la liste des congelations associees a un sperme
     *
     * @param int $sperme_id
     */
    function getListFromSperme($sperme_id)
    {
        if ($sperme_id > 0) {
            $where = " where sperme_id = :sperme_id";
            $order = " order by congelation_date";
            $arg = array(
                "sperme_id" => $sperme_id
            );;
            return $this->getListeParamAsPrepared($this->sql . $where . $order, $arg);
        }
    }
}

/**
 * ORM de gestion de la table sperme_mesure
 *
 * @author quinton
 *        
 */
class SpermeMesure extends ObjetBDD
{

    private $sql = "select sperme_mesure_id, sperme_id, sperme_mesure_date,
			motilite_initiale, tx_survie_initial, motilite_60, tx_survie_60, temps_survie,
			sperme_ph, nb_paillette_utilise, sperme_qualite_id, sperme_qualite_libelle,
            sperme_congelation_id
			from sperme_mesure
			join sperme using (sperme_id)
			left outer join sperme_qualite using (sperme_qualite_id)";

    private $order = " order by sperme_mesure_date";

    function __construct($bdd, $param = null)
    {
        $this->param = $param;
        $this->paramori = $param;
        $this->table = "sperme_mesure";
        $this->id_auto = 1;
        $this->colonnes = array(
            "sperme_mesure_id" => array(
                "type" => 1,
                "key" => 1,
                "requis" => 1,
                "defaultValue" => 0
            ),
            "sperme_id" => array(
                "type" => 1,
                "requis" => 1,
                "parentAttrib" => 1
            ),
            "sperme_qualite_id" => array(
                "type" => 1
            ),
            "sperme_mesure_date" => array(
                "type" => 3,
                "requis" => 1,
                "defaultValue" => "getDateHeure"
            ),
            "motilite_initiale" => array(
                "type" => 1
            ),
            "tx_survie_initial" => array(
                "type" => 1
            ),
            "motilite_60" => array(
                "type" => 1
            ),
            "tx_survie_60" => array(
                "type" => 1
            ),
            "temps_survie" => array(
                "type" => 1
            ),
            "sperme_ph" => array(
                "type" => 1
            ),
            "nb_paillette_utilise" => array(
                "type" => 1
            ),
            "sperme_congelation_id" => array(
                "type" => 1
            )
        );
        if (!is_array($param))
            $param = array();
        $param["fullDescription"] = 1;
        parent::__construct($bdd, $param);
    }

    /**
     * Surcharge de la fonction ecrire, pour renseigner les caracteristiques et les mesures realisees au moment du prelevement
     *
     * @see ObjetBDD::ecrire()
     */
    function ecrire($data)
    {
        if ($data["sperme_mesure_id"] > 0) {
            $dataold = $this->lire($data["sperme_mesure_id"]);
        }
        $id = parent::ecrire($data);
        if ($id > 0 && $data["sperme_congelation_id"] > 0) {
            /*
             * Modification du nombre de paillettes utilisees
             */
            $data["sperme_mesure_id"] = $id;
            $sc = new SpermeCongelation($this->connection, $this->paramori);
            $dsc = $sc->lire($data["sperme_congelation_id"]);
            $dsc["nb_paillettes_utilisees"] = $dsc["nb_paillettes_utilisees"] - $dataold["nb_paillette_utilise"] + $data["nb_paillette_utilise"];
            $sc->ecrire($dsc);
        }
        return $id;
    }

    /**
     * Recherche la liste des analyses effectuees
     *
     * @param int $sperme_id
     * @return array
     */
    function getListFromSperme($sperme_id)
    {
        if (is_numeric($sperme_id) && $sperme_id > 0) {
            $where = " where sperme_id = " . $sperme_id;
            return $this->getListeParam($this->sql . $where . $this->order);
        }
    }

    /**
     * Retourne la liste des analyses effectuees a partir d'une congelation
     *
     * @param int $sperme_congelation_id
     * @return array
     */
    function getListFromCongelation($sperme_congelation_id)
    {
        if (is_numeric($sperme_congelation_id) && $sperme_congelation_id > 0) {
            $where = " where sperme_congelation_id = $sperme_congelation_id ";
            return $this->getListeParam($this->sql . $where . $this->order);
        }
    }

    /**
     * Retourne l'analyse realisee le jour du prelevement
     *
     * @param int $sperme_id
     * @return array
     */
    function getFromSpermeDate($sperme_id)
    {
        if (is_numeric($sperme_id) && $sperme_id > 0) {
            $where = " where sperme_id = " . $sperme_id . "
				and sperme_date::date = sperme_mesure_date::date";
            $limit = " LIMIT 1";
            return $this->lireParam($this->sql . $where . $this->order . $limit);
        }
    }
}

/**
 * ORM de gestion de la table sperme_dilueur
 *
 * @author quinton
 *        
 */
class SpermeDilueur extends ObjetBDD
{

    function __construct($bdd, $param = null)
    {
        $this->param = $param;
        $this->paramori = $param;
        $this->table = "sperme_dilueur";
        $this->id_auto = "1";
        $this->colonnes = array(
            "sperme_dilueur_id" => array(
                "type" => 1,
                "key" => 1,
                "requis" => 1,
                "defaultValue" => 0
            ),
            "sperme_dilueur_libelle" => array(
                "type" => 0,
                "requis" => 1
            )
        );
        if (!is_array($param))
            $param = array();
        $param["fullDescription"] = 1;
        parent::__construct($bdd, $param);
    }
}

/**
 * ORM de gestion de la table sperme_caracteristique
 *
 * @author quinton
 *        
 */
class SpermeCaracteristique extends ObjetBDD
{

    function __construct($bdd, $param = null)
    {
        $this->param = $param;
        $this->paramori = $param;
        $this->table = "sperme_caracteristique";
        $this->id_auto = "1";
        $this->colonnes = array(
            "sperme_caracteristique_id" => array(
                "type" => 1,
                "key" => 1,
                "requis" => 1,
                "defaultValue" => 0
            ),
            "sperme_caracteristique_libelle" => array(
                "type" => 0,
                "requis" => 1
            )
        );
        if (!is_array($param))
            $param = array();
        $param["fullDescription"] = 1;
        parent::__construct($bdd, $param);
    }

    /**
     * Retourne la liste des caracteristiques, attachees ou non au sperme_id fourni
     *
     * @param number $sperme_id
     * @return array
     */
    function getFromSperme($sperme_id = 0)
    {
        if (strlen($sperme_id) == 0)
            $sperme_id = 0;
        if (is_numeric($sperme_id)) {
            $sql = "select s.sperme_caracteristique_id, s.sperme_caracteristique_libelle, sperme_id
					from sperme_caracteristique s
					left outer join sperme_caract c on (s.sperme_caracteristique_id = c.sperme_caracteristique_id 
					and c.sperme_id = " . $sperme_id . ")
						order by sperme_caracteristique_libelle";
            return $this->getListeParam($sql);
        }
    }
}

class SpermeCaract extends ObjetBDD
{

    function __construct($bdd, $param = null)
    {
        $this->table = "sperme_caract";
        $this->colonnes = array(
            "sperme_id" => array(
                "type" => 1,
                "requis" => 1
            ),
            "sperme_caracteristique_id" => array(
                "type" => 1,
                "requis" => 1
            )
        );
        if (!is_array($param))
            $param = array();
        $param["fullDescription"] = 1;
        parent::__construct($bdd, $param);
    }
}

/**
 * ORM de gestion de la table sperme_aspect
 *
 * @author quinton
 *        
 */
class SpermeAspect extends ObjetBDD
{

    function __construct($bdd, $param = null)
    {
        $this->param = $param;
        $this->paramori = $param;
        $this->table = "sperme_aspect";
        $this->id_auto = "1";
        $this->colonnes = array(
            "sperme_aspect_id" => array(
                "type" => 1,
                "key" => 1,
                "requis" => 1,
                "defaultValue" => 0
            ),
            "sperme_aspect_libelle" => array(
                "type" => 0,
                "requis" => 1
            )
        );
        if (!is_array($param))
            $param = array();
        $param["fullDescription"] = 1;
        parent::__construct($bdd, $param);
    }
}

/**
 * ORM de gestion de la table sperme_utilise
 *
 * @author quinton
 *        
 */
class SpermeUtilise extends ObjetBDD
{

    function __construct($bdd, $param = null)
    {
        $this->param = $param;
        $this->paramori = $param;
        $this->table = "sperme_utilise";
        $this->id_auto = "1";
        $this->colonnes = array(
            "sperme_utilise_id" => array(
                "type" => 1,
                "key" => 1,
                "requis" => 1,
                "defaultValue" => 0
            ),
            "croisement_id" => array(
                "type" => 1,
                "requis" => 1,
                "parentAttrib" => 1
            ),
            "sperme_id" => array(
                "type" => 1,
                "requis" => 1
            ),
            "volume_utilise" => array(
                "type" => 1
            ),
            "nb_paillette_croisement" => array(
                "type" => 1
            )
        );
        if (!is_array($param))
            $param = array();
        $param["fullDescription"] = 1;
        parent::__construct($bdd, $param);
    }

    /**
     * Surcharge de la fonction ecrire
     * pour mettre a jour le nombre de paillettes utilisees
     *
     * {@inheritdoc}
     * @see ObjetBDD::ecrire()
     */
    function ecrire($data)
    {
        /*
         * Recuperation des donnees anciennes pour lire le nombre de paillettes precedemment utilisees
         */
        $dataold = $this->lire($data["sperme_utilise_id"]);
        $retour = parent::ecrire($data);

        if ($retour > 0) {
            if (($dataold["nb_paillette_croisement"] > 0 || $data["nb_paillette_croisement"] > 0) && $data["sperme_congelation_id"] > 0) {
                $sc = new SpermeCongelation($this->connection, $this->paramori);
                $dsc = $sc->lire($data["sperme_congelation_id"]);
                $dsc["nb_paillettes_utilisees"] = $dsc["nb_paillettes_utilisees"] - $dataold["nb_paillette_croisement"] + $data["nb_paillette_croisement"];
                $sc->ecrire($dsc);
            }
        }
        return $retour;
    }

    /**
     * Fonction recuperant la liste des spermes utilises dans un croisement
     *
     * @param int $croisement_id
     * @return array
     */
    function getListFromCroisement($croisement_id)
    {
        if (is_numeric($croisement_id) && $croisement_id > 0) {
            $sql = "select sperme_utilise_id, matricule, prenom,
					sperme_date, congelation_date,
					volume_utilise, nb_paillette_croisement
					from sperme_utilise su
					join sperme s on  (s.sperme_id = su.sperme_id)
					join poisson_campagne using (poisson_campagne_id)
					join poisson using (poisson_id)
					left outer join sperme_congelation sc on (sc.sperme_congelation_id = su.sperme_congelation_id)";
            $where = " where croisement_id = " . $croisement_id;
            $order = " order by su.sperme_id";
            $this->types["sperme_date"] = 2;
            $this->types["congelation_date"] = 2;
            return $this->getListeParam($sql . $where . $order);
        }
    }
}

class SpermeConservateur extends ObjetBDD
{

    function __construct($bdd, $param = NULL)
    {
        $this->table = "sperme_conservateur";
        // Definition des formats des colonnes, et des controles a leur appliquer
        $this->colonnes = array(
            "sperme_conservateur_id" => array(
                "type" => 1,
                "requis" => 1,
                "key" => 1,
                "defaultValue" => 0
            ),
            "sperme_conservateur_libelle" => array(
                "requis" => 1
            )
        );
        // Si toutes les colonnes de la table sont decrites :
        $this->fullDescription = 1;
        // Appel du constructeur de la classe ObjetBDD

        parent::__construct($bdd, $param);
    }
}

class SpermeFreezingPlace extends ObjetBDD
{

    function __construct($bdd, $param = NULL)
    {
        $this->table = "sperme_freezing_place";
        // Definition des formats des colonnes, et des controles a leur appliquer
        $this->colonnes = array(
            "sperme_freezing_place_id" => array(
                "type" => 1,
                "requis" => 1,
                "key" => 1,
                "defaultValue" => 0
            ),
            "sperme_congelation_id" => array(
                "type" => 1,
                "requis" => 1,
                "parentAttrib" => 1
            ),
            "cuve_libelle" => array(
                "type" => 0
            ),
            "canister_numero" => array(
                "type" => 0
            ),
            "position_canister" => array(
                "type" => 1,
                "defaultValue" => 1
            ),
            "nb_visiotube" => array(
                "type" => 1
            )
        );
        // Si toutes les colonnes de la table sont decrites :
        $this->fullDescription = 1;
        // Appel du constructeur de la classe ObjetBDD

        parent::__construct($bdd, $param);
    }
}

class SpermeFreezingMeasure extends ObjetBDD
{

    function __construct($bdd, $param = NULL)
    {
        $this->table = "sperme_freezing_measure";
        // Definition des formats des colonnes, et des controles a leur appliquer
        $this->colonnes = array(
            "sperme_freezing_measure_id" => array(
                "type" => 1,
                "requis" => 1,
                "key" => 1,
                "defaultValue" => 0
            ),
            "sperme_congelation_id" => array(
                "type" => 1,
                "requis" => 1,
                "parentAttrib" => 1
            ),
            "measure_date" => array(
                "type" => 3,
                "requis" => 1
            ),
            "measure_temp" => array(
                "type" => 0,
                "requis" => 1
            )
        );

        // Appel du constructeur de la classe ObjetBDD

        parent::__construct($bdd, $param);
    }
    /**
     * Modification de la fonction de definition des colonnes par defaut,
     * pour recuperer le cas echeant l'heure de derniere mesure, augmentee
     * d'une minute
     * {@inheritDoc}
     * @see ObjetBDD::getDefaultValue()
     */
    function getDefaultValue($parentValue = 0)
    {
        $data = parent::getDefaultValue($parentValue);
        /*
         * Recherche de la derniere date enregistree
         */
        if ($parentValue > 0) {
            $sql = "select measure_date as mt from sperme_freezing_measure" .
                " where sperme_congelation_id = :id" .
                " order by measure_date desc limit 1";
            $last = $this->lireParamAsPrepared($sql, array("id" => $parentValue));
            if (strlen($last["mt"]) > 0) {
                $time = new DateTime($last["mt"]);
                date_add($time, new DateInterval("PT1M"));
                $data["measure_date"] = $this->formatDateDBversLocal(date_format($time, "Y-m-d H:i:s"), 3);
            }
        }
        return $data;
    }
}
