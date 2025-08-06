<?php

/**
 * Operation over DB table user
 * 
 * @author David Ehrlich, 2014
 * @version 1.1
 * @license MIT
 */

namespace App\WesprModule\Repository;

use Nette,
    App,
    App\Repository;

class UserRepository extends Repository\GeneralRepository {
    private function setSelect($lang) {
        return 'user.*';
    }
    
    public function findOneUser($id) {
        return $this->findBy(array ('id' => $id));
    }
    
    public function findOneUserByEmail($email) {
        return $this->findBy(array ('email' => $email));
    }
    
    public function findOneUserByTag($tag) {
        return $this->findBy(array ('tag' => $tag));
    }
}
