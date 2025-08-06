<?php

/**
 * ArticleMenu
 * Submenu area for Article Latte templates.
 *
 * @author     David Ehrlich, 2013
 * @package    WESPR
 */

namespace App\WesprModule\TextModule;

use Nette\Application\UI\Control;

class ArticleMenuControl extends Control {
    
    /** @var object Translator */
    private $translator;
    /** @var string Current language */
    private $lang;
    /** @var object Page repository. */
    private $pageRepository;
    /** @var object User */
    private $user;
    
    public function __construct($translator, $lang, $pageRepository, $user){
        parent::__construct();
        $this->translator = $translator;
        $this->lang = $lang;
        $this->pageRepository = $pageRepository;
        $this->user = $user;
    }

    /**
     * (non-phpDoc)
     *
     * @see Nette\Application\Control#render()
     */
    public function render() {
        if($this->user->isInRole('admin')) {
            $menu = $this->pageRepository->findAdminPages($this->lang, $this->user->id);
        } else {
            $menu = $this->pageRepository->findUserPages($this->lang, $this->user->id);
        }
        
        $this->template->setFile(__DIR__.'/ArticleMenu.latte');
        $this->template->setTranslator($this->translator);
        $this->template->menu = $menu;
        $this->template->countPages = $menu->count();
        
        $this->template->render();
    }    
}