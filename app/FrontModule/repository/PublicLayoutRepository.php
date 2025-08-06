<?php

/**
 * Operation over DB table layput
 *
 * @author David Ehrlich, 2013
 * @version 1.1
 * @license MIT
 */

namespace App\FrontModule\Repository;

use Nette,
    App,
    App\WesprModule\Repository;

class PublicLayoutRepository extends Repository\LayoutRepository {
    
    public function checkStatusLayouts() {
        return $this->findWhere('state ?', array('locked', 'link', 'public'));
    }
}