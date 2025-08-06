<?php
/**
 * Description of Menu component
 *
 * @author David Ehrlich
 */

namespace OnePageModule;

use \Nette\Application\UI\Control,
    \Nette\Database\Table\Selection;

class MenuControl extends Control {

    private $menuItem;
    
    public function __construct(Selection $menuItem){
        parent::__construct();
        $this->menuItem = $menuItem;
    }
    
    public function render(){
        $this->template->setFile(__DIR__.'/Menu.latte');
        $this->template->menu = $this->menuItem;
        $this->template->render();
    }
    
}

?>