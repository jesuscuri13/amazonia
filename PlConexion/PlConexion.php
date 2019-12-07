<?php
namespace Amazonia\PlConexion;

use Amazonia\PlConexion\PlMysqlDriver;
use Amazonia\PlConexion\PlSqlDriver;
use Amazonia\PlService;

final class PlConexion extends PlService {
    private $serverType = null;
    
    private static $INSTANCE = null;
    private $conn = null;
    private $data = null;
    private $sql = "";
    private $results = array();
    public $lastInsertId = null;
    private $_debug = null;
    private $config = NULL;

    private $driver = null;

    public static function getConexion() {
        return self::obtenerConexion();
    }
    
    public static function obtenerConexion() {
        if (self::$INSTANCE == null) {
            self::$INSTANCE = new PlConexion();
        }
        return self::$INSTANCE;
    }
    
    public function __construct() {
        $this->_debug = array(
            "printsql" => false,
            "nosql" => false,
            "printvalues" => false
        );
        // default
        $this->setDriver("mysql");
        if (isset($this->config->dbDriver))
            $this->setDriver($this->config->dbDriver);
    }

    public function setConfig ($config) {
        
        $this->config = $config;
        if (isset($this->config->dbDriver))
            $this->setDriver($this->config->dbDriver);
    }

    public function setDriver($str) {

        switch ($str) {
            case "sqlserver":
                $this->driver = new PlSqlDriver();
                $this->driver->config = $this->config;
                break;
            case "mysql":
                $this->driver = new PlMysqlDriver();
                $this->driver->config = $this->config;
                break;
        }
    }

    public function debug($index, $value = null) {
        if ($value === null)
            if (array_key_exists($index, $this->_debug))
                return $this->_debug[$index];
            else
                return null;
        $this->_debug[$index] = $value;
        return $this;
    }

    private function connect() {
        $this->conn = $this->driver->connectServer();
    }
    public function getConnectObject() {
        return $this->conn;
    }
    private function close() {
        $this->driver->close($this->conn);
    }
    private function clearData() {
        foreach($this->data as $key => $value) {
            if ($value !== null)
                $this->data[$key] = $this->clear($value);
        }
    }
    public function limpiarTexto($str) {
        return $str;
    }
    private function clear($data) {
        return $this->driver->scapeString($data);
    }
    private function createSqlQuery() {
        $this->sql = $this->driver->createQuery($this->sql, $this->data);
    }
    private function executeQuery($isSelect) {
        if ($this->debug("printsql"))
            echo $this->sql;
        if ($this->debug("nosql")) {
            throw new Exception("SQL can't be executed");
        }

        $this->results = $this->driver->execute ($this->conn, $this->sql, $isSelect);
    }
    public function query($sql, $data, $isSelect = false) {
        
        $this->data = $data;
		if ($this->debug("printvalues")) {
            var_dump($data);
        }
        $this->sql = $sql;
		try {
			$this->connect();
		} catch (Exception $ex) {
			throw $ex;
		}
		
        $this->clearData();
        $this->createSqlQuery();
		
        try {
            $this->executeQuery($isSelect);
			//var_dump($this->conn->insert_id);
			
			if (property_exists($this->conn, "insert_id")) {
				$this->lastInsertId = $this->conn->insert_id;
			}
            $this->close();
			
            return $this->results;
        } catch (Exception $ex) {
            $this->close();
            throw $ex;
        }
        
    }
    public function selectRow ($qry, $data) {
        $rows = $this->select ($qry, $data);
        return count ($rows) ? $rows[0] : NULL;
    }
    public function select($qry, $data) {
        return $this->query($qry, $data, true);
    }
    public function selectOne($table, $id) {
        return $this->query("select * from $table where id=?", array($id), true);
    }
    
    public function generateId($table, $length) {
        $existences = $this->query("select count(id) as num from $table", array(), true);
        if ($existences[0]['num'] >= pow(10, $length)) {
            return false;
        }
        do {
        $possible = "";
        for ($i = 0; $i < $length; $i++) {
            $possible .= rand(0,9);
        }
        $existences = $this->query("select id from $table where id=?", array($possible), true);
        } while (count($existences) != 0);
        return $possible;
    }
}
?>