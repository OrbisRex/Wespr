<?php

/**
 * Operation over DB table article
 *
 * @author David Ehrlich, 2013
 * @version 1.1
 * @license MIT
 */

namespace App\FrontModule\Repository;

use Nette,
    App,
    App\WesprModule\TextModule\Repository;

class PublicArticleRepository extends Repository\ArticleRepository {
    
    public function findUserArticles($lang, $userId) {
        return $this->findSelectWhere('*, title_'.$lang.' AS title, perex_'.$lang.' AS perex, text_'.$lang.' AS text', 'user_id = '.$userId);
    }
}
