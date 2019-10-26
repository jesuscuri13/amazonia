<?php
namespace Amazonia;
class PlNodo {
	public function __construct($id, $objeto) {
		$this->padre = null;
		$this->hijos = [];
		$this->id = $id;
		$this->objeto = $objeto;
	}
	public function insertar ($id, $objeto) {
		$this->hijos[] = new PlNodo($id, $objeto);
	}
	public function buscar ($arr) {
		$aux = $this;
		$indice = 0;
		if ($indice <= $arr) {
			$encontrado = $this->buscarPorNodo($aux, $indice, $arr);
			return $encontrado;
		}
	}
	private function buscarPorNodo ($nodo, $indice, $arr) {
		if ($nodo->id == $arr[$indice]) {
		
			if ($indice == count($arr) - 1) {
				return $nodo;
			} else {
				$length = count($nodo->hijos);
				for ($i = 0; $i < $length; $i++) {
					$result = $this->buscarPorNodo($nodo->hijos[$i], $indice + 1, $arr);
					if ($result != null)
						return $result;
				}
			}
			
			return null;
		}
	}
}