<?php

/**
 * Operation over DB table User_Authority
 * 
 * @author David Ehrlich, 2016
 * @version 1.0
 * @license MIT
 */

namespace App\WesprModule\Repository;

use Nette,
    App,
    App\Repository;

class UserAuthorityRepository extends Repository\GeneralRepository {
    
    private function setSelect($lang) {
        return 'authority.*, user_authority.*, authority.description_'.$lang.' AS description';
    }
    
    public function findUserAuthority($lang, $id) {
        return $this->findSelectWhere($this->setSelect($lang), 'user_authority.state ? AND user_authority.user_id ?', array(array('valid'), $id));
    }
}
