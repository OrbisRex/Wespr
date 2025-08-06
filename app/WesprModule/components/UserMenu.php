<?php

/**
 * FileMenu
 * Submenu area for User Latte templates.
 *
 * @author     David Ehrlich, 2013
 * @package    WESPR
 */

namespace App\WesprModule;

use Nette\Application\UI\Control;

class UserMenuControl extends Control {
    
    /** @var object Translator */
    private $translator;
    /** @var string Current language */
    private $lang;
    /** @var object Presenter */
    private $presenter;
    
    public function __construct($translator, $lang, $presenter){
        parent::__construct();
        $this->translator = $translator;
        $this->lang = $lang;
        $this->presenter = $presenter;
    }

    /**
     * (non-phpDoc)
     *
     * @see Nette\Application\Control#render()
     */
    public function render() {
        $this->template->setFile(__DIR__.'/UserMenu.latte');
        $this->template->setTranslator($this->translator);
        $this->template->presenter = $this->presenter;
        
        $this->template->render();
    }
    
    public function handleSignOut() {
        
        $this->presenter->getUser()->logout();
        $this->flashMessage('Odhlášení bylo úspěšné.');
        $this->presenter->redirect('Sign:in');
    }    
}