<?php
/**
 * Description of News component
 *
 * @author David Ehrlich
 */

namespace OnePageModule;

use \Nette\Application\UI\Control,
    \Nette\Database\Table\Selection;

class NewsControl extends Control {

    private $news;
    private $event;
    
    public function __construct(Selection $newses, Selection $events){
        parent::__construct();
        $this->news = $newses;
        $this->event = $events;
    }
    
    public function render(){
        $this->template->setFile(__DIR__.'/News.latte');
        $this->template->news = $this->news;
        $this->template->event = $this->event;
        $this->template->countEvent = $this->event->count();
        $this->template->render();
    }
    
}

?>