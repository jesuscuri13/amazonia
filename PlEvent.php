<?php
namespace Amazonia;

abstract class PlEvent {
	protected $events;
	protected $controllers;
	public function __construct() {
		$this->events = array();
	}
	public function ctrl ($eventName, $eventCallback) {
		$callback = null;
		$callArray = [];

		if (is_array($eventCallback)) {
			for ($i = 0; $i < count($eventCallback); $i++) {
				if ($i == count($eventCallback) - 1)
					$callback = $eventCallback[$i];
				else
					$callArray[] = $eventCallback[$i];
			}

		} else {
			$callback = $eventCallback;
		}
		if (!isset($this->controllers[$eventName])) {
			$this->controllers[$eventName] = [];
		}

		$this->controllers[$eventName][] = [
			"callback" => $callback,
			"resources" => $callArray
		];

	}
	public abstract function dispatchCtrl($eventName);

	public function on ($eventName, $eventCallback) {
		if (!isset($this->events[$eventName])) {
			$this->events[$eventName] = [];
		}
		$this->events[$eventName][] = $eventCallback;
	}
	public function dispatch ($eventName, ...$vars) {
		if (isset($this->events[$eventName])) {
			for ($i = 0; $i < count($this->events[$eventName]); $i++) {
				call_user_func_array('$this->events[$eventName]', $vars);
			}
			
		}
	}
}