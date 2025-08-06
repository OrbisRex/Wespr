<?php

/**
 * Sign in presenters.
 *
 * @author     David Ehrlich
 * @package    wespr
 */

namespace App\WesprModule;

use Nette,
    App,
    Nette\Security\AuthenticationException,
    Nette\Application\UI\Form;

class SignPresenter extends App\BasePresenter {
    /** @var App\WesprModule\Repository\ModifyUserRepository */
    protected $userRepository;
    
    /** @param App\WesprModule\Repository\ModifyUserRepository $userRepository */
    public function injectUserRepository(App\WesprModule\Repository\ModifyUserRepository $userRepository) {
        
        $this->userRepository = $userRepository;
    }
    
    /**
     * Sign in form component factory.
     * @return Nette\Application\UI\Form
     */
    protected function createComponentSignInForm() {
        $form = new Form();
        $form->setTranslator($this->translator);                
        $form->addText('email', 'Email')->setAttribute('type', 'email')->addRule(Form::EMAIL, 'User name is your email.')->setRequired('Please fill in your registered email.');
        $form->addPassword('password', 'Password')->setRequired('Please enter your password.');
        $form->addCheckbox('remember', 'Remember me?');

        $form->addSubmit('send', 'Sign in')->setAttribute('class', 'main-button');
        $form->onSuccess[] = array($this, 'signInFormSubmitted');
        return $form;
    }

    public function signInFormSubmitted($form) {
        try {
            $user = $this->getUser();
            $values = $form->getValues();

            if ($values->remember) {
                    $user->setExpiration('+14 days', FALSE);
            } else {
                    $user->setExpiration('+10 minutes', TRUE);
            }
            
            $user->login($values->email, $values->password);
            $this->redirect('Default:');
            
        } catch (AuthenticationException $e) {
            $form->addError($e->getMessage());
        }
    }
    
    
    /* Sign in reminder */
    public function renderReminder() {
        
    }
    
    public function createComponentSignReminderForm() {
        $form = new Form();
        $form->setTranslator($this->translator);                
        $form->addText('email', 'Email')->setAttribute('type', 'email')->addRule(Form::EMAIL, 'Username is your email.')->setRequired('Please fill in your registered email.');
        $form->addHidden('mail'); //Antispam

        $form->addSubmit('send', 'Send')->setAttribute('class', 'main-button');
        $form->onSuccess[] = array($this, 'signReminderFormSubmitted');
        
        $form->onValidate[] = array($this, 'validateSignReminderForm');
        return $form;        
    }
    
    public function validateSignReminderForm($form) {
        $values = $form->getValues();
        
        //Antispam Validation
        if($values->mail) {
            $this->flashMessage('Go away a robot! Please do NOT fill in last two fields.', 'error');
            $this->redirect('Sign:in');
        }
            
        $emails = $this->userRepository->findOneUserByEmail($values->email);
        if(count($emails) == 0) {
            $form->addError('Oups! I can not find this email. Please use another one.');
        }
    }
    
    public function signReminderFormSubmitted(Form $form) {
        //Save to session
        $signReminder = $this->getSession('signReminder');
        $signReminder->email = $form->values->email;
        
        $this->redirect('Mail:reminder');
    }
    
    protected function verifyUserState() {
        if($this->user->storage->identity->state === 'public') {
            
        } else if($this->user->storage->identity->state === 'locked') {
            
        } else if($this->user->storage->identity->state === 'unprove') {
            
        } else {
            
        }
    }
}
