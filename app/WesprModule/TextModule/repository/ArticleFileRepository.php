<?php

/**
 * Operation over DB table article_file
 *
 * @author David Ehrlich, 2013
 * @version 1.2
 * @license MIT
 */

namespace App\WesprModule\TextModule\Repository;

use Nette,
    App;

class ArticleFileRepository extends App\Repository\GeneralRepository {
    private $selectFile;
    private $selectArticleFile;
    private $selectNoUsedFile;
    
    private function setSelect($lang) {
        $this->selectFile = 'file.*, file.name_'.$lang.' AS name, file.describe_'.$lang.' AS describe, file:file_meta.path AS meta_path';
        $this->selectArticleFile = 'article_file.*, file.*, file.name_'.$lang.' AS name, file.describe_'.$lang.' AS describe';
        $this->selectNoUsedFile = 'file.*, file.file_group.*, file.name_'.$lang.' AS name, file.describe_'.$lang.' AS describe, file.group.alias_'.$lang.' AS group, file.group.id AS group_id, file.file_meta.type AS meta_type, file.file_meta.path AS meta_path, file.user.name AS user_name';
    }
    
    //Admin role - Be carefull
    public function max($coll, $articleId) {
        return $this->findByMax($coll, array('article_id' => $articleId));
    }
    
    public function findUsedFile ($lang, $articleId) {
        $this->setSelect($lang);
        return $this->findSelectWhereOrder($this->selectFile, 'article_file.article_id ? AND file:file_meta.type ?', array($articleId, array('crop')), 'article_file.order ASC');
    }
}
