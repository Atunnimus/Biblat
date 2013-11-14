<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title><?php echo $title;?></title>
	<link rel="stylesheet" href="<?php echo base_url();?>css/estiloBiblat.css" type="text/css" />
	<script type="text/javascript" src="<?php echo base_url();?>js/jquery.js"></script>
	<script type="text/javascript" language="javascript">
		jQuery(document).ready(function()
		{
			jQuery(document).bind("contextmenu",function(e){
				return false;
			});
		}); 
	</script>
	<script type="text/javascript">
		var _gaq = _gaq || [];
		_gaq.push(['_setAccount', 'UA-33940112-1']);
		_gaq.push(['_trackPageview']);

		(function() {
		var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
		ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
		var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
		})();
	</script>
<?php 
if(isset($content)):
	echo $content;
endif;
?>
</head>
<body>
