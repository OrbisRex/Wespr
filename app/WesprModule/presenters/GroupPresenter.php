<?php

/**
 * Group management
 * @version 1.2
 * @author David Ehrlich, 2014
 */

namespace App\WesprModule;

use Nette,
    App,
    Nette\Application\UI\Form;

class GroupPresenter extends App\SecuredPresenter {
    /** @var Nette\Database\Statement Row form table groups */
    private $groups;
    /** @var Nette\Database\Statement Data form table group */
    private $group;
    /** @var integer ID Group */
    private $id;
    /** @var string Set secure passcode.*/
    private $passcode;
    
    /**
     * Write sub-menu area on the group.
     * @return \App\\SettingMenuControl
     */
    public function createComponentSettingMenu() {

        return new SettingMenuControl($this->translator, $this->lang, $this);
    }
    
    private function dataMiner($id) {
        if($this->user->isInRole('admin')) {
            $this->groups = $this->groupRepository->findGroups($this->lang);
        } else {
            $this->groups = $this->groupRepository->findUserGroups($this->lang, $this->user->id);
        }
        
        //Edit group
        if(isset($id)) {
            $this->id = $id;
            $this->group = $this->groupRepository->findOneGroup($id)->fetch();
        }        
    }

    public function actionDefault($id) {        
        $this->dataMiner($id);
    }
    
    public function renderDefault($id) {
        
        $this->dataMiner($id);
        
        $this->template->id = $id;
        $this->template->groups = $this->groups;
        $this->template->countGroups = count($this->groups);
        //Solution for snippet
        $this->template->form = $this->template->_form = $this['groupList'];
    }
    
    protected function createComponentGroupList($name) {
        $form = new Form($this, $name);
        $form->setTranslator($this->translator);        

        if (!$this->groups) {
            throw new \Nette\Application\BadRequestException;
        }

        $select = $form->addContainer('select');
        $state = $form->addContainer('state');
        foreach ($this->groups as $item) {
            $select->addCheckbox($item->id)
                    ->setAttribute('onchange', 'return elementEnable("data-wespr-check", "data-slave")')
                    ->setAttribute('data-wespr-check', 'check');

            $state->addHidden($item->id)->setValue($item->state);
        }

        $form->addSubmit('delete', 'X')
                ->setAttribute('title', 'Pozor, všechny stránky a obsah získá stav zneveřejnit. Skutečně smazat vybrané položky?')
                ->setAttribute('data-slave', 'background-color: rgb(200,200,200); color: rgb(230,230,230); cursor: inherit; border-color: rgb(180,180,180);')
                ->setAttribute('style', 'background-color: rgb(200,200,200); color: rgb(230,230,230); cursor: inherit; border-color: rgb(180,180,180);')
                ->setAttribute('onclick', 'return warning(this)')
                ->setDisabled()
                ->onClick[] = array($this, 'groupListDeleteSubmitted');
        
        if($this->user->storage->identity->state == 'public') {
            $form->addSubmit('publish', '!')
                    ->setAttribute('title','Zveřejnit / nezveřejnit vybrané položky?')
                    ->setAttribute('data-slave', 'background-color: rgb(200,200,200); color: rgb(230,230,230); cursor: inherit; border-color: rgb(180,180,180);')
                    ->setAttribute('style', 'background-color: rgb(200,200,200);  color: rgb(230,230,230); cursor: inherit; border-color: rgb(180,180,180);')
                    ->setAttribute('onclick', 'return warning(this)')
                    ->setDisabled()
                    ->onClick[] = array($this, 'groupListPublishSubmitted');
        } else {
            $form->addSubmit('publish', '!')
                    ->setAttribute('title','Není dostupné. Ověřte svůj účet.')
                    ->setAttribute('style', 'background-color: rgb(200,200,200);  color: rgb(230,230,230); cursor: inherit; border-color: rgb(180,180,180);')
                    ->setDisabled();
        }
        
        $form->addButton('selectAll', 'all')
                ->setAttribute('onclick', 'return checkAll("data-slave", "data-wespr-check")');
    }
    
    public function groupListDeleteSubmitted(Nette\Forms\Controls\SubmitButton $button) {
        $values = $button->form->getValues(true);
        $keys = array_keys(array_filter($values['select']));
        
        foreach ($keys as $id) {
            $this->groupRepository->updateGroup('id', $id, array('state' => null));
            
            //Update state in connected content.
            $this->fileGroupRepository->updateFileGroupByGroupId($id, array('state' => null));
        }

        $this->flashMessage('I have moved the items into the bin.', 'success');
        if($this->isAjax()) {
            $button->form->setValues(array(), true);
            $this->redrawControl('table');
        } else {
            $this->redirect('this');
        }
    }
   
    public function groupListPublishSubmitted(Nette\Forms\Controls\SubmitButton $button) {      
        /** @var integer cyklus foreach*/
        $i=0;

        $values = $button->form->getValues(true);
        $keys = array_keys(array_filter($values['select']));

        foreach ($keys as $id) {
            if($values['state'][$keys[$i++]] == 'nonpublic') {$state = 'public';} else {$state = 'nonpublic';}
            
            $this->groupRepository->updateGroup('id', $id, array('state' => $state));
            
            //Update state in connected content.
            $this->fileGroupRepository->updateFileGroupByGroupId($id, array('state' => $state));
        }
        
        $this->flashMessage('I have changed state of groups.', 'success');
        
        if($this->isAjax()) {
            $button->form->setValues(array(), true);
            $this->redrawControl('table');
        } else {
            $this->redirect('this');
        }
    }
    
