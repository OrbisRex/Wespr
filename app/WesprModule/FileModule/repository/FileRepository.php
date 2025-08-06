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

class FileRepository extends \App\Repository\GeneralRepository {
    /** @var String SELECT to databse. */
    private $selectFile;
    
    private function setSelect($lang) {
        $this->selectFile = 'file.*, file.file_group.*, file.name_'.$lang.' AS name, file.describe_'.$lang.' AS describe, file.group.alias_'.$lang.' AS group, file.group.id AS group_id, file.file_meta.type AS meta_type, file.file_meta.path AS meta_path, file.user.name AS user_name';
    }
    
    //Admin role - Be carefull
    public function maxFile ($column) {
        return $this->findAll()->max($column);
    }
    
    public function findAllFile ($state) {
        return $this->findWhereOrder(':file_group.state IN '.$state,'inserttime DESC');
    }
}
