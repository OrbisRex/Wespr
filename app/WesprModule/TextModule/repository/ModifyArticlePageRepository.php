<?php

/**
 * Modify operation over DB table article_page
 *
 * @author David Ehrlich, 2013
 * @version 1.1
 * @license MIT
 */

namespace App\WesprModule\TextModule\Repository;

use Nette,
    App,
    App\WesprModule\TextModule\Repository;

class ModifyArticlePageRepository extends Repository\ArticlePageRepository {
    
    public function orderArticle($order, $id, $pageId = null) {
        if($pageId != null) {
            return $this->executeQuery('UPDATE `article_page` SET `order` = '.$order.' WHERE (`article_id` = '.$id.' AND `page_id` = '.$pageId.')');
        } else {
            return $this->executeQuery('UPDATE `article_page` SET `order` = '.$order.' WHERE (`article_id` = '.$id.')');
        }
    }
    
    public function insertArticlePage($data) {
        return $this->insertData($data);
    }
    
    public function updateArticlePageById($id, $data) {
        return $this->updateById(array('id' => $id), $data);
    }
    
    public function updateArticlePage($col, $value, $data) {
        return $this->updateByColl($col, $value, $data);
    }
    
    public function updateArticlePageByPageId($value, $data) {
        return $this->updateByWhere('page_id ? AND state IS NOT NULL', array($value), $data);
    }
    
    /*Various methodes*/
    //Reorder all items in article tables
    public function sortArticle ($oldOrder, $newOrder, $pageId) {
        
        $combineOrder = array_combine($oldOrder, $newOrder);

        foreach($combineOrder as $order => $articleId) {
            $this->orderArticle($order, $articleId, $pageId);
        }
    }
    
    /**
     * variableStirng
     * Replace special characters by safe symbols and create a camel syntax string. 
     * @param String $string
     * @return Mixed
     */
    public function variableString ($string, $separator = NULL) {
        //Remove special characters.
        $cleanString = preg_replace('/[(£$%&=§°@#\<\>\)\(\{\}\[\]\^\*\|\.\?\!\+)]+/', '', $string);
        
        //Translate specail letters to ASCII.
        $asciiString = preg_replace('/[^ \w]+/', '', iconv("UTF-8", "ASCII//TRANSLIT", $cleanString));
        
        //Finish string with separator or camelized string.
        if($separator == '-') {
            $variableString = preg_replace('/[\s]+/', '-', strtolower($asciiString));
        } else {
            $variableString = preg_replace('/[\s]+/', '', ucwords($asciiString));
        }
        
        return $variableString;
    }
}
