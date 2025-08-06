<?php

/**
 * Operation over DB table article_file
 *
 * @author David Ehrlich, 2013
 * @version 1.1
 * @license MIT
 */

namespace App\FrontModule\Repository;

use Nette,
    App,
    App\WesprModule\TextModule\Repository;

class PublicArticleFileRepository extends Repository\ArticleFileRepository {
    private $selectFile;
    private $selectArticleFile;
    
    private function setSelect($lang) {
        $this->selectFile = 'file.*, file.name_'.$lang.' AS name, file.describe_'.$lang.' AS describe';
        $this->selectArticleFile = 'article_file.*, file_id:file_meta.path AS path_img, file.*, file.name_'.$lang.' AS name, file.describe_'.$lang.' AS describe';
    }
    
    public function findPublishedFile($lang, $user) {
        $this->setSelect($lang);
        return $this->findSelectWhereOrder($this->selectFile, array('file.user_id' => $user, 'article_file.state IN ("public")'), 'article_file.order ASC');
    }
    
    public function findPublishedMainFiles($lang, $articleId, $fileMetaType = NULL) {
        $this->setSelect($lang);
        if($fileMetaType === NULL)
        {
            return $this->findSelectWhere($this->selectArticleFile, 'article_file.article_id ? AND article_file.state ?', array($articleId, array("public")));
        }
        else
        {
            return $this->findSelectWhere($this->selectArticleFile, 'article_file.article_id ? AND article_file.state ? AND file_id:file_meta.type ?', array($articleId, array("public"), $fileMetaType));
        }
    }
    
    public function findRandomFiles($lang, $limit) {
        $this->setSelect($lang);
        return $this->findSelectWhereLimit($this->selectArticleFile, 'article_file.state ?', array(array("public")), $limit);
    }
}
