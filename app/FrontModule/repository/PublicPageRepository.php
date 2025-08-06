<?php

/**
 * Operation over DB table page
 *
 * @author David Ehrlich, 2013
 * @version 1.1
 * @license MIT
 */

namespace App\FrontModule\Repository;

use Nette,
    App,
    App\WesprModule\Repository;

class PublicPageRepository extends Repository\PageRepository {
    private $selectPage;
    
    private function setSelect($lang) {
        $this->selectPage = 'page.*, page.alias_'.$lang.' AS alias, content_'.$lang.' AS content, source.nettename, source.state AS source_state';
    }
    
    public function checkStatusPages() {
        return $this->findWhere('state ?', array('locked', 'link', 'public'));
    }
    
    /*UserPresenter*/
    public function findPublicPages($lang, $limit) {
        $this->setSelect($lang);
        return $this->findSelectWhereLimit($this->selectPage, 'page.state ? AND page.level ? AND page.parent IS NOT NULL', array(array('public'), 0), $limit);
    }
    
    public function findUserPages($lang, $user) {
        $this->setSelect($lang);
        return $this->findSelectWhereOrder($this->selectPage, 'page.state IN ("locked", "link", "public") AND page.level = 0 AND page.parent IS NOT NULL AND page.user_id = '.$user, 'page.order');
    }
    
    /* PublicPresenter */
    public function findOnePageById($lang, $id) {
        $this->setSelect($lang);
        return $this->findSelectWhere($this->selectPage, 'id = '.$id);
    }
    
    public function findPageByNettename($lang, $nettename) {
        $this->setSelect($lang);
        return $this->findSelectWhere($this->selectPage, 'nettename LIKE "'.$nettename.'" AND page.state IN ("public")');
    }
    
    /* Menu */
    public function findMainPage($lang) {
        $this->setSelect($lang);
        return $this->findSelectWhereOrder($this->selectPage, 'level ? AND (parent IS NULL OR parent ?) AND page.state ? AND page.user_id IS NULL', array(0, 0, array('public')), 'order');
    }
    
    public function findPagesChilde($lang, $level, $pageId) {
        $this->setSelect($lang);
        return $this->findSelectWhereOrder($this->selectPage, 'level = '.$level.' AND parent = '.$pageId.' AND state = IN ("public") AND page.user_id IS NULL', 'order');
    }
    
    public function findPageTitle($lang, $id) {
        return $this->findWhere('page.alias_'.$lang.' AS alias', array('id' => $id));
    }
    
    public function findLevelPages($lang, $level, $parent, $order) {
        return $this->findSelectWhereOrder('page.*, page.alias_'.$lang.' AS alias, page.anchor AS anchor', 'page.level >= '.$level.' AND page.parent = '.$parent.' AND page.state >= 1', $order);
    }
}