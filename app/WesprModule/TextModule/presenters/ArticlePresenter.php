<?php
/**
 * Article management
 *
 * @author David Ehrlich
 */

namespace App\WesprModule\TextModule;

use Nette,
        Model,
        App,
        Nette\Application\UI\Form,
        App\WesprModule\TextModule\ArticleMenuControl,
        App\WesprModule\FileModule\FileSelectControl,
        App\WesprModule\PasscodeSecure;

class ArticlePresenter extends App\SecuredPresenter {
   
    /** @var Nette\Database\Statement data from DB*/
    private $articles;
    /** @var Nette\Database\Statement data from DB to edit form*/
    private $article;
    /** @var Nette\Database\Statement Row from table file*/
    private $fileManage;
    /** @var Nette\Database\Statement Row from table page*/
    public $pages;
    /** @var Nette\Database\Statement Row from table article*/
    private $translationArticle;
    
    /** @inject @var App\WesprModule\FileModule\IFileManagerControlFactory */
    public $fileManagerControlFactory;
    /** @inject @var App\WesprModule\FileModule\IFileSelectControlFactory */
    public $fileSelectControlFactory;

    /** @var int ID of table article_page*/
    private $id;
    /** @var int ID of table file*/
    private $fileId;
    /** @var string current language*/
    private $langTranslation;
    /** @var string Set secure passcode.*/
    private $passcode;
    /** @var array Link to FrontModule presenter.*/
    private $links;
    
    /** @var array $arrayLang List of languages for translation. */
    public $arrayLang = array('en');
    
    /**
     * Write sub-menu area on the page.
     * @return \App\WesprModule\ArticleMenuControl
     */
    public function createComponentArticleMenu() {

        return new ArticleMenuControl($this->translator, $this->lang, $this->pageRepository, $this->user);
    }
    
    /**
     * Article list
     */
    private function dataMiner($id) {
        if(isset($id)) {
            if($this->user->isInRole('admin')) {
                $this->articles = $this->articlePageRepository->filterAdminArticles($this->lang, $this->user->id, $id);
            } else {
                $this->articles = $this->articlePageRepository->filterArticle($this->lang, $this->user->roles, $this->user->id, $id);
            }
            
            if(count($this->articles) == 0) {
                $this->flashMessage('Ve zvolené sekci jsem nenalezl žádný článek, proto jej přidejte.');
                $this->redirect('new');
            }
        } else {
            if($this->user->isInRole('admin')) {
                $this->articles = $this->articlePageRepository->filterAllArticles($this->lang);
                $this->pages = $this->pageRepository->findAllPages($this->lang);
            } else {
                $this->articles = $this->articlePageRepository->filterArticle($this->lang, $this->user->roles, $this->user->id);
                $this->pages = $this->pageRepository->findUserPages($this->lang, $this->user->id);
            }
            
            if(count($this->pages) == 0) {
                $this->flashMessage('Web je nyní deaktivován. Pro jeho aktivaci vložte alespoň 1 stránku.');
            }
        }
    }
    
    private function creatLink() {
        foreach($this->articles as $article) {
            $link = new \OrbisRex\Wespr\TextManipulation($article->page_nettename);
            $link->extractPresenter();            
            
            $this->links[$article->article_id] = $this->link($link->getResult().':show', $article->article_id);            
        }
    }
    
    public function actionDefault($id) {
        
        $this->dataMiner($id);
    }
    
    public function renderDefault($id) {
        
        $this->dataMiner($id);
        
        $this->template->pageId = $id;
        $this->template->articles = $this->articles;
        $this->template->countArticles = $this->articles->count();
        $this->template->langs = $this->arrayLang;
        //Solution for snippet
        $this->template->form = $this->template->_form = $this['actionArticle'];
    }
    
