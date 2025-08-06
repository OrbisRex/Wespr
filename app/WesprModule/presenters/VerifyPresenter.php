<?php

/**
 * Verify presenter.
 *
 * @author     David Ehrlich, 2014
 * @package    wespr
 */

namespace App\WesprModule;

use Nette,
    App,
    App\WesprModule,
    Nette\Application\UI\Form;

class VerifyPresenter extends App\BasePresenter {
    /** @var WesprModule\Repository\ModifyUserRepository */
    protected $userRepository;
    
    /** @param WesprModule\Repository\ModifyUserRepository $userRepository */
    public function injectModifyUserRepository(WesprModule\Repository\ModifyUserRepository $userRepository) {
        
        $this->userRepository = $userRepository;
    }
    
    /* Activation process */
    public function actionAccount($tag) {
        $user = $this->userRepository->verifyTag($tag);
        
        //Successfull verification
        if($user) {
            $this->userRepository->updateUser('id', $user->fetch()->id, array(
                'state' => 'public',
                'tag' => null
            ));
            
            //Logout and reset User Identity
            $this->user->logout(true);
            
            $this->flashMessage('Odhlásil jsem vás a ověřil účet. Nyní je plně funkční. Užíjte si to!', 'success');
            $this->redirect('Sign:in');
        }
    }

    public function renderAccount() {
        if($this->user->isLoggedIn()) {
            $this->template->email = $this->user->storage->identity->email;
            $this->template->name = $this->user->storage->identity->name;
        } else {
            //Session start
            $verifyUser = $this->getSession('verifyUser');

            $this->template->email = $verifyUser->email;
            $this->template->name = $verifyUser->name;
        }
    }
    
    public function actionResetPassword($tag) {
        $user = $this->userRepository->verifyTag($tag);
        
        //Successfull verification
        if($user) {
            //Save to session
            $resetPassword = $this->getSession('resetPassword');
            $resetPassword->userId = $user->fetch()->id;
        } else {
            $this->flashMessage('Nepodařilo se mi ověřit váš účet. Zkuste postup znovu.', 'error');
            $this->redirect('Sign:in');
        }
    }
    
    public function renderResetPassword() {
        
    }
    
    public function createComponentResetPasswordForm() {
        $form = new Form();
        
        $form->addPassword('password', 'Nové heslo / Kontrola*')
                ->setAttribute('class', 'text-short')
                ->addRule(Form::MIN_LENGTH, 'Heslo by mělo mít alespoň %d znaků.', 6);
        $form->addPassword('verifyPassword','Kontrola hesla*')
                ->setAttribute('class', 'text-short')
                ->addRule(Form::EQUAL, 'Hesla se neshodují. Zkuste je zadat znovu.', $form['password'])
                ->addConditionOn($form['password'], ~Form::FILLED)
                ->setRequired('Pro kontrolu zadejte heslo ještě jednou.');
        $form->addHidden('mail'); //Antispam

        $form->addSubmit('save', 'Nastav')->setAttribute('class', 'main-button');
        $form->onSuccess[] = array($this, 'resetPasswordSubmitted');
        
        $form->onValidate[] = array($this, 'validateResetPasswordForm');
        return $form;        
    }
    
    public function validateResetPasswordForm($form) {
        $values = $form->getValues();
        
        //Antispam Validation
        if($values->mail) {
            $this->flashMessage('Roboty neberem. Nevyplňujte poslední neoznačené pole.', 'error');
            $this->redirect('Sign:in');
        }
            
        if($values->password != $values->verifyPassword) {
            $form->addError('Ajaj, hesla se neshodují. Zadejte jej prosím ještě jednou.');
        }
    }
    
    public function resetPasswordSubmitted(Form $form) {
        //Read session
        $resetPassword = $this->getSession('resetPassword');
        
        $password = App\WesprModule\Repository\ModifyUserRepository::calculateHash($form->values->password);
        
        $this->userRepository->updateUser('id', $resetPassword->userId, array(
            'password' => $password,
            'state' => 'public',
            'tag' => null
        ));

        $this->flashMessage('Hurá! Nastavil jsem vám nové heslo. Nyní se můžete přihlásit.', 'success');
        $this->redirect('Sign:in');        
    }
}
