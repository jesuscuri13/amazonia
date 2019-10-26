<?php
use \stdClass;
class PlModelList {
	public const DEFAULT = "Default";
	public const IDENTITY = "Identity";
	public const MAXLETTER = "MaxLetter";
    public const MINLETTER = "MinLetter";
    public const MAX = "Max";
    public const MIN = "Min";
    public const REQUIRED = "Required";
	public const LABEL = "Label";
	public const ALIAS = "Alias";
	public const OCULT = "Ocult";

	private $table = null;
	private $rowsRules = [];
	private $pKeys;
	private $alias;
	private $relationships = [];

	public $objects;
	public function __construct($table, $idName) {
        $this->alias = [];
        $this->data = [];
	}

	public function setData ($data) {
		$this->data[] = $data;
    }
    
    public function setCollection ($collectionData) {
        $this->data = $data;
    }

    public function set ($index, $value) {
        foreach ($this->data as $k => $v) {
            $v->$index = $value;
        }
    }
    public function get ($index) {
        if (!count($this->data))
                return NULL;

        if (!property_exists ($this->data[0], $index))
            $this->data[0]->{$index} = $this->getDefault($index);
        return $this->data[0]->{$index};
    }

	public function captureData ($data) {
		foreach ($data as $k => $v) {
			$index = $this->alias->indexOf($k);
			if ($index === false)
				$this->set ($k, $v);
			else
				$this->set ($index, $v);
		}
    }
    
    public function markAsAvailable () {
        $this->available = true;
    }
    public function isAvailable () {
        return $this->available;
    }
/*
	public function rows ($array) {
		$this->rowsRules = $array;
	}

	public function overRow($string) {
		$this->rowsRules[$string] = [];
    }
*/
	public function setQuality ($row, $qualityId, $value) {
		!isset($this->rowsRules[$row]) && $this->rowsRules[$row] = [];

		$this->rowsRules[$row][$qualityId] = $value;

		if (method_exists($this, "_setQ".$qualityId))
			$this->{"_setQ$qualityId"}($row, $value);
	}

	protected function setIdName ($idName) {
        $this->idName = $row;
	}

	protected function _setQAlias($row, $value) {
		$this->alias[$row] = $value;
	}

	public function table ($value = null) {
		$value != null && $this->table = $value;
		return $this->table;
	}

	public function newOne($increase = true) {
		$std = new stdClass();
		foreach ($this->rowsRules as $k => $v) {
			$std->$k = $this->ruleDefault($k);
		}

		if ($increase)
			$this->objects->push($std);
		return $std;
	}

	public function toFalseObject($start = null, $end = null) {
		if ($start === null)
			$start = 0;
		if ($end === null)
			$end = $this->objects->length;

		$privates = [];
		foreach ($this->rowsRules as $k => $v) {
			foreach ($v as $k2 => $v2) {
				if ($k2 === self::$_private && $v2 === true)
					$privates[] = $k;
			}
		}
		$this->hidePropierties($privates);
		return $this->objects->getAll();
	}

	public function toJson($start = null, $end = null) {
		
		return json_encode($this->toFalseObject($start, $end));
	}

	public function hidePropierties ($propierties) {
		$this->objects->_each(function ($k, $v) use ($propierties) {
			foreach ($propierties as $key => $values) {
				unset($v->$values);
			}
		});
	}

	public function getId() {
		return $this->get ($this->idName);
	}

	public function getDefault($k) {
		if (isset($this->rowsRules[$k][self::DEFAULT]))
			return $this->rowsRules[$k][self::DEFAULT];
	}

	/*public function get($states) {
		if (is_object($states)) {
			$arr = new PlArray();
			foreach ($states as $k => $v)
				$arr->set($k, $v);
			$states = $arr;
		}

		$arr = new PlArray($this->newOne(false));
		$table = $this->table;

		$indexes = $arr->indexes()->map(function ($k, $v) use ($table) { return "`$table`.`$v`"; })->attach(", ");
		$st = $states->map(function ($k, $v) { return "$k = ?"; })->attach(" and ");
		$values = $states->map(function ($k, $v) { return $v; });
		$st = $st != "" ?  "where $st" : "";
		$query = "select $indexes from `{$this->table}` $st";
		$conn = PlConexion::getConexion();
		
		$obj = $conn->select($query, $values->getAll());
		$this->objects->merge($obj);
		return $obj;
		//var_dump($obj);
	}*/

	public function reviewAllRules(PlApplication $app) {
		foreach($this->rowsRules as $index => $rules) {
			$this->checkAllRulesByIndex($app, $index, $rules);
		}
	}

	protected function checkAllRulesByIndex (PlApplication $app, $index, $rules) {
		foreach ($rules as $k => $rule) {
			foreach ($this->data as $i => $data) {
				$obj = $this->checkOneRuleByIndex ($app, $data, $index, $rule, $value);
				var_dump($obj);
			}
		}
	}

	protected function checkOneRuleByIndex (PlApplication $app, $data, $index, $rule, $value) {
		$obj = new stdClass();
		$obj->propierty = $index;
        $obj->ruleValue = $value;
		switch ($rule) {
			case self::$_maxletter:
				strlen($$data->$index) <= $value && $this->app->launch("400", $obj);
				break;
			case self::$_minletter:
				strlen($$data->$index) >= $value && $this->app->launch("401", $obj);
				break;
			default:
				return true;
				break;
		}
		return true;
	}

	public function structure() {
		$obj = new stdClass();
		$name = "";
		foreach ($this->rowsRules as $k => $arr) {
			$name = $k;
			$rules = [];
			foreach ($arr as $i => $v) {
				switch ($i) {
					case self::$_identity:
					case self::$_private:
						continue;
					case self::$_alias:
						$name = $v;
						break;
					default:
						$rules[$i] = $v;
						break;
				}
			}
			$obj->$name = $rules;
		}
		return $obj;
	}
}