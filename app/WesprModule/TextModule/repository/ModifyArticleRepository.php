<?php

/**
 * Modify operation over DB table article
 *
 * @author David Ehrlich, 2013
 * @version 1.1
 * @license MIT
 */

namespace App\WesprModule\TextModule\Repository;

use Nette,
    App,
    App\WesprModule\TextModule\Repository;

class ModifyArticleRepository extends Repository\ArticleRepository {

    public function insertArticle($data) {
             return $this->insertData($data);
    }

    public function updateArticle($id, $data) {
             return $this->updateById(array('id' => $id), $data);
    }

    /*Language control*/
    public function addLanguage($table, $column) {
             return $this->executeQuery('ALTER TABLE '.$table.' ADD COLUMN '.$column.' VARCHAR(45) DEFAULT null');
    }

    /*Various methods*/
    //Looking for language for translate
    public function findTranslation ($langTranslation, $articleId) {

        $result = $this->checkLanguage('article', $langTranslation)->fetchAll();

        foreach($result as $column) {
            if(substr($column->Field, -2) == $langTranslation) {
                return $this->readTranslation($langTranslation, $articleId, 1);
            }
            else {
                $nameColumn = rtrim($column->Field, 'cs').$langTranslation;
                return $this->addLanguage('article', $nameColumn);
            }
        }
    }
}