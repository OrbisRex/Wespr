<?php

/**
 * Operation over DB table file_page
 * 
 * @author David Ehrlich, 2014
 * @version 1.1
 * @license MIT
 */

namespace App\WesprModule\FileModule\Repository;

use Nette,
    App;

class FilePageRepository extends \App\Repository\GeneralRepository {
    
    private $selectFilePage;
    
    private function setSelect($lang) {
        $this->selectFilePage = 'file.*, file_page.*, file.name_'.$lang.' AS name, file.describe_'.$lang.' AS describe, page.alias_'.$lang.' AS page, page.id AS page_id, file.user.name AS user_name';
    }
    
    /*Search and select*/
    //Admin role - Be carefull
    public function maxFileOrder($max) {
        return $this->findMax($max);
    }
    
    //Other roles
    public function filterFile($lang, $user, $pageId = null) {
        $this->setSelect($lang);
        
        if($pageId != null) {
            return $this->findSelectWhereOrder($this->selectFilePage, 'file_page.page_id ? AND file.user_id ? AND file_page.state ?', array($pageId, $user, array()), 'file_page.order DESC');
        } else {
            return $this->findSelectWhereOrder($this->selectFilePage, 'file.user_id ? AND file_page.state ?', array($user, array()), 'file_page.order DESC');
        }
    }    
}
