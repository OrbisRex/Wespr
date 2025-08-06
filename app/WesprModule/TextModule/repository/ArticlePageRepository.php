<?php

/**
 * Modify operation over DB table article_page
 *
 * @author David Ehrlich, 2013
 * @version 1.2
 * @license MIT
 */

namespace App\WesprModule\TextModule\Repository;

use Nette,
    App;

class ArticlePageRepository extends App\Repository\GeneralRepository {
    private $selectArticle;
    
    private function select($lang) {
        $this->selectArticle = 'article.*, article_page.*, article.id AS article_id, article.title_'.$lang.' AS title, article.perex_'.$lang.' AS perex, page.alias_'.$lang.' AS page, page.state AS page_state, page.nettename AS page_nettename, page.id AS page_id, passcode, article.user.name';
    }

    //Admin role - Be carefull
    public function maxArticle($max) {
        return $this->findMax($max);
    }
    
    public function findMenu($id) {
        return $this->findBy(array('article_id' => $id));
    }
    
    public function filterAllArticles($lang, $pageId = null) {
        $this->select($lang);

        if($pageId != null) {
            return $this->findSelectWhereOrder($this->selectArticle, 'article_page.page_id ? AND article_page.state ?', array($pageId, array("nonpublic", "locked", "link", "public", "news")), 'article_page.order ASC');
        } else {
            return $this->findSelectWhereOrder($this->selectArticle, 'article_page.state ?', array("nonpublic", "locked", "link", "public", "news"), 'article_page.order ASC');
        }
    }
    
    public function filterAdminArticles($lang, $user, $pageId = null) {
        $this->select($lang);
        
        if($pageId != null) {
            return $this->findSelectWhereOrder($this->selectArticle, 'article_page.page_id ? AND (article.user_id ? OR article.user_id IS NULL) AND article_page.state ?', array($pageId, $user, array("nonpublic", "locked", "link", "public", "news")), 'article_page.order ASC');
        } else {
            return $this->findSelectWhereOrder($this->selectArticle, '(article.user_id ? OR article.user_id IS NULL) AND article_page.state ?', array($user, array("nonpublic", "locked", "link", "public", "news")), 'article_page.order ASC');
        }
    }
    
    public function findOneArticle($lang, $articlePageId) {
        $this->select($lang);
        return $this->findSelectWhere($this->selectArticle, "article_id ?", array($articlePageId));
    }
    
    //Other roles
    public function filterArticle($lang, $delegation, $user, $pageId = null) {
        $this->select($lang);
        
        if($pageId != null) {
            return $this->findSelectWhereOrder($this->selectArticle, 'article_page.page_id ? AND (page.delegating ? OR article.user_id ?) AND article_page.state ?', array($pageId, $delegation, $user, array("nonpublic", "locked", "link", "public", "news")), 'article_page.order ASC');
        } else {
            return $this->findSelectWhereOrder($this->selectArticle, '(page.delegating ? OR article.user_id ?) AND article_page.state ?', array($delegation, $user, array("nonpublic", "locked", "link", "public", "news")), 'article_page.order ASC');
        }
    }
    
    public function findOneUserArticle($lang, $articlePageId, $user, $role) {
        $this->select($lang);
        return $this->findSelectWhere($this->selectArticle, "article.id ? AND (article_page.user_id ? OR `page`.`delegating` ?)", array ($articlePageId, $user, $role));
    }
    
    public function findArticlesByLayout($lang, $id, $userId = NULL)
    {
        $this->select($lang);
        
        return $this->findSelectWhereOrder($this->selectArticle, 'article_page.state ? AND page.source_id ? AND article.user_id ?', array(array("nonpublic", "locked", "link", "public", "news"), $id, $userId), 'article_page.order');
    }    
}
