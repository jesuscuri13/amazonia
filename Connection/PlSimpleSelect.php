<?php
namespace Amazonia\Connection;
use \stdClass;
class PlSimpleSelect {
    protected $_cols;
    protected $_where;

    protected $lastIdName;
    protected $_join;
    public $values;
    public function __construct ($table, $cols = [], $idName = null) {
        $this->table = $table;
        $this->_cols = $cols;
        $this->_where = [];
        $this->values = [];
        $this->idName = $idName;
        $this->lastIdName = null;
        $this->lastTable = null;
        $this->_join = '';

    }

    public function join ($table, $idName) {
        $lastIdName = $this->lastIdName ?: $this->idName;
        $lastTable = $this->lastTable ?: $this->table;

        $this->_join .= " join $table on $table.$lastIdName = $lastTable.$lastIdName ";
        $this->lastIdName = $idName;
        $this->lastTable = $table;
        return $this;
    }
    
    public function columns ($arr) {
        $this->_cols = array_merge ($this->_cols, $arr);
        return $this;
    }

    protected function where ($leftElement, $rightElement, $operator) {
        $object = new stdClass();
        $object->left = $leftElement;
        $object->right = $rightElement;
        $object->op = $operator;
        $this->_where[] = $object;
    }

    protected function valuable ($col, $value, $sign) {
        $this->where ($col, '?', $sign);
        $this->values[] = $value;
        return $this;
    }

    public function state ($col, $value) {
        return $this->valuable ($col, $value, '=');
    }

    public function greater ($col, $value) {
        return $this->valuable ($col, $value, '>');
    }
    
    public function eGreater ($col, $value) {
        return $this->valuable ($col, $value, '>=');
    }

    public function smaller ($col, $value) {
        return $this->valuable ($col, $value, '<');
    }

    public function eSmaller ($col, $value) {
        return $this->valuable ($col, $value, '<=');
    }

    public function whereIn ($col, $arrValues) {
        $str = '';
        foreach ($arrValues as $i => $v) {
            $str .= ($i == 0 ? '' : ',') . '?';
            $this->values[] = $v;
        }
        $str = '(' . (count ($arrValues) > 0 ? $str : 'false') . ')';
        
        $this->where ($col, $str, ' in ');
        return $this;
    }

    public function states ($states) {
        foreach ($states as $k => $v) {
            $this->state ($k, $v);
        }
        return $this;
    }

    public function run () {
        $where = '';
        foreach ($this->_where as $k => $val) {
            $where .= ($k == 0 ? '' : ' AND ' ) . $val->left . $val->op . $val->right . " ";
        }
        
        $where = (count ($this->_where) > 0 ? 'WHERE ' : '') . $where;
        
        $select = '';
        foreach ($this->_cols as $k => $val) {
            $select .= ($k == 0 ? '': ', ') . $val;
        }

        $select = (count ($this->_cols) > 0 ? '' : '*') . $select;

        return 'SELECT ' . $select . ' FROM ' . $this->table . ' ' . $this->_join . ' ' . $where;
        
    }
}