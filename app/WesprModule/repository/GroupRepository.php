<?php

/**
 * Operation over DB's table Group
 *
 * @author David Ehrlich, 2013
 * @version 1.2
 * @license  MIT
 */

namespace App\WesprModule\Repository;

use Nette,
    App;

class GroupRepository extends App\Repository\GeneralRepository {
    private $selectGroup;
    
    private function setSelect($lang) {
        $this->selectGroup = 'group.*, group.alias_'.$lang.' AS alias, group.text_'.$lang.' AS text, user_id.name AS user_name';
    }
    
    /*Search and Select*/
    //Admin role - Be carefull
    public function findAllGroups() {
        return $this->findAll();
    }
    
    public function maxGroup($column) {
        return $this->findAll()->max($column);
    }
    
    public function findGroups($lang) {
        $this->setSelect($lang);
        return $this->findSelectWhereOrder($this->selectGroup, "group.state ? AND type IS NOT NULL", array("nonpublic", "locked", "link", "public"), 'group.order');
    }
    
    public function findAdminGroups($lang) {
        $this->setSelect($lang);
        return $this->findSelectWhereOrder($this->selectGroup, "group.state ? AND type IS NOT NULL AND user_id IS NULL", array('nonpublic', 'locked', 'link', 'public'), 'group.order');
    }
    
    public function findGroupsByType($lang, $type) {
        $this->setSelect($lang);
        return $this->findSelectWhereOrder($this->selectGroup, "group.state ? AND (type ? OR type IS NULL) AND user_id IS NULL", array(array('nonpublic', 'locked', 'link', 'public'), $type), 'group.order');
    }
    
    public function findGroupByName($lang, $name, $user) {
        $this->setSelect($lang);
        return $this->findSelectWhereOrder($this->selectGroup, "group.state IS NOT NULL AND group.name ? AND (user_id ? OR user_id IS NULL)", array($name, $user), 'group.order');
    }
    
    public function findOneGroup($id) {
        return $this->findBy(array ('id' => $id));
    }
    
    //User role
    public function findUserGroups($lang, $user) {
        $this->setSelect($lang);
        return $this->findSelectWhereOrder($this->selectGroup, "group.state ? AND type IS NOT NULL AND user_id ?", array(array('nonpublic', 'locked', 'link', 'public'), $user), 'group.order');
    }
    
    public function findGenericGroups($lang) {
        $this->setSelect($lang);
        return $this->findSelectWhereOrder($this->selectGroup, "group.state ? AND type IS NOT NULL AND user_id IS NULL", array('public'), 'group.order');
    }
}