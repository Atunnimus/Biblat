	<link rel="stylesheet" href="<?php echo base_url();?>css/estiloBiblatresultados.css" />
	<link rel="stylesheet" href="<?php echo base_url();?>css/colorbox.css" />
	<link rel="stylesheet" href="<?php echo base_url();?>css/colorboxIndices.css" />
	<script type="text/javascript" src="<?php echo base_url();?>js/colorbox.js"></script>
	<script type="text/javascript" src="<?php echo base_url();?>js/jquery.highlight.js"></script>
	<script type="text/javascript">
		jQuery.noConflict();
		jQuery(document).ready(function() {
			jQuery(".saveCheck").click(function(){
				formBuscador = jQuery("#buscador").serialize();
				jQuery.ajax({
					type: "POST",
					async: false,
					url: "<?php echo base_url()?>/saveCheck.php",
					data: formBuscador
				}).done(function( msg ) {
					//alert( "Data Saved: " + msg );
				});
			});
			jQuery(".registro").colorbox({
				rel:'registro', 
				transition:"fade", 
				data: {ajax:true}, 
				height:"90%", 
				current:"<?php _printf('Artículo %s de %s', '{current}', '{total}');?>",

			});
			jQuery(document).bind('cbox_complete', function(){
				addthis.toolbox('.addthis_toolbox');
			});
			jQuery(".resultados").highlight([<?php echo $slugHighLight;?>]);
		});
	</script>
