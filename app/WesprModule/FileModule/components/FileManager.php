<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\WesprModule\FileModule;

use Nette;
use App;
use OrbisRex\Wespr;
use App\WesprModule\Repository;
use App\WesprModule\FileModule;
use Nette\Utils\Finder;
use App\WesprModule\FileModule\Repository\FileData;
use App\WesprModule\FileModule\Repository\UploadFiles;

/**
 * Description of FileManager
 *
 * @author David Ehrlich
 */
class FileManagerControl extends App\WesprModule\BasicControl
{
    protected $groupRepository;
    protected $fileRepository;
    protected $tagRepository;
    protected $fileGroupRepository;
    protected $fileMetaRepository;
    protected $fileTagRepository;
    protected $fileManager;
    
    protected $fileId;
    private $tagIds;
    private $groupId;
    
    public function __construct(Wespr\Translator $translator, FileModule\Repository\ModifyFileRepository $fileRepository, Repository\ModifyTagRepository $tagRepository, FileModule\Repository\ModifyFileGroupRepository $fileGroupRepository, FileModule\Repository\ModifyFileTagRepository $fileTagRepository, FileModule\Repository\ModifyFileMetaRepository $fileMetaRepository, Repository\ModifyGroupRepository $groupRepository){
        parent::__construct($translator);
        
        $this->fileRepository = $fileRepository;
        $this->tagRepository = $tagRepository;
        $this->fileGroupRepository = $fileGroupRepository;
        $this->fileTagRepository = $fileTagRepository;
        $this->fileMetaRepository = $fileMetaRepository;
        $this->groupRepository = $groupRepository;
    }    
    
    protected function previewImportFiles($path) {
        $filesInfo = null;
        
        $files = Finder::find('*.*')->from($path);
        foreach($files as $filePath => $file) {
            $fileData = new FileData($file);
            $filesData[$filePath] = $fileData;
        }
        
        return $filesData;
    }
    
    protected function importFiles(Array $filesData)
    {
        foreach($filesData as $path => $fileData)
        {
            $fileInfo = unserialize($fileData);
            $file = new UploadFiles($fileInfo);
            $file->uploadFile();
            $filesInfo[] = $file;
            //$this->deleteFiles($path);
        }
        
        return $filesInfo;
    }
    
    protected function deleteFiles($path)
    {
        Nette\Utils\FileSystem::delete($path);
    }
    
    //Find proper place for these functions
    protected function createFileSession($session, array $files)
    {
        if(!empty($files))
        {
            foreach($files as $path => $file)
            {
                $fileData[$path] = $file->getFileData();
            }
            
            $session->importFiles = $fileData;
        }
    }

    protected function insertFileInfo(UploadFiles $fileInfo, $lang, $userId, $form = NULL) {
        //Edit file name
        $removeChars = array('-', '_');
        $name = str_replace($removeChars, ' ', strstr($fileInfo->getData('info', 'originalName'), '.', true));
        
        //Prepare description text
        $description = (isset($form->values->description)) ? $form->values->description : NULL;
        
        $this->fileId = $this->fileRepository->insertFile(array(
            'name_'.$lang => $name,
            'path' => $fileInfo->getData('info', 'original'),
            'type' => $fileInfo->getData('info', 'type'),
            'describe_'.$lang => $description,
            'inserttime' => new \DateTime(),
            'user_id' => $userId
        ));
    }
    
    protected function insertFileMeta(UploadFiles $fileInfo) {
        if(!$this->fileId) {
            return false;
        } 
        else 
        {    
            foreach($fileInfo->getFileMeta() as $fileType => $fileData) 
            {
                $this->fileMetaRepository->insertFileMeta(array(
                    'type' => $fileType,
                    'path' => $fileData, /** @todo Move over to data only */
                    'data' => $fileData,
                    'file_id' => $this->fileId
                ));
            }
        }
    }
    
    protected function extractExif(Array $parameters, UploadFiles $fileInfo)
    {
        $exif = $fileInfo->getExifInfo();
        if(is_array($exif))
        {

            foreach($parameters as $parameter)
            {
                if(array_key_exists($parameter, $exif))
                {
                    $exctractedExif[$parameter] = $exif[$parameter];
                }
                else
                {
                    $exctractedExif[] = NULL;
                }
            }
        }
        else
        {
            $exctractedExif[] = NULL;
        }
        
        return $exctractedExif;
    }

    protected function prepareTags(array $exif, $form = NULL) 
    {
        //EXIF DateTime
        if(array_key_exists('DateTime', $exif)) 
        {
            $timeTags = $this->tagRepository->timeToTag($exif['DateTime']);
            
            //Remove DateTime from tags.
            unset($exif['DateTime']);
            
            foreach($timeTags as $parameter => $tag) {
                $exif[$parameter] = $tag;
            }
        }
        else
        {
            $tags = array();
        }
        
        //NETTE Form values
        if((isset($form->values->tags)) && (!strstr($form->values->tags, ','))) 
        {
            $tags = explode(' ', $form->values->tags);
        } 
        else if (isset($form->values->tags))
        {
            $tags = explode(',', $form->values->tags);
        } 
        else
        {
            $tags = array();
        }
        
        return array_merge($exif, $tags);
    }

    protected function insertTags(array $tags, $lang, $userId) 
    {
        foreach($tags as $tag) 
        {
            $trimedTag = trim($tag);
            $oldTag = $this->tagRepository->findTag($lang, $trimedTag);
            
            if($oldTag->count() === 0)
            {
                $this->tagIds[] = $this->tagRepository->insertTag(array(
                    'tag_'.$lang => $trimedTag,
                    'inserttime' => new \DateTime(),
                    'user_id' => $userId
                ));
            } else {
                $this->tagIds[] = $oldTag->fetch()->id;
            }
        }
    }
    
    protected function joinFileTag($userId)
    {
        foreach($this->tagIds as $tagId)
        {
            $this->fileTagRepository->insertFileTag(array(
                'state' => 'public',
                'file_id' => $this->fileId,
                'tag_id' => $tagId,
                'user_id' => $userId
            ));
        }
    }
    
    protected function createGroup(UploadFiles $fileInfo, $lang, $userId) {
        $alias = $fileInfo->getData('info', 'lastFolder');
        
        if($alias !== null) {
            $name = urlencode($alias);
            $group = $this->groupRepository->findGroupByName($lang, $name, $userId)->fetch();
            
            if(!$group) {
                $this->groupId = $this->groupRepository->insertGroup(array(
                    'name' => $name,
                    'alias_'.$lang => $alias,
                    'type' => 'photo',
                    'state' => 'nonpublic',
                    'inserttime' => new \DateTime(),
                    'user_id' => $userId
                ));
            } else {
                $this->groupId = $group->id;
            }
        } else {
            $this->groupId = 1;
        }
    }

    protected function insertFileGroup($userId, $form = NULL) 
    {
        $state = ($form === NULL) ? 'nonpublic' : $form->values->state;
        $groupId = ($form === NULL) ? $this->groupId : $form->values->groups;
        
        $order = $this->fileGroupRepository->maxFileOrder('order');
        if($order === null)
        {
            $order = 1;
        } 
        else 
        {
            $order += 1;
        }

        //Connect file and page
        $this->fileGroupRepository->insertFileGroup(array(
            'state' => $state,
            'order' => $order,
            'file_id' => $this->fileId,
            'group_id' => $groupId,
            'user_id' => $userId
        ));
    }    
}
