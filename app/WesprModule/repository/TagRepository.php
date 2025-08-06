<?php
/**
 * Operation over DB's table tag
 *
 * @author David Ehrlich, 2014
 * @version 1.1
 * @license MIT
 */

namespace App\WesprModule\Repository;

use Nette,
    App,
    App\Repository;

class TagRepository extends Repository\GeneralRepository {
    private $selectTag;
    
    private function setSelect($lang) {
        $this->selectTag = 'tag.*, tag_'.$lang.' AS tag';
    }
    
    public function findAllTags() {
        return $this->findAll();
    }
    
    public function maxTag($column) {
        return $this->findAll()->max($column);
    }
    
    public function findTags($lang) {
        $this->setSelect($lang);
        return $this->findSelectWhere($this->selectTag, 'tag.state ?', array("nonpublic", "locked", "link", "public"));
    }
    
    public function findTag($lang, $tag) {
        return $this->findBy(array('tag_'.$lang => $tag));
    }
    
    public function findOneTag($id) {
        return $this->findBy(array ('id' => $id));
    }
    
    //Other roles
    public function findUserGroups($lang, $user) {
        $this->setSelect($lang);
        return $this->findSelectWhere($this->selectTag, "tag.state ? AND type IS NOT NULL AND user_id ?", array(array("nonpublic", "locked", "link", "public"), $user));
    }    
}