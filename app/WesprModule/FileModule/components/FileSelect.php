<?php
/**
 * WESPR component: Upload/Insert new files to article.
 *
 * @author      David Ehrlich
 * @package     WESPR
 * @version     1.1
 * @copyright   (c) 2015, David Ehrlich
 * @license     http://opensource.org/licenses/MIT MIT
 */

namespace App\WesprModule\FileModule;

use Nette;
use App;
use App\WesprModule;
use App\WesprModule\Repository;
use App\WesprModule\FileModule;
use Nette\Application\UI\Form;
use OrbisRex\Wespr;

class FileSelectControl extends FileManagerControl
{
    
    /** @var ModifyArticleFileRepository Files for article. */
    private $articleFileRepository;
    private $files;
    private $articleId;
    private $articleUserId;
    private $newFileId;
    
    public function __construct(Wespr\Translator $translator, FileModule\Repository\ModifyFileRepository $fileRepository, Repository\ModifyTagRepository $tagRepository, FileModule\Repository\ModifyFileGroupRepository $fileGroupRepository, FileModule\Repository\ModifyFileTagRepository $fileTagRepository, FileModule\Repository\ModifyFileMetaRepository $fileMetaRepository, WesprModule\Repository\ModifyGroupRepository $groupRepository, WesprModule\TextModule\Repository\ModifyArticleFileRepository $articleFileRepository){
        parent::__construct($translator, $fileRepository, $tagRepository, $fileGroupRepository, $fileTagRepository, $fileMetaRepository, $groupRepository);
        
        $this->articleFileRepository = $articleFileRepository;
    }
    
    public function setArticleId($articleId)
    {
        $this->articleId = $articleId;
    }
    
    public function setArticleUserId($userId)
    {
        $this->articleUserId = $userId;
    }
    
    public function setNewFileId($newFileId)
    {
        $this->newFileId = $newFileId;
    }
    
    private function readFiles()
    {
        //Get User ID
        $this->getUserId($this->presenter->user);
        
        if($this->presenter->user->isInRole('admin'))
        {
            $this->files = $this->fileGroupRepository->findAllNoUsedFiles($this->lang, $this->articleId);
        }
        else
        {
            $this->files = $this->fileGroupRepository->findNoUsedUserFiles($this->lang, $this->articleId, $this->userId);
        }
    }
    
    public function render()
    {
        
        $this->readFiles();
        
        $this->template->setFile(__DIR__.'/FileSelect.latte');
        $this->template->setTranslator($this->translator);
        
        //Selection part
        $this->template->files = $this->files;
        $this->template->countFiles = count($this->files);

        //Solution for snippet
        $this->template->form = $this->template->_form = $this['addFileForm'];
        
        $this->template->render();
    }
    
    /* Insert new file form */
    protected function createComponentAddFileForm($name)
    {
        //Read files for form.
        $this->readFiles();
        //Get User ID
        $this->getUserId($this->presenter->user);
        
        /** @todo Move to DB / config */
        if($this->presenter->user->storage->identity->state == 'public')
        {
            $selectState = array('nonpublic' => 'nonpublic - not visible', 'public' => 'public - visible');
        }
        else
        {
            $selectState = array('nonpublic' => 'nonpublic - not visible');
        }
        
        //Read groups for select in form.
        if($this->presenter->user->isInRole('admin'))
        {
            $groups = $this->groupRepository->findAdminGroups($this->lang)->fetchPairs('id', 'alias_'.$this->lang);
        }
        else
        {
            $groups = $this->groupRepository->findUserGroups($this->lang, $this->userId)->fetchPairs('id', 'alias_'.$this->lang);
        }
        
        $form = new Form($this, $name);
        $form->setTranslator($this->translator);
        $form->addHidden('idFile');
        $form->addUpload("path", "Files", true)->addRule(Form::MAX_FILE_SIZE, 'Soubor je bohužel příliš velký. Musí mít maximálně 4 MB.', 4000 * 1024 /*byte*/)->setAttribute('class','upload');
        $form->addSelect('groups', 'Group', $groups)->setPrompt('Choose a group.')->setAttribute('class','select_short')->addConditionOn($form['path'], Form::FILLED)->addRule(Form::FILLED, 'Je nutné zvolit skupinu. Pokud žádnout nemáte, vytvořte jí v nastavení.');
        $form->addSelect('state', 'Group* & State', $selectState)->setAttribute('class','select_short');
        
        //Select part
        $select = $form->addContainer('select');
        $fileState = $form->addContainer('fileState');
        foreach ($this->files as $file)
        {
            $select->addCheckbox($file->file_id)
                    ->setAttribute('onchange', 'return elementEnable("data-wespr-check", "data-slave")')
                    ->setAttribute('data-wespr-check', 'check');
                        
            $fileState->addHidden($file->file_id)->setValue($file->state);
        }
        
        $form->addSubmit('save', 'Save')
                ->setAttribute('class','auto')
                ->setAttribute('title','Select files.');
        $form->onSuccess[] = array($this, 'addFileFormSubmitted');
   }
           
