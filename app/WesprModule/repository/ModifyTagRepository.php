<?php
/**
 * Operation over DB's table Tag
 *
 * @author David Ehrlich, 2014
 * @version 1.1
 * @license MIT
 */

namespace App\WesprModule\Repository;

use Nette,
    App,
    App\WesprModule\Repository;

class ModifyTagRepository extends Repository\TagRepository {
    private $tagIds;

    /*Actions*/
    public function insertTag($data) {
        return $this->insertData($data);
    }

    public function updateTag($col, $value, $data) {
        return $this->updateByColl($col, $value, $data);
    }

    /*Other functions*/
    protected function insertTags($form, $lang) {
        if(!strstr($form->values->tags, ',')) {
            $tags = explode(' ', $form->values->tags);
        } else {
            $tags = explode(',', $form->values->tags);
        }
        
        foreach($tags as $tag) {
            
            $trimedTag = trim($tag);
            $oldTag = $this->findTag($lang, $trimedTag);
            
            if($oldTag->count() === 0){
                $this->tagIds[] = $this->insertTag(array(
                    'tag_'.$lang => $trimedTag,
                    'inserttime' => new \DateTime(),
                    'user_id' => $this->userId
                ));
            } else {
                $this->tagIds[] = $oldTag->fetch()->id;
            }
        }
    }
    
    protected function joinFileTag() {
        foreach($this->tagIds as $tagId) {
            $this->fileTagRepository->insertFileTag(array(
                'file_id' => $this->fileId,
                'tag_id' => $tagId,
                'user_id' => $this->userId
            ));
        }
    }
    
    public function timeToTag($timeString) {
        $dateTime = new \DateTime($timeString);
        
        $timeTags['Month'] = $dateTime->format('F');
        $timeTags['DayPeriod'] = $dateTime->format('a');
        $timeTags['DayInWeek'] = $dateTime->format('l');
        
        return $timeTags;
    }
}