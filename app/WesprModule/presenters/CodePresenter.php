<?php

/**
 * Code Editor & Management
 *
 * @author David Ehrlich, 2016
 * @version 1.0
 * @license http://URL MIT
 */

namespace App\WesprModule;

use Nette,
    App,
    Nette\Application\UI\Form;

class CodePresenter extends App\SecuredPresenter {
    /** @var Nette\Database\Statement Row form table codes */
    private $codes;
    /** @var Nette\Database\Statement Data form table code */
    private $code;
    /** @var integer ID Group */
    private $id;
    
    /**
     * Write sub-menu area on the code.
     * @return \App\\SettingMenuControl
     */
    public function createComponentSettingMenu() {

        return new SettingMenuControl($this->translator, $this->lang, $this);
    }
    
    public function actionDefault($id) {
        if($this->user->isInRole('admin')) {
            $this->codes = $this->codeRepository->findAdminCodes($this->lang, $this->user->id);
        } else {
            $this->codes = $this->codeRepository->findUserCodes($this->lang, $this->user->id);
        }
        
        //Edit item
        if(isset($id)) {
            $this->id = $id;
            $this->code = $this->codeRepository->findOneCode($id)->fetch();
        }
    }
    
    public function renderDefault($id) {
        if($this->user->isInRole('admin')) {
            $this->codes = $this->codeRepository->findAdminCodes($this->lang, $this->user->id);
        } else {
            $this->codes = $this->codeRepository->findUserCodes($this->lang, $this->user->id);
        }
        
        //Edit item
        if(isset($id)) {
            $this->id = $id;
            $this->code = $this->codeRepository->findOneCode($id)->fetch();
        }
        
        $this->template->id = $id;
        $this->template->codes = $this->codes;
        $this->template->countCodes = count($this->codes);
        //Solution for snippet
        $this->template->form = $this->template->_form = $this['codeList'];
    }
    
    protected function createComponentCodeList($name) {
        $form = new Form($this, $name);

        if (!$this->codes) {
            throw new \Nette\Application\BadRequestException;
        }

        $select = $form->addContainer('select');
        $state = $form->addContainer('state');
        foreach ($this->codes as $item) {
            $select->addCheckbox($item->id)
                    ->setAttribute('onchange', 'return elementEnable("data-wespr-check", "data-slave")')
                    ->setAttribute('data-wespr-check', 'check');

            $state->addHidden($item->id)->setValue($item->state);
        }

        $form->addSubmit('delete', 'X')
                ->setAttribute('title', 'WARNING! All dependent content will gain a unpublish state. Are you sure you want delete selected items?')
                ->setAttribute('data-slave', 'background-color: rgb(200,200,200); color: rgb(230,230,230); cursor: inherit; border-color: rgb(180,180,180);')
                ->setAttribute('style', 'background-color: rgb(200,200,200); color: rgb(230,230,230); cursor: inherit; border-color: rgb(180,180,180);')
                ->setAttribute('onclick', 'return warning(this)')
                ->setDisabled()
                ->onClick[] = array($this, 'codeListDeleteSubmitted');
        
        if($this->user->storage->identity->state == 'public') {
            $form->addSubmit('publish', '!')
                    ->setAttribute('title','WARNING! All dependent content will gain a publish/unpublish state. Publish / unpublish selected items?')
                    ->setAttribute('data-slave', 'background-color: rgb(200,200,200); color: rgb(230,230,230); cursor: inherit; border-color: rgb(180,180,180);')
                    ->setAttribute('style', 'background-color: rgb(200,200,200);  color: rgb(230,230,230); cursor: inherit; border-color: rgb(180,180,180);')
                    ->setAttribute('onclick', 'return warning(this)')
                    ->setDisabled()
                    ->onClick[] = array($this, 'codeListPublishSubmitted');
        } else {
            $form->addSubmit('publish', '!')
                    ->setAttribute('title','Sorry no access to this function. Please verify your account.')
                    ->setAttribute('style', 'background-color: rgb(200,200,200);  color: rgb(230,230,230); cursor: inherit; border-color: rgb(180,180,180);')
                    ->setDisabled();
        }
        
        $form->addButton('selectAll', 'all')
                ->setAttribute('onclick', 'return checkAll("data-slave", "data-wespr-check")');
    }
    
    public function codeListDeleteSubmitted(Nette\Forms\Controls\SubmitButton $button) {
        $values = $button->form->getValues(true);
        $keys = array_keys(array_filter($values['select']));
        
        foreach ($keys as $id) {
            $this->codeRepository->updateCode('id', $id, array('state' => null));
            
            //Update state in connected content.
            $this->sourceRepository->updateSourceByCodeId($id, array('state' => null));
        }

        $this->flashMessage('Položky jsem umístil do koše.', 'success');
        if($this->isAjax()) {
            $button->form->setValues(array(), true);
            $this->redrawControl('table');
        } else {
            $this->redirect('this');
        }
    }
    
