<?php
/**
 * WESPR frontend presenter.
 *
 * @author      David Ehrlich
 * @package     OrbisRex:Homepage
 * @version     1.0
 * @copyright   (c) 2014, David Ehrlich
 * @license     http://opensource.org/licenses/MIT MIT
 */

namespace App\FrontModule;

use App;

class HomepagePresenter extends App\PublicPresenter {
    /** @var Nette\Database\Statement Select form article and page table. */
    private $mainArticles;
    /** @var Nette\Database\Statement Select form files and page table. */
    private $backgroundFiles;
    /** @var Nette\Database\Statement Select form article_page table articles. */
    private $articles;
    /** @var Nette\Database\Statement Select form article_page table articles. */
    private $mainFiles;
    /** @persistent */
    public $offset;
    /** @var int Number of main articles */
    private $mainArticlesCount;
    
    public function actionDefault() {
        
        /*News Slider*/
        //Set offset links
        $this->mainArticlesCount = $this->articlePageRepository->countNewsArticles($this->lang, ':Front:Homepage:default');
        
        if($this->offset !== null) {
            $this->mainArticles = $this->articlePageRepository->findNewsArticles($this->lang, ':Front:Homepage:default', 1, $this->offset);
        } else {
            $this->mainArticles = $this->articlePageRepository->findNewsArticles($this->lang, ':Front:Homepage:default', 1, 0);                        
        }
        
        $mainArticles = $this->mainArticles->fetchAll();
        foreach($mainArticles as $article) {
            $this->backgroundFiles = $this->articleFileRepository->findPublishedMainFiles($this->lang, $article->id);
        }
    }
    
    public function renderDefault() {
        //Sections
        $this->articles = $this->articlePageRepository->findArticlesByNettename($this->lang, ':Front:Home%');
        
        //Add files for articles
        $articles = $this->articles->fetchAll();
        foreach($articles as $article)
        {
            $this->mainFiles[$article->article_id] = $this->articleFileRepository->findPublishedMainFiles($this->lang, $article->article_id, 'crop');
            //Number of files
            $mainFilesCounts[$article->article_id] =  $this->mainFiles[$article->article_id]->count();
        }

        /*Send to template*/
        $this->template->userData = null;
        $this->template->fixMenuPosition = 'menu_container';
        //$this->template->pages = $this->pages;
        
        //News Slider
        $this->template->mainArticlesCount = $this->mainArticlesCount;
        $this->template->mainArticles = $this->mainArticles;
        $this->template->backgroundFiles = $this->backgroundFiles;
        
        //Sections
        $this->template->articles = $this->articles;
        $this->template->mainFiles = $this->mainFiles;
        $this->template->mainFilesCount = $mainFilesCounts;
    }
    
    //Compulsory method for preview from WESPR.    
    public function renderShow($id, $passcode) {
        $this->article = $this->articlePageRepository->findArticle($this->lang, $id);
        $title = $this->article->fetch()->title;
        
        $this->template->title = $title;
        $this->template->article = $this->article;
    }
    
    public function handleNextArticle($offset) {
        $this->offset = $offset;
        
        if($this->isAjax()) {
            $this->redrawControl('slider');
        } else {
            $this->redirect('this');
        }
    }
}
