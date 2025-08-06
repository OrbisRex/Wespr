<?php
/**
 * Modify operations over DB's table Group
 *
 * @author David Ehrlich, 2013
 * @version 1.1
 * @license  MIT
 */

namespace App\WesprModule\Repository;

use Nette,
    App,
    App\WesprModule\Repository;

class ModifyGroupRepository extends Repository\GroupRepository {

    public function insertGroup($data) {
        return $this->insertData($data);
    }

    public function orderGroup($order, $id) {
        return $this->executeQuery('UPDATE `group` SET `order` = '.$order.' WHERE (`id` = '.$id.')');
    }

    public function updateGroup ($col, $value, $data) {
        return $this->updateByColl($col, $value, $data);
    }
    
    public function updateGroupByLayout($layoutId, $data) {
        return $this->updateByWhere('layout_id = '.$layoutId.' AND state IN ("nonpublic", "locked", "link", "public")', $data);
    }
    
    /*Various methodes*/
    //Reorder all items in article tables
    public function sortGroup ($oldOrder, $newOrder) {
        
        $combineOrder = array_combine($oldOrder, $newOrder);

        foreach($combineOrder as $order => $articleGroupId) {
            $this->orderGroup($order, $articleGroupId);
        }
    }
}