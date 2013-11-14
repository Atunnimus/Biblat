	<link rel="stylesheet" href="<?php echo base_url();?>js/select2/select2.css" />
	<link rel="stylesheet" href="<?php echo base_url();?>js/anythingslider/css/anythingslider.css" />
	<link rel="stylesheet" href="<?php echo base_url();?>css/theme-anythingslider-scielo.css" />
	<link rel="stylesheet" href="<?php echo base_url();?>css/jquery.slider.min.css" />
	<link rel="stylesheet" href="<?php echo base_url();?>css/tabsIndicadores.css" />
	<script src="<?php echo base_url();?>js/select2/select2.js"></script>
	<script src="<?php echo base_url();?>js/jquery.slider.min.js"></script>
	<script src="<?php echo base_url();?>js/jquery.serializeJSON.min.js"></script>
	<script src="<?php echo base_url();?>js/jquery.blockUI.js"></script>
	<script src="<?php echo base_url();?>js/anythingslider/js/jquery.anythingslider.min.js"></script>
	<script src="<?php echo base_url();?>js/jquery-ui.min.js"></script>
	<script type="text/javascript" src="https://www.google.com/jsapi"></script>
	<script type="text/javascript">
		google.load("visualization", "1", {packages:["corechart"], 'language': 'en'});
		var charts = {normal: null, bradford:null, group1:null, group2:null, pratt:null};
		var charType = null;
		var popState = {indicador:false, disciplina:false, revista:false, pais:false, periodo:false};
		var rangoPeriodo="0-0";
		var dataPeriodo="0-0";
		var paisRevista="";
		var asyncAjax=false;
		var soloDisciplina = ['indice-concentracion', 'modelo-bradford-revista', 'modelo-bradford-institucion', 'productividad-exogena'];
		jQuery(document).ready(function(){
			jQuery("#indicador").select2({
				allowClear: true
			});

			jQuery("#indicador").on("change", function(e){
				value = jQuery(this).val();
				jQuery("#paisRevista, #periodos, #tabs, #chart, #bradfodContainer, #prattContainer").hide("slow");
				jQuery("#bradfordSlide").anythingSlider(1);
				jQuery("#disciplina").select2("val", "");
				if (value == "") {
					jQuery("#disciplina, #revista, #pais").select2("enable", false);
					jQuery("#revista, #pais").empty().append('<option></option>');
					jQuery("#revista, #pais").select2("destroy");
				}else if(jQuery.inArray(value, soloDisciplina) > -1){
					jQuery("#revista, #pais").select2("enable", false);
					jQuery("#revista, #pais").empty().append('<option></option>');
					jQuery("#revista, #pais").select2("destroy");
					jQuery("#disciplina").select2("enable", true);
				}else{
					jQuery("#revista, #pais").select2({allowClear: true, closeOnSelect: true});
					jQuery("#disciplina").select2("enable", true);
				}

				if(typeof history.pushState === "function" && !popState.indicador){
					history.pushState(jQuery("#generarIndicador").serializeJSON(), null, '<?php echo site_url('indicadores')."/"?>' + value);
				}
				popState.indicador=false;
				console.log(e);
			});

			jQuery(window).bind('popstate',  function(event) {
				console.log('pop:');
				updateData(event.originalEvent.state)
				
			});

			jQuery("#disciplina").select2({
				allowClear: true
			});

			jQuery("#disciplina").on("change", function(e){
				value = jQuery(this).val();
				indicadorValue = jQuery("#indicador").val();
				if (value == "") {
					jQuery("#paisRevista, #periodos, #tabs, #chart, #bradfodContainer, #prattContainer").hide("slow");
					jQuery("#revista, #pais").empty().append('<option></option>');
					jQuery("#revista, #pais").select2("destroy");
					jQuery("#revista, #pais").select2({allowClear: true, closeOnSelect: true});
					jQuery("#revista, #pais").select2("enable", false);
				} else if (jQuery.inArray(indicadorValue, soloDisciplina) > -1) {
					jQuery("#generarIndicador").submit();
				} else {
					loading.start();
					jQuery("#paisRevista").show("slow");
					jQuery("#periodos, #tabs, #chart, #bradfodContainer, #prattContainer").hide("slow");
					jQuery.ajax({
						url: '<?php echo site_url("indicadores/getRevistasPaises");?>',
						type: 'POST',
						dataType: 'json',
						data: jQuery("#generarIndicador").serialize(),
						async: asyncAjax,
						success: function(data) {
							console.log(data);
							jQuery("#revista, #pais").empty().append('<option></option>');
							jQuery("#revista, #pais").select2("destroy");
							jQuery("#revista, #pais").select2({allowClear: true, closeOnSelect: true});
							jQuery("#revista, #pais").select2("enable", false);
							jQuery.each(data.revistas, function(key, val) {
								jQuery("#revista").append('<option value="' + val.val +'">' + val.text + '</option>');
							});

							jQuery.each(data.paises, function(key, val) {
								jQuery("#pais").append('<option value="' + val.val +'">' + val.text + '</option>');
							});
							jQuery("#revista, #pais").select2("enable", true);
							loading.end();
						}
					});
				}
				if(typeof history.pushState === "function" && !popState.disciplina){
					disciplina="";
					if(value != "" && value != null){
						disciplina='/disciplina/' + value;
					}
					history.pushState(jQuery("#generarIndicador").serializeJSON(), null, '<?php echo site_url('indicadores')."/"?>' + indicadorValue + disciplina);
				}
				popState.disciplina=false;
				console.log(e);
			});

			jQuery("#revista").select2({
				allowClear: true,
				closeOnSelect: true
			});

			jQuery("#revista").on("change", function(e){
				value = jQuery(this).val();
				indicadorValue = jQuery("#indicador").val();
				disciplinaValue = jQuery("#disciplina").val();
				jQuery("#sliderPeriodo").prop("disabled", true);
				if (value != "" && value != null) {
					jQuery("#pais").select2("enable", false);
					setPeridos();
				}else{
					jQuery("#periodos, #tabs, #chart").hide("slow");
					jQuery("#pais").select2("enable", true);
				}

				if(typeof history.pushState === "function" && !popState.revista){
					paisRevista="";
					if(value != "" && value != null){
						paisRevista='/revista/' + value.join('/');
					}
					history.pushState(jQuery("#generarIndicador").serializeJSON(), null, '<?php echo site_url('indicadores')."/"?>' + indicadorValue + '/disciplina/' + disciplinaValue + paisRevista);
				}
				popState.revista=false;
				console.log(e);
			});

			jQuery("#pais").select2({
				allowClear: true,
				closeOnSelect: true
			});

			jQuery("#pais").on("change", function(e){
				value = jQuery(this).val();
				indicadorValue = jQuery("#indicador").val();
				disciplinaValue = jQuery("#disciplina").val();
				jQuery("#sliderPeriodo").prop("disabled", true);
				if (value != "" && value != null) {
					jQuery("#revista").select2("enable", false);
					setPeridos();
				}else{
					jQuery("#periodos, #tabs, #chart").hide("slow");
					jQuery("#revista").select2("enable", true);
				}
				if(typeof history.pushState === "function" && !popState.pais){
					paisRevista="";
					if(value != "" && value != null){
						paisRevista='/pais/' + value.join('/');
					}
					history.pushState(jQuery("#generarIndicador").serializeJSON(), null, '<?php echo site_url('indicadores')."/"?>' + indicadorValue + '/disciplina/' + disciplinaValue + paisRevista);
				}
				popState.pais=false;
				console.log(e);
			});
			
			jQuery("#sliderPeriodo").slider();

			jQuery("#bradfordSlide, #prattSlide").anythingSlider({
						theme: 'scielo',
						mode: 'fade',
						expand: true,
						easing: "linear",
						buildNavigation: true,
						buildStartStop: false,
						hashTags: false,
						animationTime: 1200
					});
			jQuery(function(){
				jQuery("#tabs").tabs();
			});
			
			jQuery("#generarIndicador").on("submit", function(e){
				loading.start();
				indicadorValue = jQuery("#indicador").val();
				urlRequest = '<?php echo site_url("indicadores/getChartData");?>';
				switch(indicadorValue){
					case "modelo-bradford-revista":
					case "modelo-bradford-institucion":
						urlRequest = '<?php echo site_url("indicadores/getChartDataBradford");?>';
						break;
					case "indice-concentracion":
						urlRequest = '<?php echo site_url("indicadores/getChartDataPratt");?>';
						break;
					case "productividad-exogena":
							loading.end();
							return false;
						break;
				}
				jQuery.ajax({
				  url: urlRequest,
				  type: 'POST',
				  dataType: 'json',
				  data: jQuery(this).serialize(),
				  success: function(data) {
				  	console.log(data);
					switch(indicadorValue){
						case "modelo-bradford-revista":
						case "modelo-bradford-institucion":
							jQuery("#tabs, #bradfodContainer").slideDown("slow");

							var chartData = new google.visualization.DataTable(data.chart.bradford);
							if(chart.bradford == null){
								chart.bradford = new google.visualization.LineChart(document.getElementById('chartBradford'));
							}
							chart.bradford.draw(chartData, data.options.bradford);

							var chartData = new google.visualization.DataTable(data.chart.group1);
							if(chart.group1 == null){
								chart.group1 = new google.visualization.ColumnChart(document.getElementById('chartGroup1'));
							}
							chart.group1.draw(chartData, data.options.groups);

							var chartData = new google.visualization.DataTable(data.chart.group2);
							if(chart.group2 == null){
								chart.group2 = new google.visualization.ColumnChart(document.getElementById('chartGroup2'));
							}
							chart.group2.draw(chartData, data.options.groups);
							break;
						case "indice-concentracion":
							jQuery("#tabs, #prattContainer").slideDown("slow");
							jQuery("#prattSlide").empty();
							jQuery.each(data.chart, function(key, grupo) {
								if(typeof chart.pratt === "undefined"){
									chart.pratt = new Array();
								}
								jQuery("#prattSlide").append('<li><div id="chartPratt' + key +'"></div></li>').anythingSlider();
								var chartData = new google.visualization.DataTable(grupo);
								chart.pratt[key] = new google.visualization.ColumnChart(document.getElementById('chartPratt' + key));
								chart.pratt[key].draw(chartData, data.options);
							});
							break;
						case "productividad-exogena":
							break;
						default:
							jQuery("#tabs, #chart").show("slow");
							var chartData = new google.visualization.DataTable(data.data);
							if(chart.normal == null || charType != 'line'){
								charType = 'line';
								chart.normal = new google.visualization.LineChart(document.getElementById('chart'));
							}
							console.log(chart);
							chart.normal.draw(chartData, data.options);
							break;
					}
					loading.end();
				  }
				});
				return false;
			});

			urlData = {
<?php if (preg_match('%indicadores/(.+?)(/.*|$)%', uri_string())):?>
				indicador:"<?php echo preg_replace('%indicadores/(.+?)(/.*|$)%', '\1', uri_string());?>",
<?php endif;?>
<?php if (preg_match('%.*?/disciplina/(.+?)(/.*|$)%', uri_string())):?>
				disciplina:"<?php echo preg_replace('%.*?/disciplina/(.+?)(/.*|$)%', '\1', uri_string());?>",
<?php endif;?>
<?php if (preg_match('%.*?/revista/(.+?)($|/[0-9]{4}-[0-9]{4})%', uri_string())):?>
				revista:"<?php echo preg_replace('%.*?/revista/(.+?)($|/[0-9]{4}-[0-9]{4})%', '\1', uri_string());?>".split('/'),
<?php endif;?>
<?php if (preg_match('%.*?/pais/(.+?)($|/[0-9]{4}-[0-9]{4})%', uri_string())):?>
				pais:"<?php echo preg_replace('%.*?/pais/(.+?)($|/[0-9]{4}-[0-9]{4})%', '\1', uri_string());?>".split('/'),
<?php endif;?>
<?php if (preg_match('%.*?/([0-9]{4})-([0-9]{4})%', uri_string())):?>
				periodo:"<?php echo preg_replace('%.*?/([0-9]{4})-([0-9]{4})%', '\1;\2', uri_string());?>"
<?php endif;?>
			}
<?php if (preg_match('%.*?/revista/(.+?)($|/[0-9]{4}-[0-9]{4})%', uri_string())):?>
			paisRevista="/revista/<?php echo preg_replace('%.*?/revista/(.+?)($|/[0-9]{4}-[0-9]{4})%', '\1', uri_string());?>";
<?php endif;?>
<?php if (preg_match('%.*?/pais/(.+?)($|/[0-9]{4}-[0-9]{4})%', uri_string())):?>
			paisRevista="/pais/<?php echo preg_replace('%.*?/pais/(.+?)($|/[0-9]{4}-[0-9]{4})%', '\1', uri_string());?>";
<?php endif;?>
			if(typeof urlData.indicador !== "undefined"){
				updateData(urlData);
			}
			
			delete urlData;
			if(typeof history.replaceState === "function"){
				history.replaceState(jQuery("#generarIndicador").serializeJSON(), null);
			}
		});

		setPeridos = function(){
			loading.start();
			jQuery("#periodos").slideDown("slow");
			jQuery.ajax({
				url: '<?php echo site_url("indicadores/getPeriodos");?>',
				type: 'POST',
				dataType: 'json',
				data: jQuery("#generarIndicador").serialize(),
				async: asyncAjax,
				success: function(data) {
					console.log(data);
					console.log(jQuery.parseJSON(data.scale));
					console.log(jQuery.parseJSON(data.heterogeneity));
					if(data.result){
						jQuery("#sliderPeriodo").prop('disabled', false);
						jQuery("#generate").prop('disabled', false);
						rangoPeriodo=data.anioBase + ";" + data.anioFinal;
						jQuery("#sliderPeriodo").val(rangoPeriodo);
						jQuery("#sliderPeriodo").data('pre', jQuery("#sliderPeriodo").val());
						jQuery("#sliderPeriodo").slider().destroy();
						jQuery("#sliderPeriodo").slider({
							from: data.anioBase, 
							to: data.anioFinal, 
							heterogeneity: jQuery.parseJSON(data.heterogeneity), 
							scale: jQuery.parseJSON(data.scale),
							format: { format: '####', locale: 'us' }, 
							limits: false, 
							step: 1, 
							dimension: '', 
							callback: function(value){
								if(jQuery("#sliderPeriodo").data('pre') != jQuery("#sliderPeriodo").val()){
									jQuery("#sliderPeriodo").data('pre', jQuery("#sliderPeriodo").val());
									rango=jQuery("#sliderPeriodo").val().replace(';', '-');
									if(typeof history.pushState === "function"){
										history.pushState(jQuery("#generarIndicador").serializeJSON(), null, '<?php echo site_url('indicadores')."/"?>' + indicadorValue + '/disciplina/' + disciplinaValue + paisRevista + '/' + rango);
									}
									jQuery("#revista, #pais").select2("close");
									jQuery("#generarIndicador").submit();
								}
							}
						});
						if(!popState.periodo){
							jQuery("#generarIndicador").submit();
						}
						popState.periodo=false;
					}else{
						jQuery("#sliderPeriodo").prop('disabled', true);
						jQuery("#generate").prop('disabled', true);
						console.log(data.error);
					}
					//loading.end();
				}
			});
		};

		updateData = function(data){
			console.log(data);
			asyncAjax=false;
			actualForm = jQuery("#generarIndicador").serializeJSON();
			if(typeof data.periodo !== "undefined"){
				popState.periodo = true;
			}
			if(typeof data.indicador !== "undefined" && data.indicador != actualForm.indicador){
				popState.indicador=true;
				jQuery("#indicador").val(data.indicador).trigger("change");
				actualForm = jQuery("#generarIndicador").serializeJSON();
			}
			if(typeof data.disciplina !== "undefined" && data.disciplina != actualForm.disciplina){
				popState.disciplina=true;
				jQuery("#disciplina").val(data.disciplina).trigger("change");
				actualForm = jQuery("#generarIndicador").serializeJSON();
			}

			if(!actualForm.revista){
				actualForm.revista = ["revista"];
			}
			if(data.revista === "" || typeof data.revista === "undefined" && typeof data.pais === "undefined"){
				jQuery("#periodos, #tabs, #chart, #bradfodContainer, #prattContainer").hide("slow");
				jQuery("#revista").select2("val", null);
				jQuery('#revista option').first().prop('selected', false);
				jQuery("#revista").select2("destroy");
				jQuery("#revista").select2({allowClear: true, closeOnSelect: true});
				jQuery("#pais").select2("enable", true);
			}
			
			if(data.revista !== "" &&  typeof data.revista !== "undefined" && data.revista.join('/') != actualForm.revista.join('/')){
				popState.revista=true;
				jQuery("#revista").val(data.revista).trigger("change");
				actualForm = jQuery("#generarIndicador").serializeJSON();
			}

			if(!actualForm.pais){
				actualForm.pais = ["pais"];
			}

			if(data.pais === "" || typeof data.pais === "undefined" && typeof data.revista === "undefined"){
				jQuery("#periodos, #tabs, #chart, #bradfodContainer, #prattContainer").hide("slow");
				jQuery("#pais").select2("val", null);
				jQuery('#pais option').first().prop('selected', false);
				jQuery("#pais").select2("destroy");
				jQuery("#pais").select2({allowClear: true, closeOnSelect: true});
				jQuery("#revista").select2("enable", true);
			}
			if(data.pais !== "" &&  typeof data.pais !== "undefined" && data.pais.join('/') != actualForm.pais.join('/')){
				popState.pais=true;
				jQuery("#pais").val(data.pais).trigger("change");
				actualForm = jQuery("#generarIndicador").serializeJSON();
			}
			if(typeof data.periodo === "undefined" && (typeof data.revista !== "undefined" || typeof data.pais !== "undefined")){
				data.periodo = rangoPeriodo;
			}
			if(typeof data.periodo !== "undefined"){
				jQuery("#sliderPeriodo").prop("disabled", false);
				jQuery("#sliderPeriodo").slider("value", data.periodo.substring(0, 4), data.periodo.substring(5));
				jQuery("#generarIndicador").submit();
			}
			asyncAjax=true;
		};

		loading = {
			start: function(){
				jQuery.blockUI({ 
					message: '<h2 style="white-space:nowrap;"><img src="<?php echo base_url();?>img/loading.gif" /><br /><?php _e('Espere un momento...');?></h2>',
					css: { 
					color: '#000', 
					border: 'none', 
					backgroundColor:'#FBFCEF', 
					opacity: 0.6, 
					border: '2px solid #114D66',
					cursor: 'wait'
					}, 
				});
			},
			end: function(){
				jQuery.unblockUI();
			}
		}; 
		
	</script>
