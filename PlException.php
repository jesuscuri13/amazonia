<?php
namespace Amazonia;

use \Exception;
use \stdClass;

class PlException extends Exception {
    protected $object;
    public function __construct ($message, $error) {
        parent::__construct ($message, $error);
    }
}