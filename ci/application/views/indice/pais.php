<div class="contenido">
	<div class="flagContainer">
		<p class="flag">
			<em>
				<?php _printf('Revistas por país: "%s" (%d documentos)', $current['pais'], $current['total']);?>
			</em>
		</p>
	</div>
	<div>
		<table border=0 cellpadding='0' cellspacing='0' class="resultados centrado">
			<caption title="<?php _e('Revistas indizadas en CLASE y PERIÓDICA según orden alfabético y número de documentos de cada revista');?>"></caption>
			<colgroup>
				<col id='noCol' />
				<col id='indbibCol' />
				<col id='totalCol' />
			</colgroup>
			<thead>
				<tr>
					<th scope='col'><?php _e('No.');?></th>
					<th scope='col'><?php _e('Revista');?></th>
					<th scope='col'><?php _e('Documentos');?></th>
				</tr>
			</thead>
			<tbody>
<?php foreach ($registros as $key => $registro):?>
				<tr class="registro">
					<td><?=($key + 1);?></td>
					<td><?=$registro['revista'];?></td>
					<td class="totalRight"><a class="enlace" href="<?=site_url("revista/{$registro['revistaSlug']}");?>" title="<?=$registro['revista'];?>"><?=number_format($registro['articulos']);?></a></td>
				</tr>
<?php endforeach;?>
				<tr><td class='acumuladas' colspan=2><?php _e('Total:');?></td><td class='acumuladas'><?=number_format($registrosTotalArticulos);?></td></tr>
			</tbody>
		</table>
	</div>
</div>
