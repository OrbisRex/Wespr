<?php

/**
 * Operation over DB table file_tag
 * 
 * @author David Ehrlich, 2014
 * @version 1.2
 * @license MIT
 */

namespace App\WesprModule\FileModule\Repository;

class FileTagRepository extends \App\Repository\GeneralRepository {
    private $selectTag;
    private $lang;
    
    public function setLaguage($lang) {
        $this->lang = $lang;
    }
    
    private function setSelect($lang) {
        $this->selectTag = 'file_tag.*, tag.*, file_tag.id AS file_tag_id, tag_'.$lang.' AS tag';
    }

    /*Search and select*/
    //Admin role - Be carefull
    public function findFileTags($lang, $fileId) {
        $this->setSelect($lang);
        
        return $this->findSelectWhereOrder($this->selectTag, 'file_tag.state IS NOT NULL AND file_tag.file_id ?', array($fileId), 'tag.tag_'.$lang);
    }
    
    public function findFileTagTags($lang, $fileId, $tagId) {
        $this->setSelect($lang);
        
        return $this->findSelectWhereOrder($this->selectTag, 'file_tag.state IS NOT NULL AND file_tag.file_id ? AND file_tag.tag_id ?', array($fileId, $tagId), 'tag.tag_'.$lang);
    }
}
