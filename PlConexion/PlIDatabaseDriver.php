<?php
namespace Amazonia\PlConexion;
interface PlIDatabaseDriver {
	public function connectServer();
	public function close($connection);
	public function scapeString($str);
	public function createQuery ($sql, $data);
	public function execute ($connection, $sql, $isSelect);
}
