<?php

/**
 * Operation over DB table source
 *
 * @author David Ehrlich, 2013
 * @version 1.1
 * @license MIT
 */

namespace App\FrontModule\Repository;

use Nette,
    App,
    App\WesprModule\Repository;

class PublicSourceRepository extends Repository\SourceRepository {
    //Admin role
    public function maxSourceType($column) {
        return $this->findAll()->where('filetype LIKE ".latte"')->max($column);
    }
    
    public function findLatteByLayout($lang, $id) {
        return $this->findSelectWhere('*, name_'.$lang.' AS name, note_'.$lang.' AS note' , 'layout_id = '.$id.' AND nettename IS NOT NULL AND state IS NOT NULL');
    }
    
    public function findLatteByLayoutRestricted($lang, $id) {
        return $this->findSelectWhere('*, name_'.$lang.' AS name, note_'.$lang.' AS note' , 'layout_id = '.$id.' AND nettename IS NOT NULL AND state = 2');
    }

    public function findSourcesByLayout($id) {
        return $this->findBy(array('layout_id' => $id, 'layout_id = layout.id', 'source.state IS NOT NULL'));
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