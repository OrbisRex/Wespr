<?php

/**
 * Description of Submenu
 * Submenu area for all Admin pages.
 *
 * @author David Ehrlich
 */

namespace App\WesprModule;

use Nette\Application\UI\Control;

class SubmenuControl extends Control {
    
    /** @var string Name of presenter for chose menu. */
    private $namePresenter;
    
    public function __construct($namePresenter){
        parent::__construct();
        $this->namePresenter = $namePresenter;
    }

    /**
     * (non-phpDoc)
     *
     * @see Nette\Application\Control#render()
     */
    public function render() {
        $this->template->setFile(__DIR__.'/Submenu.latte');
        $this->template->menuName = $this->namePresenter->name;
        //\Nette\Diagnostics\Debugger::barDump($this->namePresenter->name);
        $this->template->render();
        
    }
    
    public function handleNew() {
        $this->redirect('File:New');
    }
}