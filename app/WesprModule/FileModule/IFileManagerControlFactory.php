<?php

/**
 * Description of IFileManagerControlFactory
 *
 * @author David, 2017
 * @version 1.0
 * @license MIT
 */

namespace App\WesprModule\FileModule;

interface IFileManagerControlFactory {
    
    /** @return FileManagerControl */
    public function create();
}