    /**
     * Sort article by order. Using JQuery.
     */
    public function handleOrder($id) {
        
        //Create array for current key
        $orderList = $this->articles;

        $order = array();
        foreach ($orderList as $itemOrder) {
            $order[] = $itemOrder->order;
        }
        
        $list = $this->request->post;
        $this->articlePageRepository->sortArticle($order, $list['item'], $id);

        if($this->isAjax()) {
            //When an article does not exist. Redirect to form new article.
            if($this->articles->count() == 0) {
                $this->flashMessage('There is no article in this section. Please, add someone.');
                $this->redirect('new'); 
            }
            
            $this->redrawControl('table');
        } else {
            $this->redirect('this');
        }
    }
    
    /** 
    * Publish and delete article. 
    */
    protected function createComponentActionArticle($name) {
        $form = new Form($this, $name);

        if (!$this->articles) {
            throw new Nette\Application\BadRequestException;
        }
        
        //Extract name of presenter for link
        $this->creatLink();
        
        $form->getElementPrototype()->class('ajax');
        $selectArticle = $form->addContainer('select');
        $state = $form->addContainer('state');
        $link = $form->addContainer('link');
        
        foreach($this->articles as $article) {
                $selectArticle->addCheckbox($article->article_id)
                        ->setAttribute('onchange', 'return elementEnable("data-wespr-check", "data-slave")')
                        ->setAttribute('data-wespr-check', 'check')
                        ->setAttribute('class', 'checkbox');

                $state->addHidden($article->article_id)->setValue($article->state);
                $link->addText($article->article_id, 'Link')->setAttribute('readonly', 'readonly')->setValue($this->links[$article->article_id]);
        }
        
        $form->addSubmit('delete', 'X')
                ->setAttribute('title','Smazat vybrané položky?')
                ->setAttribute('data-slave', 'background-color: rgb(200,200,200); color: rgb(230,230,230); cursor: inherit; border-color: rgb(180,180,180);')
                ->setAttribute('style', 'background-color: rgb(200,200,200); color: rgb(230,230,230); cursor: inherit; border-color: rgb(180,180,180);')
                ->setAttribute('onclick', 'return warning(this)')
                ->setDisabled()
                ->onClick[] = array($this, 'actionArticleDeleteSubmitted');

        if($this->user->storage->identity->state == 'public') {
            $form->addSubmit('publish', '!')
                    ->setAttribute('title','Zveřejnit / nezveřejnit vybrané položky?')
                    ->setAttribute('data-slave', 'background-color: rgb(200,200,200); color: rgb(230,230,230); cursor: inherit; border-color: rgb(180,180,180);')
                    ->setAttribute('style', 'background-color: rgb(200,200,200);  color: rgb(230,230,230); cursor: inherit; border-color: rgb(180,180,180);')
                    ->setAttribute('onclick', 'return warning(this)')
                    ->setDisabled()
                    ->onClick[] = array($this, 'actionArticlePublishSubmitted');
        } else {
            $form->addSubmit('publish', '!')
                    ->setAttribute('title','Není dostupné. Ověřte svůj účet.')
                    ->setAttribute('style', 'background-color: rgb(200,200,200);  color: rgb(230,230,230); cursor: inherit; border-color: rgb(180,180,180);')
                    ->setDisabled();
        }
        
        $form->addButton('selectAll', 'all')
                ->setAttribute('onclick', 'return checkAll("data-slave", "data-wespr-check")');
    }

   public function actionArticleDeleteSubmitted(Nette\Forms\Controls\SubmitButton $button) {
        
       $values = $button->form->getValues(true);
       $keys = array_keys(array_filter($values['select']));
       
      foreach ($keys as $id) {
          $this->articlePageRepository->updateArticlePage('id', $id, array('state' => null));
       } 

       $this->flashMessage('I have moved the article into the bin.', 'success');

       if($this->isAjax()) {
            $button->form->setValues(array(), true);
            $this->redrawControl('table');
        } else {
            $this->redirect('this');
        }
   }
   
