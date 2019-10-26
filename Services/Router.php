<?php 
namespace Amazonia\Services;
use Amazonia\PlService;
use Amazonia\PlNodo;
use Amazonia\PlException;
use \stdClass;
use \Exception;

class Router extends PlService {
    private $uri = [];
    private $nodos = [];
    private $_default = null;

    public function __construct() {

    }
    public function ruta($uri, $nombreClase) {
        $this->uri[$uri] = $nombreClase;
    }
    public function start () {
    	$this->app->mainResource = $this->routing($this->app->path);
    }
    
    public function byDefault ($controlador) {
        $this->_default = $controlador;
    }

    public function routing($urlObtenida) {
        $indice  = $this->reconocer($urlObtenida);
        
        if ($indice != null) {
            return array(
                "resource" => $indice[2],
                "params" => $indice[1]
            );
            
        } else {
            if ($this->_default == NULL)
                throw new PlException ("Resource not found", 404);
            
        }
    }

    public function ruteo ($identificador, $uri, $controlador) {
        $desglose = explode(".", $identificador);

        $nodo = new stdClass;
        $nodo->id = $desglose[count($desglose) - 1];
        $nodo->uri = $uri;
        $nodo->controlador = $controlador;
        if (count($desglose) > 1) {
            unset($desglose[count($desglose) - 1]);
            $resultado = null;
            for ($i = 0; $i < count($this->nodos); $i++)
                $resultado = $this->nodos[$i]->buscar($desglose);
            //var_dump($resultado);
            if ($resultado != null) {
                $resultado->insertar($nodo->id, $nodo);
            }
        } else {
            $this->nodos[] = new PlNodo($nodo->id, $nodo);
        }
        //var_dump($this->nodos);
    }

    private function reconocerNodo ($urlObtenida, $nodo, $uri) {
        $index = $uri . $nodo->objeto->uri;
        $value = $nodo->objeto->controlador;
        
        //boy esto es para los parametros en la url definida por @
        $reglaUrl = preg_replace('/@([^\/]+)/', '(?<\1>[^/]+)', $this->app->mainRoute . $index);
    
        $reglaUrl= str_replace('/', '\/', $reglaUrl);
        preg_match_all('/@([^\/]+)/', $index, $parametro);
        //esto es el minimotor :v

        if (preg_match('/^' . $reglaUrl . '\/*$/s', $urlObtenida, $coincidencia)) {
            $parametros = array_intersect_key($coincidencia, array_flip($parametro[1]));
            return array($index, $parametros, $value);
        }
        $length = count ($nodo->hijos);
        for ($i = 0; $i < $length; $i++)
            if (($resultado = $this->reconocerNodo($urlObtenida, $nodo->hijos[$i], $index)) != null)
                return $resultado;
    }

	protected function reconocer($urlObtenida) {
        
        foreach($this->nodos as $indice => $nodo) {
            $resultado = $this->reconocerNodo($urlObtenida, $nodo, "");
            if ($resultado != null)
                return $resultado;
        }
        return null;
    }
}