<?php
namespace Amazonia;

use Amazonia\Services\Exceptions;
use \Exception;
use \stdClass;

class PlApplication extends PlEvent {
    protected $method;
    protected $tokenized = false;
    protected $modelsPath = '';
    protected $servicesPath = '';
    public function __construct () {
        $this->controllersPath = '';
        $this->fillVariables();
        $this->createAutoload();
    }
    public function run() {
        try {
            $this->detectVariables();
            $this->dispatchCtrl("config");
            

            if ($this->mainResource == NULL) {
                Exceptions::responseNotFound();
            }
            
            if (!is_file ($this->mainPath . '/' . $this->controllersPath . $this->mainResource['resource'] . '.php')) {
                Exceptions::responseNotFound();
            }

            include_once ($this->mainPath . '/' . $this->controllersPath . $this->mainResource['resource'] . '.php');
            if (!class_exists ($this->mainResource['resource'])) {
                Exceptions::responseNotFound();
            }
            $classname = $this->mainResource['resource'];
            $resource = new $classname();

            $method = '_' . $this->method;
            if (!method_exists ($resource, $method)) {
                Exceptions::responseMethodNotAllowed();
            }
            $resource->app = $this;
            $resource->$method ($this->mainResource['params']);
        } catch (PlException $ex) {
            $this->response ($ex->getMessage(), $ex->getCode(), new stdClass);
        } catch (Exception $ex) {
            $this->response ($ex->getMessage(), $ex->getCode(), new stdClass);
        } catch (Throwable $ex) {
            $this->response ($ex->getMessage(), $ex->getCode(), new stdClass);
        }
    }
    public function createAutoload () {
        $dirs = [
            $this->servicesPath,
            $this->modelsPath
        ];
        $v = $this;
        $mainPath = $this->mainPath;
        
        spl_autoload_register(function ($class) use ($dirs, $mainPath) {
            foreach ($dirs as $dir) {
                // base directory for the namespace prefix
                $baseDir = $mainPath . '/' . $dir;

                $file = rtrim($baseDir, '/') . '/' . str_replace('\\', '/', $class) . '.php';
                // if the file exists, require it
                if (file_exists ($file)) {
                    require $file;
                    break;
                }
            }
        });
    }
    public function isTokenized () {
        return $this->tokenized;
    }
    public function response ($message, $code, $object) {
        $turn = new stdClass;
        $turn->error = $message;
        $turn->details = $object;
        $turn->code = $code;
        header($this->protocol . " " . $code . " ");
        echo json_encode ($turn);
    }
    public function runAsync () {

    }
    protected function fillVariables () {
        
        $this->mainRoute = AMBASE;
        $this->mainURL = AMURL;
        $this->ssl_enabled = SSL_ENABLED;
        $this->mainPath = AMPATH;
        
        $this->method = $_SERVER["REQUEST_METHOD"];
        $this->request_time = $_SERVER["REQUEST_TIME_FLOAT"];
        $this->uri = $_SERVER["REQUEST_URI"];
        
        $urlData = parse_url($this->uri);
        parse_str (array_key_exists("query", $urlData) ? $urlData["query"] : "", $urlData["query"]);
        $this->protocol = $_SERVER["SERVER_PROTOCOL"];
        $this->path = $urlData["path"];
        $this->_get = $urlData["query"];

        $this->requestMedia = !!(count ($_FILES) > 0);
        
        $this->files = $_FILES;

        if ($this->requestMedia) {
            $this->request = json_decode (json_encode ($_POST));
        } else {
            $this->request = json_decode (file_get_contents("php://input"));
            if ($this->request == NULL) {
                $this->request = new stdClass;
            }
        }
        // $this->mainRoute //Required
        // $this->mainResource //Required
    }

    protected function detectVariables () {
        $vars = ["method", "request_time", "uri", "protocol", "path", "_get", "requestMedia", "files",
            "request", "mainRoute", "mainResource"];
        $flag = true;
        foreach ($vars as $key) {
            if (!property_exists ($this, $key)) {
                $flag = false;
                break;
            }
        }
        if (!$flag)
            throw new \Exception ("Undefined error", 203);
    }

    public function dispatchCtrl($eName) {

        if (isset($this->controllers[$eName])) {

            foreach ($this->controllers[$eName] as $k => $ctrl) {

                $services = $this->convertToServices($ctrl["resources"]);
                call_user_func_array($ctrl["callback"], $services);

            }
        }
    }

    public function config ($callback) {
        $this->ctrl("config", $callback);
    }

    private function convertToServices($arr) {
        $turn = [];
        for ($i = 0; $i < count($arr); $i++) {
            if (($serv = $this->service($arr[$i])) == null)
                throw new Exception("Service " . $arr[$i] . " is not defined");
            $turn[] = $serv;
        }
        return $turn;
    }

    public function template ($templateUrl) {
        include_once ($templateUrl);
    }

    public function inp ($index) {
        return $this->request->index;
    }
    
    public function isIn ($index) {
        return property_exists($this->request, $index);
    }

    public function service($serviceName, PlService $service = null) {
        if ($service != null) {
            $service->app = $this;
            $service->onStart();
        }
        return $this->getOrSet("services", $serviceName, $service);
    }
    
    // Devuelve o establece el valor de un array asociativo miembro de la clase actual
    // $variable => Nombre del array asociativo $this->$variable;
    // $index => Nombre del índice asociado al objeto que se desea obtener / establecer
    // $object => Si es null, devuelve el valor. Si no es null, lo establece
    private function getOrSet($variable, $index, $object) {
        // Revisa si la variable existe. De no ser el caso, lo crea
        if (!isset($this->$variable))
            $this->$variable = [];

        // Si la variable $object no es null, le asigna un valor al array asociativo
        if ($object != null) {
            $this->{$variable}[$index] = $object; // Establece el valor a $object
        } else { } // Si es null, continúa el script
        
        return $this->{$variable}[$index];
    }
}