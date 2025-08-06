<?php
/**
 * Description of FileSelect
 *
 * @author David Ehrlich
 */

namespace App\WesprModule\FileModule;

use Nette,
    App,
    OrbisRex\Wespr,
    App\WesprModule\Repository,
    App\WesprModule\FileModule,
    App\WesprModule\TextModule,
    Nette\Application\UI\Form,
    Nette\Forms\Controls\SubmitButton;

class FileEditControl extends App\WesprModule\BasicControl {

    /** @var articleFileRepository connect FileRepository. */
    private $articleFileRepository;
    private $fileRepository;
    private $tagRepository;
    private $fileGroupRepository;
    private $fileTagRepository;
    
    /** @var string File ID for query to DB in IN clausule. */
    private $fileIds;
    private $files;
    private $group;
    private $tagIds;
    private $select;

    public function __construct(Wespr\Translator $translator, FileModule\Repository\ModifyFileRepository $fileRepository, Repository\ModifyTagRepository $tagRepository, FileModule\Repository\ModifyFileGroupRepository $fileGroupRepository, FileModule\Repository\ModifyFileTagRepository $fileTagRepository, TextModule\Repository\ModifyArticleFileRepository $articleFileRepository){
        parent::__construct($translator);
        
        $this->fileRepository = $fileRepository;
        $this->tagRepository = $tagRepository;
        $this->fileGroupRepository = $fileGroupRepository;
        $this->fileTagRepository = $fileTagRepository;
        $this->articleFileRepository = $articleFileRepository;
    }
    
    public function setFileIds(array $fileIds) {
        $this->fileIds = $fileIds;
    }
    
    public function setGroup(Nette\Database\Table\Selection $group) {
        $this->group = $group;
    }
    
    public function setFiles(Nette\Database\Table\Selection $files) {
        $this->files = $files;
    }
    
    public function getFiles() {
        return $this->files;
    }
    
    private function readFiles() {
        \Tracy\Debugger::log($this->fileIds);

        if(!empty($this->fileIds)) {
            $this->files = $this->fileGroupRepository->findEditFiles($this->lang, $this->fileIds);
        }
    }

    public function render() {
        $this->readFiles();
        
        $this->template->setFile(__DIR__.'/FileEdit.latte');
        $this->template->setTranslator($this->translator);
        $this->template->files = $this->files;
        $this->template->countFiles = count($this->files);
        //Solution for snippet
        $this->template->form = $this->template->_form = $this['actionFileEdit'];
        
        $this->template->render();
    }