    public function codeListPublishSubmitted(Nette\Forms\Controls\SubmitButton $button) {      
        /** @var integer cyklus foreach*/
        $i=0;

        $values = $button->form->getValues(true);
        $keys = array_keys(array_filter($values['select']));

        foreach ($keys as $id) {
            if($values['state'][$keys[$i++]] == 'nonpublic') {$state = 'public';} else {$state = 'nonpublic';}
            
            $this->codeRepository->updateCode('id', $id, array('state' => $state));
            
            /** todo */
            //Update state in connected content.
            //$this->sourceRepository->updateSource('id', $source_id, array('state' => $state));
        }
        
        $this->flashMessage('I have changed state of code.', 'success');
        if($this->isAjax()) {
            $button->form->setValues(array(), true);
            $this->redrawControl('table');
        } else {
            $this->redirect('this');
        }
    }
    
    /*
     * New code or edit code.
     */
    protected function createComponentCodeForm($name) {
        /** @todo Move to DB / config */
        if($this->user->storage->identity->state == 'public') {
            $selectState = array('nonpublic' => 'nonpublic - not visible', 'public' => 'publish - visible');
        } else {
            $selectState = array('nonpublic' => 'nonpublic - not visible');
        }
        
        $selectType = array('css' => 'CSS');
        
        $form = new Form($this, $name);
        $form->addText('alias','Name* and Typy*')->addRule(Form::FILLED, 'Please select type of source code.')->setAttribute('class', 'text-short');
        $form->addText('text','Description')->setAttribute('size', 156);
        $form->addSelect('type', 'Type*', $selectType)->setAttribute('class','select_short');
        $form->addSelect('state', 'State*', $selectState)->setAttribute('class', 'select_short');
        $form->addTextArea('code','Source Code');
        $form->addHidden('id');

        $form->addSubmit('save', 'Save')->setAttribute('class','main-button');
        $form->onSuccess[] = array($this, 'codeFormSubmitted');
        
        //Values for editing
        if(!empty($this->code)) {
            $editValues = array (
            'id' => $this->code["id"],
            'name' => $this->code["name"],
            'alias' => $this->code["alias_".$this->lang],
            'type' => $this->code["type"],
            'state' => $this->code["state"],
            'text' => $this->code["text_".$this->lang],
            'code' => $this->code["code"],
            'inserttime' => new \DateTime()
            );
            $form->setDefaults($editValues);
            
            $form->addSubmit('edit', 'Save changes')->setAttribute('title','Save all changes.')->setAttribute('class','main-button')->onClick[] = array($this, 'codeEditSubmitted');

            $presenter = $this;
            $form->addSubmit('cancel', 'Cancel')->setValidationScope(FALSE)->setAttribute('title','Cancel changes.')->setAttribute('class','add-button')
                             ->onClick[] = function () use ($presenter) {
                                 $presenter->flashMessage('I have canceled all changes.', 'success');
                                 $presenter->redirect('default');
                             };
        }
   }
   
    public function codeFormSubmitted(Form $form) {
        if($this->user->isInRole('admin')) {
            $userId = null;
        } else {
            $userId = $this->user->id;
        }
        
        $this->codeRepository->insertCode(array(
           'type' => $form->values->type,
           'name' => urlencode($form->values->alias),
           'alias_'.$this->lang => $form->values->alias,
           'text_'.$this->lang => $form->values->text,
           'code' => $form->values->code,
           'order' => null,
           'state' => $form->values->state,
           'inserttime' => new \DateTime(),
           'layout_id' => null,
           'user_id' => $userId
        ));
        
        $this->flashMessage('I have inserted new source code.', 'success');
        $this->redirect('default');
        
    }

    public function codeEditSubmitted(Nette\Forms\Controls\SubmitButton $button) {
        $values = $button->form->getValues();
        
        $this->codeRepository->updateCode('id', $values['id'], array(
           'type' => $values['type'],
           'name' => urlencode($values['alias']),
           'alias_'.$this->lang => $values['alias'],
           'text_'.$this->lang => $values['text'],
           'code' => $values['code'],
           'order' => null,
           'state' => $values['state'],
           'updatetime' => new \DateTime(),
           'layout_id' => null
        ));
        
        //Update state in connected content.
        //$this->sourceRepository->updateSourceByCodeId($values['id'], array('state' => $values['state']));

        $this->flashMessage('I have changed the source code and parameters.', 'success');
        $this->redirect('default');
    } 
    
}
