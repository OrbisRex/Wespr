<?php

/**
 * Description of IFileSelectControlFactory
 *
 * @author David, 2015
 * @version 1.0
 * @license MIT
 */

namespace App\WesprModule\FileModule;

interface IFileSelectControlFactory {
    
    /** @return FileSelectControl */
    public function create();
}
