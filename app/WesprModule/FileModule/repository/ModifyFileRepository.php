<?php

/**
 * Modify operation over DB table file_page
 * 
 * @author David Ehrlich, 2014
 * @version 1.1
 * @license MIT
 */

namespace App\WesprModule\FileModule\Repository;

use Nette;
use App;
use App\WesprModule\FileModule\Repository;
use Nette\Utils\Finder;

class ModifyFileRepository extends Repository\FileRepository {
    
    private $fileId;
    
    public function orderFile($order, $id) {
        return $this->executeQuery('UPDATE `file` SET `order` = '.$order.' WHERE (`id` = '.$id.')');
    }
    
    public function insertFile (array $file) {
        return $this->insertData($file);
    }
    
    public function fileUpdate ($coll, $value, $data) {
        return $this->updateByColl($coll, $value, $data);
    }    
        
    /*Varous methods*/
    //Reorder all items in file tables
    public function sortFile ($oldOrder, $newOrder) {
        
        $combineOrder = array_combine($oldOrder, $newOrder);
        
        foreach($combineOrder as $order => $id) {
            $this->orderFile($order, $id);
        }
    }
}
