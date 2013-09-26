/* Fonctions generiques javascript */

/**
 * Fonction permettant de forcer la variable typeAction pour verifier la volonte de l'utilisateur
 * Obsolete - conserve pour compatibilite avec de vieux modules
 * @param nomForm : nom du formulaire
 * @param nomChamp : nom du champ contenant la variable action
 * @param typeAction : type d'action a tester
 */
function setAction(nomForm, nomChamp, typeAction) {
	nomChamp.value = typeAction;
	// Confirmation de la suppression
	if (typeAction == "S" || typeAction == "suppr") {
		if (confirm("Confirmez-vous la suppression ?") != 1) {
			nomChamp.value = "X";
		}
	}
	nomForm.submit();
}

function setDataTables(nomTable, bPaginate, bSort, bFilter) {
	if (bPaginate == null) bPaginate = true;
	if (bSort == null) bSort = false;
	if (bFilter == null) bFilter = false;
	$(document).ready( function () {
		$('#'+nomTable).dataTable( {
			 "bPaginate": bPaginate,
			"bFilter": bFilter,
	        "bSort": bSort,
	        "iDisplayLength": 25,
			 "oLanguage": {
		            "sLengthMenu": "Afficher _MENU_ lignes par page",
		            "sZeroRecords": "Pas de données",
		            "sInfo": "_START_ - _END_ / _TOTAL_ lignes",
		            "sInfoEmpty": "0 - 0 / 0 lignes",
		            "sInfoFiltered": "(Filtré pour _MAX_ total lignes)",
		            "sSearch": "Chercher...",
		            "oPaginate": {
		                "sFirst":      "Premier",
		                "sPrevious":   "Pr&eacute;c&eacute;dent",
		                "sNext":       "Suivant",
		                "sLast":       "Dernier"
		            }
		        },
		        "sDom": 'T<"clear">lfrtip',
				"oTableTools": {
					"sSwfPath": "display/javascript/DataTables-1.9.4/extras/TableTools/media/swf/copy_csv_xls_pdf.swf",
					"aButtons": [ 
									{
									"sExtends":	"print",
									"sButtonText": "Imprimer",
									"sToolTip": "Imprimer le tableau"
									}, 
									{
									"sExtends":	"pdf",
									"sButtonText": "PDF",
									"sToolTip": "Exporter au format PDF"
									},
									{
									"sExtends":	"csv",
									"sButtonText": "CSV",
									"sToolTip": "Exporter au format CSV (séparateur : virgule)"
									},
									{
									"sExtends":	"copy",
									"sButtonText": "Copier",
									"sToolTip": "Copier dans le presse-papiers"
									} ]
				}		
		} );
	} );
}

/**
 * Fonction permettant de fixer une valeur dans un champ
 * et d'envoyer ensuite le formulaire
 * obsolete
 */
function setValeur(nomForm, nomChamp, valeur) {
	nomChamp.value = valeur;
	nomForm.submit();
}
/**
 * Fonction identique a setAction, mais pour confirmation d'une operation
 * Fonction egalement obsolete 
 * @param nomForm
 * @param nomChamp
 * @param typeAction
 */
function confirmAction(nomForm, nomChamp, typeAction) {
	nomChamp.value = typeAction;
	if (confirm("Confirmez-vous l'op�ration ?") != 1) {
		nomChamp.value = "X";
	}
}
/**
 * Fonction permettant de verifier que les mots de passe entres dans les deux zones
 * sont identiques
 * @param pass1
 * @param pass2
 * @returns {Boolean}
 */
function verifieMdp(pass1, pass2) {
	if (pass1.value != pass2.value && pass1.value.length > 0
			&& pass2.value.length > 0) {
		alert("\erreur: les mots de passes ne correspondent pas");
		action.value = "X";
		return false;
	} else {
		return true;
	}
}
/**
 * Fonction permettant de verifier les champs obligatoires lors de l'envoi d'un
 * formulaire La liste des champs obligatoires doit etre construite ainsi :
 * idChamp:libelle affiche,idChamp2:libelle affiche champ2,etc... La fonction
 * s'appelle dans le formulaire ainsi (exemple avec controle sur id departement
 * et nom) : <form action="index.php" method="post" onSubmit='return
 * validerForm("departement:D�partement;nom:Nom")'>
 * 
 * @returns boolean
 */
function validerForm(nomChampAction, listeId) {
	/*
	 * Verification des zones obligatoires
	 */
	var retour = true;
	var message = "";
	if (listeId.length > 0) {
		var listeChamp = listeId.split(',');
		var i = 0;
		for (i = 0; i < listeChamp.length; i++) {
			var detailChamp = listeChamp[i].split(':');
			if (document.getElementById(detailChamp[0]).value.length == 0) {
				if (detailChamp[1] == undefined)
					detailChamp[1] = detailChamp[0];
				message = message + "Le champ " + detailChamp[1]
						+ " est obligatoire\n";
				retour = false;
			}
		}
	}
	if (retour == false) {
		alert(message);
	}
	return retour;
}
/**
 * Fonction permettant de verifier la volonte de l'utilisateur
 * @param message : texte a afficher
 * @returns {Boolean}
 */
