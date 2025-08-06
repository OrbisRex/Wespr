<?php

/**
 * Description of IFileImportControlFactory
 *
 * @author David, 2017
 * @version 1.0
 * @license MIT
 */

namespace App\WesprModule\FileModule;

interface IFileImportControlFactory {
    
    /** @return FileImportControl */
    public function create();
}
