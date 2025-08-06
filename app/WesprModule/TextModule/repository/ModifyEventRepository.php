<?php

/**
 * Modify operation over DB table event
 *
 * @author David Ehrlich, 2013
 * @version 1.1
 * @license MIT
 */

namespace App\WesprModule\TextModule\Repository;

use Nette,
    App,
    App\WesprModule\TextModule\Repository;

class ModifyEventRepository extends Repository\EventRepository {
    
    public function orderEvent($order, $id) {
        return $this->executeQuery('UPDATE `event` SET `order` = '.$order.' WHERE (`id` = '.$id.')');
    }
    
    public function insertEvent($data) {
        return $this->insertData($data);
    }

    public function editEvent($eventId) {
        return $this->findBy(array ('id' => $eventId));
    }
    
    public function updateEvent($coll, $value, $data) {
        return $this->updateByColl($coll, $value, $data);
    }
    
    /*Various Methods*/
    //Reorder all items in article tables
    public function sortEvent ($oldOrder, $newOrder) {
        
        $combineOrder = array_combine($oldOrder, $newOrder);

        foreach($combineOrder as $order => $eventId) {
            $this->orderEvent($order, $eventId);
        }
    }
    
    //Open file and read content by line
    public function readFolderLine ($path) {
        //Local variables
        $x = 0;
        
        if(!$fileOpen = fopen($path,"rb")) {
            // soubor nema spravny format
            throw new Nette\Application\ApplicationException('File have incorect format.');
         } else {
            //Read file by line and looking for separator. 
            while(!feof($fileOpen)) {
                
                $fileLine = trim(fgets($fileOpen));
                
                //Remove BOM UTF-8 mark form begin of file
                $bom = pack('CCC', 0xEF, 0xBB, 0xBF);
                if(substr($fileLine, 0, 3) === $bom) {
                    $fileLine = substr($fileLine, 3);
                }
                
                if(strstr($fileLine, "\t")) {
                    $ControlChar = 1;
                    $arrayFileLine[$x] = Explode("\t", $fileLine);
                 } else if(strstr($fileLine, ";")) {
                  $ControlChar = 2;
                  $arrayFileLine[$x] = Explode(";", $fileLine);
                 } else if(strstr($fileLine,  "*")) {
                  $ControlChar = 3;
                  $arrayFileLine[$x] = Explode("*", $fileLine);
                 } else if(strstr($fileLine, "-")) {
                  $ControlChar = 4;
                  $arrayFileLine[$x] = Explode("-", $fileLine);
                 } else if(strstr($fileLine, "+")) {
                  $ControlChar = 5;
                  $arrayFileLine[$x] = Explode("+", $fileLine);
                 } else {
                    if(strstr($fileLine, "###")) {
                      break;
                    } else {
                      $arrayFileLine[$x]=$fileLine;
                    }
                }
                
                $x++;
            }

         return $arrayFileLine;
       }
    }
}