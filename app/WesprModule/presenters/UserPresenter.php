<?php
/**
 * User management
 *
 * @author David Ehrlich, 2014
 */

namespace App\WesprModule;

use Nette,
    App,
    App\WesprModule\Repository,
    Nette\Application\UI\Form;

class UserPresenter extends App\SecuredPresenter {
    
    /** @var Nette\Database\Statement Selected pages from table user. */
    private $userEdit;
    /** @var Nette\Database\Statement User $authorities. */
    private $authorities;
    
    /* User ID */
    private $id;
    
    /**
     * Write sub-menu area on the page.
     * @return \App\WesprModule\UserMenuControl
     */
    public function createComponentSettingMenu() {

        return new SettingMenuControl($this->translator, $this->lang, $this);
    }
    
    public function actionDefault() {
        //Edit item
        $this->id = $this->user->getId();
        $this->userEdit = $this->userRepository->findOneUser($this->user->getId())->fetch();
        $this->authorities = $this->userAuthorityRepository->findUserAuthority($this->lang, $this->user->id);

    }
    
    public function renderDefault($id) {

        $this->template->id = $this->user->getId();
        $this->template->authorities = $this->authorities;
    }

    /*
     * New page or edit page.
     */
    protected function createComponentUserForm($name) {
        $form = new Form($this, $name);
        $form->setTranslator($this->translator);                
        $form->addText('name','Full name*')->addRule(Form::FILLED, 'Please enter name and surname.');
        $form->addText('nickname', 'Nick & Old password')->setAttribute('class', 'text-short');
        $form->addText('email','Email*')->addRule(Form::EMAIL, 'Please enter valid email.');
        $form->addPassword('oldPassword','Old password')
                ->setAttribute('class', 'text-short')
                ->addCondition(Form::FILLED)
                ->addRule('App\WesprModule\Repository\ModifyUserRepository::verifyPassword', 'The old password does not match saved password.', $this->userEdit["password"]);
        $form->addPassword('newPassword', 'New password & Confirm password*')
                ->setAttribute('class', 'text-short')
                ->addConditionOn($form['oldPassword'], Form::FILLED)
                ->addRule(Form::MIN_LENGTH, 'Password should be at least %d characters long.', 6);
        $form->addPassword('verifyPassword','Confirm password*')
                ->setAttribute('class', 'text-short')
                ->addConditionOn($form['oldPassword'], Form::FILLED)
                ->addConditionOn($form['newPassword'], ~Form::FILLED)
                ->setRequired('Please enter the password again for confirmation.')
                ->addRule(Form::EQUAL, 'New password and Confirmation password did not match. Please try it again.', $form['newPassword']);
        $form->addHidden('id');
        $form->addHidden('password'); //Check password
        $form->addHidden('oldEmail'); //Check email
        $form->addHidden('role');

        $form->addSubmit('save', 'Save')->setAttribute('class','main-button');
        $form->onSuccess[] = array($this, 'userFormSubmitted');
        
        $form->onValidate[] = array($this, 'validateUserForm');
        
        if(!empty($this->userEdit)) {
            $editValues = array (
            'id' => $this->userEdit["id"],
            'name' => $this->userEdit["name"],
            'nickname' => $this->userEdit["nickname"],
            'email' => $this->userEdit["email"],
            'oldEmail' => $this->userEdit["email"],
            'password' => $this->userEdit["password"],
            'role' => $this->userEdit["role"],
            'inserttime' => new \DateTime()
            );
            $form->setDefaults($editValues);
            
            $form->addSubmit('edit', 'Save changes')->setAttribute('title','Save changes in profile.')->setAttribute('class','main-button')->onClick[] = array($this, 'userEditSubmitted');

            $presenter = $this;
            $form->addSubmit('cancel', 'Cancel')->setValidationScope(FALSE)->setAttribute('title','Cancel adjustment of the profile.')->setAttribute('class','add-button')
                             ->onClick[] = function () use ($presenter) {
                                 $presenter->flashMessage('I have canceld adjustemnt of the profile', 'success');
                                 $presenter->redirect('default');
                             };
        }
   }
   
    public function validateUserForm($form) {
        $values = $form->getValues();

        if($values->newPassword != $values->verifyPassword) {
            $form->addError('Oups! New password and Confirm password did not match. Please, try it again.');
        }
        
        if($values->email != $values->oldEmail) {
            $emails = $this->userRepository->findOneUserByEmail($values->email);
            if(count($emails) != 0) {
                $form->addError('Oups! This account is already used. Please choose different name.');
            }
        }
    }
   
    public function userEditSubmitted(Nette\Forms\Controls\SubmitButton $button) {
        $values = $button->form->getValues();
        
        $oldPassword = App\WesprModule\Repository\ModifyUserRepository::calculateHash($values['oldPassword']);
        
        if(!empty($values['newPassword']) && ($oldPassword === $values['password'])) {
            $newPassword = App\WesprModule\Repository\ModifyUserRepository::calculateHash($values['newPassword']);
        } else {
            $newPassword = $values['password'];
        }
        
        if(!empty($values['nickname'])) {
            $nickname = $values['nickname'];
        } else {
            $nickname = null;
        }
        
        $tag = $this->userRepository->calculateTag($values['email']);
        
        $this->userRepository->updateUser('id', $values['id'], array(
           'name' => $values['name'],
           'nickname' => $nickname,
           'email' => $values['email'],
           'password' => $newPassword,
           'role' => $values['role'],
           'tag' => $tag,
           'updatetime' => new \DateTime()
        ));
        
        $this->flashMessage('I have changed settings of your profile.', 'success');
        $this->redirect('default');
    } 
}