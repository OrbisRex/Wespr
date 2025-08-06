<?php
/**
 * Write/Update operation over DB's table Page
 *
 * @author David Ehrlich, 2013
 * @version 1.1
 * @license MIT
 */

namespace App\WesprModule\Repository;

use Nette,
    App,
    App\WesprModule\Repository;

class ModifyPageRepository extends Repository\PageRepository {
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
        return $this->updateByWhere('layout_id ? AND state IS NOT NULL', array($layoutId), $data);
    }
    
    /*Various methodes*/
    //Reorder all items in article tables
    public function sortPage ($oldOrder, $newOrder) {
        
        $combineOrder = array_combine($oldOrder, $newOrder);

        foreach($combineOrder as $order => $articlePageId) {
            $this->orderPage($order, $articlePageId);
        }
    }
    
    //Open file and read content by line
    public function readFolderContent ($path, $textOnly = false) {
        $arrayFileLine = array();
        
        if(empty($path)) {
            return NULL;
        }
        
        if(!$fileOpen = fopen($path,"rb")) {
            throw new Nette\Application\ApplicationException('File has got incorect format.');
         } else {
            //Read file by line and looking for separator. 
            while(!feof($fileOpen)) {
                
                $fileLine = trim(fgets($fileOpen));
                
                //Remove BOM UTF-8 mark form begin of file
                $bom = pack('CCC', 0xEF, 0xBB, 0xBF);
                if(substr($fileLine, 0, 3) === $bom) {
                    $fileLine = substr($fileLine, 3);
                }
                
                //Detection anchor link in template - just text content or any content of link
                if($textOnly) {
                    $resultMatched = preg_match('#^(<a name=\")?[a-zA-Z0-9\->]+(\"><\/a>)?$#', $fileLine);
                } else {
                    $resultMatched = preg_match('#^(<a name=\")?[a-zA-Z0-9${}\->]+(\"><\/a>)?$#', $fileLine);
                }
                
                //Store into array
                if($resultMatched === 1) {
                    $partLine = explode('"', $fileLine);
                    $arrayFileLine[$partLine[1]] = $partLine[1];
                }
            }

         return $arrayFileLine;
       }
    }
}