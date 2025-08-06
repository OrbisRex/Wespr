<?php

/**
 * Read operation over DB table event
 *
 * @author David Ehrlich, 2013
 * @version 1.1
 * @license MIT
 */

namespace App\WesprModule\TextModule\Repository;

use Nette,
    App;

class EventRepository extends App\Repository\GeneralRepository {
    private $selectEvent;
    
    private function setSelect($lang) {
        $this->selectEvent = 'event.*';
    }
    
    public function findUserEvent($lang, $user) {
        $this->setSelect($lang);
        
        return $this->findSelectWhereOrder($this->selectEvent, "user_id ? AND state ?", array($user, array("nonpublic", "public")), 'inserttime DESC');
    }
}