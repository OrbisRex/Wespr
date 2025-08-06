<?php

/**
 * Setting web pages.
 *
 * @author David Ehrlich
 */

namespace App\WesprModule;

use Nette,
        App,
        Model,
        Nette\Application\UI\Form;

class PagePresenter extends App\SecuredPresenter {

    /** @var Nette\Database\Statement Selected pages from table page. */
    private $pages;
    /** @var Nette\Database\Statement Selected page for edit. */
    private $page;
    /** @var Nette\Database\Statement Selected all layouts. */
    private $layoutsData;
    /** @var Nette\Database\Statement Selected all sources. */
    private $sourcesData;
    /** @var Nette\Database\Statement Selected all pair sources. */
    private $sources;
    /** @var array Template content for link. */
    private $anchor;
    /** @var Nette\Database\Statement Selected parent page from table page_page. */
    private $parents;
    /** @var Nette\Database\Statement Selected main menu pages from table page. */
    private $parentPage;
    
    /** @var ID Page for edit. */
    private $id;
    /** @var String Promt text for Form. */
    private $promtText;
    
    private $source;
    
    /**
     * Write sub-menu area on the page.
     * @return \App\\SettingMenuControl
     */
    public function createComponentSettingMenu() {

        return new SettingMenuControl($this->translator, $this->lang, $this);
    }
    
    public function actionDefault($id) {
        if($this->user->isInRole('admin')) {
            $this->pages = $this->pageRepository->findAdminPages($this->lang, $this->user->id);
            
            $this->layoutsData = $this->layoutRepository->findLayouts($this->lang);
            
            foreach($this->layoutsData->fetchAll() as $layout) {
                $this->sourcesData = $this->sourceRepository->findLatteByLayout($this->lang, $layout->id);
                $this->sources[$layout->alias] = $this->sourcesData->fetchPairs('id', 'nettename');
            }
            
            //Pages for main menu
            $this->parentPage = $this->pageRepository->findAdminMainPages($this->user->id)->fetchPairs('id', 'alias_'.$this->lang);
        } else {
            $this->pages = $this->pageRepository->findOwnsPages($this->lang, $this->user->roles, $this->user->id, 'page.order');
            
            $this->layoutsData = $this->layoutRepository->findLayouts($this->lang);
            
            foreach($this->layoutsData->fetchAll() as $layout) {
                $this->sourcesData = $this->sourceRepository->findLatteByLayoutRestricted($this->lang, $layout->id);
                \Tracy\Debugger::barDump($this->sourcesData->fetchPairs('id', 'nettename'));
                //Check availability of layouts.
                if(!empty($this->sourcesData->fetchPairs('id', 'nettename'))){              
                   $this->sources[$layout->alias] = $this->sourcesData->fetchPairs('id', 'nettename');
                }
            }
            \Tracy\Debugger::barDump($this->sources);
        }
        
        //Find parent page
        foreach($this->pages->fetchAll() as $page) {

            $parents = $this->pageRepository->findParentPages($this->lang, $page->parent);
            
            if($page->parent === 0) {
                $this->parents[$page->parent] = 'Homepage';
            } else {
                $this->parents[$page->parent] = $parents->fetch()->alias;
            }
        }
        
        //Edit item
        if(isset($id)) {
            $this->id = $id;
            $this->page = $this->pageRepository->findOnePage($id)->fetch();
        }
    }

    public function renderDefault($id) {
        if($this->user->isInRole('admin')) {
            $this->pages = $this->pageRepository->findAdminPages($this->lang, $this->user->id);
            
            if(count($this->pages) == 0) {
                $this->flashMessage('Web is inactive, for activation creat at least one page.');
            }
        } else {
            $this->pages = $this->pageRepository->findOwnsPages($this->lang, $this->user->roles, $this->user->id, 'page.order');
        }

        $this->template->id = $id;
        $this->template->pages = $this->pages;
        $this->template->parents = $this->parents;
        $this->template->countPage = count($this->pages);
        $this->template->countSource = count($this->sources);
        //Solution for snippet
        $this->template->form = $this->template->_form = $this['pageList'];
    }

