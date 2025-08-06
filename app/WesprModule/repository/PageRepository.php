<?php
/**
 * Operation over DB's table Page
 *
 * @author David Ehrlich, 2013
 * @version 1.1
 * @license MIT
 */

namespace App\WesprModule\Repository;

use Nette,
    App,
    App\Repository;

class PageRepository extends Repository\GeneralRepository {
    private $selectPage;
    
    private function setSelect($lang) {
        $this->selectPage = 'page.*, page.alias_'.$lang.' AS alias, content_'.$lang.' AS content, source.nettename, source.state AS source_state';
    }

    //Admin role - Be carefull
    public function findAllPage() {
        return $this->findAll();
    }
    
    public function maxPage($column) {
        return $this->findAll()->max($column);
    }
    
    public function countPages($state) {
        return $this->countWhere('state ?', $state);
    }
    
    public function findAllPages($lang) {
        $this->setSelect($lang);
        return $this->findSelectWhereOrder($this->selectPage, 'page.state NOT ?', NULL, 'page.order');
    }
    
    public function findAdminPages($lang, $user) {
        $this->setSelect($lang);
        return $this->findSelectWhereOrder($this->selectPage, '(page.user_id ? OR page.user_id IS NULL) AND page.state IS NOT NULL', array($user), 'page.order');
    }
    
    public function findParentPages($lang, $id) {
        $this->setSelect($lang);
        return $this->findSelectWhere($this->selectPage, "page.id ?", array($id));
    }
    
    public function findOnePage($id) {
        return $this->findBy(array ('id' => $id));
    }
    
    public function findMainPages($level) {
        return $this->findBy(array ('level = '.$level, 'state IS NOT NULL'));
    }
    
    public function findAdminMainPages($user) {
        return $this->findBy(array ('level = 0', 'user_id = '.$user.' OR user_id IS NULL', 'state IS NOT NULL'));
    }
    
    //Other roles
    public function countUserPages($state, $delegating, $user) {
        return $this->countWhere('state ? AND (user_id ? OR delegating ?)', array($state, $user, $delegating));
    }
    
    public function findUserMainPages($parent) {
        return $this->findSelectWhere($this->selectPage, 'page.id ? AND page.state IS NOT NULL', array ($parent));
    }
    
    public function findUserPages($lang, $user) {
        $this->setSelect($lang);
        return $this->findSelectWhereOrder($this->selectPage, 'page.state IS NOT NULL AND page.parent IS NOT NULL AND page.user_id ? OR page.delegating ?', array($user, 'user'), 'page.order');
    }
    
    public function findOwnsPages($lang, $delegating, $user, $order) {
        $this->setSelect($lang);
        return $this->findSelectWhereOrder($this->selectPage, 'page.state IS NOT NULL AND (page.delegating ? OR page.user_id ?)', array($delegating, $user), $order);
    }
    
    public function findUserLevelUpPages($lang, $level, $user) {
        $this->setSelect($lang);
        return $this->findSelectWhere($this->selectPage, 'level <= '.$level.' AND state IS NOT NULL AND user_id = '.$user);
    }
   
    public function findPageByLayout($layoutId) {
        return $this->findWhere('layout_id ? AND state IS NOT NULL', array($layoutId));
    }    
}