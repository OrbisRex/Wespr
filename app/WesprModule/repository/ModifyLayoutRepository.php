<?php
/**
 * Write/Update operation over DB's table Template
 *
 * @author David Ehrlich, 2014
 * @version 1.1
 * @license MIT
 */

namespace App\WesprModule\Repository;

use Nette,
    App,
    App\WesprModule\Repository;

class ModifyLayoutRepository extends Repository\LayoutRepository {
    /** @var string Directory for Layout */
    private $directory;
    /** @var string Name for Front Module */
    private $frontModuleName;
    
    public function insertLayout($data) {
        return $this->insertData($data);
    }
    
    public function updateLayout($coll, $value, $data) {
        return $this->updateByColl($coll, $value, $data);
    }
    
    /*Various methodes*/
    public function sanitizeName($text) {
        $removeChars = array('-', '_');
        return str_replace($removeChars, ' ', $text);
    }
    
    public function readDirectory($directory) {
        $contentDir = array_diff(scandir($directory), array('..', '.'));
        
        if(empty($contentDir)) {
            $dirs = array();
        }
        
        foreach($contentDir as $itemDir) {
            if(is_dir($directory.$itemDir)) {
                $dirs['dirs'][$directory.$itemDir] = $itemDir;
            } else if(is_file($directory.$itemDir)) {
                $dirs['files'][$directory.$itemDir] = $itemDir;
            } else {
                $dirs = array();
            }
        }
        
        return $dirs;
    }
    
    public function setDirectory($files) {

        foreach($files as $file) {
            if($this->fileType($file) == '.php') {
                $fileName = strstr($file->getName(), '.', true);
                $this->directory = strstr($fileName, 'Presenter', true);
            }
        }
        
        return $this->directory;
    }
    
    public function setFrontModuleName($name) {
        return $this->frontModuleName = $name;
    }
    
    
    public function fileType($file) {
        return strstr($file->getName(), '.', false);
    }
    
    public function countValidation($files) {
        $state = array('presenter' => 0, 'latte' => 0, 'style' => 0, 'other' => 0);
        $presenter = 0;
        $latte = 0;
        $style = 0;
        $other = 0;
        
        foreach($files as $file) {
            
            if(strstr($file->getName(), 'Presenter.php') == 'Presenter.php') {
                $state['presenter'] = ++$presenter;
            } else if ($this->fileType($file) == '.latte') {
                $state['latte'] = ++$latte;
            } else if($this->fileType($file) == '.css') {
                $state['style'] = ++$style;
            } else {
                $state['other'] = ++$other;
            }
                
        }
        
        return $state;
    }

    public function fileValidation($files) {
        $state = array();
        
        foreach($files as $file) {
            if($this->fileType($file) == '.php') {
                $storeFiles = $this->readDirectory('../app/'.$this->frontModuleName.'/presenters/');
                \Tracy\Debugger::barDump($storeFiles);
                if(!empty($storeFiles)) {
                    foreach($storeFiles['files'] as $storeFile) {
                         $state[] = ($storeFile == $file->getName()) ? true : false;
                    }
                } else {
                    $state[] = false;
                }

            } else if ($this->fileType($file) == '.latte' || $this->fileType($file) == '.css'){
                $state[] = false;
            } else {
                $state[] = true;
            }
        }
        
        return $state;
    }
    
    public function getNettename($file) {
        if(($this->fileType($file) == '.latte') && (!strstr($file->getName(), '@'))) {
            
            //Prepare Nette Action
            $netteActionSanitized = $this->sanitizeName(strstr($file->getName(), '.', true));
            
            //Separate Module Name
            $moduleName = strstr($this->frontModuleName, 'Module', true);
            
            $nettename = ':'.$moduleName.':'.$this->directory.':'.$netteActionSanitized;
        } else {
            $nettename = null;
        }
        
        return $nettename;
    }
    
    public function moveLayoutFiles($file) {
        
        if($this->fileType($file) == '.php') {
            $path = '../app/'.$this->frontModuleName.'/presenters/'.ucfirst($file->getSanitizedName());
            $file->move($path);
            
            return $path;
        } else if($this->fileType($file) == '.latte') {
            if(preg_match('/^@.*$/', $file->getName())) {
                $path = '../app/'.$this->frontModuleName.'/templates/@'.strtolower($file->getSanitizedName());
            } else {
                $path = '../app/'.$this->frontModuleName.'/templates/'.ucfirst($this->directory).'/'.strtolower($file->getSanitizedName());
            }
            
            $file->move($path);
            
            return $path;
        } else if($this->fileType($file) == '.css') {
            if($file->getName() == 'general.css') {
                $path = 'css/'.$this->frontModuleName.'/'.strtolower($file->getSanitizedName());
            } else {
                $path = 'css/'.$this->frontModuleName.'/'.strtolower($this->directory).'/'.strtolower($file->getSanitizedName());
            }
            $file->move($path);
            
            return $path;
        } else {
            return false;
        }
    }
    
    public function removeValidation($files, $oldNames) {
        $state = 0;
        
        foreach($files as $file) {
            foreach($oldNames as $oldName) {
                $fileName = pathinfo($oldName['path'], PATHINFO_BASENAME);
                
                if($this->fileType($file) == '.php') {
                    ($fileName == $file->getName()) ? $state++ : $state;
                } else {
                    ($fileName == $file->getName()) ? $state : $state;
                }
            }
        }
        
        return $state;
    }
    
    public function removeLayoutFiles($oldnames, $bin) {
        mkdir($bin);
            
        foreach($oldnames as $oldname) {
            $filename = pathinfo($oldname['path'], PATHINFO_BASENAME);
            rename($oldname['path'], $bin.'/'.$filename);
        }
    } 
}