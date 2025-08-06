<?php

/**
 * Operation over DB table article
 *
 * @author David Ehrlich, 2013
 * @version 1.1
 * @license MIT
 */

namespace App\WesprModule\TextModule\Repository;

use Nette,
    App;

class ArticleRepository extends App\Repository\GeneralRepository {
    //Admin role - Be carefull
    public function maxArticle ($column) {
        return $this->findAll()->max($column);
    }
    
    /** @deprecated since version 1.4 **/
    public function findNewsArticle($lang, $menuId, $state) {
             return $this->findJoin('article.*, title_'.$lang.' AS title, perex_'.$lang.' AS perex, text_'.$lang.' AS text, article.order, menu.alias_'.$lang.' AS menu', '(menu_id = '.$menuId.' AND article.state IN ("nonpublic")) OR article.state IN '.$state)->order('article.order ASC');
    }
    
    //Language control
    public function checkLanguage($table, $lang) {
             return $this->executeQuery('SHOW COLUMNS FROM '.$table.' WHERE `Field` LIKE "%_'.$lang.'"');
    }

    public function readTranslation($lang, $articleId) {
             return $this->findSelectWhere('*, title_'.$lang.', perex_'.$lang.', text_'.$lang, 'id = '.$articleId);
    }
}
