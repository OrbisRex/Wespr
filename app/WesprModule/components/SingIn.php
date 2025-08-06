<?php
/**
 * Description of SingIn
 *
 * @author David Ehrlich
 */

namespace App\WesprModule;

use \Nette\Application\UI\Control;

class SingIn extends Control {
    
    private $template;
    
    public function __construct() {
        parent::__construct();
        $this->template = $this->template;
    }
    
   public function render(){
        
        $this->template->setFile(__DIR__.'/SingIn.latte');
        $this->template->singInForm();
        $this->template->render();
    }

}
