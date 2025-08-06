<?php

/**
 * Settings presenter
 *
 * @author     David Ehrlich, 2014
 * @package    WESPR
 */

namespace App\WesprModule;

use Nette,
    App;

class SettingsPresenter extends App\SecuredPresenter {
    /**
     * Write sub-menu area on the page.
     * @return \App\\SettingMenuControl
     */
    public function createComponentSettingMenu() {

        return new SettingMenuControl($this->translator, $this->lang, $this);
    }    
}
