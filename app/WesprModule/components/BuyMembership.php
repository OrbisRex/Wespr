<?php

/**
 * Component: BuyMembership
 * PayPal gate for purchase a membership.
 *
 * @author     David Ehrlich, 2016
 * @package    WESPR
 * @version    1.0
 */

namespace App\WesprModule;

use Nette\Application\UI\Control;

class BuyMembershipControl extends Control {
    
    /** @var object Translator */
    private $translator;
    /** @var string Current language */
    private $lang;
    /** @var object Presenter */
    private $presenter;
    
    public function __construct($translator, $lang, $presenter){
        parent::__construct();
        $this->translator = $translator;
        $this->lang = $lang;
        $this->presenter = $presenter;
    }

    /**
     * (non-phpDoc)
     *
     * @see Nette\Application\Control#render()
     */
    public function render() {
        $this->template->setFile(__DIR__.'/BuyMembership.latte');
        $this->template->setTranslator($this->translator);
        $this->template->presenter = $this->presenter;
        
        $this->template->render();
    }
}