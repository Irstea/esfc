<script>
$(document).ready(function() { 
	$("select").change(function () {
		$("#search").submit();
	} );
	$(".confirmation").on('click', function () {
        return confirm("Confirmez-vous la suppression du reproducteur pour l'année considérée ?");
    } );
} ) ;
setDataTables("cpoissonList", false, false, true);
</script>
<form method="get" action="index.php" id="search">
<input type="hidden" name="module" value="poissonCampagneList">
<table class="tableaffichage">
<tr><td>Année : 
<select name="annee">
{section name=lst loop=$annees}
<option value="{$annees[lst].annee}" {if $annees[lst].annee == $annee}selected{/if}>
{$annees[lst].annee}
</option>
{/section}
</select>
<input type="submit" value="Rechercher">
</td>
</tr>
</table>
</form>

<table id="cpoissonList" class="tableaffichage">
<thead>
<tr>
<th>Données<br>d'élevage</th>
<th>Matricule</th>
<th>Prénom</th>
<th>Pittag</th>
<th>Cohorte</th>
<th>Sexe</th>
<th>Tx de croissance<br>journalier</th>
<th>Specific<br>growth rate</th>
<th>Années de<br>croisement</th>
<th>Séquences</th>
<th>Suppr.</th>
</tr>
</thead>
<tdata>
{section name=lst loop=$data}
<tr>
<td class="center">
<a href=index.php?module=poissonDisplay&poisson_id={$data[lst].poisson_id}>
<img src="display/images/fish.png" height="24" title="Accéder à la fiche détaillée du poisson">
</a>
<td>
<a href="index.php?module=poissonCampagneDisplay&poisson_campagne_id={$data[lst].poisson_campagne_id}">
{$data[lst].matricule}
</a>
</td>
<td>
<a href="index.php?module=poissonCampagneDisplay&poisson_campagne_id={$data[lst].poisson_campagne_id}">
{$data[lst].prenom}
</a>
</td>
<td>
<a href="index.php?module=poissonCampagneDisplay&poisson_campagne_id={$data[lst].poisson_campagne_id}">
{$data[lst].pittag_valeur}
</a>
</td>
<td>{$data[lst].cohorte}</td>
<td>{$data[lst].sexe_libelle_court}</td>
<td class="{if $data[lst].tx_croissance_journalier > 0.02}etat3{else}right{/if}">{$data[lst].tx_croissance_journalier}</td>
<td class="{if $data[lst].specific_growth_rate > 0.02}etat3{else}right{/if}">{$data[lst].specific_growth_rate}</td>
<td>{$data[lst].annees}</td>
<td>{$data[lst].sequences}</td>
<td class="center">
{if strlen($data[lst].sequences) == 0}
<a class="confirmation" href="index.php?module=poissonCampagneDelete&poisson_campagne_id={$data[lst].poisson_campagne_id}">
<img src="display/images/cross.png" height="25">
{/if}
</a>
</td>
</tr>
{/section}
</tdata>
</table>