    public function addFileFormSubmitted(Form $form)
    {
        if ($form['save']->isSubmittedBy())
        {
            $values = $form->getValues();
            
            if(!empty($values["path"]))
            {
                $files = $values["path"];
                
                //Upload files to the folder
                $uploadFolder = '../upload/'.time();
                foreach($files as $file)
                {
                    if ($file->isOk())
                    {
                        $uploadPath = $uploadFolder.'/'.strtolower($file->getSanitizedName());
                        $file->move($uploadPath);
                    }
                }
                
                $importFiles = $this->previewImportFiles($uploadFolder);

                foreach($importFiles as $path => $file)
                {
                    $filesData[$path] = $file->getFileData();
                }

                $filesInfo = $this->importFiles($filesData);

                foreach($filesInfo as $fileInfo)
                {
                    //Insert File info
                    $this->insertFileInfo($fileInfo, $this->lang, $this->userId, $form);

                    //Insert Meta info
                    $this->insertFileMeta($fileInfo);

                    //Insert tags and Exif info
                    $exifType = array('Make', 'Model', 'Orientation', 'DateTime', 'ExposureTime', 'FNumber', 'ISOSpeedRatings');
                    $exifInfo = $this->extractExif($exifType, $fileInfo);
                    $tags = $this->prepareTags($exifInfo, $form);
                    $this->insertTags($tags, $this->lang, $this->userId);
                    $this->joinFileTag($this->userId);

                    //Insert File group
                    $this->insertFileGroup($this->userId, $form);

                    //Add File ID to array for article
                    $this->registerNewFile();            
                }
            }
            
            $this->insertFilesToArticle($form);
        }
        else
        {
            $this->flashMessage('Ups! The upload has faild. Try it again please.', 'error');
            $this->redirect('this');
        }
    }
    
    private function registerNewFile()
    {
        if(!empty($this->fileId->id))
        {
            $this->newFileId[$this->fileId->id] = true;
        }
    }
    
    private function insertFilesToArticle(Form $form)
    {      
        //Get ID from values.
        $values = $form->getValues(true);
        
        //Add new files to array for article
        if(!empty($this->newFileId))
        {
            foreach($this->newFileId as $id => $value)
            {
                $values['select'][$id] = $value;
            }
        }
        
        $keys = array_keys(array_filter($values['select']));
        
        foreach ($keys as $id)
        {
            $order = $this->articleFileRepository->max('order', $this->articleId);
            if($order === null)
            {
                $order = 1;
            }
            else
            {
                $order += 1;
            }
            
            $this->articleFileRepository->insertFile(array(
                'order' => $order,
                'state' => 'public',
                'article_id' => $this->articleId,
                'article_user_id' => $this->articleUserId,
                'file_id' => (int)$id,
                'file_user_id' => $this->userId /* Note: Should be file user id, no current user id.*/
               ));
        }
       
       $this->flashMessage('Files have been linked to article.', 'success');
       $this->redirect('this');
   }
}
