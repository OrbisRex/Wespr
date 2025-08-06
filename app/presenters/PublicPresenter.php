<?php

namespace App;

use Nette,
    OrbisRex\Wespr,
    App\WesprModule\Repository,
    App\FrontModule;

/**
 * Public presenter for Frontend.
 */
abstract class PublicPresenter extends BasePresenter {
    /** @var FrontModule\Repository\EditUserRepository */
    protected $userRepository;
    /** @var FrontModule\Repository\PublicPageRepository */
    protected $pageRepository;
    /** @var FrontModule\Repository\PublicLayoutRepository */
    protected $layoutRepository;
    /** @var FrontModule\Repository\PublicSourceRepository */
    protected $sourceRepository;
    /** @var FrontModule\Repository\PublicArticleRepository */
    protected $articleRepository;
    /** @var FrontModule\Repository\PublicArticlePageRepository */
    protected $articlePageRepository;
    /** @var FrontModule\Repository\PublicArticleFileRepository */
    protected $articleFileRepository;
    /** @var FrontModule\Repository\PublicFileRepository */
    protected $fileRepository;
    /** @var FrontModule\Repository\PublicGroupRepository */
    protected $groupRepository;
    /** @var FrontModule\Repository\PublicFileGroupRepository */
    protected $fileGroupRepository;
    
    private $statusPages;
    private $statusLayouts;
    protected $page;
    protected $childs;
    protected $linksSubmenu;
    
    /** @param FrontModule\Repository\EditUserRepository $userRepository */
    public function injectEditUserRepository (FrontModule\Repository\EditUserRepository $userRepository) {
        
        $this->userRepository = $userRepository;
    }
    
    /** @param FrontModule\Repository\PublicPageRepository $pageRepository */
    public function injectPublicPageRepository (FrontModule\Repository\PublicPageRepository $pageRepository) {
        
        $this->pageRepository = $pageRepository;
    }
    
    /** @param FrontModule\Repository\PublicLayoutRepository $layoutRepository */
    public function injectPublicLayoutRepository (FrontModule\Repository\PublicLayoutRepository $layoutRepository) {
        
        $this->layoutRepository = $layoutRepository;
    }
    
    /** @param FrontModule\Repository\PublicSourceRepository $sourceRepository */
    public function injectPublicSourceRepository(FrontModule\Repository\PublicSourceRepository $sourceRepository) {
        
        $this->sourceRepository = $sourceRepository;
    }
    
    /** @param FrontModule\Repository\PublicArticlePageRepository $articlePageRepository */
    public function injectPublicArticlePageRepository (FrontModule\Repository\PublicArticlePageRepository $articlePageRepository) {
        
        $this->articlePageRepository = $articlePageRepository;
    }
    
    /** @param FrontModule\Repository\PublicArticleFileRepository $articleFileRepository */
    public function injectPublicArticleFileRepository (FrontModule\Repository\PublicArticleFileRepository $articleFileRepository) {
        
        $this->articleFileRepository = $articleFileRepository;
    }
    
    /** @param FrontModule\Repository\PublicArticleRepository $articleRepository */
    public function injectPublicArticleRepository (FrontModule\Repository\PublicArticleRepository $articleRepository) {
        
        $this->articleRepository = $articleRepository;
    }
    
    /** @param FrontModule\Repository\PublicGroupRepository $groupRepository */
    public function injectPublicGroupRepository (FrontModule\Repository\PublicGroupRepository $groupRepository) {
        
        $this->groupRepository = $groupRepository;
    }
    
    /** @param FrontModule\Repository\PublicFileGroupRepository $fileGroupRepository */
    public function injectPublicFileGroupRepository (FrontModule\Repository\PublicFileGroupRepository $fileGroupRepository) {
        
        $this->fileGroupRepository = $fileGroupRepository;
    }
        
    protected function startup() {
        parent::startup();
        
        //Check webpage status and redirect to admin, if is unactive.
        $this->statusPages = $this->pageRepository->checkStatusPages();
        $this->statusLayouts = $this->layoutRepository->checkStatusLayouts();
        
        if($this->statusPages->count() == 0 || $this->statusLayouts->count() == 0) {
            $this->redirect(':Wespr:Sign:in');
        }
    }
    
    protected function beforeRender() {
        parent::beforeRender();
        
        //Creating main menu
        $pages = $this->pageRepository->findMainPage($this->lang)->fetchAll();
        
        //Bad request
        if(count($pages) == 0) {
            throw new Nette\Application\BadRequestException;
        }
        
        foreach($pages as $page) {
            
            \Tracy\Debugger::barDump($page->nettename);
            if(preg_match('/^(.*):default$/', $page->nettename)) {
                $links[$page->alias] = $this->link($page->nettename.$page->anchor);
            } else {
                $links[$page->alias] = $this->link($page->nettename, array('page' => $page->id));
            }
        }
        
        //Set path for translations files.
        /** @todo Map path automaticaly.*/
        $this->translator->setPath('../app/FrontModule/Translations');
        $this->translator->setLang($this->lang);

        /* Templates */
        $this->template->links = $links;
        
        //Get Page ID for all Presenters
        /*$id = $this->getParameter('id');
        if($id != null) {
            $currentPage = $this->pageRepository->findOnePageById($this->lang, $id);
        } else {
            $action = trim(strstr(trim($this->getAction(true), ':'), ':'), ':');
            $nettename = 'source.nettename LIKE "'.$action.'"';
            $currentPage = $this->pageRepository->findPageByNettename($this->lang, $nettename);
        }
        
        //Bad request
        if(count($currentPage) == 0) {
            throw new Nette\Application\BadRequestException;
        }
        
        //Share to Presenters
        $this->page = $currentPage->fetch();
        \Nette\Diagnostics\Debugger::barDump($this->page);
        if($this->page->parent === null) {
            $pageId = $this->page->id;
        } else {
            $pageId = $this->page->parent;
        }
        
        $pageLevel = $this->page->level;
        
        \Nette\Diagnostics\Debugger::barDump($pageId);
        \Nette\Diagnostics\Debugger::barDump($pageLevel);
        
        //Submenu
        $childsPage = $this->pageRepository->findLevelPages($this->lang, $pageLevel, $pageId, 'page.order');
        
        //Share to Presenters
        $this->childs = $childsPage;
        \Nette\Diagnostics\Debugger::barDump($childsPage->fetch());
        
        if(count($childsPage) != 0) {
            
            foreach($childsPage->fetchAll() as $menu) {
                $linksSubmenu[$menu->alias] = $this->link($menu->nettename, $menu->id);
            }
            $this->template->linksSubmenu = $linksSubmenu;
            
        } else {
            $this->template->linksSubmenu = false;
        }
        */
    }
}
