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

class ModifyPageRepository extends Repository\ModifyPageRepository {

    public function insertPage($data) {
        return $this->insertData($data);
    }

    public function orderPage($order, $id) {
        return $this->executeQuery('UPDATE `page` SET `order` = '.$order.' WHERE (`id` = '.$id.')');
    }

    public function updatePage ($col, $value, $data) {
        return $this->updateByColl($col, $value, $data);
    }
    
    public function updatePageByLayout($layoutId, $data) {
        return $this->updateByWhere('layout_id = '.$layoutId.' AND state IS NOT NULL', $data);
    }
}