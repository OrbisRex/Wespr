<?php

namespace App;

use Nette,
    App,
    Texy\Texy,
    Nette\Utils,
    OrbisRex\Wespr;

/**
 * Base presenter for all application presenters.
 */
abstract class BasePresenter extends Nette\Application\UI\Presenter {   
    /** @persistent @var string Language version */
    public $lang;
    
    /** @var App\Repository\GeneralRepository */
    protected $repository;
    
    /** @var Wespr\Translator */
    protected $translator;
    
    /** @var String Version of WESPR system core. */
    public $versionWespr = '1.7.2';
    
    /** @var string Allow user registration */
    public $registration = true;
    
    /** @param Wespr\Translator $translator */
    public function injectWesprTranslator(Wespr\Translator $translator) {
        
        $this->translator = $translator;
    }
    
    public function createTemplate($class = NULL) {
        $template = parent::createTemplate($class);
        
        /* Localization */
        $template->setTranslator($this->translator);
        
        /* Texy */
        $texy = new Texy();
        $texy->imageModule->root = 'data/';
        //Static headings. Always depend on marks in text.
        $texy->headingModule->balancing = TEXY_HEADING_DYNAMIC;
        
        $template->addFilter('texy', [$texy, 'process']);
        
        //Allow user registration (true = allow / false = forbbiden)
        $template->registration = $this->registration;
        
        /* Set version of WESPR */
        $template->versionWespr = $this->versionWespr;
        
        return $template;
    }
    
    protected function beforeRender() {
        parent::beforeRender();
        
    }
    
    /**
     * Translate test in flash messages.
     * @param string $message
     * @param string $type
     * @return
     */
    public function flashMessage($message, $type = "info") {
        
        $translateMessage = $this->translator->translate($message);
        return parent::flashMessage($translateMessage, $type);
    }
}