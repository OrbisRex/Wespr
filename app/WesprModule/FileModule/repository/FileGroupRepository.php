<?php

/**
 * Operation over DB table file_group
 * 
 * @author David Ehrlich, 2014
 * @version 1.3
 * @license MIT
 */

namespace App\WesprModule\FileModule\Repository;

use Nette,
    App;

class FileGroupRepository extends App\Repository\GeneralRepository {
    private $selectTag;
    private $selectFile;
    
    private function setSelect($lang) {
        $this->selectTag = 'file.*, file_group.*, file:file_tag.tag.tag_'.$lang.' AS tags, file.id AS file_id, file.name_'.$lang.' AS name, file.describe_'.$lang.' AS describe, group.alias_'.$lang.' AS group, group.id AS group_id, file:file_meta.type AS meta_type, file:file_meta.data AS meta_path, file.user.name AS user_name';
        $this->selectFile = 'file.*, file_group.*, file.id AS file_id, file.name_'.$lang.' AS name, file.describe_'.$lang.' AS describe, group.alias_'.$lang.' AS group, group.id AS group_id, file:file_meta.type AS meta_type, file:file_meta.data AS meta_path, file.user.name AS user_name';
    }
    
    /*Search and select*/
    //Admin role - Be carefull
    public function maxFileOrder($max) {
        return $this->findMax($max);
    }
    
    public function findAllFiles($lang, $groupId = NULL) {
        $this->setSelect($lang);

        if(is_null($groupId)) {
            return $this->findSelectWhereOrder($this->selectTag, 'file_group.state ? AND file:file_meta.type ?', array(array("nonpublic", "locked", "link", "public"), array("appearance")), 'file_group.order DESC');
        } else {
            return $this->findSelectWhereOrder($this->selectTag, 'file_group.group_id ? AND file_group.state ? AND file:file_meta.type ?', array($groupId, array("nonpublic", "locked", "link", "public"), array('appearance')), 'file_group.order DESC');
        }
    }
    
    public function findfiles($lang, $user, $groupId, $limit = NULL) {
        $this->setSelect($lang);
        
        if(is_null($groupId)) {
            return $this->findSelectWhereOrderLimit($this->selectTag, 'file_group.user_id ? AND file_group.state ? AND file:file_meta.type ?', array($user, array("nonpublic", "locked", "link", "public"), array("appearance")), 'file_group.order DESC', $limit);
        } else {
            return $this->findSelectWhereOrderLimit($this->selectTag, 'file_group.group_id ? AND file_group.user_id ? AND file_group.state ? AND file:file_meta.type ?', array($groupId, $user, array("nonpublic", "locked", "link", "public"), array("appearance")), 'file_group.order DESC', $limit);
        }
    }

    public function findGeneralFiles($lang, $groupId = null) {
        $this->setSelect($lang);
        
        if(is_null($groupId)) {
            return $this->findSelectWhereOrder($this->selectTag, 'file_group.user_id IS NULL AND file_group.state ? AND file:file_meta.type ?', array(array("public"), array("appearance")), 'file_group.order DESC');
        } else {
            return $this->findSelectWhereOrder($this->selectTag, 'file_group.group_id ? AND file_group.user_id IS NULL AND file_group.state ? AND file:file_meta.type ?', array($groupId, array("public"), array("appearance")), 'file_group.order DESC');
        }
    }
    
    public function filterAdminLatestFiles($lang, $user) {
        $this->setSelect($lang);
        
        return $this->findSelectWhereOrder($this->selectFile, "(file.user_id ? OR file.user_id IS NULL) AND file_group.state ? AND file:file_meta.type ? AND file.inserttime BETWEEN NOW() - INTERVAL '1' MONTH AND NOW()", array($user, array("nonpublic", "locked", "link", "public"), array("appearance")), 'file.id DESC');
    }
    
    public function findAllNoUsedFiles($lang, $articleId) {
        $this->setSelect($lang);
        return $this->findSelectWhereOrder($this->selectFile, 'file.id NOT IN (SELECT file_id FROM article_file WHERE article_id ?) AND file_group.state ? AND file_id:file_meta.type ?', array($articleId, array("nonpublic", "public"), array("crop")), 'file_group.order DESC');
    }    
    
    public function findNoUsedUserFiles($lang, $articleId, $userId) {
        $this->setSelect($lang);
        return $this->findSelectWhereOrder($this->selectFile, 'file.id NOT IN (SELECT file_id FROM article_file WHERE article_id ?) AND (file.user_id ? OR file.user_id IS NULL) AND file_group.state ? AND file_id:file_meta.type ?', array($articleId, $userId, array("nonpublic", "public"), array("appearance")), 'file.user_id DESC');
    }

    //Other roles
    public function findUserLatestFiles($lang, $user) {
        $this->setSelect($lang);
        
        return $this->findSelectWhereOrderLimit($this->selectTag, "(file.user_id ? OR file.user_id IS NULL) AND file_group.state ? AND file:file_meta.type ? AND file.inserttime BETWEEN NOW() - INTERVAL '1' MONTH AND NOW()", array($user, array("nonpublic", "locked", "link", "public"), array('appearance')), 'file.id DESC', '10');
    }
    
    public function findEditFiles($lang, $ids) {
        $this->setSelect($lang);
        
        return $this->findSelectWhereOrder($this->selectTag, 'file_group.state ? AND file:file_meta.type ? AND file.id ?',array(array("nonpublic", "locked", "link", "public"), array("appearance"), $ids), 'file.id DESC');
    }
    
}