    //Form for edit files.
    protected function createComponentActionFileEdit($name) {
        //Set user variables (ID, Data Path).
        $this->getUserId($this->presenter->user);        
        //Read Files for form.
        $this->readFiles();
        
        //dump($this->files->fetchAll());
        $form = new Form($this, $name);
        $this->select = $form->addContainer('select');
        $state = $form->addContainer('state');
        $fileName = $form->addContainer('fileName');
        $tags = $form->addContainer('tags');
        $description = $form->addContainer('description');
        
        if(!empty($this->files)) {
            foreach ($this->files as $item) {
                $this->select->addCheckbox($item->file_id)
                        ->setAttribute('onchange', 'return elementEnable("data-wespr-check", "data-slave")')
                        ->setAttribute('data-wespr-check', 'check');

                $state->addHidden($item->file_id)->setValue($item->state);

                $fileName->addText($item->file_id, 'Name and Tags')
                            ->addRule(Form::FILLED, 'Fill in file name, please.')
                            ->setAttribute('class','text-short')
                            ->setAttribute('oninput', 'return elementEnable("data-wespr-text", "data-slave-save")')
                            ->setAttribute('data-wespr-text', 'true')
                            ->setAttribute('placeholder', 'File name.')
                            ->setDefaultValue($item->name);

                //Get tags
                $oldTags = $this->fileTagRepository->findFileTags($this->lang, $item->file_id)->fetchPairs('id', 'tag');
                $tagsString = implode(' ', $oldTags);

                $tags->addText($item->file_id, 'Tags')
                            ->setAttribute('class','text-short')
                            ->setAttribute('oninput', 'return elementEnable("data-wespr-text", "data-slave-save")')
                            ->setAttribute('data-wespr-text', 'true')
                            ->setAttribute('placeholder', 'Tags for file.')
                            ->setDefaultValue($tagsString);

                $description->addText($item->file_id, 'Description')
                            ->setAttribute('oninput', 'return elementEnable("data-wespr-text", "data-slave-save")')
                            ->setAttribute('data-wespr-text', 'true')
                            ->setAttribute('placeholder', 'Short file description.')
                            ->setDefaultValue($item->describe);
            }
        }
        
        $form->addSubmit('delete', 'X')
                ->setAttribute('class','auto')
                ->setAttribute('title','Delete items?')
                ->setAttribute('data-slave', 'background-color: rgb(200,200,200); color: rgb(230,230,230); cursor: inherit; border-color: rgb(180,180,180);')
                ->setAttribute('style', 'background-color: rgb(200,200,200); color: rgb(230,230,230); cursor: inherit; border-color: rgb(180,180,180);')
                ->setAttribute('onclick', 'return warning(this)')
                ->setDisabled()
                ->onClick[] = array($this, 'actionItemDeleteSubmitted');
        
        if($this->presenter->user->storage->identity->state == 'public') {
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
        
        $form->addSubmit('save', 'save')
                ->setAttribute('class','auto')
                ->setAttribute('title','Save changes.')
                ->setAttribute('data-slave-save', 'background-color: rgb(200,200,200); color: rgb(230,230,230); cursor: inherit; border-color: rgb(180,180,180);')
                ->setAttribute('style', 'background-color: rgb(200,200,200); color: rgb(230,230,230); cursor: inherit; border-color: rgb(180,180,180);')
                ->setDisabled()
                ->onClick[] = array($this, 'actionItemSaveSubmitted');
    }

   public function actionItemDeleteSubmitted(Nette\Forms\Controls\SubmitButton $button) {
        $values = $button->form->getValues(true);
        $keys = array_keys(array_filter($values['select']));
        
        foreach ($keys as $id) {
            $this->fileGroupRepository->updateFileGroup('file_id', $id, array('state' => null));
        }
        
       $this->presenter->flashMessage('I have moved selected items into the bin.', 'success');
       
        if($this->presenter->isAjax()) {
            $this->readFiles();
            $button->form->setValues(array(), true);
            $this->redrawControl('file-edit');
        } else {
            $this->redirect('this');
        }
   }
   
   public function actionItemPublishSubmitted(Nette\Forms\Controls\SubmitButton $button) {      
        /** @var integer cyklus foreach */
        $i=0;
        
        //dump($button->form->getValues(true));

        $values = $button->form->getValues(true);
        $keys = array_keys(array_filter($values['select']));
        
        foreach ($keys as $id) {
             if($values['state'][$keys[$i++]] == 'nonpublic') {$state = 'public';} else {$state = 'nonpublic';}
             $this->fileGroupRepository->updateFileGroup('file_id', $id, array('state' => $state));
        }
        
        $this->presenter->flashMessage('Upravil jsem úspěšně stav položek.', 'success');
        
        if($this->presenter->isAjax()) {
            $this->readFiles();
            $this->cleareFormFields($button->form, array('select'));
            $this->redrawControl('file-edit');
        } else {
            $this->redirect('this');
        }
   }
   
   public function actionItemSaveSubmitted(SubmitButton $button) {      
        $values = $button->form->getValues(true);
        
        $files = $this->files->fetchAll();
        foreach ($files as $item) {
            
            if(!empty($values['fileName'][$item->file_id])) {
                $name = $values['fileName'][$item->file_id];
            } else {
                $name = null;
            }
            
            $this->fileRepository->fileUpdate('id', $item->file_id, array(
                'name_'.$this->lang => $name,
                'describe_'.$this->lang => $values['description'][$item->file_id]
            ));
            
            //Edit tags
            if(!empty($values['tags'][$item->file_id])) {
                $this->insertTags($values['tags'][$item->file_id], $item->file_id);
                $this->joinFileTag($item->file_id);
                $this->removeFileTag($item->file_id);
            } else {
                $this->removeFileTag($item->file_id);
            }
        }
        
        $this->presenter->flashMessage('I have changed files data.', 'success');

        if($this->presenter->isAjax()) {
            $this->readFiles();
            $this->cleareFormFields($button->form, array('select'));
            $this->redrawControl('file-edit');
        } else {
            $this->redirect('this');
        }
   }
   
    /** Private functions */
    private function insertTags($tags, $fileId) {
        if(!strstr($tags, ',')) {
            $tags = explode(' ', $tags);
        } else {
            $tags = explode(',', $tags);
        }
        
        foreach($tags as $tag) {
            
            //Search for inserted tags. If doesn't exist insert new.
            $trimedTag = trim($tag);
            $oldTag = $this->tagRepository->findTag($this->lang, $trimedTag);
            
            if($oldTag->count() === 0){
                $this->tagIds[$fileId][] = $this->tagRepository->insertTag(array(
                    'tag_'.$this->lang => $trimedTag,
                    'inserttime' => new \DateTime(),
                    'user_id' => $this->userId
                ))->getPrimary(); //Get inserted ID.
            } else {
                $this->tagIds[$fileId][] = $oldTag->fetch()->id;
            }
        }
    }
    
    private function joinFileTag($fileId) {
        foreach($this->tagIds[$fileId] as $tagId) {
            
            //Search for used tags in file.
            $oldTags = $this->fileTagRepository->findFileTagTags($this->lang, $fileId, $tagId);
            
            if(count($oldTags) === 0) {
                $this->fileTagRepository->insertFileTag(array(
                    'state' => 'public',
                    'file_id' => $fileId,
                    'tag_id' => $tagId,
                    'user_id' => $this->userId
                ));
            }
        }
    }
    
    private function removeFileTag($fileId) {
        //Remove all tags.
        if(empty($this->tagIds[$fileId])) {
            $this->tagIds[$fileId][] = null;
        }
        
        //Search for used tags in file.
        $oldTags = $this->fileTagRepository->findFileTags($this->lang, $fileId)->fetchPairs('file_tag_id', 'tag_id');
        $spareIds = array_diff($oldTags, $this->tagIds[$fileId]);
        
        foreach($spareIds as $fileTagId => $tagId) {
            $this->fileTagRepository->updateFileTag('id', $fileTagId, array(
                'state' => null
            ));
        }
    }
}