<?php
/**
 * WESPR component: Import new files.
 *
 * @author      David Ehrlich
 * @package     WESPR:FileModule
 * @version     1.0
 * @copyright   (c) 2017, David Ehrlich
 * @license     http://opensource.org/licenses/MIT MIT
 */

namespace App\WesprModule\FileModule;

class FileImportControl extends FileManagerControl 
{
    /** @var String Source folder for import. */
    private $uploadFolder;
    /** @var Array Files SplFileInfo. */
    private $files;
    
    /**
     * Fetch data from repository
     */
    private function dataMiner()
    {
        $this->uploadFolder = '../upload';
        
        if($this->presenter->user->isInRole('admin'))
        {
            $this->files = $this->previewImportFiles($this->uploadFolder);
        }
        else
        {
        }
        
        //Prepare and save data to session section.
        if($this->files !== NULL)
        {
            $this->createFileSession($this->presenter->getSession('importFiles'), $this->files);
        }
    }
    
    public function render() {
        $this->dataMiner();
        $this->template->files = $this->files;

        $this->template->setFile(__DIR__.'/FileImport.latte');
        $this->template->setTranslator($this->translator);

        $this->template->render();
    }

    public function handleUpload() {
        //Get current data
        $this->dataMiner();
        $session = $this->presenter->getSession('importFiles');

        $filesInfo = $this->importFiles($session->importFiles);
        
        foreach($filesInfo as $fileInfo) {
            //Insert File info
            $this->insertFileInfo($fileInfo, $this->lang, $this->userId);
            
            //Insert Meta info
            $this->insertFileMeta($fileInfo);
            
            //Insert tags
            $exifType = array('Make', 'Model', 'Orientation', 'DateTime', 'ExposureTime', 'FNumber', 'ISOSpeedRatings');
            $exifInfo = $this->extractExif($exifType, $fileInfo);
            $tags = $this->prepareTags($exifInfo);
            \Tracy\Debugger::barDump($tags);
            $this->insertTags($tags, $this->lang, $this->userId);
            $this->joinFileTag($this->userId);

            //Insert File group
            $this->createGroup($fileInfo, $this->lang, $this->userId);
            $this->insertFileGroup($this->userId);
        }
        
        
        if(count($this->files) == 1) {
            $this->flashMessage('File has been uploaded successfuly.', 'success');
        } else {
            $this->flashMessage('Files have been uploaded successfuly.', 'success');
        }
        
        $this->presenter->redirect('File:Default');
    }
}