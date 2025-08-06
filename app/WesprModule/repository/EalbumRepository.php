<?php

/**
 * Operation over DB table ealbum
 * 
 * @author David Ehrlich, 2014
 */

namespace App\WesprModule\Repository;

use Nette,
    App;

class EalbumRepository extends App\Repository\GeneralRepository {
    
    public function insertPage($data) {
        return $this->database->table('page')->insert($data);
    }
    
    public function insertGroup($data) {
        return $this->database->table('group')->insert($data);
    }
    
}
