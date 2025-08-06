<?php
/**
 * File lists
 *
 * @author David Ehrlich
 */

namespace App\WesprModule\FileModule;

use Nette;
use App;
use Nette\Application\UI\Form;
use OrbisRex;

class FilePresenter extends App\SecuredPresenter {
    /** @var Nette\Database\Statement Row form table file */
    private $files;
    /** @var Nette\Database\Statement Row from table group*/
    public $groups;
    /** @var string File IDs */
    private $fileIds;
    /** @var array General files (user_id = NULL) */
    private $generalFiles;
    
    /** @inject @var App\WesprModule\FileModule\IFileNewControlFactory */
    public $fileNewControlFactory;
    /** @inject @var App\WesprModule\FileModule\IFileImportControlFactory */
    public $fileImportControlFactory;
    /** @inject @var App\WesprModule\FileModule\IFileEditControlFactory */
    public $fileEditControlFactory;
    
    /**
     * Write sub-menu area on the page.
     * @return \App\FileMenuControl
     */
    public function createComponentFileMenu() {
        return new FileMenuControl($this->translator, $this->lang, $this->groupRepository, $this->user);
    }
    
    /**
     * Article list
     */
    private function dataMiner($id) {
        if($this->user->isInRole('admin')) {
            $this->files = $this->fileGroupRepository->findAllFiles($this->lang, $id);
        } else {
            $this->files = $this->fileGroupRepository->findFiles($this->lang, $this->user->id, $id);
            $this->generalFiles = $this->fileGroupRepository->findGeneralFiles($this->lang, $id);
        }

        if(count($this->files) == 0 && count($this->generalFiles) == 0) {
            $this->flashMessage('It seems to be an empty group. Try upload some files.');
            $this->redirect('new');
        }
    }
    
    public function actionDefault($id) {
        $this->dataMiner($id);
    }

    public function renderDefault($id) {
        $this->dataMiner($id);
        
        $this->template->pageId = $id;
        $this->template->files = $this->files;
        $this->template->countFiles = count($this->files);
        $this->template->generalFiles = $this->generalFiles;
        $this->template->countGeneralFiles = count($this->generalFiles);
        //Solution for snippet
        $this->template->form = $this->template->_form = $this['actionFile'];
    }
    
    /**
     * Sort files by order. Using JQuery.
     */
    public function handleOrder($id) {
        
        //Create array for current key
        $orderList = $this->files;
        $order = array();
        foreach ($orderList as $itemOrder) {
            $order[] = $itemOrder->order;
        }

        $list = $this->request->post;
        $this->fileGroupRepository->sortFile($order, $list['item'], $id);
        
        if($this->isAjax()) {
            if($this->files->count() == 0) {
                //When an files does not exist. Redirect to form new file.
                $this->flashMessage('There is no file in selected group. Please add some.');
                $this->redirect('File:new'); 
            }
            
            $this->redrawControl('table');
        } else {
            $this->redirect('this');
        }
    }
    
    /** 
    * Publish and delete files. 
    */
    protected function createComponentActionFile($name) {
        $form = new Form($this, $name);

        if (!$this->files) {
            throw new \Nette\Application\BadRequestException;
        }
        
        $select = $form->addContainer('select');
        $state = $form->addContainer('state');
        foreach ($this->files as $item) {
                $select->addCheckbox($item->file_id)
                        ->setAttribute('onchange', 'return elementEnable("data-wespr-check", "data-slave")')
                        ->setAttribute('data-wespr-check', 'check');
                        
                $state->addHidden($item->file_id)->setValue($item->state);
        }

        $form->addSubmit('delete', 'X')
                ->setAttribute('title','Smazat vybrané položky?')
                ->setAttribute('data-slave', 'background-color: rgb(200,200,200); color: rgb(230,230,230); cursor: inherit; border-color: rgb(180,180,180);')
                ->setAttribute('style', 'background-color: rgb(200,200,200); color: rgb(230,230,230); cursor: inherit; border-color: rgb(180,180,180);')
                ->setAttribute('onclick', 'return warning(this)')
                ->setDisabled()
                ->onClick[] = array($this, 'actionItemDeleteSubmitted');
        
        if($this->user->storage->identity->state == 'public') {
            $form->addSubmit('publish', '!')
                    ->setAttribute('title','Zveřejnit / nezveřejnit vybrané položky?')
                    ->setAttribute('data-slave', 'background-color: rgb(200,200,200); color: rgb(230,230,230); cursor: inherit; border-color: rgb(180,180,180);')
                    ->setAttribute('style', 'background-color: rgb(200,200,200);  color: rgb(230,230,230); cursor: inherit; border-color: rgb(180,180,180);')
                    ->setAttribute('onclick', 'return warning(this)')
                    ->setDisabled()
                    ->onClick[] = array($this, 'actionItemPublishSubmitted');
        } else {
            $form->addSubmit('publish', '!')
                    ->setAttribute('title','Není dostupné. Ověřte svůj účet.')
                    ->setAttribute('style', 'background-color: rgb(200,200,200);  color: rgb(230,230,230); cursor: inherit; border-color: rgb(180,180,180);')
                    ->setDisabled();
        }
        
        $form->addSubmit('edit', '?')
                ->setAttribute('title','Do you want edit selected items?')
                ->setAttribute('data-fancybox-href', $this->link('File:edit'), $this->fileIds)
                ->setAttribute('class', 'component fancybox.iframe')
                ->setAttribute('data-slave', 'background-color: rgb(200,200,200); color: rgb(230,230,230); cursor: inherit; border-color: rgb(180,180,180);')
                ->setAttribute('style', 'background-color: rgb(200,200,200); color: rgb(230,230,230); cursor: inherit; border-color: rgb(180,180,180);')
                ->setDisabled()
                ->onClick[] = array($this, 'actionItemEditSubmitted');
                
        
        $form->addButton('selectAll', 'all')
            ->setAttribute('onclick', 'return checkAll("data-slave", "data-wespr-check")');
    }
    
