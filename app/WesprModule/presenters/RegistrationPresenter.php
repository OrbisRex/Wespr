<?php

/**
 * Registration presenters.
 *
 * @author     David Ehrlich, 2014
 * @package    wespr
 */

namespace App\WesprModule;

use Nette,
    App,
    Nette\Application\UI\Form;

class RegistrationPresenter extends App\PublicPresenter {
    private $verifyUser;
            
    public function actionDefault() {
        if(!$this->registration) {
            $this->flashMessage('Sorry, but registration of new users is blocked.', 'error');
            $this->redirect('Sign:in');
        }
        
        if($this->user->isLoggedIn()) {
            $this->redirect('Default:');
        }
    }
    
    public function renderDefault() {
        
    }

    /*
     * New user.
     */
    protected function createComponentRegistrationForm($name) {
        $form = new Form($this, $name);
        $form->addText('name','Full name*')->setAttribute('size', 73)->addRule(Form::FILLED, 'Je nutné zadat celé jméno.');
        $form->addText('nickname', 'Nickname*')->addRule(Form::FILLED, 'Přezdívku je třeba zadat.')->setAttribute('size', 73);
        $form->addText('email','Email*')->setAttribute('size', 73)->addRule(Form::EMAIL, 'Je nutné zadat správně email.');
        $form->addPassword('password', 'Password*')
                ->setAttribute('class', 'text-short')
                ->addRule(Form::MIN_LENGTH, 'Password should have at least %d characters.', 6);
        $form->addPassword('verifyPassword','Verify password*')
                ->setAttribute('class', 'text-short')
                ->addConditionOn($form['password'], Form::FILLED)
                ->setRequired('Please type the password again in for verification.')
                ->addRule(Form::EQUAL, 'Passwords are NOT identical. Please try it again.', $form['password']);
        $form->addHidden('mail'); //Antispam

        $form->addSubmit('save', 'Sign up')->setAttribute('class','main-button');
        $form->onSuccess[] = array($this, 'registrationFormSubmitted');
        
        $form->onValidate[] = array($this, 'validateRegistrationForm');
        return $form;
    }
    
    public function validateRegistrationForm($form) {
        $values = $form->getValues();
        
        //Antispam Validation
        if($values->mail) {
            $this->flashMessage('We do NOT like robots. Don NOT fill the last form field.', 'error');
            $this->redirect('Wespr:Sign:in');
        }
            
        if($values->password != $values->verifyPassword) {
            $form->addError('Oups! The passwords are NOT identical. Please try it again.');
        }
        
        $emails = $this->userRepository->findOneUserByEmail($values->email);
        if(count($emails) != 0) {
            $form->addError('Oups! I am sorry but the user name is NOT available anymore. Try another one.');
        }
    }

   public function registrationFormSubmitted(Form $form) {
        
        $password = \App\WesprModule\Repository\ModifyUserRepository::calculateHash($form->values->password);
        $tag = $this->userRepository->calculateTag($form->values->email);
        
        $this->userRepository->insertUser(array(
           'name' => $form->values->name,
           'nickname' => $form->values->nickname,
           'email' => $form->values->email,
           'password' => $password,
           'role' => 'user',
           'state' => 'unprove',
           'tag' => $tag,
           'inserttime' => new \DateTime()
        ));
        
        //Auto log in
        $this->user->login($form->values->email, $form->values->password);
        
        //Save to session for validation
        $verifyUser = $this->getSession('verifyUser');
        $verifyUser->email = $form->values->email;
        $verifyUser->name = $form->values->name;
        $this->verifyUser = $verifyUser;
        
        //Send validation email and registration info
        $this->redirect('Mail:activation');
    }
}
