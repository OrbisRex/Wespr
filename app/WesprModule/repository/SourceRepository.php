<?php
/**
 * Operation over DB's table Source
 *
 * @author David Ehrlich, 2014
 * @version 1.1
 * @license MIT
 */

namespace App\WesprModule\Repository;

use Nette,
    App,
    App\Repository;

class SourceRepository extends Repository\GeneralRepository {
    private $selectLayout;
    
    private function setSelect($lang) {
        return $this->selectLayout = "*, name_".$lang." AS name, note_".$lang." AS note";
    }
        
    /*Search and Select*/
    //Admin role
    public function findAllSources() {
        return $this->findAll();
    }
    
    public function maxSourceType($column) {
        return $this->findAll()->where('filetype LIKE ".latte"')->max($column);
    }
    
    public function findLatteByLayout($lang, $id) {
        return $this->findSelectWhere($this->setSelect($lang) , 'layout_id ? AND nettename IS NOT NULL AND state IS NOT NULL AND version IN (SELECT MAX(version) FROM source WHERE layout_id ?)', array($id, $id));
    }
    
    public function findLatteByLayoutRestricted($lang, $id) {
        return $this->findSelectWhere($this->setSelect($lang) , 'layout_id ? AND nettename IS NOT NULL AND source.state IS NOT NULL AND layout.delegating ? AND layout.version IN (SELECT MAX(layout.version) FROM source WHERE layout_id ?)', array($id, array('editor', 'user'), $id));
    }

    public function findSourcesByLayout($id) {
        return $this->findWhere('layout_id ? AND layout_id = layout.id AND source.version = (SELECT MAX(version) FROM source WHERE layout_id ?) AND source.state IS NOT NULL', array($id, $id));
    }
    
    public function findOneSourcesByLayout($id) {
        return $this->findBy(array('layout_id' => $id, 'layout_id = layout.id', 'source.filetype = ".php" AND source.state IS NOT NULL'));
    }
    
    public function findOneSourceByPath($path, $layoutId, $nettename = false) {
        if(!$nettename) {
            return $this->findBy(array('path' => $path, 'layout_id' => $layoutId, 'state IS NOT NULL'));
        } else {
            return $this->findBy(array('path' => $path, 'layout_id' => $layoutId, 'nettename IS NOT NULL' ,'state IS NOT NULL'));
        }
    }
    
    public function findOneSource($id) {
        return $this->findBy(array('id' => $id));
    }
}