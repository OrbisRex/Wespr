<?php

/**
 * Modify operation over DB table file_group
 * 
 * @author David Ehrlich, 2014
 * @version 1.1
 * @license MIT
 */

namespace App\WesprModule\FileModule\Repository;

use Nette,
    App;

class ModifyFileGroupRepository extends App\WesprModule\FileModule\Repository\FileGroupRepository {

    public function orderFile($order, $fileId, $groupId = null) {
        if($groupId != null) {
            return $this->executeQuery('UPDATE `file_group` SET `order` = '.$order.' WHERE (`file_id` = '.$fileId.' AND `group_id` = '.$groupId.')');
        } else {
            return $this->executeQuery('UPDATE `file_group` SET `order` = '.$order.' WHERE (`file_id` = '.$fileId.')');
        }
    }
    
    public function insertFileGroup($data) {
        return $this->insertData($data);
    }
    
    public function updateFileGroup($coll, $value, $date) {
        return $this->updateByColl($coll, $value, $date);
    }
    
    public function updateFileGroupByGroupId($value, $data) {
        return $this->updateByWhere('group_id ?', array($value), $data);
    }
    
    /*Various methodes*/
    //Reorder all items in article tables
    public function sortFile ($oldOrder, $newOrder, $groupId) {
        
        $combineOrder = array_combine($oldOrder, $newOrder);

        foreach($combineOrder as $order => $fileId) {
            $result[] = $this->orderFile($order, $fileId, $groupId);
        }
        return $result;
    }
}
