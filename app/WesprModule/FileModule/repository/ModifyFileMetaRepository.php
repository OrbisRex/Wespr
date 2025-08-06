<?php
/**
 * Operation over DB table file_meta
 * 
 * @author David Ehrlich, 2014
 * @version 1.1
 * @license MIT
 */

namespace App\WesprModule\FileModule\Repository;

use Nette,
    App,
    App\WesprModule\FileModule\Repository;

class ModifyFileMetaRepository extends Repository\FileMetaRepository {

    public function insertFileMeta (array $file) {
        return $this->insertData($file);
    }    
}
