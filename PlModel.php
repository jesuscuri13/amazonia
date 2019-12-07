<?php
namespace Amazonia;

use \stdClass;
use \Exception;
use Amazonia\Connection\PlSimpleSelect;
use Amazonia\PlApplication;

class PlModel {
    public const ALIAS = 1;
    public const DEFAULT = 2;
    public const REQUIRED = 2;
    
    protected $_nonNullId = false;
    protected $data = null;
    protected $_available = false;
    
    public function __construct (PlApplication $app, $table = null, $idName = null) {
        $this->data = new stdClass;
        $this->app = $app;
        $this->_nonNullId = false;
        $this->prefix = "";
        $this->table = $table;
        $this->idName = $idName;
        $this->rules = [];
        $this->aliases = [];
        $this->required = [];
        $this->onlyRulesAccepted = false;
    }

    public function addRule ($index, $rule, $value = null) {
        if (!isset ($this->rules[$index])) {
            $this->rules[$index] = [];
        }
        $this->rules[$index][$rule] = $value;
        if ($rule == self::ALIAS) {
            $this->aliases[$value] = $index;
        }
        if ($rule == self::REQUIRED) {
            $this->required[] = $index;
        }
    }

    public function set ($index, $value) {
        $this->data->$index = $value;
        return $this;
    }

    public function setData ($data) {
        foreach ($data as $k => $v) {
            $this->checkAllRules ($k, $v);
        }
        return $this;
    }
    public function checkAllRules ($k, $v) {
        // Alias
        if (array_key_exists ($k, $this->aliases)) {
            $index = $this->aliases[$k];
        } else {
            $index = $k;
        }/* else if (array_key_exists ($k, $this->rules) && array_key_exists (self::ALIAS, $this->rules[$k])) {
            throw new Exception ('El elemento ' . $k . ' tiene un alias, no se puede establecer directamente');
        }*/
        
        $ruled = false;
        if (array_key_exists ($index, $this->rules)) {
            $ruled = true;
            foreach ($this->rules[$index] as $rule => $rv) {
                if ($rule == self::REQUIRED) {
                    
                    if ($v === NULL || $v === '') {
                        throw new Exception ('El elemento ' . $index . ' no puede estar vacío', 400);
                    }
                }
            }
        }
        if ($this->onlyRulesAccepted) {
            if (!$ruled) {
                throw new Exception ('El elemento ' . $index . ' no está aceptado', 400);
            }
        } 
        $this->data->$index = $v;
    
    }

    protected function checkRequired ($data, $index) {
        if (!property_exists ($data, $index) || $data->$index === NULL || $data->$index === '') {
            throw new Exception ('El elemento ' . $index . ' no puede estar vacío', 400);
        }
    }

    public function checkData() {
        foreach ($this->rules as $index => $rule) {
            if (array_key_exists (self::REQUIRED, $rule)) {
                $this->checkRequired ($this->data, $index);
            }
        }
    }

    public function setId ($value) {
        if (!isset ($this->idName))
            throw new Exception ("No se ha establecido el nombre del id", 500);
        $this->set ($this->idName, $value);
        return $this;
    }

    public function unsetData ($index) {
        unset($this->data->$index);
        return $this;
    }

    public function get ($index) {
        return $this->data->$index;
    }
    
    public function getId () {
        if (!isset ($this->idName))
            throw new Exception ("No se ha establecido el nombre del id", 500);
        return $this->get ($this->idName);
    }

    public function getData () {
        return $this->data;
    }

    public function exists ($index) {
        return property_exists ($this->data, $index);
    }

    public function isAvailable () {
        return $this->_available;
    }

    public function markAsAvailable () {
        $this->_available = true;
        return $this;
    }

    public function nonNullId ($prefix) {
        $this->_nonNullId = true;
        $this->prefix = $prefix;
    }

    public function nullId () {
        $this->_nonNullId = false;
    }

    protected function refreshWithId ($id) {
        if ($id !== null) {
            $this->set ($this->idName, $id);
            $this->refresh();
        }
    }

    public function strCol ($column) {
        return $this->table . '.' . $column;
    }

    public function tbl ($column) {
        return $this->strCol ($column);
    }

    public function idSettled() {
        try {
            return $this->get ($this->idName) !== null;
        } catch (Exception $ex) {
            throw $ex;
        }
    }

    public function probeIdSettled() {
        if (!$this->idSettled()) {
            throw new Exception ("The id haven't been settled", 500);
        }
        return $this;
    }

    public function refreshOnUnavailable() {
        if (!$this->isAvailable()) {
            $this->refresh();
            if (!$this->isAvailable()) {
                throw new Exception ("Resource not found", 404);
            }
        }
        return $this;
    }

    public function refresh () {
        
        if ($this->get ($this->idName) === null) {
            return $this;
        }

        $row = $this->getOne ($this->table, $this->getId(), $this->idName);
        
        if ($row != null) {
            $this->markAsAvailable();
            $this->data = $row;
        }
        return $this;
    }

    public function getOne ($table, $id, $idName) {
        $select = new PlSimpleSelect ($table, ['*'], $idName);
        $select->state ($idName, $id);

        $conn = $this->app->service('DB');
        return $conn->selectRow ($select->run(), $select->values);
	}

    public function insert () {
        if ($this->_available) {
            return false;
        }

        if (!$this->falseModel) {
            return false;
        }
        
        $idName = $this->idName;
        if (!$this->_nonNullId) {
            $this->data->$idName = $this->falseModel->createWithoutId ($this->table, $this->data);
        } else {
            $this->data->$idName = $this->falseModel->create ($this->table, $this->idName, $this->data, $this->prefix);
        }
        $this->markAsAvailable();
        
        return $this;
    }
    
    public function update () {
        if (!$this->_available) {
            return false;
        }

        if (!$this->falseModel) {
            return false;
        }
        
        $this->falseModel->update ($this->table, $this->idName, $this->data);
        
        return $this;
    }
    
    public function _delete () {
        if (!$this->_available) {
            return false;
        }

        if (!$this->falseModel) {
            return false;
        }
        
        //$this->falseModel->_delete ($this->table, $this->idName, $this->data);
        
        return $this;
    }
}