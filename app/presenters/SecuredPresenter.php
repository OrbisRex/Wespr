<?php
/**
 * Description of SecuredPresenter
 *
 * @author David Ehrlich
 */

namespace App;

use Nette;
use App;
use App\WesprModule;
use App\WesprModule\TextModule;
use App\WesprModule\FileModule;
use Nette\Security\User;

abstract class SecuredPresenter extends BasePresenter {
    /** @var WesprModule\Repository\ModifyUserRepository */
    protected $userRepository;
    /** @var WesprModule\Repository\ModifyUserAuthorityRepository */
    protected $userAuthorityRepository;
    /** @var WesprModule\Repository\ModifyPageRepository */
    protected $pageRepository;
    /** @var WesprModule\Repository\ModifyLayoutRepository */
    protected $layoutRepository;
    /** @var WesprModule\Repository\ModifySourceRepository */
    protected $sourceRepository;
    /** @var WesprModule\Repository\ModifyCodeRepository */
    protected $codeRepository;
    /** @var WesprModule\Repository\ModifyGroupRepository */
    protected $groupRepository;
    /** @var TextModule\Repository\ModifyArticleRepository */
    protected $articleRepository;
    /** @var TextModule\Repository\ModifyArticlePageRepository */
    protected $articlePageRepository;
    /** @var TextModule\Repository\ModifyArticleFileRepository */
    protected $articleFileRepository;
    /** @var FileModule\Repository\ModifyFileRepository */
    protected $fileRepository;
    /** @var FileModule\Repository\ModifyFileGroupRepository */
    protected $fileGroupRepository;
    /** @var FileModule\Repository\ModifyFileTagRepository */
    protected $fileTagRepository;
    /** @var WesprModule\Repository\ModifyTagRepository */
    protected $tagRepository;
    /** @var FileModule\Repository\ModifyFileMetaRepository */
    protected $fileMetaRepository;
            
    /** @var Nette\Database\Statement count row of table pages */
    private $countPages;
    /** @var Array Role of logged user. */
    private $roles;
    /** @var Nette\Database\Statement User authority data. */
    protected $userAuthority;
    /** @var Global User ID for Admin roles is NULL for other roles current ID. */
    protected $userId;


    /** @param WesprModule\Repository\ModifyUserRepository $userRepository */
    public function injectModifyUserRepository(WesprModule\Repository\ModifyUserRepository $userRepository) {
        
        $this->userRepository = $userRepository;
    }
    
    /** @param WesprModule\Repository\ModifyUserAuthorityRepository $userAuthorityRepository */
    public function injectModifyUserAuthorityRepository(WesprModule\Repository\ModifyUserAuthorityRepository $userAuthorityRepository) {
        
        $this->userAuthorityRepository = $userAuthorityRepository;
    }
    
    /** @param WesprModule\Repository\ModifyPageRepository $pageRepository */
    public function injectModifyPageRepository(WesprModule\Repository\ModifyPageRepository $pageRepository) {
        
        $this->pageRepository = $pageRepository;
    }
    
    /** @param WesprModule\Repository\ModifyLayoutRepository $layoutRepository */
    public function injectLayoutModifyRepository(WesprModule\Repository\ModifyLayoutRepository $layoutRepository) {
        
        $this->layoutRepository = $layoutRepository;
    }
    
    /** @param WesprModule\Repository\ModifyPageRepository $sourceRepository */
    public function injectModifySourceRepository(WesprModule\Repository\ModifySourceRepository $sourceRepository) {
        
        $this->sourceRepository = $sourceRepository;
    }
    
    /** @param WesprModule\Repository\ModifyCodeRepository $codeRepository */
    public function injectModifyCodeRepository(WesprModule\Repository\ModifyCodeRepository $codeRepository) {
        
        $this->codeRepository = $codeRepository;
    }
    
    /** @param WesprModule\Repository\ModifyGroupRepository $groupRepository */
    public function injectModifyGroupRepository (WesprModule\Repository\ModifyGroupRepository $groupRepository) {
        
        $this->groupRepository = $groupRepository;
    }
    
    /** @param TextModule\ModifyRepository\ArticleRepository $articleRepository */
    public function injectModifyArticleRepository (TextModule\Repository\ModifyArticleRepository $articleRepository) {
        
        $this->articleRepository = $articleRepository;
    }
    
