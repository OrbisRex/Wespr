<?php

/**
 * Operation over DB table group
 *
 * @author David Ehrlich, 2013
 * @version 1.1
 * @license MIT
 */

namespace App\FrontModule\Repository;

use Nette,
    App,
    App\WesprModule\Repository;

class PublicGroupRepository extends Repository\GroupRepository {
    
    private $selectGroup;
    
    private function setSelect($lang) {
        $this->selectGroup = 'group.*, group.alias_'.$lang.' AS alias, group.text_'.$lang.' AS text, user_id.name AS user_name';
    }
    
    public function maxGroup($column) {
        return $this->findAll()->max($column);
    }
    
    public function findAlbum($lang, $group) {
        $this->setSelect($lang);
        return $this->findSelectWhere($this->selectGroup, 'group.id = '.$group);
    }
    
    public function findPublicAlbum($lang, $limit) {
        $this->setSelect($lang);
        return $this->findSelectWhereLimit($this->selectGroup, 'group.state ? AND group.user_id IS NOT NULL', array(array('public')), $limit);
    }
    
    public function findUserGroups($lang, $user) {
        $this->setSelect($lang);
        return $this->findSelectWhere($this->selectGroup, 'group.state IN ("lock", "link", "public") AND user_id = '.$user);
    }
}