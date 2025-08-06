<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\WesprModule\FileModule\Repository;

/**
 * Description of FileData
 *
 * @author David Ehrlich
 */
class FileData extends \Nette\Object {
    private $fileObject;
    private $name;
    private $sanitizedName;
    private $size;
    private $fileType;
    private $path;
    private $realPath;
    private $lastFolder;
    
    public function __construct($fileObject) {
        $this->fileObject = $fileObject;

        if(get_class($fileObject) == 'SplFileInfo') {
            //Wrap data from PHP SplFile object.
            $this->wrapSplFileInfoData();
        } else {
            //Wrap data from Nette POST request.
            $this->wrapFileUploadData();
        }
    }
    
    private function wrapSplFileInfoData() {
        $this->name = $this->fileObject->getFileName();
        $this->size = $this->fileObject->getSize();
        $this->fileType = mime_content_type($this->fileObject->getPathname());
        $this->path = $this->fileObject->getPath();
        $this->realPath = $this->fileObject->getRealPath();
        $this->setLastFolder();
    }
    
    private function wrapFileUploadData() {
        if ($this->fileObject->isOk()) {
            $this->name = $this->fileObject->getName();
            $this->sanitizedName = $this->fileObject->getSanitizedName();
            $this->size = $this->fileObject->getSize();
            $this->fileType = $this->fileObject->getContentType();
            $this->path = $this->fileObject->getTemporaryFile();
            $this->realPath = null;
            $this->setLastFolder();
        } else {
            return false;
        }
    }
    
    private function setLastFolder() {
        $lastFolder = substr(strrchr($this->path, '\\'), 1);
        
        (!empty($lastFolder)) ? $this->lastFolder = $lastFolder : $this->lastFolder = null;
    }
    
    public function getName() {
        return $this->name;
    }
    
    public function getSanitizedName() {
        return $this->sanitizedName;
    }
    
    public function getSize() {
        return $this->size;
    }
    
    public function getPath() {
        return $this->path;
    }
    
    public function getLastFolder() {
        return $this->lastFolder;
    }
    
    public function getFileType() {
        return $this->fileType;
    }
    
    public function getFileData() {
        $fileData = array(
            'name' => $this->name, 
            'sanitizedName' => $this->sanitizedName, 
            'size' => $this->size, 
            'fileType' => $this->fileType, 
            'path' => $this->path,
            'realPath' => $this->realPath,
            'lastFolder' => $this->lastFolder
        );
                
        return serialize($fileData);
    }
}
