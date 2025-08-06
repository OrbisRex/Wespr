<?php

/**
 * Operation over DB table page
 *
 * @author David Ehrlich, 2013
 * @version 1.1
 * @license MIT
 */

namespace App\FrontModule\Repository;

use Nette,
    App,
    App\WesprModule\Repository;

class PublicUserRepository extends Repository\UserRepository {
    //Indiferent role - Be carefull
    public function findValidateUser($role) {
        return $this->findWhere('role LIKE "'.$role.'" AND state IN ("public")');
    }
    
    public function findPublicUser($id) {
        return $this->findWhere('id = '.$id.' AND state IN ("public")');
    }
}