    /**
     * Sort item by order. Using JQuery.
     */
    public function handleOrder() {
        //Create array for current key
        $orderList = $this->pages;
        
        $order = array();
        foreach ($orderList as $itemOrder) {
            $order[] = $itemOrder->order;
        }
        
        $list = $this->request->post;
        $this->pageRepository->sortPage($order, $list['item']);

        if($this->isAjax()) {
            //When an article does not exist. Redirect to New article form.
            if($this->pages->count() == 0) {
                $this->flashMessage('Section is empty. Create first page!');
                $this->redirect('Settings:'); 
            }
            
            $this->redrawControl('table');
        } else {
            $this->redirect('this');
        }
    }
    
    public function handleAnchor() {
        $list = $this->request->post;
        
        //Get template content
        $source = $this->sourceRepository->findOneSource($list['source'])->fetch();
        try{
            $contents = $this->pageRepository->readFolderContent($source['path'], true);
            if(empty($contents) || $contents === NULL) {
                $this->promtText = 'No anchor in the template.';
            }
            
            foreach($contents as $content) {
                $this->anchor['#'.$content] = $content;
            }
        } catch (Exception $e) {
            $this->flashMessage('I can not find template file. Please check your templates.', 'error');
        }
        
        $articleUri = $this->articlePageRepository->findArticlesByLayout($this->lang, $list['source'], $this->userId)->fetchAll();
        foreach($articleUri as $uri) {
            $this->anchor['Page Sections:']['#'.$uri['uri']] = $uri['uri'];
        }
        
        if($this->isAjax()) {
            $this->redrawControl('anchor');
        } else {
            $this->redirect('this');
        }
    }
    
    protected function createComponentPageList($name) {
        $form = new Form($this, $name);
        $form->setTranslator($this->translator);        

        if (!$this->pages) {
            throw new \Nette\Application\BadRequestException;
        }

        $select = $form->addContainer('select');
        $state = $form->addContainer('state');
        foreach ($this->pages as $item) {
                $select->addCheckbox($item->id)
                        ->setAttribute('onchange', 'return elementEnable("data-wespr-check", "data-slave")')
                        ->setAttribute('data-wespr-check', 'check');

                $state->addHidden($item->id)->setValue($item->state);
        }

        $form->addSubmit('delete', 'X')
                ->setAttribute('title','Pozor, zároveň nevratně smažete všechen připojený obsah. Skutečně smazat vybrané položky?')
                ->setAttribute('data-slave', 'background-color: rgb(200,200,200); color: rgb(230,230,230); cursor: inherit; border-color: rgb(180,180,180);')
                ->setAttribute('style', 'background-color: rgb(200,200,200); color: rgb(230,230,230); cursor: inherit; border-color: rgb(180,180,180);')
                ->setAttribute('onclick', 'return warning(this)')
                ->setDisabled()
                ->onClick[] = array($this, 'pageListDeleteSubmitted');
        
        if($this->user->storage->identity->state == 'public') {
            $form->addSubmit('publish', '!')
                    ->setAttribute('title','Zveřejnit / nezveřejnit vybrané položky?')
                    ->setAttribute('data-slave', 'background-color: rgb(200,200,200); color: rgb(230,230,230); cursor: inherit; border-color: rgb(180,180,180);')
                    ->setAttribute('style', 'background-color: rgb(200,200,200);  color: rgb(230,230,230); cursor: inherit; border-color: rgb(180,180,180);')
                    ->setAttribute('onclick', 'return warning(this)')
                    ->setDisabled()
                    ->onClick[] = array($this, 'pageListPublishSubmitted');
        } else {
            $form->addSubmit('publish', '!')
                    ->setAttribute('title','Není dostupné. Ověřte svůj účet.')
                    ->setAttribute('style', 'background-color: rgb(200,200,200);  color: rgb(230,230,230); cursor: inherit; border-color: rgb(180,180,180);')
                    ->setDisabled();
        }
        
        $form->addButton('selectAll', 'all')
                ->setAttribute('onclick', 'return checkAll("data-slave", "data-wespr-check")');
    }
    
    public function pageListDeleteSubmitted(Nette\Forms\Controls\SubmitButton $button) {
        $values = $button->form->getValues(true);
        $keys = array_keys(array_filter($values['select']));

        foreach ($keys as $id) {
            $this->pageRepository->updatePage('id', $id, array('state' => null));
            $this->articlePageRepository->updateArticlePageByPageId($id, array('state' => null));
        }

        $this->flashMessage('I have moved the item into the bin.', 'success');
        if($this->isAjax()) {
            $button->form->setValues(array(), true);
            $this->redrawControl('table');
        } else {
            $this->redirect('this');
        }
    }
   
