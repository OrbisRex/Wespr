<?php
/**
 * WESPR component: Upload new files.
 *
 * @author      David Ehrlich
 * @package     WESPR:FileModule
 * @version     1.0
 * @copyright   (c) 2015, David Ehrlich
 * @license     http://opensource.org/licenses/MIT MIT
 */

namespace App\WesprModule\FileModule;

use Nette;
use App;
use OrbisRex\Wespr;
use OrbisRex\Multimedia\Image;
use App\WesprModule\Repository;
use App\WesprModule\FileModule;
use Nette\Application\UI\Form;

class FileNewControl extends FileManagerControl 
{
    private $files;
    
    public function render() {
        $this->template->setFile(__DIR__.'/FileNew.latte');
        $this->template->setTranslator($this->translator);

        //Solution for snippet
        $this->template->form = $this->template->_form = $this['newFileForm'];
        
        $newFiles = $this->presenter->getSession('files');
        \Tracy\Debugger::barDump($newFiles->files);

        $this->template->render();
    }
    
    public function renderScripts() {
        $this->template->setFile(__DIR__.'/FileNewScripts.latte');
        $this->template->render();
    }
    
    /**
     * Save files. Using JQuery.
     */
    protected function createComponentNewFileForm($name)
    {
        //Set user variables (ID, Data Path).
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
        if(!$this->userId)
        {
            $groups = $this->groupRepository->findAdminGroups($this->lang)->fetchPairs('id', 'alias_'.$this->lang);
        }
        else
        {
            $groups = $this->groupRepository->findUserGroups($this->lang, $this->userId)->fetchPairs('id', 'alias_'.$this->lang);
        }
        
        $form = new Form($this, $name);
        $form->setTranslator($this->translator);
        $form->addUpload("upload", "Files", true)
                //->addRule(Form::MAX_FILE_SIZE, 'Sorry, but the file is too big. Maximal size is 4MB.', 4000 * 1024 /*byte*/)
                ->setAttribute('style','display: none;')
                ->setAttribute('id','upload');
        $form->addButton('uploadButton', 'Select or Drop Files')->setAttribute('id', 'dropbox')->setAttribute('class', 'upload-button');
        $form->addSelect('groups', 'Group', $groups)->setPrompt('Choose a group.')->addRule(Form::FILLED, 'It is neccesary select a group. If you do not have any, please create a group in Settings.')->setAttribute('class','select_short');
        $form->addSelect('state', 'Group* & State', $selectState)->setAttribute('class','select_short');
        $form->addText('tags','Tags');
        $form->addText('description','Description');
        
        $form->addSubmit('save', 'Save')->setAttribute('class','main-button');
        $form->onSuccess[] = array($this, 'newFileFormSubmitted');
        
        $presenter = $this->presenter;
        $form->addSubmit('cancel', 'Cancel')->setValidationScope(FALSE)->setAttribute('title','Cancel all changes.')->setAttribute('class','add-button')
                         ->onClick[] = function () use ($presenter) {
                             $presenter->flashMessage('You have canceled the addition of new files.', 'success');
                             $presenter->redirect('fileNew:cancel!');
                         };
    }
    
    public function handleSave()
    {
        //Create session sotorage for files data.
        $newFiles = $this->presenter->getSession('files');
        $files = $this->presenter->getRequest()->getFiles();
        \Tracy\Dumper::dump($files);
        
        //Create temporary copies of files.
        foreach($files['newFiles'] as $file)
        {
            if(!move_uploaded_file($file->getTemporaryFile(), $file->getTemporaryFile()))
            {
                //echo json_encode(array('msg'=>'Files are not ready to save. Please, upload them again.'));
                $newFiles->remove();
                //unset($files);
                die();
            }
            else
            {
                $newFiles->files = $files;
                //unset($newFiles->files);
                //echo json_encode(array('msg'=>'File is ready to save.'));
            }
        }
    }
    
    public function handleCancel()
    {
        $newFiles = $this->presenter->getSession('files');
        if(!empty($newFiles))
        {
            $newFiles->remove();
        }
        
        $this->presenter->redirect('this');
    }
           
    public function newFileFormSubmitted($form) {
        if ($form['save']->isSubmittedBy())
        {
            //Prepare session            
            $newFiles = $this->presenter->getSession('files');
            //\Tracy\Debugger::barDump($newFiles);
            //Read file data from session.
            $this->files = $newFiles->files;
            unset($newFiles->files);
            //\Tracy\Debugger::barDump($this->files['newFiles']);
            
            if($this->files['newFiles'] !== NULL)
            {
                //Upload files to the folder
                $uploadFolder = '../upload/'.time();
                foreach($this->files['newFiles'] as $file)
                {
                    if ($file->isOk())
                    {
                        $uploadPath = $uploadFolder.'/'.strtolower($file->getSanitizedName());
                        $file->move($uploadPath);
                    }
                }

                $importFiles = $this->previewImportFiles($uploadFolder);
                //\Tracy\Debugger::barDump($importFiles);

                foreach($importFiles as $path => $file)
                {
                    $filesData[$path] = $file->getFileData();
                }

                $filesInfo = $this->importFiles($filesData);
                \Tracy\Debugger::barDump($filesInfo);

                foreach($filesInfo as $fileInfo)
                {
                    //Insert File info
                    $this->insertFileInfo($fileInfo, $this->lang, $this->userId, $form);

                    //Insert Meta info
                    $this->insertFileMeta($fileInfo);

                    //Insert tags
                    $exifType = array('FileName', 'MimeType', 'Make', 'Model', 'Orientation', 'DateTime', 'ExposureTime', 'FNumber', 'ISOSpeedRatings');
                    $exifInfo = $this->extractExif($exifType, $fileInfo);
                    $tags = $this->prepareTags($exifInfo, $form);
                    
                    $this->insertTags($tags, $this->lang, $this->userId);
                    $this->joinFileTag($this->userId);

                    //Insert File group
                    $this->insertFileGroup($this->userId, $form);
                }


                if(count($this->files) == 1)
                {
                    $this->presenter->flashMessage('File has been uploaded successfuly.', 'success');
                }
                else
                {
                    $this->presenter->flashMessage('Files have been uploaded successfuly.', 'success');
                }
                
                $this->presenter->redirect('File:Default');
            }
            else
            {
                $this->presenter->flashMessage('No file selected or file exceed limits. Please upload at least 1 file about maximal size 4MB.', 'info');
                $this->redirect('this');
            }

            
        } else {
            $this->presenter->flashMessage('Ups! The upload faild. Try it again please.', 'error');
            $this->redirect('this');
        }
    }
}