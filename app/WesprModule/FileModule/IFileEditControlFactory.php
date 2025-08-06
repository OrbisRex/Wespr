<?php

/**
 * Description of IFileEditControlFactory
 *
 * @author David, 2015
 * @version 1.0
 * @license MIT
 */

namespace App\WesprModule\FileModule;

interface IFileEditControlFactory {
    
    /** @return FileEditControl */
    public function create();
}
