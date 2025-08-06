<?php

/**
 * Operation over DB table article_page
 *
 * @author David Ehrlich, 2013
 * @version 1.1
 * @license MIT
 */

namespace App\FrontModule\Repository;

use Nette,
    App,
    App\WesprModule\TextModule\Repository;

class PublicArticlePageRepository extends Repository\ArticlePageRepository {
    private $selectArticle;
    
    private function setSelect($lang) {
        $this->selectArticle = 'article.*, article_page.*, article.title_'.$lang.' AS title, article.perex_'.$lang.' AS perex, article.text_'.$lang.' AS text';
    }
    
    public function findPublicArticles($lang, $limit) {
        $this->setSelect($lang);
        return $this->findSelectWhereLimit($this->selectArticle, 'article_page.state ?', array(array("public")), $limit);
    }
    
    public function findArticlesByNettename($lang, $nettename) {
        $this->setSelect($lang);
        return $this->findSelectWhereOrder($this->selectArticle, 'article_page.state ? AND page.nettename LIKE ?', array(array("public"), $nettename), 'article_page.order');
    }
    
    public function findArticlesByNettenamePages($lang, $nettename, $lenght, $offset) {
        $this->setSelect($lang);
        return $this->findSelectWhereLimitOffset($this->selectArticle, 'article_page.state ? AND page.nettename ?', array(array('public'), $nettename), $lenght, $offset);
    }
    
    public function countNewsArticles($lang, $nettename) {
        $this->setSelect($lang);
        return $this->findSelectWhere($this->selectArticle, 'article_page.state ? AND page.nettename ?', array(array('news'), $nettename))->count();
    }

    public function findNewsArticles($lang, $nettename, $number, $offset) {
        $this->setSelect($lang);
        return $this->findSelectWhereOrderLimitOffset($this->selectArticle, 'article_page.state ? AND page.nettename ?', array(array('news'), $nettename), 'article_page.order', $number, $offset);
    }
    
    public function findArticleById($lang, $id) {
        $this->setSelect($lang);
        return $this->findSelectWhere($this->selectArticle, 'article_id = '.$id.' AND article_page.state IN ("public")');
    }
    
    //User
    public function findUserArticles($lang, $user) {
        $this->setSelect($lang);
        return $this->findSelectWhere($this->selectArticle, 'article_page.user_id = '.$user.' AND article_page.state IN ("public")');
    }    
}