    public function actionArticlePublishSubmitted(Nette\Forms\Controls\SubmitButton $button) {      
        /** @var int cyklus foreach*/
        $i=0;
        
        $values = $button->form->getValues(true);
        $keys = array_keys(array_filter($values['select']));
        
        foreach ($keys as $id) {
            if($values['state'][$keys[$i++]] == 'nonpublic') {$state = 'public';} else {$state = 'nonpublic';}
            $this->articlePageRepository->updateArticlePage('id', $id, array('state' => $state));
        }

        $this->flashMessage('I have successfully changed the article.', 'success');
        
        if($this->isAjax()) {
            $button->form->setValues(array(), true);
            $this->redrawControl('table');
        } else {
            $this->redirect('this');
        }
    }
    
   /** 
    * New and edit of article.
    */
    public function actionNew() {
        //Read submenu for select in form.
        if($this->user->isInRole('admin')) {
            $this->pages = $this->pageRepository->findAdminPages($this->lang, $this->user->id)->fetchPairs('id', 'alias');
            //$this->pages[0] = "Main Page"; //value for all articles without pages
        } else {
            $this->pages = $this->pageRepository->findUserPages($this->lang, $this->user->id)->fetchPairs('id', 'alias');
        }
    }

    public function renderNew() {
        $this->template->pages = $this->pages;
        //Solution for snippet
        $this->template->form = $this->template->_form = $this['articleForm'];
    }
   
    public function actionEdit($id) {
        //Read Article for manage.
        //Read Pages for article filter.
        if($this->user->isInRole('admin')) {
            $this->article = $this->articlePageRepository->findOneArticle($this->lang, $id)->fetch();
            $this->pages = $this->pageRepository->findAdminPages($this->lang, $this->user->id)->fetchPairs('id', 'alias');
            //$this->pages[0] = "Main Page"; //value for all articles without pages
        } else {
            $this->article = $this->articlePageRepository->findOneUserArticle($this->lang, $id, $this->user->id, $this->user->roles)->fetch();
            $this->pages = $this->pageRepository->findUserPages($this->lang, $this->user->id)->fetchPairs('id', 'alias');
        }

        //\Tracy\Debugger::barDump($this->article);
        //Files managemnet.
        $this->fileManage = $this->articleFileRepository->findUsedFile($this->lang, $this->article->article_id, $this->user->id);
        $this->fileId = $this->articleFileRepository->findUsedFile($this->lang, $this->article->article_id, $this->user->id)->fetchAll();
        if (empty($id)) {
            $this->template->id = NULL;
        }
    }

    public function renderEdit($id) {
        //Read article.
        //$this->template->articleId = $this->article->article_id;

        //Files management.
        $this->template->files = $this->fileManage;
        $this->template->countFiles = $this->fileManage->count();
         //Solution for snippet
         $this->template->form = $this->template->_form = $this['fileManagementForm'];
    }
    
    /** 
     * Component for set passcode for secure item.
     */
    /** @todo Fix text changes in form field. */
    public function handleSecure() {
        $list = $this->request->post;
        
        if(($list['state'] == 'nonpublic') || ($list['state'] == 'public')|| ($list['state'] == 'news')) {
            $this->passcode = null;
        } else {
            $id = $this->articleRepository->maxArticle('id') + 1;
            $this->passcode = $id.'-'.rand(100, getrandmax());
        }
        
        if($this->isAjax()) {
            $this->redrawControl('passcode');
        } else {
            $this->redirect('this');
        }
    }
    