    /** @param TextModule\Repository\ModifyArticlePageRepository $articlePageRepository */
    public function injectModifyArticlePageRepository (TextModule\Repository\ModifyArticlePageRepository $articlePageRepository) {
        
        $this->articlePageRepository = $articlePageRepository;
    }
    
    /** @param TextModule\Repository\ModifyArticleFileRepository $articleFileRepository */
    public function injectModifyArticleFileRepository (TextModule\Repository\ModifyArticleFileRepository $articleFileRepository) {
        
        $this->articleFileRepository = $articleFileRepository;
    }
    
    /** @param FileModule\Repository\ModifyFileRepository $fileRepository */
    public function injectModifyFileRepository (FileModule\Repository\ModifyFileRepository $fileRepository) {
        
        $this->fileRepository = $fileRepository;
    }
    
    /** @param FileModule\Repository\ModifyFileGroupRepository $fileGroupRepository */
    public function injectModifyFileGroupRepository(FileModule\Repository\ModifyFileGroupRepository $fileGroupRepository) {
        
        $this->fileGroupRepository = $fileGroupRepository;
    }
    
    /** @param WesprModule\Repository\ModifyTagRepository $tagRepository */
    public function injectModifyTagRepository(WesprModule\Repository\ModifyTagRepository $tagRepository) {
        
        $this->tagRepository = $tagRepository;
    }
    
    /** @param FileModule\Repository\ModifyFileTagRepository $fileTagRepository */
    public function injectModifyFileTagRepository(FileModule\Repository\ModifyFileTagRepository $fileTagRepository) {
        
        $this->fileTagRepository = $fileTagRepository;
    }
    
    /** @param FileModule\Repository\ModifyFileMetaRepository $fileMetaRepository */
    public function injectModifyFileMetaRepository(FileModule\Repository\ModifyFileMetaRepository $fileMetaRepository) {
        
        $this->fileMetaRepository = $fileMetaRepository;
    }
    
    protected function startup() {
        parent::startup();
        if(!$this->getUser()->isLoggedIn()) {
            
            if ($this->user->getLogoutReason() === User::INACTIVITY) {
                $this->flashMessage('No activity for long time. So I have logged you out.');
            }
            
            //$this->redirect(':Wespr:Sign:in');
        } else {
            
            if (!$this->user->isAllowed($this->name, $this->action)) {
                $this->flashMessage('Ups, sorry but I are not allow to do this operation.');
                $this->redirect('Default:');
            }            
        }
            
    }

    public function beforeRender() {
        //Get user role for Admin module.
        if(!$this->user->loggedIn) {
            $this->redirect(':Wespr:Sign:');
        }
        
        /* Website state */
        if($this->user->isInRole('admin')) {
            $countAllPages = $this->pageRepository->countPages(array('nonpublic', 'locked', 'link', 'public'));
            $countActivedPages = $this->pageRepository->countPages(array('locked', 'link', 'public'));
            
            //Set Global user ID for Admins to NULL = Anonymous.
            $this->userId = null;
        } else {
            $countAllPages = $this->pageRepository->countUserPages(array('nonpublic', 'locked', 'link', 'public'), $this->roles, $this->user->id);
            $countActivedPages = $this->pageRepository->countUserPages(array('locked', 'link', 'public'), $this->roles, $this->user->id);
            
            //Set Global User ID
            $this->userId = $this->user->id;
        }
        //\Tracy\Debugger::barDump($countAllPages);
        
        /* User state */
        if($this->user->storage->identity->state === 'public') {
            $verification = true;
        } else if ($this->user->storage->identity->state === 'unprove') {
            $verification = false;
        } else {
            $verification = null;
        }
        
        $this->userAuthority = $this->userAuthorityRepository->findUserAuthority($this->lang, $this->user->id)->fetchPairs('codename', 'value');
        
        //Set path for translations files.
        $this->translator->setPath('../app/WesprModule/Translations');
        $this->translator->setLang($this->lang);

        $this->template->countAllPages = $countAllPages;
        $this->template->countActivedPages = $countActivedPages;
        $this->template->verification =  $verification;
    }
    
    public function handleSignOut() {
        $this->presenter->getUser()->logout();
        $this->flashMessage('Odhlášení bylo úspěšné.');
        $this->presenter->redirect(':Wespr:Sign:in');
    }        
}