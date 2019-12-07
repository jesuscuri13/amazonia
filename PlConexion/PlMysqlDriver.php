<?php
namespace Amazonia\PlConexion;
use Amazonia\PlConexion\PlIDatabaseDriver;

use \mysqli;
use \Exception;
use \stdClass;

class PlMysqlDriver implements PlIDatabaseDriver {
    private $conn = null;
    public function connectServer () {
        $turn = new mysqli(
            $this->config->dbServer,
            $this->config->dbUser,
            $this->config->dbPassword,
            $this->config->dbName);
        

        if ($turn->connect_errno) {
            throw new Exception("Error al intentar conectar a MySQL: (" . $turn->connect_errno . ") " . $turn->connect_error);
        }
        $turn->set_charset("utf8");
        $this->conn = $turn;
        return $turn;
    }
    public function close ($connection) {
        $connection->close();
    }

    public function scapeString ($data) {
        $data = $this->conn->real_escape_string ( $data );
        //$data = str_replace ("--", "", $data);
        //$data = str_replace ("'", "\\'", $data);
        return $data;
    }

    public function createQuery ($sql, $data) {
        $j = 0;
        for ($i = 0; $i < strlen($sql); $i++) {
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
        $res = $connection->query($sql);
        //var_dump($res);
        if ($res === false) {
            throw new Exception($connection->error);
        }
        
        $j = 0;
        
        if ($isSelect) {
            while ($row = $res->fetch_assoc()) {
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