   public function actionItemDeleteSubmitted(Nette\Forms\Controls\SubmitButton $button) {
        $values = $button->form->getValues(true);

        $keys = array_keys(array_filter($values['select']));
        
        foreach ($keys as $id) {
            $this->fileGroupRepository->updateFileGroup('id', $id, array('state' => null));
        }
        
       $this->flashMessage('Položky jsem umístil do koše.', 'success');
       
        if($this->isAjax()) {
            $button->form->setValues(array(), true);
            $this->redrawControl('table');
        } else {
            $this->redirect('this');
        }
   }
   
   public function actionItemPublishSubmitted(Nette\Forms\Controls\SubmitButton $button) {      
       /** @var integer cyklus foreach */
       $i=0;

       $values = $button->form->getValues(true);
       $keys = array_keys(array_filter($values['select']));
       
        foreach ($keys as $id) {
             if($values['state'][$keys[$i++]] == 'nonpublic') {$state = 'public';} else {$state = 'nonpublic';}
             $this->fileGroupRepository->updateFileGroup('id', $id, array('state' => $state));
        }
        
        $this->flashMessage('Upravil jsem úspěšně stav položek.', 'success');
        
        if($this->isAjax()) {
            $button->form->setValues(array(), true);
            $this->redrawControl('table');
        } else {
            $this->redirect('this');
        }
   }
   
   public function actionItemEditSubmitted(Nette\Forms\Controls\SubmitButton $button) {      
        $values = $button->form->getValues(true);
        $keys = array_keys(array_filter($values['select']));

        //Set session section fileIds.
        $session = $this->getSession('fileIds');
        $session->fileIds = $keys;
        
        $this->redirect('File:edit');
        
        /*if($this->isAjax()) {
            $button->form->setValues(array(), true);
            $this->redrawControl();
        } else {
            $this->redirect('this');
        }*/
   }
   
    /** 
     * New file. 
     */
    private function readFileIds() {
        //$this->files = $this->fileGroupRepository->findUserLatestFiles($this->lang, $this->user->id);        
        $ids = $this->fileGroupRepository->findUserLatestFiles($this->lang, $this->user->id)->fetchPairs('id', 'file_id');
        $this->fileIds = implode(',', $ids);
    }
    
    public function actionNew() {
        //Latest files for editing.
        $this->readFileIds();
    }
    
    public function renderNew() {
        //Control access for Free user account.
        $countFiles = $this->fileGroupRepository->findFiles($this->lang, $this->user->id, null)->count();
        
        $this->template->userAuthority = $this->userAuthority;
        $this->template->countFiles = $countFiles;
    }
    
    /*Import files */
    public function renderImport() {
    }
    
    //Component for file creation.
    protected function createComponentFileNew() {
        $control = $this->fileNewControlFactory->create();
        return $control;
    }
    
    /**
     * Buy membership component.
     * @return \App\BuyMembershipControl
     */
    protected function createComponentBuyMembership() {
        return new \App\WesprModule\BuyMembershipControl($this->translator, $this->lang, $this->presenter);
    }
    
    //Component for file import.
    protected function createComponentFileImport() {
        $control = $this->fileImportControlFactory->create();
        return $control;
    }
    
    /** 
     * Edit file. 
     */
    public function actionEdit() {
        //Read session
        $session = $this->getSession('fileIds');

        if(!$session->fileIds) {
            $this->fileIds = $this->fileGroupRepository->findAllUserFiles($this->lang, $this->user->id)->fetchPairs('id', 'file_id');
        } else {
            $this->fileIds = $session->fileIds;
        }
        
        //unset($session->fileIds); //Remove file IDs.
    }
    
    //Component for file editation.
    protected function createComponentFileEdit() {
        $control = $this->fileEditControlFactory->create();
        $control->setFileIds($this->fileIds);
        return $control;
    }
}