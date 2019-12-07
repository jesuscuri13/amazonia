<?php
namespace Amazonia;

class PlHelp {
	public static function arrayToObject ($array) {
		if (!is_array($array)) {
			return $array;
		}
		$turn = new stdClass;
		foreach ($array as $k => $v) {
			$turn->$k = $v;
		}
		return $turn;
	}
	protected static function statesToSQL ($states, $initial = false, $orAnd = "and ") {
		$length = count ($states);
		$values = [];
		$str = "";
		$i = 0;
		
		foreach ($states as $k => $v) {
			$str .= ($i != 0 || $initial ? $orAnd : "" ) . "`$k`=? ";
			$values[] = $v;
			$i++;
		}
		return [$str, $values];
	}
	public static function cStatesToSQL ($states, $initialAnd = false) {
		return self::statesToSQL ($states, $initialAnd, "and ");
	}
	public static function andStatesToSQL2 ($states, $initialAnd = false) {
		$length = count ($states);
		$str = "";
		$i = 0;
		
		foreach ($states as $k => $v) {
			$str .= ($i != 0 || $initial ? "and " : "" ) . "`$k`='$v' ";
			$i++;
		}
		return $str;
	}
	public static function andStatesToSQL ($states, $initialAnd = false) {
		return self::statesToSQL ($states, $initialAnd, "and ");
	}
	public static function orStatesToSQL ($states, $initialOr = false) {
		return self::statesToSQL ($states, $initialOr, "or ");
	}
	public static function orStatesToSQLSame ($states, $initialOr = false, $key) {
		$length = count ($states);
		$values = [];
		$str = "";
		$orAnd = "or ";
		foreach ($states as $i => $item) {
			$str .= ($i != 0 || $initialOr ? $orAnd : "" ) . "`$key`=? ";
			$values[] = $item;
		}
		return [$str, $values];
	}
	public static function whereInAnd ($states, $key, $initial = false) {
		return self::statesToSQL ($states, $key, $initial, "and ");
	}
	public static function whereInOr ($states, $key, $initial = false) {
		return self::statesToSQL ($states, $key, $initial, "or ");
	}
	public static function whereIn ($states, $key, $initial = false, $orAnd = "and ") {
		$length = count ($states);
		
		$str = "";
		$values = [];
		if ($length > 0) {
			foreach ($states as $i => $item) {
				$str .= ($i == 0 ? "" : ",") . "?";
				$values[] = $item;
			}
			$str = ($initial ? $orAnd . " " : "") . "$key in ($str)";
		} else {
			$str = ($initial ? $orAnd . " " : "") . "(false)";
		}
		return [$str, $values];
	}
	public static function selectVariousIn ($table, $ids, $idName) {
		$states = [];
		
		foreach ($ids as $i => $item) { $states[] = $item; }

		$sql = self::whereIn($states, $idName, false);
		$str = "select * from $table where {$sql[0]}";
		return [$str, $sql[1]];

	}
	public static function selectVarious ($conn, $table, $ids, $idName) {
		$states = [];
		
		foreach ($ids as $i => $item) { $states[] = $item; }

		$sql = self::orStatesToSQLSame($states, false, $idName);
		$str = "select * from $table where {$sql[0]}";
		return [$str, $sql[1]];

	}
	public static function select () {
	}
}