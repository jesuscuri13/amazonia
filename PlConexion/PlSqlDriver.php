<?php
namespace Amazonia\PlConexion;
use Amazonia\PlConexion\PlIDatabaseDriver;

use \Exception;
use \stdClass;

class PlSqlDriver implements PlIDatabaseDriver {
	public function connectServer() {
		$turn = mssql_connect(
            $this->config->dbServer, 
            $this->config->dbUser, 
            $this->config->dbPassword);

        if (!$this->conn) {
            throw new Exception("Error al intentar conectar");
            
        }
        mssql_select_db($this->config->dbName, $this->conn);
        ini_set('mssql.charset', 'UTF-8'); 
        return $turn;
	}

	public function close($connection) {
		mssql_close($connection);
	}

	public function scapeString ($data) {
        $data = stripslashes($data);
        $data = str_replace ("--", "", $data);
        $data = str_replace ("'", "''", $data);
        return $data;
	}

	public function createQuery ($sql, $data) {
		$j = 0;
        for ($i = 0; $i < strlen($this->sql); $i++) {
            if ($sql[$i] == "?") {
                
                $sql = substr($sql, 0, $i) 
                    . ($data[$j] !== null ? 
                       ( "'" 
                       . $data[$j] 
                       . "'") :
                       ("NULL")
                    )
                    . substr($sql, $i + 1, strlen($sql));
                
                $i += strlen($data[$j]) + 1;
                $j++;
            }
        }
        return $sql;
	}

	public function execute ($connection, $sql, $isSelect) {
		$arr = array();
                
        $res = mssql_query($sql, $connection);
        
        $j = 0;

        if ($res === false) {
            throw new Exception("Error mysql: " . mssql_get_last_message(), 1);
        }
        if ($isSelect == true) {
            while ($row = mssql_fetch_array($res)) {
                $arr[$j] = new stdClass();
                foreach ($row as $i => $v)
                    $arr[$j]->$i = $v;
                $j++;
            }
            $res = $arr;
        }
        return $res;
	}
}