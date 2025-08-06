<?php

/**
 * Description of IFileNewControlFactory
 *
 * @author David, 2015
 * @version 1.0
 * @license MIT
 */

namespace App\WesprModule\FileModule;

interface IFileNewControlFactory {
    
    /** @return FileNewControl */
    public function create();
}