   protected function createComponentArticleForm($name) {
        /** @todo Move to DB / config */
        if($this->user->storage->identity->state == 'public') {
            $selectState = array('nonpublic' => 'Nonpublic - Not visible', 'locked' => 'Locked - Secured by code', 'link' => 'Restricted - People with link', 'public' => 'Public - Visibile to all', 'news' => 'News - Visibile as a news');
        } else {
            $selectState = array('nonpublic' => 'Nonpublic - Not visible');
        }
        
        //Adjust value for passcode
        if(!empty($this->article)) {
            if(empty($this->article["passcode"]) && $this->passcode !== null) {
                $id = $this->articlePageRepository->maxArticle('id') + 1;
                $this->passcode = $id.'-'.rand(100, getrandmax());
            } else if(!empty($this->article["passcode"])) {
                $this->passcode = $this->article["passcode"];
            } else {
                $this->passcode = $this->passcode;
            }
        }
        
        $form = new Form($this, $name);
        $form->setTranslator($this->translator);
        $form->addText('title','Title* & Page*')->addRule(Form::FILLED, 'Please enter title of the article.')->setAttribute('class', 'text-short');
        $form->addSelect('pages', 'Page*', $this->pages)->setPrompt('Select page for the article.')->setAttribute('class', 'select_short');
        $form->addSelect('state', 'State & Passcode', $selectState)->setAttribute('class', 'select_short');
        $form->addText('passcode', 'Passcode')
                ->setDefaultValue($this->passcode)
                ->setAttribute('class', 'text-short ajax')
                ->addCondition(Form::BLANK)
                ->addConditionOn($form['state'], Form::EQUAL, 'locked')
                ->setRequired('If you have chosen state Locked, passcode must be present.');
        $form->addTextArea('perex','Short text');
        $form->addTextArea('text','Main text');
        $form->addHidden('oldPasscode');
        $form->addHidden('articleId');
        $form->addHidden('userId');
        $form->addHidden('id');

        $form->addSubmit('save', 'Save')->setAttribute('class','main-button')->setAttribute('title','Save copy of article.');
        $form->onSuccess[] = array($this, 'articleNewSubmitted');

        if(!empty($this->article)) {
            $emptyArticle = array (
            'title' => $this->article['title_'.$this->lang],
            'pages' => $this->article['page_id'],
            'state' => $this->article['state'],
            'oldPasscode' => $this->article['passcode'],
            'text' => $this->article['text_'.$this->lang],
            'perex' => $this->article['perex'],
            'articleId' => $this->article['article_id'],
            'userId' => $this->article['user_id'],
            'id' => $this->article['id']
            );
            $form->setDefaults($emptyArticle);

            $form->addSubmit('edit', 'Save changes')->setAttribute('class','main-button')->setAttribute('title','Save changes.')->onClick[] = array($this, 'articleEditSubmitted');
            $presenter = $this;
            $form->addSubmit('cancel', 'Cancel')->setValidationScope(FALSE)->setAttribute('title','Cancel all changes.')->setAttribute('class','add-button')
                             ->onClick[] = function () use ($presenter) {
                                 $presenter->flashMessage('You have canceled editing of article.', 'success');
                                 $presenter->redirect('default');
                             };
        }
   }
   
   public function articleNewSubmitted(Form $form) {
        if($this->user->isInRole('admin')) {
            $userId = null;
        } else {
            $userId = $this->user->id;
        }
        
        //Normalize special chars.
        $asciiTitle = $this->articlePageRepository->variableString($form->values->title, '-');
        
        $this->articleRepository->insertArticle(array(
            'title_'.$this->lang => $form->values->title,
            'uri' => $asciiTitle,
            'perex_'.$this->lang => $form->values->perex,
            'text_'.$this->lang => $form->values->text,
            'inserttime' => new \DateTime(),
            'updatetime' => new \DateTime(),
            'user_id' => $userId
        ));
        
        $id = $this->articleRepository->maxArticle('id');
        
        $order = $this->articlePageRepository->maxArticle('order');
        if($order === null) {
            $order = 1;
        } else {
            $order += 1;
        }
        
        //Adjusted values for passcode
        if(!empty($form->values->passcode)) {
            $passcode = $form->values->passcode;
        } else if($form->values->state != 'locked') {
            $passcode = null;
        }

        $this->articlePageRepository->insertArticlePage(array(
            'order' => $order,
            'state' => $form->values->state,
            'passcode' => $passcode,
            'article_id' => $id,
            'page_id' => $form->values->pages,
            'user_id' => $userId
        ));
        
        /** @todo Make copies of files with article.*/
        if(!empty($form->values->passcode)) {
            $this->flashMessage('I have created new article and set passcode '.$passcode.'.', 'success');
        } else if($form->values->state != 'locked') {
            $this->flashMessage('I have created new article.', 'success');
        }
        $this->redirect('default', $form->values->pages);
   } 
   
