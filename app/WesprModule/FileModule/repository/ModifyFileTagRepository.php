<?php

/**
 * Modify operation over DB table file_tag
 * 
 * @author David Ehrlich, 2014
 * @version 1.1
 * @license MIT
 */

namespace App\WesprModule\FileModule\Repository;

use Nette,
    App,
    App\WesprModule\FileModule\Repository;

class ModifyFileTagRepository extends Repository\FileTagRepository {

    public function insertFileTag($data) {
        return $this->insertData($data);
    }
    
    public function updateFileTag($coll, $value, $date) {
        return $this->updateByColl($coll, $value, $date);
    }
}
