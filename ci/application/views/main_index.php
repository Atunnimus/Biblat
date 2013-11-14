<div class="contenido main">
	<div class="flagContainer">
		<p class="flag flag">
			<em>
				<?php _e('Revistas por disciplina');?>
			</em>
		</p>
		<p class="flag flagRight">
			<em>
				<?php _e('Revistas por país');?>
			</em>
		</p>
	</div>
	<div class="columns">
		<div class="leftColumn">
			<div class="tagCloud"></div>
		</div>
		<div class="rightColumn">
			<ul class="paisesFlags">
				<li><a href="<?php echo site_url("indice/pais/".slug("Internacional"));?>"><img src="<?php echo base_url();?>img/flags/world.png" title="Organismos Internacionales" border="0"/></a></li>
<?php foreach ($paises as $pais):?>
				<li><a href="<?php echo site_url("indice/pais/{$pais['paisSlug']}");?>" title="<?php echo $pais['pais'];?>"><img src="<?php echo base_url("img/flags/{$pais['paisSlug']}.png");?>" title="<?php echo $pais['pais'];?>" border="0"/></a></li>
<?php endforeach;?>
			</ul>
		</div>
		<p class="flag flagRight">
			<em>
				<?php _e('Revistas por orden alfabético');?>
			</em>
		</p>
		<div class="rightColumn">
			<p class="texto">
<?php foreach (range('A', 'Z') as $i):?>
				<a href="<?php echo site_url("indice/alfabetico/".strtolower($i));?>"><?php echo $i;?></a>
<?php endforeach;?>
			</p>
		</div>
		<p class="flag flagRight">
			<em>
				<?php _e('Disponibles');?>
			</em>
		</p>
		<div class="rightColumn">
			<p class="texto">
				<span class="disponibles"><?php echo number_format($totales['revistas']); ?></span> <?php _e('revistas');?><br />
				<span class="disponibles"><?php echo number_format($totales['documentos']); ?></span> <?php _e('documentos');?><br />
				<span class="disponibles"><?php echo number_format($totales['enlaces']); ?></span> <?php _e('textos completos');?><br />
				<span class="disponibles"><?php echo number_format($totales['hevila']); ?></span> <?php _e('artículos en texto completo en repositorio HEVILA');?>
			</p>
		</div>
	</div>
	<div id="creditos" class="cboth">
		<a href="creditos.html"><?php _e('Créditos');?></a>
	</div>
</div>
