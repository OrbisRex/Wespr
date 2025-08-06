<?php
/**
 * Frontend login in to WESPR.
 *
 * @author David Ehrlich, 2014
 */

namespace App\FrontModule;

use Nette,
    Nette\Application\UI\Control,
    Nette\Application\UI\Form;

class SignInControl extends Control {
    
    private $translator;
    
    public function __construct($translator) {
        parent::__construct();
        
        $this->translator = $translator;
    }
    
   public function render(){
        $this->template->setFile(__DIR__.'/SignIn.latte');
        $this->template->setTranslator($this->translator);
        
        $this->template->render();
    }
    
    /**
     * Sign in form component factory.
     * @return Nette\Application\UI\Form
     */
    protected function createComponentSignInForm() {
        $form = new Form();
        $form->setTranslator($this->translator);
        
        //$form->getElementPrototype()->target = '_blank';
        $form->addText('email', 'email / password')->setAttribute('placeholder', 'email')->setAttribute('type', 'email')->addRule(Form::EMAIL, 'Please, make sure that your email is correct.')->setRequired('Please, enter your registered email.');
        $form->addPassword('password', 'password')->setAttribute('placeholder', 'password')->setRequired('Enter your password.');
        $form->addCheckbox('remember', 'Remember?');

        $form->addSubmit('send', 'Sign in')->setAttribute('class', 'main-button');
        $form->onSuccess[] = array($this, 'signInFormSubmitted');
        return $form;
    }

    public function signInFormSubmitted($form) {
        try {
                $user = $this->presenter->getUser();
                $values = $form->getValues();

                if ($values->remember) {
                        $user->setExpiration('+14 days', FALSE);
                } else {
                        $user->setExpiration('+60 minutes', TRUE);
                }
                $user->login($values->email, $values->password);
                $this->presenter->redirect(':Wespr:Default:');

        } catch (Nette\Security\AuthenticationException $e) {
                $form->addError($e->getMessage());
        }
    }
}