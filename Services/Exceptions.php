<?php 
namespace Amazonia\Services;
use Amazonia\PlService;
use Amazonia\PlException;

class Exceptions extends PlService {

    public static function responseBadRequest ($message = 'Bad Request') {
        throw new PlException ($message, '400');
    }

    public static function responseNotFound () {
        throw new PlException ('Resource Not Found', '404');
    }

    public static function responseMethodNotAllowed () {
        throw new PlException ('Method Not Allowed', '401');
    }
}