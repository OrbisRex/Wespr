<?php

/**
 * Modify operation over DB table file_page
 * 
 * @author David Ehrlich, 2014
 * @version 1.1
 * @license MIT
 */

namespace App\WesprModule\FileModule\Repository;

use Nette,
    App,
    App\WesprModule\FileModule\Repository;

class ModifyFilePageRepository extends Repository\FilePageRepository {

    public function orderFile($order, $id, $pageId = null) {
        if($pageId != null) {
            return $this->executeQuery('UPDATE `file_page` SET `order` = '.$order.' WHERE (`id` = '.$id.' AND `page_id` = '.$pageId.')');
        } else {
            return $this->executeQuery('UPDATE `file_page` SET `order` = '.$order.' WHERE (`id` = '.$id.')');
        }
    }
    
    public function insertFilePage($data) {
        return $this->insertData($data);
    }
    
    public function updateFilePage ($coll, $value, $date) {
        return $this->updateByColl($coll, $value, $date);
    }
    
    public function updateFilePageByPageId($value, $data) {
        return $this->updateByWhere('page_id = '.$value.' AND state IS NOT NULL', $data);
    }
    
    /*Various methodes*/
    //Reorder all items in article tables
    public function sortFile ($oldOrder, $newOrder, $pageId) {
        
        $combineOrder = array_combine($oldOrder, $newOrder);

        foreach($combineOrder as $order => $articlePageId) {
            $this->orderFile($order, $articlePageId, $pageId);
        }
    }
}
