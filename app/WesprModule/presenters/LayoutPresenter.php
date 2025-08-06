<?php

/**
 * Layout management
 *
 * @author David Ehrlich, 2014
 */

namespace App\WesprModule;

use Nette,
        Model,
        App,
        Nette\Application\UI\Form,
        Nette\Utils\FileSystem;

class LayoutPresenter extends App\SecuredPresenter {
    /** @var Nette\Database\Statement Selected items form template table. */
    private $layouts;
    /** @var Nette\Database\Statement Selected items form source table. */
    private $sources = array();
    /** @var Nette\Database\Statement Selected item for edit. */
    private $editLayout;
    /** @var Nette\Database\Statement Selected item for edit. */
    private $editSource;
    /** @var int ID for action with template. */
    private $id;
    
    /**
     * Write sub-menu area on the page.
     * @return \App\\SettingMenuControl
     */
    public function createComponentSettingMenu() {

        return new SettingMenuControl($this->translator, $this->lang, $this);
    }
    
    public function actionDefault($id) {
        $this->layouts = $this->layoutRepository->findLayouts($this->lang, '("nonpublic", "locked", "link", "public")');
        $layouts = $this->layouts->fetchAll();
        foreach($layouts as $layout) {
            $this->sources[$layout->id] = $this->sourceRepository->findSourcesByLayout($layout->id);
        }
        
        //Edit item
        if(isset($id)) {
            $this->id = $id;
            $this->editLayout = $this->layoutRepository->findOneLayout($id)->fetch();
            $this->editSource = $this->sourceRepository->findSourcesByLayout($id);
        }
    }
    
    public function renderDefault($id) {
        $this->layouts = $this->layoutRepository->findLayouts($this->lang, '("nonpublic", "locked", "link", "public")');
        
        $this->template->id = $id;
        $this->template->layouts = $this->layouts;
        $this->template->sources = $this->sources;
        $this->template->countLayouts = count($this->layouts);
        //Solution for snippet
        $this->template->form = $this->template->_form = $this['layoutList'];

        //Edit item
        if(isset($id)) {
            $this->template->editSource = $this->editSource;
        }
        
        if(count($this->layouts) == 0) {
            $this->flashMessage('Web is not activated. Upload new template for activation.');
        }
    }
    
