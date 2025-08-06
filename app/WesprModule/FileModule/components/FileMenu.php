<?php

/**
 * FileMenu
 * Submenu area for File Latte templates.
 *
 * @author     David Ehrlich, 2013
 * @package    WESPR
 */

namespace App\WesprModule\FileModule;

use Nette\Application\UI\Control;

class FileMenuControl extends Control {
    
    /** @var object Translator */
    private $translator;
    /** @var string Current language */
    private $lang;
    /** @var object Group repository. */
    private $groupRepository;
    /** @var object User */
    private $user;
    
    public function __construct($translator, $lang, $groupRepository, $user){
        parent::__construct();
        $this->translator = $translator;
        $this->lang = $lang;
        $this->groupRepository = $groupRepository;
        $this->user = $user;
    }

    /**
     * (non-phpDoc)
     *
     * @see Nette\Application\Control#render()
     */
    public function render() {
        if($this->user->isInRole('admin')) {
            $menu = $this->groupRepository->findAdminGroups($this->lang);
            $this->template->countGenericGroups = 0;
        } else {
            $genericGroups = $this->groupRepository->findGenericGroups($this->lang);
            $menu = $this->groupRepository->findUserGroups($this->lang, $this->user->id);
            
            $this->template->genericGroups = $genericGroups;
            $this->template->countGenericGroups = $genericGroups->count();
        }
        
        $this->template->setFile(__DIR__.'/FileMenu.latte');
        $this->template->setTranslator($this->translator);
        $this->template->menu = $menu;
        $this->template->countGroups = $menu->count();
        
        $this->template->render();
    }    
}