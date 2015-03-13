<script>
$(document).ready(function() {
	$(".taux").attr("pattern","[0-9]+(\.[0-9]+)?");
	$(".taux").attr("title","valeur numérique");
	$(".taux").attr("size", "5");
	$(".taux").attr("maxlength", "10");
	$(".commentaire").attr("size","30");
});
</script>
<a href="index.php?module=lotList">Retour à la liste des lots</a>&nbsp;
{if $data.lot_id > 0}
<a href="index.php?module=lotDisplay&lot_id={$data.lot_id}">
Retour au lot
</a>
{/if}
<h2>Caractéristiques du lot</h2>
<div class="formSaisie">
<div>
<form id="lotForm" method="post" action="index.php?module=lotWrite">
<input type="hidden" name="lot_id" value="{$data.lot_id}">
<dl>
<dt>Croisement d'origine <span class="red">*</span> :</dt>
<dd>
<select name="croisement_id" >
{section name=lst loop=$croisements}
<option value="{$croisements[lst].croisement_id}" {if $croisements[lst].croisement_id == $data.croisement_id}selected{/if}>
{$croisements[lst].parents}
</option>
{/section}
</select>
</dd>
</dl>
<dl>
<dt>Nom du lot <span class="red">*</span> :</dl>
<dd>
<input class="commentaire" name="lot_nom" value="{$data.lot_nom}">
</dd>
<dl>
<dt>Nombre de larves initial :</dt>
<dd>
<input class="taux" name="nb_larve_initial" value="{$data.nb_larve_initial}">
</dd>
</dl>

<div class="formBouton">
<input class="submit" type="submit" value="Enregistrer">
</div>
</form>
{if $data.lot_id > 0 &&$droits["reproAdmin"] == 1}
<div class="formBouton">
<form action="index.php" method="post" onSubmit='return confirmSuppression("Confirmez-vous la suppression ?")'>
<input type="hidden" name="module" value="lotDelete">
<input type="hidden" name="lot_id" value="{$data.lot_id}">
<input class="submit" type="submit" value="Supprimer">
</form>
</div>
{/if}
</div>
</div>
<span class="red">*</span><span class="messagebas">Champ obligatoire</span>