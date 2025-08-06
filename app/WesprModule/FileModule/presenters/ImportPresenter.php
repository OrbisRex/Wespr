<?php
/**
 * Import File
 *
 * @author David Ehrlich
 */

namespace App\WesprModule\FileModule;

use Nette;
use App;

class ImportPresenter extends App\SecuredPresenter {
    /** @var String Source folder for import. */
    private $uploadFolder;
    /** @var Array Files SplFileInfo. */
    private $files;
    private $fileId;

    /**
     * Draw sub-menu area on the page.
     * @return \App\FileMenuControl
     */
    public function createComponentFileMenu() {
        return new FileMenuControl($this->translator, $this->lang, $this->groupRepository, $this->user);
    }
    
    /**
     * Fetch data from repository
     */
    private function dataMiner($id) {
        $this->uploadFolder = '../upload';
        
        if($this->user->isInRole('admin')) {
            $this->files = $this->fileRepository->previewImportFiles($this->uploadFolder);
        } else {
        }
        
        //Prepare and save data to session section.
        $this->fileRepository->createFileSession($this->getSession('importFiles'), $this->files);
    }
    
    public function actionDefault($id) {
        $this->dataMiner($id);
    }

    public function renderDefault($id) {
        $this->dataMiner($id);
        
        $this->template->id = $id;
        $this->template->files = $this->files;
    }
    
    public function actionUpload() {
        $session = $this->getSession('importFiles');

        $filesInfo = $this->fileRepository->importFiles($session->importFiles);
        
        foreach($filesInfo as $fileInfo) {
            //Insert File info
            $this->insertFileInfo($fileInfo);
            
            //Insert Meta info
            $this->insertFileMeta($fileInfo);
            
            //Insert tags
            $exifType = array('Make', 'Model', 'Orientation', 'DateTime', 'ExposureTime', 'FNumber', 'ISOSpeedRatings');
            $exifInfo = $this->extractExif($exifType, $fileInfo);
            $tags = $this->prepareTags($exifInfo);
            $this->insertTags($tags);

            //Insert File group
            $groupId = $this->createGroup($fileInfo);
            $this->insertFileGroup($groupId);
        }
        
        
        if(count($this->files) == 1) {
            $this->flashMessage('File has been uploaded successfuly.', 'success');
        } else {
            $this->flashMessage('Files have been uploaded successfuly.', 'success');
        }
        
        $this->redirect('File:Default', 1);
    }
    
    private function insertFileInfo(Repository\UploadFiles $fileInfo) {
        //Edit file name
        $removeChars = array('-', '_');
        $name = str_replace($removeChars, ' ', strstr($fileInfo->getData('info', 'originalName'), '.', true));
        
        $this->fileId = $this->fileRepository->insertFile(array(
            'name_'.$this->lang => $name,
            'path' => $fileInfo->getData('info', 'original'),
            'type' => $fileInfo->getData('info', 'type'),
            'describe_'.$this->lang => null,
            'inserttime' => new \DateTime(),
            'user_id' => $this->userId
        ));
    }
    
    private function insertFileMeta(Repository\UploadFiles $fileInfo) {
        if(!$this->fileId) {
            return false;
        } else {    
            foreach($fileInfo->getFileMeta() as $fileType => $fileData) {
                
                //Select correct colum in database.
                if($fileType == 'exif') {
                    $fileExif = $fileData;
                    $fileData = null;
                } else {
                    $fileExif = null;
                }
                
                $this->fileMetaRepository->insertFileMeta(array(
                    'type' => $fileType,
                    'path' => $fileData,
                    'data' => $fileExif,
                    'file_id' => $this->fileId
                ));
            }
        }
    }
    
    private function extractExif(Array $parameters, Repository\UploadFiles $fileInfo) {
        $exif = $fileInfo->getExifInfo();
        
        foreach($parameters as $parameter) {
            if(array_key_exists($parameter, $exif)) {
                $exctractedExif[$parameter] = $exif[$parameter];
            } else {
                $exctractedExif[] = null;
            }
        }
        
        return $exctractedExif;
    }
    
    private function prepareTags(array $tags) {
        
        if(array_key_exists('DateTime', $tags)) {
            $timeTags = $this->tagRepository->timeToTag($tags['DateTime']);
            
            //Remove DateTime from tags.
            unset($tags['DateTime']);
            
            foreach($timeTags as $parameter => $tag) {
                $tags[$parameter] = $tag;
            }
        }
        
        return $tags;
    }
    
    private function insertTags(array $tags) {
        
        foreach($tags as $descriptor => $tag) {
            
            $trimedTag = trim($tag);
            $oldTag = $this->tagRepository->findTag($this->lang, $trimedTag);
            
            //Insert new tag if tag doesn't exist otherwise join tag to current one.
            if($oldTag->count() === 0){
                $tagIds[] = $this->tagRepository->insertTag(array(
                    'type' => $descriptor,
                    'tag_'.$this->lang => $trimedTag,
                    'inserttime' => new \DateTime(),
                    'user_id' => $this->userId
                ));
            } else {
                $tagIds[] = $oldTag->fetch()->id;
            }
        }
        
        $this->joinFileTag($tagIds);
    }

    private function joinFileTag($tagIds) {
        foreach($tagIds as $tagId) {
            $this->fileTagRepository->insertFileTag(array(
                'state' => 'public',
                'file_id' => $this->fileId,
                'tag_id' => $tagId,
                'user_id' => $this->userId
            ));
        }
    }
    
    private function createGroup(Repository\UploadFiles $fileInfo) {
        $alias = $fileInfo->getData('info', 'lastFolder');
        
        if($alias !== null) {
            $name = urlencode($alias);
            $group = $this->groupRepository->findGroupByName($this->lang, $name, $this->userId)->fetch();
            
            if(!$group) {
                $groupId = $this->groupRepository->insertGroup(array(
                    'name' => $name,
                    'alias_'.$this->lang => $alias,
                    'type' => 'photo',
                    'state' => 'nonpublic',
                    'inserttime' => new \DateTime(),
                    'user_id' => $this->userId
                ));
            } else {
                $groupId = $group->id;
            }
        } else {
            $groupId = 1;
        }
        
        return $groupId;
    }
    
    private function insertFileGroup($groupId) {
        $order = $this->fileGroupRepository->maxFileOrder('order');
        if($order === null) {
            $order = 1;
        } else {
            $order += 1;
        }

        //Connect file and page
        if(!$this->fileId) {
            return false;
        } else {
            $this->fileGroupRepository->insertFileGroup(array(
                'state' => 'nonpublic',
                'order' => $order,
                'file_id' => $this->fileId,
                'group_id' => $groupId,
                'user_id' => $this->userId
            ));
        }
    }

    
    public function renderUpload() {
        
    }
}