<?php
/**
 * Modify operations over DB table for the Code Editor & Management
 *
 * @author David Ehrlich, 2016
 * @version 1.0
 * @license  MIT
 */

namespace App\WesprModule\Repository;

use Nette,
    App,
    App\WesprModule\Repository;

class ModifyCodeRepository extends Repository\CodeRepository {

    public function insertCode($data) {
        return $this->insertData($data);
    }

    public function orderCode($order, $id) {
        return $this->executeQuery('UPDATE `code` SET `order` = '.$order.' WHERE (`id` = '.$id.')');
    }

    public function updateCode($col, $value, $data) {
        return $this->updateByColl($col, $value, $data);
    }
    
    /*Various methodes*/
    //Reorder all items in article tables
    public function sortCodes($oldOrder, $newOrder) {
        
        $combineOrder = array_combine($oldOrder, $newOrder);

        foreach($combineOrder as $order => $articleGroupId) {
            $this->orderCode($order, $articleGroupId);
        }
    }
}