   public function articleEditSubmitted(Nette\Forms\Controls\SubmitButton $button) {
        $values = $button->form->getValues();
        
        //Normalize special characters.
        $asciiTitle = $this->articlePageRepository->variableString($values['title'], '-');

        $this->articleRepository->updateArticle($values['articleId'], array(
            'title_'.$this->lang => $values['title'],
            'uri' => $asciiTitle,
            'perex_'.$this->lang => $values['perex'],
            'text_'.$this->lang => $values['text'],
            'updatetime' => new \DateTime()
        ));

        //Adjusted value for passcode
        if((empty($values['passcode']) && $values['state'] != 'locked') || 
          (!empty($values['passcode']) && $values['state'] == 'public') ||
          (!empty($values['passcode']) && $values['state'] == 'news')) {
            $passcode = null;
            $this->flashMessage('I have changed the article and passcode has been removed.', 'success');
        } else if(empty($values['passcode']) && $values['state'] == 'locked') {
            $passcode = $values['oldPasscode'];
            $this->flashMessage('I have changed the article. Passcode is same.', 'success');
        } else {
            $passcode = $values['passcode'];
            $this->flashMessage('I have changed the article. Passcode is '.$passcode.'.', 'success');
        }
        
        //Adjust user ID for Admins
        if($this->user->isInRole('admin')) {
            $userId = null;
        } else {
            $userId = $values['userId'];
        }
        
        //Connect article and pages
        $this->articlePageRepository->updateArticlePageById($values['id'], array(
            'state' => $values['state'],
            'passcode' => $passcode,
            'article_id' => $values['articleId'],
            'page_id' => $values['pages'],
            'user_id' => $userId
        ));
        
        $this->redirect('default', $values['pages']);
   } 

    //Component for selection of files. 
    public function createComponentFileSelect() {
        $control = $this->fileSelectControlFactory->create();
        $control->setArticleId($this->article->article_id);
        $control->setArticleUserId($this->article->user_id);
        return $control;
    }
    
    //Component for file creation.
    protected function createComponentFileNew() {
        $control = $this->fileNewControlFactory->create();
        return $control;
    }
    
   /**
    * Component for file management.
    * @param string $name
    */
   protected function createComponentFileManagementForm($name) {
        $form = new Form($this, $name);
        $select = $form->addContainer('select');
        foreach ($this->fileId as $item) {
                $select->addCheckbox($item->id)
                        ->setAttribute('onchange', 'return elementEnable("data-wespr-check", "data-slave")')
                        ->setAttribute('data-wespr-check', 'check')
                        ->setAttribute('class', 'checkbox');
        }
        
        if(!empty($this->article)) {
            $form->addHidden('articleId')->setValue($this->article['article_id']);
        }
        
        $form->addSubmit('delete', 'X')
                ->setAttribute('title','Remove selected items from article?')
                ->setAttribute('data-slave', 'background-color: rgb(200,200,200); color: rgb(230,230,230); cursor: inherit; border-color: rgb(180,180,180);')
                ->setAttribute('style', 'background-color: rgb(200,200,200); color: rgb(230,230,230); cursor: inherit; border-color: rgb(180,180,180);')
                ->setAttribute('onclick', 'return warning(this)')
                ->setDisabled()
                ->onClick[] = array($this, 'fileManagementDeleteSubmitted');

        $form->addButton('add', '+')
                ->setAttribute('title','Přidat soubory k článku?')
                ->setAttribute('data-fancybox-href', $this->link('Article:edit#add-file', $this->id))
                ->setAttribute('class', 'component fancybox.inline');
        
        $form->addButton('selectAll', 'all')
                ->setAttribute('onclick', 'return checkAll("data-slave", "data-wespr-check")');
   }

