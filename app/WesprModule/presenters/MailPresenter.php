<?php

/**
 * Mail presenters.
 *
 * @author     David Ehrlich, 2014
 * @package    wespr
 */

namespace App\WesprModule;

use Nette,
    App;

class MailPresenter extends App\BasePresenter {
    /** @var App\WesprModule\Repository\MailManager */
    protected $mailManager;
    /** @var App\WesprModule\Repository\UserRepository */
    protected $userRepository;
    
    /** @var string Tag for emailing */
    protected $tag;
    
    /** @param App\WesprModule\MailManager $mailManager */
    public function injectMailManager (App\WesprModule\MailManager $mailManager) {
        
        $this->mailManager = $mailManager;
    }
    
    /** @param App\WesprModule\Repository\ModifyUserRepository $userRepository */
    public function injectUserRepository(App\WesprModule\Repository\ModifyUserRepository $userRepository) {
        
        $this->userRepository = $userRepository;
    }
    
    /* Activation process */
    public function actionActivation() {
        $template = $this->createTemplate();
        $template->setFile(__DIR__.'/../templates/Mail/verify.latte');
        
        //Set tag for user
        $this->setTag($this->user->storage->identity->email, 'email');
        
        $template->email = $this->user->storage->identity->email;
        $template->name = $this->user->storage->identity->name;
        $template->tag = $this->tag;
        
        $this->mailManager->setMessage($this->user->storage->identity->email, 'Ověření účtu', $template);
        $this->mailManager->setMailer('noreplay@OrbisRexsoft.cz', 'WESPR');
        $this->mailManager->sendMail();
        
        $this->flashMessage('Odeslal jsem vám ověřovací email. Kliknutím na odkaz v emailu účet ověříte.', 'success');
        $this->redirect('Default:');
    }
    
    /* Reminde password */
    public function actionReminder() {
        $template = $this->createTemplate();
        $template->setFile(__DIR__.'/../templates/Mail/reminder.latte');
        
        //Read session
        $signReminder = $this->getSession('signReminder');
        
        //Set tag for user
        $this->setTag($signReminder->email, 'email');
        
        $template->email = $signReminder->email;
        $template->tag = $this->tag;
        
        $this->mailManager->setMessage($signReminder->email, 'Reset hesla', $template);
        $this->mailManager->setMailer('noreplay@OrbisRexsoft.cz', 'WESPR');
        $this->mailManager->sendMail();
        
        $this->flashMessage('Odeslal jsem vám resetovací email. Kliknutím na odkaz v emailu resetujete heslo.', 'success');
        $this->redirect('Sign:in');
    }
    
    protected function setTag($input, $column = 'id') {
        //Generate tag
        $this->tag = hash('sha256', $input);
        
        //Set tag for user
        $this->userRepository->updateUser($column, $input, array(
            'tag' => $this->tag
        ));
    }
}
