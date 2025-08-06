<?php

/**
 * Modify operation over DB table article_file
 *
 * @author David Ehrlich, 2013
 * @version 1.1
 * @license MIT
 */

namespace App\WesprModule\TextModule\Repository;

use Nette,
    App,
    App\WesprModule\TextModule\Repository;

class ModifyArticleFileRepository extends Repository\ArticleFileRepository {

    public function insertFile ($data) {
        return $this->insertData($data);
    }
    
    public function updateFileArticleByPageId($value, $data) {
        return $this->updateByWhere('page_id = '.$value.' AND state IS NOT NULL', $data);
    }
        
    public function deleteArticleFile($fileId, $articleId) {
        return $this->deleteById(array('file_id' => $fileId, 'article_id' => $articleId));
    }
}