    protected function createComponentLayoutList($name) {
        $form = new Form($this, $name);
        $form->setTranslator($this->translator);        

        if (!$this->layouts) {
            throw new \Nette\Application\BadRequestException;
        }

        $select = $form->addContainer('select');
        $state = $form->addContainer('state');
        foreach ($this->layouts as $item) {
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
                ->onClick[] = array($this, 'layoutListDeleteSubmitted');
        
        if($this->user->storage->identity->state == 'public') {
            $form->addSubmit('publish', '!')
                    ->setAttribute('title','Zveřejnit / nezveřejnit vybrané položky?')
                    ->setAttribute('data-slave', 'background-color: rgb(200,200,200); color: rgb(230,230,230); cursor: inherit; border-color: rgb(180,180,180);')
                    ->setAttribute('style', 'background-color: rgb(200,200,200);  color: rgb(230,230,230); cursor: inherit; border-color: rgb(180,180,180);')
                    ->setAttribute('onclick', 'return warning(this)')
                    ->setDisabled()
                    ->onClick[] = array($this, 'layoutListPublishSubmitted');
        } else {
            $form->addSubmit('publish', '!')
                    ->setAttribute('title','Není dostupné. Ověřte svůj účet.')
                    ->setAttribute('style', 'background-color: rgb(200,200,200);  color: rgb(230,230,230); cursor: inherit; border-color: rgb(180,180,180);')
                    ->setDisabled();
        }
        
        $form->addButton('selectAll', 'all')
                ->setAttribute('onclick', 'return checkAll("data-slave", "data-wespr-check")');
    }
    
    public function layoutListDeleteSubmitted(Nette\Forms\Controls\SubmitButton $button) {
        $values = $button->form->getValues(true);
        $keys = array_keys(array_filter($values['select']));

        
        foreach ($keys as $id) {
            //Delete linked files.
            $layout = $this->layoutRepository->findOneLayout($id)->fetch();
            
            $trashdir = '../trash/'.$id.'_'.$layout->name.'_v'.$layout->version.'_'.rand(100, 999);
            try {
                $this->layoutRepository->removeLayoutFiles($this->sources[$id], $trashdir);
            } catch (Exception $ex) {
                $this->flashMessage('Ajaj, došlo na chybu '.$ex->getMessage().'. Napište, aby se to opravilo. Díky.', 'error');
                $this->redirect('this');
            }
            
            $this->layoutRepository->updateLayout('id', $id, array('state' => null));
            $this->updateDependecies($id, null);
        }

        $this->flashMessage('Položky jsem umístil do koše.', 'success');
        if($this->isAjax()) {
            $button->form->setValues(array(), true);
            $this->redrawControl('table');
        } else {
            $this->redirect('this');
        }
    }
   
    public function layoutListPublishSubmitted(Nette\Forms\Controls\SubmitButton $button) {      
        /** @var integer cyklus foreach*/
        $i=0;

        $values = $button->form->getValues(true);
        $keys = array_keys(array_filter($values['select']));

        foreach ($keys as $id) {
            if($values['state'][$keys[$i++]] == 'nonpublic') {$state = 'public';} else {$state = 'nonpublic';}
            
            $this->layoutRepository->updateLayout('id', $id, array('state' => $state));
            $this->updateDependecies($id, $state);
        }
        
        $this->flashMessage('Upravil jsem úspěšně stav položek.', 'success');
        if($this->isAjax()) {
            $button->form->setValues(array(), true);
            $this->redrawControl('table');
        } else {
            $this->redirect('this');
        }
    }
    
    /*
     * New page or edit page.
     */
    protected function createComponentLayoutForm($name) {
        /** @todo Move to DB / config */
        if($this->user->storage->identity->state == 'public') {
            $selectState = array('nonpublic' => 'Nonpublic - Not visible', 'public' => 'Public - Visible');
        } else {
            $selectState = array('nonpublic' => 'Nonpublic - Not visible');
        }
        
        $selectDelegating = array('none' => 'no-one', 'admin' => 'admins', 'editor' => 'editors', 'user' => 'users');
        //Read directories for saving new Nette template.
        $dirs = $this->layoutRepository->readDirectory('../app/FrontModule/templates/');
        
        $form = new Form($this, $name);
        $form->setTranslator($this->translator);        
        if(!empty($this->editLayout)) {
            $form->addUpload('layoutFile', 'Files', true)->addRule(Form::MAX_FILE_SIZE, 'Sorry but the files are too big. Maximal size is 2MB.', 2000 * 1024 /*byte*/)->setAttribute('class','upload');
        } else {
            $form->addUpload('layoutFile', 'Files*', true)->addRule(Form::FILLED, 'Please, uplod files up to 2MB of size.')->addRule(Form::MAX_FILE_SIZE, 'Sorry but the files are too big. Maximal size is 2MB.', 2000 * 1024 /*byte*/)->setAttribute('class','upload');
        }
        $form->addText('alias', 'Name')->setAttribute('size', 70);
        $form->addText('description', 'Description')->setAttribute('size', 70);
        if($this->user->isInRole('admin')) {
            $form->addSelect('state', 'State & delegacy', $selectState)->setAttribute('class','select_short');
            $form->addSelect('delegating', 'Delegacy', $selectDelegating)->setAttribute('class','select_short');
        } else {
            $form->addSelect('state', 'State', $selectState)->setAttribute('class','select');
            $form->addHidden('delegating');
        }
        $form->addHidden('version');
        $form->addHidden('name');
        $form->addHidden('id');

        $form->addSubmit('save', 'Save')->setAttribute('class','main-button');
        $form->onSuccess[] = array($this, 'layoutFormSubmitted');

        if(!empty($this->editLayout)) {
            $editValues = array (
            'id' => $this->editLayout["id"],
            'name' => $this->editLayout["name"],
            'alias' => $this->editLayout["alias_".$this->lang],
            'version' => $this->editLayout["version"],
            'state' => $this->editLayout["state"],
            'delegating' => $this->editLayout["delegating"],
            'description' => $this->editLayout["description_".$this->lang]
            );
            $form->setDefaults($editValues);

            $form->addSubmit('edit', 'Save changes')->setAttribute('title','Save changed template.')->setAttribute('class','main-button')->onClick[] = array($this, 'layoutEditSubmitted');

            $presenter = $this;
            $form->addSubmit('cancel', 'Cancel')->setValidationScope(FALSE)->setAttribute('title','Cancel alteration of template.')->setAttribute('class','add-button')
                             ->onClick[] = function () use ($presenter) {
                                 $presenter->flashMessage('Changes have not been saved.', 'success');
                                 $presenter->redirect('default');
                             };
        }
   }
   
    public function layoutFormSubmitted(Form $form) {
        if ($form['save']->isSubmittedBy()) {
            $values = $form->getValues();

            $files = $values['layoutFile'];
            
            //Insert files and basic layout
            $autoName = $this->insertLayoutFiles($files);
            
            if(empty($values['alias'])) {
                $name = $alias = $autoName;
            } else {
                $name = $this->layoutRepository->sanitizeName($values['alias']);
                $alias = $values['alias'];
            }
            
            $id = $this->layoutRepository->maxLayout('id');
            //Update basic layout
            $this->layoutRepository->updateLayout('id', $id, array(
               'name' => $name,
               'alias_'.$this->lang => $alias,
               'state' => $values['state'],
               'delegating' => $values['delegating'],
               'description_'.$this->lang => $values['description'],
               'inserttime' => new \DateTime()
            ));
            
            if(count($files) == 1) {
                $this->flashMessage('I have saved template file '.$files->getSanitizedName().' successfuly.', 'success');
            } else if(count($files) > 1 && count($files) <= 4) {
                $this->flashMessage('I have saved '.count($files).' template files.', 'success');
            } else {
                $this->flashMessage('I have saved '.count($files).' template files.', 'success');
            }
            
            $this->redirect('default');
        } else {
            $this->flashMessage('Ooh, Template has not been saved. Sorry, try it again.', 'error');
            $this->redirect('this');
        }
    }

    public function layoutEditSubmitted(Nette\Forms\Controls\SubmitButton $button) {
        $values = $button->form->getValues();
        
        $files = $values['layoutFile'];
        
        if(count($files) !== 0) {
            $countFile = $this->layoutRepository->countValidation($files);
            $removeState = $this->layoutRepository->removeValidation($files, $this->editSource);
            if($removeState != $countFile['presenter']) {
                $this->flashMessage('I am affraid the template has not same file names. Please upload the files with same names.', 'error');
                $this->redirect('this');
            }
            
            //Move files on harddrive to Bin.
            $trashdir = '../trash/'.$values['id'].'_'.$values['name'].'_v'.$values['version'].'_'.rand(100, 999);
            try {
                $this->layoutRepository->removeLayoutFiles($this->editSource, $trashdir);
            } catch (Exception $ex) {
                $this->flashMessage('Ouh! Error accure '.$ex->getMessage().'. Please drop me an email. Thank you.', 'error');
                $this->redirect('this');
            }
            
            $autoName = $this->insertLayoutFiles($files, $values['id']);
        }
        
        if(empty($values['alias']) && !empty($autoName)) {
            $name = $alias = $autoName;
        } else if(empty($values['alias'])) {
            $name = $alias = $values['name'];
        } else {
            $name = $this->layoutRepository->sanitizeName($values['alias']);
            $alias = $values['alias'];
        }
        
        $version = $values['version'] + 1;
        
        if($this->user->isInRole('admin')) {
            $userId = null;
        } else {
            $userId = $this->user->id;
        }
        
        $this->layoutRepository->updateLayout('id', $values['id'], array(
           'name' => $name,
           'alias_'.$this->lang => $alias,
           'description_'.$this->lang => $values['description'],
           'state' => $values['state'],
           'delegating' => $values['delegating'],
           'version' => $version,
           'updatetime' => new \DateTime(),
           'user_id' => $userId
        ));
        
        if(count($files) === 0) {
            $this->flashMessage('Template data has been adjusted.', 'success');
        } else if(count($files) == 1) {
            $this->flashMessage(count($files).' template file has been successfully uploaded.', 'success');
        } else if(count($files) > 1 && count($files) <= 4) {
            $this->flashMessage(count($files).' template files have been successfully uploaded.', 'success');
        } else {
            $this->flashMessage(count($files).' template files have been successfully uploaded', 'success');
        }
        
        $this->updateDependecies($values['id'], $values['state']);
        
        $this->redirect('default');
    }
    
    public function insertLayoutFiles($files, $rewrite = false) {
        /** @var \Nette\Database\Statement Old source object. */
        $source = null;
        
        $countFiles = $this->layoutRepository->countValidation($files);
        if(($countFiles['presenter'] == 0 || $countFiles['latte'] == 0 || $countFiles['style'] == 0) && ($countFiles['other'] == 0)){
            $this->flashMessage('Oups! Template is not complete. I have uploaded '.$countFiles['presenter'].'x PHP, '.$countFiles['latte'].'x LATTE, '.$countFiles['style'].'x CSS. Please, upload correct number of files.', 'error');
            $this->redirect('this');
        }else if(($countFiles['presenter'] != 0 || $countFiles['latte'] != 0 || $countFiles['style'] != 0) && $countFiles['other'] != 0) {
            $this->flashMessage('Oups! I have found '.$countFiles['other'].' denied files. Allowed are only PHP, LATTE, CSS.', 'error');
            $this->redirect('this');
        }
        
        if(!$rewrite) {
            $fileState = $this->layoutRepository->fileValidation($files);
            if(array_search('true', $fileState) !== false) {
                $this->flashMessage('Oups! I have found same file names on server. Please change the file names.', 'error');
                $this->redirect('this');
            }
        }
        
        $name = $this->layoutRepository->setDirectory($files);
        
        if($this->user->isInRole('admin')) {
            $userId = null;
        } else {
            $userId = $this->user->id;
        }
        
        if(!$rewrite) {
            //Insert new basic layout
            $this->layoutRepository->insertLayout(array(
                'version' => 0,
                'user_id' => $userId
            ));
            
            //Get layout ID
            $layoutId = $this->layoutRepository->maxLayout('id');
        } else {
            $layoutId = $rewrite;
        }
        
        foreach($files as $file) {
            
            if ($file->isOk()) {
                
                $path = $this->layoutRepository->moveLayoutFiles($file);
                $nettename = $this->layoutRepository->getNettename($file);
                $fileType = $this->layoutRepository->fileType($file);
                
                //Get and remove old sources.
                if($rewrite !== false) {
                    
                    $source = $this->sourceRepository->findOneSourceByPath($path, $layoutId)->fetch();
                    if($source !== false) {
                        $this->sourceRepository->updateSource('id', $source->id, array(
                           'state' => null,
                           'updatetime' => new \DateTime()
                        ));
                    }
                    
                    $version = $source->version + 1; //Updated source file
                } else {
                    $version = 0; //New source file.
                }
                
                if($this->user->isInRole('admin')) {
                    $userId = null;
                } else {
                    $userId = $this->user->id;
                }
                
                $newSource = $this->sourceRepository->insertSource(array(
                   'path' => $path,
                   'mimetype' => $file->getContentType(),
                   'filetype' => $fileType,
                   'version' => $version,
                   'nettename' => $nettename,
                   'name_'.$this->lang => $name,
                   'state' => 'public',
                   'delegating' => null,
                   'inserttime' => new \DateTime(),
                   'layout_id' => $layoutId,
                   'user_id' => $userId
                ));
                
                //Update source_id in page table.
                if($rewrite !== false && $newSource !== false) {
                    $id = $newSource->id;

                    $this->pageRepository->updatePage('nettename', $nettename, array(
                       'source_id' => $id,
                       'updatetime' => new \DateTime()
                    ));
                }
                
            } else {
                $this->flashMessage('Oups! You should upload entire template. I am expecting a presenter PHP, a template LATTE and style CSS.', 'error');
                $this->redirect('this');
            }
        }
        
        return $name;
    }
    
    private function updateDependecies($layoutId, $state) {
        //Update Sources
        $this->sourceRepository->updateSourceWhere($layoutId, array('state' => $state));
        //Update page state.
        $this->pageRepository->updatePageByLayout($layoutId, array('state' => $state));
        //Update state in connected content.
        $pages = $this->pageRepository->findPageByLayout($layoutId)->fetchAll();

        if($pages) {
            foreach($pages as $page) {
                $this->articlePageRepository->updateArticlePageByPageId($page->id, array('state' => $state));
                //$this->fileArticleRepository->updateFileArticleByPageId($page->id, array('state' => $values['state']));
            }
        }        
    }
    
}
