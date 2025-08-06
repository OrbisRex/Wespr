<?php
/**
 * User management for e-album
 *
 * @author David Ehrlich, 2014
 */

namespace App\WesprModule;

use Nette,
    App,
    App\WesprModule,
    Nette\Application\UI\Form;

class EalbumPresenter extends App\WesprModule\RegistrationPresenter {
    
    /* User ID */
    private $id;
    /** @var WesprModule\Repository\EalbumRepository $ealbumRepository */
    private $ealbumRepository;
    
    /** @param WesprModule\Repository\EalbumRepository $ealbumRepository */
    public function injectEalbumRepository(WesprModule\Repository\EalbumRepository $ealbumRepository) {
        
        $this->ealbumRepository = $ealbumRepository;
    }
    
    public function actionRegistration() {
        if(!$this->registration) {
            $this->flashMessage('Sorry, but registration of new users is blocked.', 'error');
            $this->redirect('Sign:in');
        }
        
        if($this->user->isLoggedIn()) {
            $this->redirect('Default:');
        }
    }
    
    /*
     * New user and user pages.
     */
   public function registrationFormSubmitted(Form $form) {
        
        $password = App\WesprModule\Repository\ModifyUserRepository::calculateHash($form->values->password);
        $tag =  App\WesprModule\Repository\ModifyUserRepository::calculateTag($form->values->email);
        
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
        
        //Create default structure and content
        /** @todo Move to config */
        $sourcesId = array(22);
        foreach($sourcesId as $sourceId) {
            $this->createUserPage($form->values->email, $sourceId);
        }
        $this->createUserGroup($form->values->email);
        
        //Auto log in
        $this->user->login($form->values->email, $form->values->password);
        
        //Send validation email and registration info
        $this->redirect('Mail:activation');
    }
    
    private function createUserPage($email, $sourceId) {
        $order = $this->pageRepository->maxPage('order');
        if($order === null) {
            $order = 1;
        } else {
            $order += 1;
        }
        
        $user = $this->userRepository->findOneUserByEmail($email);
        $userId = $user->fetch()->id;
        
        $sourceData = $this->sourceRepository->findOneSource($sourceId);
        $source = $sourceData->fetch();
        
        //Create nettename for menu link.
        $nettename = $source->nettename;
        //Page name and alias.
        $name = $source->name_cs;
        
        $this->ealbumRepository->insertPage(array(
           'name_en' => $name,
           'name_'.$this->lang => $name,
           'alias_en' => $name,
           'alias_'.$this->lang => $name,
           'nettename' => $nettename,
           'level' => 0,
           'parent' => 4,
           'order' => $order,
           'state' => 'public',
           'delegating' => 'none',
           'anchor' => null,
           'content_en' => 'Default user blog page.',
           'content_'.$this->lang => 'Default user blog page.',
           'inserttime' => new \DateTime(),
           'layout_id' => $source->layout_id,
           'source_id' => $sourceId,
           'user_id' => $userId
        ));
    }
    
    private function createUserGroup($email) {
        $order = $this->groupRepository->maxGroup('order');
        if($order === null) {
            $order = 1;
        } else {
            $order += 1;
        }
        
        $user = $this->userRepository->findOneUserByEmail($email);
        $userId = $user->fetch()->id;
        
        $this->ealbumRepository->insertGroup(array(
           'name' => urlencode(''),
           'alias_en' => 'Default',
           'alias_'.$this->lang => 'Default',
           'type' => 'photo',
           'order' => $order,
           'state' => 'nonpublic',
           'text_en' => 'Default group for files.',
           'text_'.$this->lang => 'Default group for files.',
           'inserttime' => new \DateTime(),
           'user_id' => $userId
        ));
    }
}
