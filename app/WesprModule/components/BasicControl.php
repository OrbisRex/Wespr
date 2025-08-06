<?php

/**
 * Description of BasicControl
 *
 * @author David Ehrlich, 2015
 * @version 1.1
 * @license MIT
 */
 
namespace App\WesprModule;

use Nette,
    App,
    OrbisRex\Wespr;

class BasicControl extends Nette\Application\UI\Control {
    
    /** @var Translator Translator object. */
    protected $translator;
    /** @var String Lang identifier for DB. */
    protected $lang;
    /** @var Nette\Application\UI\Presenter User Object. */
    protected $user;
    /** @var Integer User ID. */
    protected $userId;
    /** @var Integer Original user ID for admins. */
    protected $userAdminId;
    /** @var String Personal or Public folder in www/Data. */
    protected $userDataFolder;

    public function __construct(Wespr\Translator $translator) {
        parent::__construct();
        
        $this->translator = $translator;
        $this->lang = $translator->getLang();
    }
    
    protected function setLang() {
        $this->lang = $this->translator->getLang();
    }
    
    public function getUserId(Nette\Security\User $user) {
        if($user->isInRole('admin')) {
            $this->userId = null;
            $this->userAdminId = $user->id;
            $this->userDataFolder = 'public';
        } else {
            $this->userId = $user->id;
            $this->userDataFolder = strstr($user->getIdentity()->data['email'], '@', true);            
        }
    }
    
    protected function cleareFormFields(Nette\Forms\Container $form, array $fileds) {
        
        $values = $form->getValues(true);
        
        foreach($fileds as $filed) {
            $values[$filed] = array();
        }
        
        $form->setValues($values, true);
    }

}