    public function pageListPublishSubmitted(Nette\Forms\Controls\SubmitButton $button) {      
        /** @var integer loop foreach*/
        $i=0;

        $values = $button->form->getValues(true);
        $keys = array_keys(array_filter($values['select']));


        foreach ($keys as $id) {
            if($values['state'][$keys[$i++]] == 'nonpublic') {$state = 'public';} else {$state = 'nonpublic';}

            $this->pageRepository->updatePage('id', $id, array('state' => $state));
            $this->articlePageRepository->updateArticlePageByPageId($id, array('state' => $state));
        }
        
        
        $this->flashMessage('I have changed state of item.', 'success');
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
    protected function createComponentPageForm($name) {
        /** @todo Move to DB / config */
        if($this->user->storage->identity->state == 'public') {
            $selectState = array('nonpublic' => 'Nonpublic - Not visible', 'public' => 'Public - Visible');
        } else {
            $selectState = array('nonpublic' => 'Nonpublic - Not visible');
        }
        
        $selectDelegating = array('none' => 'no-one', 'admin' => 'admins', 'editor' => 'editors', 'user' => 'users');
        $selectLevel = array(0 => 'Main Menu');
        
        if(!isset($this->promtText)) {
            $this->promtText = 'Select anchor.';
        }

        if(!empty($this->page)) {
            //Get content of template
            $this->source = $this->sourceRepository->findOneSource($this->page["source_id"])->fetch();

            if($this->source['state'] !== NULL) {
                $contents = $this->pageRepository->readFolderContent($this->source['path'], true);
                if(empty($contents) || $contents === NULL) {
                    $this->promtText = 'No anchor within template.';
                }

                foreach($contents as $content) {
                    $this->anchor['#'.$content] = $content;
                }

                $articleUri = $this->articlePageRepository->findArticlesByLayout($this->lang, $this->page["source_id"], $this->userId)->fetchAll();
                foreach($articleUri as $uri) {
                    $this->anchor['Page Sections:']['#'.$uri['uri']] = $uri['uri'];
                }
            } else {
                $this->promtText = 'No anchore within template.';
            }
        }
        
        $form = new Form($this, $name);
        $form->setTranslator($this->translator);        
        $form->addText('alias','Name*')->addRule(Form::FILLED, 'Please enter Page name.');
        $form->addText('content','Description');
        if(!$this->parents) {
            $form->addSelect('parent', 'Place* & Menu Level', array(0 => 'Creat Homepage'))->addRule(Form::FILLED, 'Please choose place.')->setAttribute('class','select_short');
        } else {
            $form->addSelect('parent', 'Place* & Menu Level', $this->parents)->setPrompt('Choose Parent page.')->addRule(Form::FILLED, 'Please choose place.')->setAttribute('class','select_short');
        }
        $form->addSelect('level', 'Level', $selectLevel)->setAttribute('class','select_short');
        if($this->user->isInRole('admin')) {
            $form->addSelect('state', 'State & Delegacy', $selectState)->setAttribute('class','select_short');
            $form->addSelect('delegating', 'Delegacy', $selectDelegating)->setAttribute('class','select_short');
        } else {
            $form->addSelect('state', 'State', $selectState)->setAttribute('class','select_short');
            $form->addHidden('delegating');
        }

        if($this->sources === FALSE) {
            $form->addSelect('source', 'Template* & Anchore', array())->setPrompt('Upload / Publish template.')->addRule(Form::FILLED, 'Please select template.')->setAttribute('class','select_short');
        } else if($this->sources === NULL) {
            $form->addSelect('source', 'Template* & Anchore', array())->setPrompt('Templates are not available.')->setAttribute('class','select_short');
        } else {
            $form->addSelect('source', 'Template* & Anchore', $this->sources)->setPrompt('Choose template.')->addRule(Form::FILLED, 'Please select template.')->setAttribute('class','select_short');
            $form->addSelect('anchor', 'Anchore', $this->anchor)->setPrompt($this->promtText)->setAttribute('class','select_short ajax');
        }
        $form->addHidden('nettename');
        $form->addHidden('layout');
        $form->addHidden('id');

        $form->addSubmit('save', 'Save')->setAttribute('class','main-button');
        $form->onSuccess[] = array($this, 'pageFormSubmitted');

        if(!empty($this->page)) {
            $editValues = array (
            'id' => $this->page["id"],
            'name' => $this->page["name_".$this->lang],
            'alias' => $this->page["alias_".$this->lang],
            'nettename' => $this->page["nettename"],
            'level' => $this->page["level"],
            'parent' => $this->page["parent"],
            'state' => $this->page["state"],
            'delegating' => $this->page["delegating"],
            'source' => $this->page["source_id"],
            'layout' => $this->page["layout_id"],
            'anchor' => $this->page["anchor"],
            'content' => $this->page["content_".$this->lang],
            'inserttime' => new \DateTime()
            );
            
            if($this->source["state"] === null) {
                unset($editValues['source']);
            }
            
            $form->setDefaults($editValues);
            
            $form->addSubmit('edit', 'Save changes')->setAttribute('title','Rewrite tamplate.')->setAttribute('class','main-button')->onClick[] = array($this, 'pageEditSubmitted');

            $presenter = $this;
            $form->addSubmit('cancel', 'Cancel')->setValidationScope(FALSE)->setAttribute('title','Cancel changes.')->setAttribute('class','add-button')
                             ->onClick[] = function () use ($presenter) {
                                 $presenter->flashMessage('I have canceled template changes.', 'success');
                                 $presenter->redirect('default');
                             };
        }
   }
   
    public function pageFormSubmitted(Form $form) {
        $order = $this->pageRepository->maxPage('order');
        if($order === null) {
            $order = 1;
        } else {
            $order += 1;
        }
        
        if($this->user->isInRole('admin')) {
            $userId = null;
        } else {
            $userId = $this->user->id;
        }
        
        if($form->values->parent === 0) {
            $parent = 0;
        } else {
            $parent = $form->values->parent;
        }
        
        $sourceData = $this->sourceRepository->findOneSource($form->values->source);
        $source = $sourceData->fetch();
        
        //Create nettename for menu link.
        $nettename = $source->nettename;
        
        $this->pageRepository->insertPage(array(
           'name_'.$this->lang => strtolower(iconv("utf-8", "us-ascii//TRANSLIT", $form->values->alias)),
           'alias_'.$this->lang => $form->values->alias,
           'nettename' => $nettename,
           'level' => $form->values->level,
           'parent' => $form->values->parent,
           'order' => $order,
           'state' => $form->values->state,
           'delegating' => $form->values->delegating,
           'anchor' => $form->values->anchor,
           'content_'.$this->lang => $form->values->content,
           'inserttime' => new \DateTime(),
           'layout_id' => $source->layout_id,
           'source_id' => $form->values->source,
           'user_id' => $userId
        ));
        
        $this->flashMessage('I have created new page.', 'success');
        $this->redirect('default');
        
    }

    public function pageEditSubmitted(Nette\Forms\Controls\SubmitButton $button) {
        $values = $button->form->getValues();
        
        $sourceData = $this->sourceRepository->findOneSource($values['source']);
        $source = $sourceData->fetch();
        if(strstr($source->nettename, ':') != ':default') {
            $nettename = $source->nettename;
        } else {
            $nettename = $source->nettename;
        }
        
        $this->pageRepository->updatePage('id', $values['id'], array(
           'name_'.$this->lang => strtolower(iconv("utf-8", "us-ascii//TRANSLIT", $values['alias'])),
           'alias_'.$this->lang => $values['alias'],
           'nettename' => $nettename,
           'level' => $values['level'],
           'parent' => $values['parent'],
           'state' => $values['state'],
           'delegating' => $values['delegating'],
           'anchor' => $values['anchor'],
           'content_'.$this->lang => $values['content'],
           'layout_id' => $values['layout'],
           'source_id' => $values['source'],
           'updatetime' => new \DateTime()
        ));
        
        //Update state in connected content.
        $this->articlePageRepository->updateArticlePageByPageId($values['id'], array('state' => $values['state'], 'delegating' => $values['delegating']));

        $this->flashMessage('I have changed page properties.', 'success');
        $this->redirect('default');
    }    
}