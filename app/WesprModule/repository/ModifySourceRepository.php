<?php
/**
 * Write/Update operation over DB's table Source
 *
 * @author David Ehrlich, 2014
 * @version 1.1
 * @license MIT
 */

namespace App\WesprModule\Repository;

use Nette,
    App,
    App\WesprModule\Repository;

class ModifySourceRepository extends Repository\SourceRepository {
    public function insertSource($data) {
        return $this->insertData($data);
    }
    
    public function updateSource($coll, $value, $data) {
        return $this->updateByColl($coll, $value, $data);
    }
    
    public function updateSourceWhere($id, $data) {
        return $this->updateByWhere('layout_id ? AND state IS NOT NULL', array($id), $data);
    }
}