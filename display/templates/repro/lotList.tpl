
{if $droits.reproGestion == 1}
<a href="index.php?module=lotChange&lot_id=0">Nouveau lot de larves...</a>
{/if}
<table id="clotlist" class="tableliste">
<thead>
<tr>
<th>Nom du lot</th>
<th>Parents</th>
<th>Séquence</th>
<th>Date<br>d'éclosion</th>
<th>Nbre de larves<br>initial</th>
<th>Nbre de larves<br>compté</th>
</tr>
</thead>
<tdata>
{section name=lst loop=$lots}
<tr>
<td>
<a href="index.php?module=lotDisplay&lot_id={$lots[lst].lot_id}">
{$lots[lst].lot_nom}
</a>
</td>
<td>{$lots[lst].parents}</td>
<td class="center">
<a href="index.php?module=sequenceDisplay&sequence_id={$lots[lst].sequence_id}">
{$lots[lst].sequence_nom}
&nbsp;
{$lots[lst].croisement_nom}
</a>
</td>
<td>{$lots[lst].eclosion_date}</td>
<td class="right">{$lots[lst].nb_larve_initial}</td>
<td class="right">{$lots[lst].nb_larve_compte}</td>
</tr>
{/section}
</tdata>
</table>