    /** 
     * Component for set passcode for secure item.
     */
    /** @todo Fix text changes in form field. */
    public function handleSecure() {
        $list = $this->request->post;
        
        if(($list['state'] == 'nonpublic') || ($list['state'] == 'public')) {
            $this->passcode = null;
        } else {
            $id = $this->groupRepository->maxGroup('id') + 1;
            $this->passcode = $id.'-'.rand(100, getrandmax());
        }
        
        if($this->isAjax()) {
            $this->redrawControl('passcode');
        } else {
            $this->redirect('this');
        }
    }
    
    /*
     * New group or edit group.
     */
    protected function createComponentGroupForm($name) {
        /** @todo Move to DB / config */
        if($this->user->storage->identity->state == 'public') {
            $selectState = array('nonpublic' => 'Nonpublic - Not visible', 'locked' => 'Locked - Secured by code', 'public' => 'Public - Visible');
        } else {
            $selectState = array('nonpublic' => 'Nonpublic - Not visible');
        }
        
        $selectType = array('photo' => 'photo');
        
        //Adjust value for passcode
        if(!empty($this->group)) {
            if(empty($this->group["passcode"]) && $this->passcode !== null) {
                $id = $this->groupRepository->maxGroup('id') + 1;
                $this->passcode = $id.'-'.rand(100, getrandmax());
            } else if(!empty($this->group["passcode"])) {
                $this->passcode = $this->group["passcode"];
            } else {
                $this->passcode = $this->passcode;
            }
        }
        
        $form = new Form($this, $name);
        $form->setTranslator($this->translator);        
        $form->addText('alias','Name* & Type*')->addRule(Form::FILLED, 'Please enter group name.')->setAttribute('class', 'text-short');
        $form->addText('text','Description');
        $form->addSelect('type', 'Type*', $selectType)->setAttribute('class','select_short');
        $form->addSelect('state', 'State & Passcode', $selectState)->setAttribute('class', 'select_short');
        $form->addText('passcode', 'Passcode')
                ->setDefaultValue($this->passcode)
                ->setAttribute('class', 'text-short ajax')
                ->addCondition(Form::BLANK)
                ->addConditionOn($form['state'], Form::EQUAL, 'locked')
                ->setRequired('If you have chosen state Locked, passcode must be present.');
        $form->addHidden('oldPasscode');
        $form->addHidden('id');

        $form->addSubmit('save', 'Save')->setAttribute('class','main-button');
        $form->onSuccess[] = array($this, 'groupFormSubmitted');

        if(!empty($this->group)) {
            $editValues = array (
            'id' => $this->group["id"],
            'name' => $this->group["name"],
            'alias' => $this->group["alias_".$this->lang],
            'type' => $this->group["type"],
            'state' => $this->group["state"],
            'oldPasscode' => $this->group['passcode'],
            'text' => $this->group["text_".$this->lang],
            'inserttime' => new \DateTime()
            );
            $form->setDefaults($editValues);
            
            $form->addSubmit('edit', 'Save changes')->setAttribute('title','Save chnages.')->setAttribute('class','main-button')->onClick[] = array($this, 'groupEditSubmitted');

            $presenter = $this;
            $form->addSubmit('cancel', 'Cancel')->setValidationScope(FALSE)->setAttribute('title','Cancel changes in group properties.')->setAttribute('class','add-button')
                             ->onClick[] = function () use ($presenter) {
                                 $presenter->flashMessage('I have canceled changes.', 'success');
                                 $presenter->redirect('default');
                             };
        }
   }
   
    public function groupFormSubmitted(Form $form) {
        if($this->user->isInRole('admin')) {
            $userId = null;
        } else {
            $userId = $this->user->id;
        }
        
        //Adjusted values for passcode
        if(!empty($form->values->passcode)) {
            $passcode = $form->values->passcode;
        } else if($form->values->state != 'locked') {
            $passcode = null;
        }
        
        $this->groupRepository->insertGroup(array(
           'name' => urlencode($form->values->alias),
           'alias_'.$this->lang => $form->values->alias,
           'type' => $form->values->type,
           'order' => null,
           'state' => $form->values->state,
           'passcode' => $passcode,
           'text_'.$this->lang => $form->values->text,
           'inserttime' => new \DateTime(),
           'user_id' => $userId
        ));
        
        if(!empty($form->values->passcode)) {
            $this->flashMessage('I have created new group and set passcode '.$passcode.'.', 'success');
        } else if($form->values->state != 'locked') {
            $this->flashMessage('I have created new group.', 'success');
        }
        
        $this->redirect('default');
    }

    public function groupEditSubmitted(Nette\Forms\Controls\SubmitButton $button) {
        $values = $button->form->getValues();
        
        //Adjusted value for passcode
        if((empty($values['passcode']) && $values['state'] != 'locked') || 
          (!empty($values['passcode']) && $values['state'] == 'public')) {
            $passcode = null;
            $this->flashMessage('I have changed the group and Passcode has been removed.', 'success');
        } else if(empty($values['passcode']) && $values['state'] == 'locked') {
            $passcode = $values['oldPasscode'];
            $this->flashMessage('I have changed the group. Passcode is same.', 'success');
        } else {
            $passcode = $values['passcode'];
            $this->flashMessage('I have changed the group. Passcode is '.$passcode.'.', 'success');
        }
        
        $this->groupRepository->updateGroup('id', $values['id'], array(
           'name' => urlencode($values['alias']),
           'alias_'.$this->lang => $values['alias'],
           'type' => $values['type'],
           'text_'.$this->lang => $values['text'],
           'order' => null,
           'state' => $values['state'],
           'passcode' => $passcode,
           'updatetime' => new \DateTime()
        ));
        
        //Update state in connected content.
        $this->fileGroupRepository->updateFileGroupByGroupId($values['id'], array('state' => $values['state']));

        $this->redirect('default');
    } 
    
}