   public function fileManagementDeleteSubmitted(Nette\Forms\Controls\SubmitButton $button) {
        $values = $button->form->getValues(true);

        $keys = array_keys(array_filter((array) $values['select']));
        foreach ($keys as $fileId) {
            $this->articleFileRepository->deleteArticleFile($fileId, $values['articleId']);
        }

       $this->flashMessage('Files have been removed from the article..', 'success');

       if($this->isAjax()) {
            $button->form->setValues(array(), true);
            $this->redrawControl('table');
        } else {
            $this->redirect('this');
        }
   }

   /** 
    * Translation of article. 
    */
    public function actionLanguage($id, $langTranslation) {
        $this->langTranslation = $langTranslation;
        
        $this->translationArticle = $this->articleRepository->findTranslation($langTranslation, $id)->fetch();
    }

    public function renderLanguage($id, $langTranslation) {
        //Read article.
        $this->template->id = $id;

        //Selected language for translate.
        $this->template->lang  = $langTranslation;
    }
    
    protected function createComponentTranslationForm($name) {

        if(!empty($this->translationArticle)) {
            /** @var array $emptyArticle empty values for form */
            $emptyArticle = array (
            'titleDefault' => $this->translationArticle["title_".$this->lang],
            'titleTranslation' => $this->translationArticle["title_".$this->langTranslation],
            'perexDefault' => $this->translationArticle["perex_".$this->lang],
            'perexTranslation' => $this->translationArticle["perex_".$this->langTranslation],
            'textDefault' => $this->translationArticle["text_".$this->lang],
            'textTranslation' => $this->translationArticle["text_".$this->langTranslation],
            'id' => $this->translationArticle['id']
            );
         }

        $form = new Form($this, $name);
        $form->addText('titleDefault','Titulek*')->setAttribute('size', 71)->addRule(Form::FILLED, 'Je nutné zadat název článku.')->setDisabled(true);
        $form->addText('titleTranslation','Titulek')->setAttribute('size', 71);
        $form->addTextArea('perexDefault','Perex')->setAttribute('cols', 53)->setAttribute('rows', 5)->setDisabled(true);
        $form->addTextArea('perexTranslation','Perex')->setAttribute('cols', 53)->setAttribute('rows', 5);
        $form->addTextArea('textDefault','Text*')->setAttribute('cols', 53)->setAttribute('rows', 5)->addRule(Form::FILLED, 'Je nutné zadat text článku.')->setDisabled(true);
        $form->addTextArea('textTranslation','Text')->setAttribute('cols', 53)->setAttribute('rows', 5);
        $form->addHidden('id');

        if(!empty($this->translationArticle)) {
            $form->setDefaults($emptyArticle);
        }
        
        $form->addSubmit('translation', 'Uložit překlad')->setAttribute('class','main-button')->setAttribute('title','Uloží překlad článku.');
        $form->onSuccess[] = array($this, 'translationFormSubmitted');
        
        $presenter = $this;
        $form->addSubmit('cancel', 'Zpět')->setAttribute('class','add-button')->setValidationScope(FALSE)->setAttribute('title','Zruší úpravy článku.')
                         ->onClick[] = function () use ($presenter) {
                             $presenter->flashMessage('Překlad článku jsem zrušil.', 'success');
                             $presenter->redirect('default');
                         };
        }

    public function translationFormSubmitted(Form $form) {

        $this->articleRepository->updateArticle($form->values->id, array(
            'title_'.$this->langTranslation => $form->values->titleTranslation,
            'perex_'.$this->langTranslation => $form->values->perexTranslation,
            'text_'.$this->langTranslation => $form->values->textTranslation,
            'updatetime' => new \DateTime()
        ));

        $this->flashMessage('Článek se podařilo přeložit.', 'success');
        $this->redirect('default', $this->translationArticle['id']);
    } 

}