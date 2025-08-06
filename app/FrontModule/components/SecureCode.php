<?php
/**
 * Password secure for content of WESPR.
 *
 * @author David Ehrlich, 2014
 */

namespace App\FrontModule;

use Nette,
    App,
    Nette\Application\UI\Control,
    Nette\Application\UI\Form;

class SecureCodeControl extends Control {
   /** @var OrbisRex\Translator Translator for form.*/
    private $translator;
   /** @var Nette\Databare\Statement Article data.*/
    private $article;
    private $session;
    
    public function __construct($translator, $article, $session) {
        parent::__construct();
        
        $this->translator = $translator;
        $this->article = $article;
        $this->session = $session;
    }
    
   public function render(){
        $this->template->setFile(__DIR__.'/SecureCode.latte');
        $this->template->setTranslator($this->translator);

        $this->template->render();
    }
    
    //Form for set code.
    public function createComponentSecureCodeForm($name) {
        $form = new Form($this, $name);
        
        $form->addText('code', 'Kód*')
                ->setAttribute('size', 33)
                ->addRule(Form::MIN_LENGTH, 'Kód by mělo mít alespoň %d znaků.', 3);
        $form->addSubmit('send', 'odeslat')->setAttribute('class','main-button');
        $form->onSuccess[] = array($this, 'SetCodeFormSubmitted');
    }
    
    public function setCodeFormSubmitted(Form $form) {
        Nette\Diagnostics\Debugger::barDump($this->article->id);
        if($form->values->code == $this->article->code) {
            $this->session->album = $this->article->id;

            $this->redirect('this');
        }
    }
}
