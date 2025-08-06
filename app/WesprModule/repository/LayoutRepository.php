<?php
/**
 * Operation over DB's table Template
 *
 * @author David Ehrlich, 2014
 * @version 1.2
 * @license MIT
 */

namespace App\WesprModule\Repository;

use Nette,
    App;

class LayoutRepository extends App\Repository\GeneralRepository {
    private $selectLayout;
    
    private function setSelect($lang) {
        $this->selectLayout = "layout.*, alias_".$lang." AS alias, description_".$lang." AS description";
    }

    //Admin role
    public function maxLayout($column) {
        return $this->findAll()->max($column);
    }
    
    public function findAllLayouts() {
        return $this->findAll();
    }
    
    public function findLayouts($lang) {
        $this->setSelect($lang);
        return $this->findSelectWhere($this->selectLayout, "layout.state ?", array('nonpublic', 'locked', 'link', 'public'));
    }
    
    public function findLayoutsRestricted($lang) {
        $this->setSelect($lang);        
        return $this->findSelectWhere($this->selectLayout, "layout.delegating ? AND layout.state IS NOT NULL", array('user'));
    }

    public function findOneLayout($id) {
        return $this->findBy(array ('id' => $id));
    }
}