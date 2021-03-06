<link rel="stylesheet" href="display/javascript/magnific-popup/magnific-popup.css"> 
<script src="display/javascript/magnific-popup/jquery.magnific-popup.min.js"></script> 
<script>
$(document).ready(function() { 
	//setDataTables("documentList");
	$('.image-popup-no-margins').magnificPopup( {
		type: 'image',
		closeOnContentClick: true,
		closeBtnInside: false,
		fixedContentPos: true,
		mainClass: 'mfp-no-margins mfp-with-zoom', // class to remove default margin from left and right side
		image: {
			verticalFit: false
		},
		zoom: {
			enabled: true,
			duration: 300 // don't foget to change the duration also in CSS
		}
	});
});
</script>
<table class="tablemulticolonne">
<tr>
<td style="width:25%;">
{if $document_offset > 0}
{$document_offset_prec = $document_offset - 10}
<a href="index.php?document_offset={$document_offset_prec}&document_limit={$document_limit}&module={$moduleParent}&{$parentIdName}={$parent_id}&parentType={$parentType}{if $parentIdName != 'poisson_id'}&poisson_id={$data.poisson_id}{/if}" title="Données précédentes">
&lt;préc
</a>
{/if}
</td>
<td style="width:50%;">&nbsp;</td>
<td style="width:25%;text-align:right;">
{$document_offset_suiv=$document_offset + 10}
<a href="index.php?document_offset={$document_offset_suiv}&document_limit={$document_limit}&module={$moduleParent}&{$parentIdName}={$parent_id}&parentType={$parentType}{if $parentIdName != 'poisson_id'}&poisson_id={$data.poisson_id}{/if}" title="Données suivantes">
suiv&gt;
</a>
</td>
</tr>
<tr>
<td colspan="3">
<table id="documentList" class="tableliste">
<thead>

<tr>
<th>Vignette</th>
<th>Nom du document</th>
<th>Description</th>
<th>Taille</th>
<th>Prise de vue ou création</th>
<th>Date<br>d'import</th>
{if $droits["bassinGestion"] == 1 || $droits["poissonGestion"] == 1 || $droits["reproGestion"]}
<th>Modif.</th>
<th>Suppr.</th>
{/if}
</tr>
</thead>
<tbody>
{section name=lst loop=$dataDoc}
<tr>
<td style="text-align:center;">
{if in_array($dataDoc[lst].mime_type_id, array(4, 5, 6)) }
<a class="image-popup-no-margins" href="index.php?module=documentGet&document_id={$dataDoc[lst].document_id}&document_name={$dataDoc[lst].photo_preview}&attached=0&phototype=1" title="aperçu de la photo : {substr($dataDoc[lst].photo_name, strrpos($dataDoc[lst].photo_name, '/') + 1)}">
<img src="index.php?module=documentGet&document_id={$dataDoc[lst].document_id}&document_name={$dataDoc[lst].thumbnail_name}&attached=0&phototype=2" height="30">
</a>
{elseif  $dataDoc[lst].mime_type_id == 1}
<a class="image-popup-no-margins" href="index.php?module=documentGet&document_id={$dataDoc[lst].document_id}&&document_name={$dataDoc[lst].thumbnail_name}&attached=0&phototype=2" title="aperçu du document : {substr($dataDoc[lst].thumbnail_name, strrpos($dataDoc[lst].thumbnail_name, '/') + 1)}">
<img src="index.php?module=documentGet&document_id={$dataDoc[lst].document_id}&document_name={$dataDoc[lst].thumbnail_name}&attached=0&phototype=2" height="30">
</a>
{/if}
<td>
<a href="index.php?module=documentGet&document_id={$dataDoc[lst].document_id}&document_name={$dataDoc[lst].photo_name}&attached=1&phototype=0" title="document original">
{$dataDoc[lst].document_nom}
</a>
</td>
<td>{$dataDoc[lst].document_description}</td>
<td>{$dataDoc[lst].size}</td>
<td>{$dataDoc[lst].document_date_creation}</td>
<td>{$dataDoc[lst].document_date_import}</td>
{if $droits["bassinAdmin"] == 1 || $droits["poissonAdmin"] == 1}
<td>
<div class="center">
<a href="index.php?module=documentChangeData&document_id={$dataDoc[lst].document_id}&moduleParent={$moduleParent}&parentIdName={$parentIdName}&parent_id={$parent_id}&parentType={$parentType}{if $parentIdName != 'poisson_id'}&poisson_id={$data.poisson_id}{/if}">
<img src="display/images/edit.gif" height="20">
</a>
</div>
</td>
<td>
<div class="center">
<a href="index.php?module=documentDelete&document_id={$dataDoc[lst].document_id}&moduleParent={$moduleParent}&parentIdName={$parentIdName}&parent_id={$parent_id}&parentType={$parentType}{if $parentIdName != 'poisson_id'}&poisson_id={$data.poisson_id}{/if}" onclick="return confirm('Confirmez-vous la suppression ?');">
<img src="display/images/corbeille.png" height="20">
</a>
</div>
</td>
{/if}
</tr>
{/section}
</tbody>
</table>
</td>
</tr>
</table>
