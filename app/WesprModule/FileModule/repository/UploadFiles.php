<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\WesprModule\FileModule\Repository;

use OrbisRex\Multimedia\Image;

/**
 * Description of UploadFiles
 *
 * @author David Ehrlich
 */
class UploadFiles {
    /** @var OrbisRex\Multimedia\Image Image object for image manipulations. */
    private $image;
    
    /** @var String Original file name. */
    private $originalName;
    /** @var String Source path of file. */
    private $realPath;
    /** @var String Original file type. */
    private $type;
    /** @var Integer Original file size. */
    private $size;
    /** @var String Last folder in path. */
    private $lastFolder;

    /** @var String File name used by WESPR. */
    private $fileName;
    /** @var String File path to the upload folder. */
    private $sourcePath;
    /** @var String User folder used by WESPR. Usually placed in the WWW folder. */
    private $destinationPath;
    /** @var String Completed path of file. */
    private $path;
    
    /** @var Array Core information of file. */
    private $fileInfo;
    /** @var Array Meta information of file. */
    private $fileMeta;
    private $exifInfo;
    
    public function __construct(Array $fileData) {
        $this->originalName = $fileData['name'];
        $this->realPath = $fileData['realPath'];
        $this->lastFolder = $fileData['lastFolder'];
        $this->type = $fileData['fileType'];
        $this->size = $fileData['size'];
        
        //Modified variables
        $this->destinationPath = 'data/public/files/';
        $this->fileName = time().'_'.strtolower($fileData['name']);
        $this->path = $this->destinationPath . $this->fileName;
        $this->sourcePath = $fileData['path'].'/'.$fileData['name'];
    }
    
    public function uploadImage() {
        $this->readExif();
        $this->saveImage();
        $this->saveVersions();
        $this->setFileInfo();
    }
    
    public function uploadFile() {
        \Tracy\Debugger::barDump($this->type);
        if($this->type == 'image/jpeg')
        {
            $this->readExif();
            $this->saveImage();
            $this->saveVersions();
            $this->setFileInfo();
        }
        else
        {
            $this->saveFile();
            $this->setFileInfo();
            $this->setMeta();
        }
    }
    
    private function saveFile()
    {
        if(!copy($this->sourcePath, $this->path))
        {
            return FALSE;
        } 
        else
        {
            $this->fileInfo['original'] = $this->path;
        }
    }
    
    private function setFileInfo() {
        $this->fileInfo['originalName'] = $this->originalName;
        $this->fileInfo['name'] = $this->fileName;
        $this->fileInfo['sourcePath'] = $this->sourcePath;
        $this->fileInfo['realPath'] = $this->realPath;
        $this->fileInfo['destinationPath'] = $this->destinationPath;
        $this->fileInfo['type'] = $this->type;
        $this->fileInfo['lastFolder'] = $this->lastFolder;
    }
    
    private function setMeta()
    {
        $this->fileMeta['size'] = $this->size;
        $this->fileMeta['appearance'] = $this->path;
    }

    /** @todo Transfer this part to new class. */
    private function saveImage() {
        $this->image = new Image($this->realPath, $this->sourcePath, $this->destinationPath, $this->fileName, $this->exifInfo);
        $this->fileInfo['original'] = $this->image->saveOriginal('original');
    }
    
    private function saveVersions() {
        //Create smaller file for other operation.
        $this->fileMeta['web'] = $this->image->createWorkingCopy('web', 1920, null, 100);
        //Create new version of image
        $this->fileMeta['prev'] = $this->image->createPreview('prev', 230);
        $this->fileMeta['crop'] = $this->fileMeta['appearance'] = $this->image->createCrop('crop', 400, 400, '30%', '35%', 230, 230); /**/
        $this->fileMeta['cropHigh'] = $this->image->createCrop('cropHigh', 400, 400, '15%', '0%', 150, '100%');
        $this->fileMeta['cropWidth'] = $this->image->createCrop('cropWidth', 400, 400, '0%', '25%', '100%', 150);
    }
    
    private function readExif() {        
        error_reporting(E_ALL & ~E_WARNING); //Temporary settings for bug in read_exif_data (PHP 5.6.28)
        $this->exifInfo = read_exif_data($this->sourcePath);        
        $this->fileMeta['exif'] = serialize($this->exifInfo);
    }
    
    public function getExifInfo() {
        return $this->exifInfo;
    }
    
    public function getData($category, $type) {
        $fileData['info'] = $this->fileInfo;
        $fileData['meta'] = $this->fileMeta;
        
        return $fileData[$category][$type];
    }
    
    public function getFileInfo() {
        return $this->fileInfo;
    }
    
    public function getFileMeta() {
        return $this->fileMeta;
    }
}