function confirmEnvoi(message) {
	if (message.length==0) message = "Confirmez-vous l'op�ration ?"; 
	if (confirm(message) == 1) {
		return true;
	} else {
		return false;
	}
}
/**
 * Fonction verifiant la volonte de suppression
 * @returns {Boolean}
 */
function confirmSuppression() {
	return confirmEnvoi("Confirmez-vous la suppression ?");
}
/**
 * Gestion Ajax
 */
var xhr = null;
/**
 * Initialisation de l'objet AJAX
 */
function getXhr() {
	if (window.XMLHttpRequest)
		xhr = new XMLHttpRequest();
	else if (window.ActiveXObject) {
		try {
			xhr = new ActiveXObject("Msxml2.XMLHTTP");
		} catch (e) {
			xhr = new ActiveXObject("Microsoft.XMLHTTP");
		}
	} else {
		alert("Votre navigateur ne supporte pas les objets XMLHTTPRequest, veuillez le mettre a jour");
		xhr = false;
	}
}
/**
 * Fonction modifiant la zone champSelect en recuperant une liste d'items depuis
 * zone La requete Ajax doit renvoyer une liste au format JSON Chaque item
 * renvoye par la requete Ajax doit disposer obligatoirement de deux attributs,
 * dont le nom doit etre imperativement : id et value
 * 
 * Apres execution de la requete et remplissage du select, les champs indiques
 * dans champActifs sont actives (ils peuvent ainsi etre desactives tant que la
 * recherche n'a pas ete lancee) Si la requete ne ramene aucune donnee, les
 * champs correspondants sont desactives
 * 
 * Exemple de requete PHP pour restituer un format JSON :
$liste = $exploitant->recherche($_REQUEST["libelle"]);
$retour='{"Liste":[';
$flag=0;
foreach ($liste as $key=>$value) {
	if($flag==0) $flag=1 ; else $retour.=",";
	$retour.='{"id":"'.$liste[$key]["idExploitant"].'","value":"'.utf8_encode($liste[$key]["numPacage"]." - "
			.$liste[$key]["nom"]." ".$liste[$key]["prenom"])." - ".$liste[$key]["communeAdressePostale"].'"}';
}
$retour.=']}';
print($retour);
 * 
 * @param zone :
 *            id de la zone contenant le texte a rechercher
 * @param longueur :
 *            longueur minimale de zone pour que la recherche soit declenchee
 * @param champSelect :
 *            id du champ select concerne
 * @param adresse :
 *            adresse http pour interrogation Ajax
 * @param champsActifs :
 *            liste des id des champs a activer apres execution de la requete -
 *            separateur : virgule
 */
function rechercheSelect(zone, longueur, champSelect, adresse, champsActifs) {
	libelle = document.getElementById(zone).value;
	if (libelle.length >= longueur) {
		viderSelect(champSelect);
		getXhr();
		var retourOK = false;
		xhr.open("GET", adresse + libelle, true);
		xhr.onreadystatechange = function() {
			if (xhr.readyState == 4 && xhr.status == 200) {
				var resultat = xhr.responseText;
				var tableau = eval("(" + resultat + ")");
				var x = document.getElementById(champSelect);
				for (key in tableau["Liste"]) {
					retourOK = true;
					var option = document.createElement("option");
					option.text = tableau.Liste[key].value;
					option.value = tableau.Liste[key].id;
					// alert (option.value);
					if (key == 0) {
						option.selected = true;
					}
					x.add(option);
				}
				/*
				 * Activation des champs
				 */
				var listeChamps = champsActifs.split(",");
				if (retourOK == true) {
					x.disabled = false;
					var i = 0;
					for (i = 0; i < listeChamps.length; i++) {
						document.getElementById(listeChamps[i]).disabled = false;
					}
				} else {
					/*
					 * Desactivation des champs
					 */
					x.disabled = "disabled";
					var i = 0;
					for (i = 0; i < listeChamps.length; i++) {
						document.getElementById(listeChamps[i]).disabled = "disabled";
					}
				}
			}
		}
		xhr.send(null);
	}
}
/**
 * Fonction permettant de vider champselect de toutes ses options
 * 
 * @param chamspelect
 */
function viderSelect(champselect) {
	var x = document.getElementById(champselect);
	// var nbtotal=x.length - 1;
	while (x.length > 0) {
		x.remove(x.length - 1);
	}
}