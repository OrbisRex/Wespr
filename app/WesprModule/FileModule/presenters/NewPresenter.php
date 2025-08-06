<?php
/**
 * New file
 *
 * @author David Ehrlich
 */

namespace App\WesprModule\FileModule;

use Nette;
use App;
use Nette\Application\UI\Form;

class NewPresenter extends App\SecuredPresenter 
{
    /** @var String Source folder for import. */
    private $uploadFolder;
    /** @var Array Files SplFileInfo. */
    private $files;
    private $fileId;

    /**
     * Draw sub-menu area on the page.
     * @return \App\FileMenuControl
     */
    public function createComponentFileMenu() 
    {
        return new FileMenuControl($this->translator, $this->lang, $this->groupRepository, $this->user);
    }
    
    public function renderDefault() 
    {
        //Solution for snippet
        $this->template->form = $this->template->_form = $this['newFileForm'];
    }
    
    /**
     * Save files. Using JQuery.
     */
    public function handleSave() 
    {
        //Create session sotorage for files data.
        $newFiles = $this->presenter->getSession('files');
        $files = $this->presenter->getRequest()->getFiles();
        \Tracy\Dumper::dump($files['newFiles']);
        //Create temporary copies of files.
        foreach($files['newFiles'] as $file) {
            if(!move_uploaded_file($file->getTemporaryFile(), $file->getTemporaryFile())) {
                echo json_encode(array('msg'=>'Files are not ready to save. Please, upload them again.'));
                unset($newFiles->files);
                die();
            } else {
                $newFiles->files = $files;
                \Tracy\Dumper::dump($files);
                echo json_encode(array('msg'=>'Files are ready to save.'));
            }
        }
    }
    
    protected function createComponentNewFileForm($name) 
    {
        //Set user variables (ID, Data Path).
        //$this->getUserId($this->presenter->user);
        
        /** @todo Move to DB / config */
        if($this->presenter->user->storage->identity->state == 'public') {
            $selectState = array('nonpublic' => 'nonpublic - not visible', 'public' => 'public - visible');
        } else {
            $selectState = array('nonpublic' => 'nonpublic - not visible');
        }
        
        //Read groups for select in form.
        if(!$this->userId) {
            $groups = $this->groupRepository->findAdminGroups($this->lang)->fetchPairs('id', 'alias_'.$this->lang);
        } else {
            $groups = $this->groupRepository->findUserGroups($this->lang, $this->userId)->fetchPairs('id', 'alias_'.$this->lang);
        }
        
        $form = new Form($this, $name);
        $form->setTranslator($this->translator);
        $form->addUpload("upload", "Files", true)
                ->addRule(Form::MAX_FILE_SIZE, 'Sorry, but the file is too big. Maximal size is 4MB.', 4000 * 1024 /*byte*/)
                ->setAttribute('style','display: none;')
                ->setAttribute('id','upload');
        $form->addButton('uploadButton', 'Select or Drop Files')->setAttribute('id', 'dropbox')->setAttribute('class', 'upload-button');
        $form->addSelect('groups', 'Group', $groups)->setPrompt('Choose a group.')->addRule(Form::FILLED, 'It is neccesary select a group. If you do not have any, please create a group in Settings.')->setAttribute('class','select_short');
        $form->addSelect('state', 'Group* & State', $selectState)->setAttribute('class','select_short');
        $form->addText('tags','Tags');
        $form->addText('describtion','Description');
        
        $form->addSubmit('save', 'Save')->setAttribute('class','main-button');
        $form->onSuccess[] = array($this, 'newFileFormSubmitted');
        
        $presenter = $this->presenter;
        $form->addSubmit('cancel', 'Cancel')->setValidationScope(FALSE)->setAttribute('title','Cancel all changes.')->setAttribute('class','add-button')
                         ->onClick[] = function () use ($presenter) {
                             $presenter->flashMessage('You have canceled addition a new file.', 'success');
                             $presenter->redirect('this');
                         };
    }
           
    public function newFileFormSubmitted($form) 
    {
        if ($form['save']->isSubmittedBy()) {
            $values = $form->getValues();
            
            //Prepare session            
            $newFiles = $this->presenter->getSession('files');
            \Tracy\Debugger::barDump($newFiles);
            //Read file data from session.
            $files = $newFiles->files;
            unset($newFiles->files);
            \Tracy\Debugger::barDump($files['newFiles']);
            
            if($files['newFiles'] === null) {
               throw new \Nette\Application\ApplicationException; 
            }
            
            //Upload files to the folder
            $uploadFolder = '../upload/'.time();
            foreach($files['newFiles'] as $file) {

                if ($file->isOk()) {
                    $uploadPath = $uploadFolder.'/'.strtolower($file->getSanitizedName());
                    $file->move($uploadPath);
                }
            }
            
            $importFiles = $this->fileRepository->previewImportFiles($uploadFolder);
            \Tracy\Debugger::barDump($importFiles);
            
            foreach($importFiles as $path => $file) {
                $filesData[$path] = $file->getFileData();
            }
            
            $filesInfo = $this->fileRepository->importFiles($filesData);
            
            foreach($filesInfo as $fileInfo) {
                //Insert File info
                $this->fileRepository->insertFileInfo($fileInfo, $this->lang, $this->userId);

                //Insert Meta info
                $this->fileRepository->insertFileMeta($fileInfo);

                //Insert tags
                $exifType = array('Make', 'Model', 'Orientation', 'DateTime', 'ExposureTime', 'FNumber', 'ISOSpeedRatings');
                $exifInfo = $this->fileRepository->extractExif($exifType, $fileInfo);
                $tags = $this->fileRepository->prepareTags($exifInfo);
                $this->fileRepository->insertTags($tags, $this->lang);

                //Insert File group
                $groupId = $this->fileRepository->createGroup($fileInfo);
                $this->fileRepository->insertFileGroup($groupId);
            }


            if(count($this->files) == 1) {
                $this->flashMessage('File has been uploaded successfuly.', 'success');
            } else {
                $this->flashMessage('Files have been uploaded successfuly.', 'success');
            }

            $this->redirect('File:Default', 1);
            
        } else {
            $this->flashMessage('Ups! The upload faild. Try it again please.', 'error');
            $this->redirect('this');
        }
    }
}