<?php

/**
 * Operation over DB table file_group
 *
 * @author David Ehrlich, 2013
 * @version 1.1
 * @license MIT
 */

namespace App\FrontModule\Repository;

use Nette,
    App,
    App\WesprModule\FileModule\Repository;

class PublicFileGroupRepository extends Repository\FileGroupRepository {

    private $selectPhotoGroup;
    private $selectGroup;
    
    private function setSelect($lang) {
        $this->selectPhotoGroup = 'file.*, file_group.*, file.name_'.$lang.' AS name, file.describe_'.$lang.' AS describe, user_id.name AS user_name';
        $this->selectGroup = 'group.*, file_group.*, file.*, group.alias_'.$lang.' AS alias, group.text_'.$lang.' AS text, file.name_'.$lang.' AS name, file.describe_'.$lang.' AS describe, group.user_id.name AS user_name';
    }
    
    /*Search and select*/
    public function findPublicGroupPhoto($lang, $limit) {
        $this->setSelect($lang);
        return $this->findSelectWhereGroupLimit($this->selectGroup, 'file_group.state ? AND file_group.user_id IS NOT NULL', array(array('public')),'group.id', $limit);
    }
    
    public function findPublicFiles($lang, $limit) {
        $this->setSelect($lang);
        return $this->findSelectWhereLimit($this->selectPhotoGroup, 'file_group.state ? AND file_group.user_id IS NOT NULL', array(array('public')), $limit);
    }
    
    public function findPublicRandomFiles($lang, $limit) {
        $this->setSelect($lang);
        return $this->findSelectWhereOrderLimit($this->selectPhotoGroup, 'file_group.state ? AND file_group.user_id IS NOT NULL', array(array('public')), 'RAND()', $limit);
    }
    
    public function findPublicPhotos($lang, $limit) {
        $this->setSelect($lang);
        return $this->findSelectWhereLimit($this->selectPhotoGroup, 'file_group.state ? AND file_group.user_id IS NOT NULL AND file.type ?', array(array('crop'), 'image/jpeg'), $limit);
    }
    
    public function findPublicRandomPhotos($lang, $limit) {
        $this->setSelect($lang);
        return $this->findSelectWhereOrderLimit($this->selectPhotoGroup, 'file_group.state ? AND file_group.user_id IS NOT NULL AND file.type ?', array(array('public'), 'image/jpeg'), 'RAND()', $limit);
    }
    
    public function findPublicFilesByType($lang, $type, $limit) {
        $this->setSelect($lang);
        return $this->findSelectWhereLimit($this->selectPhotoGroup, 'file_group.state ? AND file_group.user_id IS NOT NULL AND file.type ?', array(array('public'), $type), $limit);
    }
    
    public function findPublicUserPhotos($lang, $user) {
        $this->setSelect($lang);
        return $this->findSelectWhereOrder($this->selectPhotoGroup, 'file_group.user_id = '.$user.' AND file_group.state IN ("public") AND file.type LIKE "image/jpeg"', 'file_group.order ASC');
    }
    
    //User query
    public function findUserFiles($lang, $user) {
        $this->setSelect($lang);
        return $this->findSelectWhereOrder($this->selectPhotoGroup, 'file_group.user_id = '.$user.' AND file_group.state IN ("public")', 'file_group.order ASC');
    }
    
    public function findUserFilesByType($lang, $type, $user) {
        $this->setSelect($lang);
        return $this->findSelectWhereOrder($this->selectPhotoGroup, 'file_group.user_id = '.$user.' AND file_group.state IN ("public") AND file.type LIKE '.$type, 'file_group.order ASC');
    }
    
    //Group query
    public function findPhotoGroups($lang, $limit) {
        $this->setSelect($lang);
        return $this->findSelectWhereLimit($this->selectPhotoGroup, 'file_group.state ? AND file.type LIKE ?', array(array("lock", "link", "public"), 'image/jpeg'), $limit);
    }
    
    public function findGroupPhotos($lang, $group) {
        $this->setSelect($lang);
        return $this->findSelectWhereOrder($this->selectPhotoGroup, 'file_group.group_id = '.$group.' AND file_group.state IN ("lock", "link", "public") AND file.type LIKE "image/jpeg"', 'file_group.order ASC');
    }
    
    //Other roles
    public function filterFile($lang, $state, $pageId = null) {
        if($pageId != null) {
            return $this->findSelectWhereOrder('file.*, file_page.*, file.name_'.$lang.' AS name, file.describe_'.$lang.' AS describe, page.alias_'.$lang.' AS page, page.id AS page_id, file.user.name AS user_name', array('file_page.page_id' => $pageId, 'file_page.state >='.$state), 'file_page.order ASC');
        } else {
            return $this->findSelectWhereOrder('file.*, file_page.*, file.name_'.$lang.' AS name, file.describe_'.$lang.' AS describe, page.alias_'.$lang.' AS page, page.id AS page_id, file.user.name AS user_name', array('file_page.state >='.$state), 'file_page.order ASC');
        }
    } 
}
