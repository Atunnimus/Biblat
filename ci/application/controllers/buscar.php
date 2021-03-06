<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
class Buscar extends CI_Controller{

	public function __construct(){
		parent::__construct();
		$this->output->enable_profiler($this->config->item('enable_profiler'));
	}
	
	public function index($filtro="", $disciplina="", $slug="", $textoCompleto=""){
		/*Si se hizo una consulta con POST redirigimos a una url correcta*/
		if(isset($_POST['disciplina']) && isset($_POST['slug'])):
			if(isset($_POST['textoCompleto'])):
				$textoCompleto="texto-completo";
			endif;
			//print_r($_POST); die();
			if($_POST['filtro'] === "todos"):
				$_POST['filtro'] = "";
			endif;
			$returnURL = site_url(preg_replace('%[/]+%', '/', "buscar/{$_POST['filtro']}/{$_POST['disciplina']}/".slugSearch($_POST['slug'])."/{$textoCompleto}"));
			if(isset($_POST['ajax'])):
				$this->output->enable_profiler(false);
				echo $returnURL;
				return;
			endif;
			redirect($returnURL, 'refresh');
		endif;
		/*Si no exite ningun dato redirigimos al index*/
		if($disciplina == "" || $slug == ""):
			redirect(base_url(), 'refresh');
		endif;
		/*Variables para vistas*/
		$data = array();
		/*Arrego con descripcion y sql para cada indice*/
		$indiceArray['palabra-clave'] = array('sql' => 'palabrasClaveSlug', 'descripcion' => _('Palabras clave'));
		$indiceArray['articulo'] = array('sql' => 'articuloSlug', 'descripcion' => _('Artículo'));
		$indiceArray['autor'] = array('sql' => 'autoresSlug', 'descripcion' => _('Autor'));
		$indiceArray['institucion'] = array('sql' => 'institucionesSlug', 'descripcion' => _('Institución'));
		$indiceArray['revista'] = array('sql' => 'revistaSlug', 'descripcion' => _('Revista'));

		/*Header title*/
		$data['header']['title'] = _sprintf('Biblat - Búsqueda %s: "%s"', strtolower($indiceArray[$indice]['descripcion']), slugSearchClean($slug));
		/*Result title*/
		$data['main']['title'] = _sprintf('Resultados de la búsqueda: %s', slugSearchClean($slug));
		/*Consultas*/
		$this->load->database();
		/*Creando la consulta para los resultados*/
		$whereTextoCompleto = "";
		$data['main']['textoCompleto'] = FALSE;
		if ($textoCompleto == "texto-completo"):
			$whereTextoCompleto = "AND url <> ''";
			$data['main']['textoCompleto'] = TRUE;
		endif;

		$whereDisciplina = "";
		if ($disciplina != "null"):
			/*Obteniendo id de la disciplina*/
			$query = "SELECT * from disciplinas WHERE slug='{$disciplina}'";
			$query = $this->db->query($query);
			$disciplina = $query->row_array();
			$query->free_result();
			$whereDisciplina = "AND id_disciplina={$disciplina['id_disciplina']}";
		endif;

		$slugQuerySearch = slugQuerySearch($slug);
		if( $filtro != "null"):
			$slugQuerySearch = slugQuerySearch($slug, $indiceArray[$filtro]['sql']);
			$data['header']['title'] = _sprintf('Biblat - Búsqueda por %s: "%s"', strtolower($indiceArray[$filtro]['descripcion']), slugSearchClean($slug));
			$data['main']['title'] = _sprintf('Resultados de la búsqueda por %s: %s', strtolower($indiceArray[$filtro]['descripcion']), slugSearchClean($slug));
		endif;

		$queryFields="SELECT 
					DISTINCT (s.sistema, 
					s.iddatabase) as \"sitemaIdDatabase\", 
					articulo, 
					\"articuloSlug\", 
					revista, 
					\"revistaSlug\", 
					pais, 
					anio, 
					volumen, 
					numero, 
					periodo, 
					paginacion, 
					url, 
					\"autoresSecJSON\",
					\"autoresSecInstitucionJSON\",
					\"autoresJSON\",
					\"institucionesSecJSON\",
					\"institucionesJSON\"";
		$queryFrom="FROM \"mvSearch\" s 
				WHERE  {$slugQuerySearch[where]} {$whereTextoCompleto} {$whereDisciplina}";
		$query = "{$queryFields} 
				{$queryFrom} 
				ORDER BY anio DESC, volumen DESC, numero DESC, articulo";
		

		$queryCount = "SELECT count (DISTINCT (s.sistema, 
					s.iddatabase)) as total {$queryFrom}";
		
		/*Creando paginacion*/
		if($disciplina == "null"):
			$disciplina = array();
			$disciplina['slug'] = "";
			$disciplina['disciplina'] = "";
		endif;
		$data['header']['filtro'] = $filtro;
		if($filtro == "null"):
			$filtro = "";
			$data['header']['filtro'] = "todos";
		endif;
		if ($textoCompleto == "texto-completo"):
			$paginationURL = site_url(preg_replace('%[/]+%', '/',"buscar/{$filtro}/{$disciplina['slug']}/{$slug}/{$textoCompleto}"));
		else:
			$paginationURL = site_url(preg_replace('%[/]+%', '/',"buscar/{$filtro}/{$disciplina['slug']}/{$slug}"));
			$data['main']['paginationURL'] = $paginationURL;
		endif;
		$perPage = 20;
		$articulosResultado = articulosResultado($query, $queryCount, $paginationURL, $perPage, $countCompleto=TRUE);

		$data['main']['links'] = $articulosResultado['links'];
		/*Datos de la busqueda*/
		$data['main']['search']['slug'] = slugSearchClean($slug);
		$data['main']['search']['disciplina'] = $disciplina['disciplina'];
		$data['main']['search']['total'] = $articulosResultado['totalRows'];
		$data['main']['search']['totalCompleto'] = $articulosResultado['totalCompleto'];
		$data['header']['search'] = $data['main']['search'];
		$data['header']['slugHighLight']=slugHighLight($slug);
		/*Resultados de la página*/
		$data['main']['resultados']=$articulosResultado['articulos'];
		$this->db->close();
		/*Vistas*/
		$data['header']['content'] =  $this->load->view('buscar/header', $data['header'], TRUE);
		$this->load->view('header', $data['header']);
		$this->load->view('menu', $data['header']);
		$this->load->view('buscar/index', $data['main']);
		$this->load->view('footer');
	}
}
