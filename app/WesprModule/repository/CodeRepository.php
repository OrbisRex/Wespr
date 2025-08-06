<?php
/**
 * Operations over DB table for the Code Editor & Management
 *
 * @author David Ehrlich, 2016
 * @version 1.2
 * @license MIT
 */

namespace App\WesprModule\Repository;

use Nette,
    App,
    App\Repository;

class CodeRepository extends Repository\GeneralRepository {
    private $selectCode;
    
    private function setSelections($lang) {
        $this->selectCode = 'code.*, code.alias_'.$lang.' AS alias, code.text_'.$lang.' AS text, user_id.name AS user_name';
    }
    
    /*Search and Select*/
    public function findAllCodes() {
        return $this->findAll();
    }
    
    public function findOneCode($id) {
        return $this->findBy(array('id' => $id));
    }
    
    /**
     * Get max value from DB Table.
     * @param string $column
     * @return Select
     */
    public function maxCode ($column) {
        return $this->findAll()->max($column);
    }
    
    //Admin role
    public function findAdminCodes($lang, $user) {
        $this->setSelections($lang);
        return $this->findSelectWhere($this->selectCode, "code.state ? AND type IS NOT NULL AND user_id ? OR user_id IS NULL", array(array("nonpublic", "locked", "link", "public"), $user));
    }
    
    //User role
    public function findUserCodes($lang, $user) {
        $this->setSelections($lang);
        return $this->findSelectWhere($this->selectCode, "code.state ? AND type IS NOT NULL AND user_id ?", array(array("nonpublic", "locked", "link", "public"), $user));
    }
}