<?php
/**
 * Event management - module.
 *  
 * @author David Ehrlich
 * @package WESPR
 */

namespace App\WesprModule\TextModule;

use Nette,
        App,
        Model,
        Nette\Diagnostics\Debugger,
        Nette\Application\UI\Form,
        Nette\Forms\Controls\TextDate,
        Nette\Forms\Controls\TextTime,
        Nette\Forms\Controls\SubmitButton;

class EventPresenter extends App\SecuredPresenter {

    /** @var Model\EventRepository*/
    protected $eventRepository;
    
    private $events;
    private $price;
    /** @var array data from DB to form for edit*/
    private $editEvents;
    /** @var int ID of event for editation*/
    private $id;
    /** @var array text from file */
    private $checkEvent;
    /** @var array session for import events */
    private $sessionImport;
    
    /** @param App\WesprModule\TextModule\Repository\ModifyEventRepository $eventRepository */
    public function injectEventRepository (App\WesprModule\TextModule\Repository\ModifyEventRepository $eventRepository) {
        
        $this->eventRepository = $eventRepository;
    }

    /**
     * Write sub-menu area on the page.
     * @return \App\WesprModule\TextModule\ArticleMenuControl
     */
    public function createComponentArticleMenu() {

        return new ArticleMenuControl($this->translator, $this->lang, $this->pageRepository, $this->user);
    }
    
    public function actionDefault() {
        $this->events = $this->eventRepository->findUserEvent($this->lang, $this->user->id);

        if (empty($this->id)) {
            $this->template->id = NULL;
        }
    }

    public function renderDefault() {
        $this->events = $this->eventRepository->findUserEvent($this->lang, $this->user->id);

        $this->template->events = $this->events;
        $this->template->countEvents = count($this->events);
        //Solution for snippet
        $this->template->form = $this->template->_form = $this['actionEvent'];
    }

    protected function createComponentActionEvent($name) {
        $form = new Form($this, $name);

        if (!$this->events) {
                throw new Nette\Application\BadRequestException;
        }

        $select = $form->addContainer('select');
        $state = $form->addContainer('state');
        foreach ($this->events as $item) {
                $select->addCheckbox($item->id)                        
                        ->setAttribute('onchange', 'return elementEnable("data-wespr-check", "data-slave")')
                        ->setAttribute('data-wespr-check', 'check');

                $state->addHidden($item->id)->setValue($item->state);
        }

        $form->addSubmit('delete', 'X')
                ->setAttribute('title','Smazat vybrané položky?')
                ->setAttribute('data-slave', 'background-color: rgb(200,200,200); color: rgb(230,230,230); cursor: inherit; border-color: rgb(180,180,180);')
                ->setAttribute('style', 'background-color: rgb(200,200,200); color: rgb(230,230,230); cursor: inherit; border-color: rgb(180,180,180);')
                ->setAttribute('onclick', 'return warning(this)')
                ->setDisabled()
                ->onClick[] = array($this, 'actionEventDeleteSubmitted');
        
        if($this->user->storage->identity->state == 'public') {
            $form->addSubmit('publish', '!')
                    ->setAttribute('title','Zveřejnit / nezveřejnit vybrané položky?')
                    ->setAttribute('data-slave', 'background-color: rgb(200,200,200); color: rgb(230,230,230); cursor: inherit; border-color: rgb(180,180,180);')
                    ->setAttribute('style', 'background-color: rgb(200,200,200);  color: rgb(230,230,230); cursor: inherit; border-color: rgb(180,180,180);')
                    ->setAttribute('onclick', 'return warning(this)')
                    ->setDisabled()
                    ->onClick[] = array($this, 'actionEventPublishSubmitted');
        } else {
            $form->addSubmit('publish', '!')
                    ->setAttribute('title','Není dostupné. Ověřte svůj účet.')
                    ->setAttribute('style', 'background-color: rgb(200,200,200);  color: rgb(230,230,230); cursor: inherit; border-color: rgb(180,180,180);')
                    ->setDisabled();
        }
        
        $form->addButton('selectAll', 'all')
            ->setAttribute('onclick', 'return checkAll("data-slave", "data-wespr-check")');
    }

   public function actionEventDeleteSubmitted(SubmitButton $button) {
        
       $values = $button->form->getValues(true);
       $keys = array_keys(array_filter($values['select']));
       
      foreach ($keys as $id) {
          $this->eventRepository->updateEvent('id', $id, array('state' => null));
       } 

       $this->flashMessage('Položka byla umístěna do koše.', 'success');

       if($this->isAjax()) {
            $button->form->setValues(array(), true);
            $this->redrawControl('table');
        } else {
            $this->redirect('this');
        }
   }
   
   public function actionEventPublishSubmitted(SubmitButton $button) {      
       /** @var integer cyklus foreach*/
       $i=0;

       $values = $button->form->getValues(true);
       $keys = array_keys(array_filter($values['select']));
       
       foreach ($keys as $id) {
           if($values['state'][$keys[$i++]] == 'nonpublic') {$state = 'public';} else {$state = 'nonpublic';}
           $this->eventRepository->updateEvent('id', $id, array('state' => $state));
       }
       $this->flashMessage('Úspěšně jsem upravil stav akce.', 'success');
       
       if($this->isAjax()) {
            $button->form->setValues(array(), true);
            $this->redrawControl('table');
        } else {
            $this->redirect('this');
        }
   }
   
   /** 
    * Edit of event. 
    */
    public function actionEdit($id) {
        /* Read Article for manage. */
        $this->id = $id;
        $this->events = $this->eventRepository->findUserEvent($this->lang, $this->id);
        $this->editEvents = $this->eventRepository->editEvent($id)->fetch();
        
        if (empty($this->id)) {
            $this->template->id = NULL;
        }
    }

    public function renderEdit ($id) {
        /* Read event. */ 
        $this->template->id = $id;
   }
   
   protected function createComponentEventForm($name) {
       /** @todo Move to DB / config */
        if($this->user->storage->identity->state == 'public') {
            $selectState = array('nonpublic' => 'Nonpublic - Not visible', 'public' => 'Public - Visibile to all', 'news' => 'News - Visibile as a news');
        } else {
            $selectState = array('nonpublic' => 'Nonpublic - Not visible');
        }
       
       /** @var $emptyEvent array empty values for form */
       if(!empty($this->editEvents)) {
          /** @var $dateTime \DateTime variable for modify date*/
          $dateTime = new \DateTime($this->editEvents["date"]);
          $date = $dateTime->format('d.m.Y');

          /** @var $timeInterval \TimeInterval variable for modify time*/
          $timeInterval = $this->editEvents["time"];
          $time = $timeInterval->format('%H:%I');
          
          $emptyEvent = array (
          'name' => $this->editEvents["name"],
          'text' => $this->editEvents['text'],
          'state' => $this->editEvents["state"],
          'date' => $date,
          'time' => $time,
          'place' => $this->editEvents["place"],
          'price' => $this->editEvents["price"],
          'id' => $this->editEvents['id']
          );
       }
       
       $form = new Form($this, $name);
       $form->setTranslator($this->translator);       
       $form->addText('name','Name*')->setRequired('Vyplňte název akce.')->addRule(Form::FILLED, 'Něco je špatně, zadejte název akce.');
       $form->addSelect('state', 'State', $selectState)->setAttribute('class','select_short');
       $form->addText('date', 'Date & Time*')->setAttribute('placeholder', 'DD.MM.YY')->addRule(TextDate::DATE, 'Něco je špatně, zkontrolujte správnost data. Hodnota musí mít formát DD.MM.RR.')->addRule(Form::FILLED, 'Něco chybí, je nutné zadat datum akce.')->setAttribute('class', 'date text-short');
       $form->addText('time', 'Time')->setAttribute('placeholder', 'HH:MM')->addRule(TextTime::TIME, 'Něco je špatně, zkontrolujte správnost času. Hodnota musí mít formát HH:MM')->addRule(Form::FILLED, 'Něco chybí, je nutné zadat čas začátku.')->setAttribute('class', 'time text-short');
       $form->addText('price','Price & State')->setAttribute('class', 'text-short')->setAttribute('placeholder', 'neuvedeno')->addCondition(Form::FILLED)->addRule(Form::NUMERIC, 'Cenu uveďte jako číslo bez měny.');
       $form->addText('place','Venue*')->addRule(Form::FILLED, 'Je dobré zadat místo konání.');
       $form->addTextArea('text','Text');
       $form->addHidden('id');

       $form->addSubmit('save', 'Save')->setAttribute('class','main-button');
       $form->onSuccess[] = array($this, 'eventFormSubmitted');
       
       if(!empty($this->editEvents)) {
            $form->setDefaults($emptyEvent);
            
            $form->addSubmit('edit', 'Save changes')->setAttribute('title','Save changes.')->setAttribute('class','main-button')->onClick[] = array($this, 'eventEditSubmitted');

            $presenter = $this;
            $form->addSubmit('cancel', 'Cancel')->setValidationScope(FALSE)->setAttribute('title','Cancel all changes.')->setAttribute('class','add-button')
                             ->onClick[] = function () use ($presenter) {
                                 $presenter->flashMessage('You have canceled event editing.', 'success');
                                 $presenter->redirect('default');
                             };
       }
   }
   
    public function eventFormSubmitted(Form $form) {
        $date = TextDate::formatDate($form->values->date);        

        $time = new \DateTime($form->values->time);
        /** @var $eventTime \DateTime */
        $eventTime = $time->format("H:i");

        if($form->values->price == "") {
          $price = null;
        } else {
          $price = $form->values->price;
        }

        $this->eventRepository->insertEvent(array(
            'name' => $form->values->name,
            'text' => $form->values->text,
            'date' => $date,
            'time' => $eventTime,
            'place' => $form->values->place,
            'price' => $price,
            'state' => $form->values->state,
            'inserttime' => new \DateTime(),
            'updatetime' => new \DateTime(),
            'user_id' => $this->getUser()->getId()
        ));

        $this->flashMessage('Event has been saved.', 'success');
        $this->redirect('default');
    }
   
    public function eventEditSubmitted(SubmitButton $button) {
        $values = $button->form->getValues();
       
        $date = TextDate::formatDate($values['date']);
        
        $time = new \DateTime($values['time']);
         /** @var $eventTime \DateTime */
        $eventTime = $time->format("H:i");

        if($values['price'] == "") {
             $price = null;
        } else {
            $price = $values['price'];
        }

        $this->eventRepository->updateEvent('id', $values['id'], array(
            'name' => $values['name'],
            'text' => $values['text'],
            'date' => $date,
            'time' => $eventTime,
            'place' => $values['place'],
            'price' => $price,
            'state' => $values['state'],
            'updatetime' => new \DateTime()
        ));

        $this->flashMessage('Event has been changed.', 'success');
        $this->redirect('default');
    } 

    /** 
     * Import event. 
     */
    public function actionImport($step) {
        //Start session
        $this->sessionImport = $this->getSession('sessionImport');
    }

    public function renderImport ($step) {
        $this->template->step = $step;
   }
   
   public function createComponentImportEventForm ($name) {
       $form = new Form($this, $name);
       $form->addUpload('file','File')->addRule(Form::MAX_FILE_SIZE, 'A maximal file size is 2000 kB.', 64 * 1024 /* v bytech */);
       $form->addHidden('stepImport', 1);
       
       $form->addSubmit('read', 'Next')->setAttribute('class','main-button');
       $form->onSuccess[] = array($this, 'checkImportFormSubmitted');

       $presenter = $this;
       $form->addSubmit('cancel', 'Cancel')->setValidationScope(FALSE)->setAttribute('title','Cancel operation.')->setAttribute('class','add-button')
                        ->onClick[] = function () use ($presenter) {
                            $presenter->flashMessage('Improt has been canceled.', 'success');
                            $presenter->redirect('default');
                        };
   }

   public function checkImportFormSubmitted($form) {
        //Local variables
        $fileLine = null;
        
        if ($form['read']->isSubmittedBy()) {
            $values = $form->getValues();

            $file = $values["file"];
            
            if ($file->isOk()) {
                $path = "data/files/" . $file->getSanitizedName();
                $file->move($path);

                $fileLine = $this->eventRepository->readFolderLine($path);
                $this->sessionImport->checkEvent = $fileLine;
                
                $this->flashMessage('File has been uploaded.', 'success');
                }
                
                $this->redirect('import', $step = 1); //Move to next step.
           }
           else {
                $this->flashMessage('Ups. File has not been uploaded. Please try it again.', 'error');
                $this->redirect('default');
           }
        }

    public function createComponentCheckEventForm ($name) {
        //Local variables
        $fileContent = null;
        $fileEvent = $this->sessionImport->checkEvent;
        
        if(isset($fileEvent)) {
            foreach($fileEvent as $key) {
                /** @todo Detect character for impode. */
                if(is_array($key)) {
                    $fileContent .= implode('+', $key);
                    $fileContent .= PHP_EOL;
                }
            }
        }

       $form = new Form($this, $name);
       $form->addTextArea('text','Text for import')->setDefaultValue(trim($fileContent))->addRule(Form::FILLED, 'Please fill in at least 1 event.');;
       $form->addCheckbox('state','Mark everything as public.');
       
       $form->addSubmit('import', 'Import')->setAttribute('class','main-button');
       $form->onSuccess[] = array($this, 'importEventFormSubmitted');

       $presenter = $this;
       $form->addSubmit('cancel', 'Cancel')->setValidationScope(FALSE)->setAttribute('title','Cancel operation.')->setAttribute('class','add-button')
                        ->onClick[] = function () use ($presenter) {
                            $this->sessionImport->checkEvent = NULL;
                            $presenter->flashMessage('Import has been canceled.', 'success');
                            $presenter->redirect('default');
                        };

       $form->addSubmit('back', 'Back')->setValidationScope(FALSE)->setAttribute('title','Go back to previous screen.')->setAttribute('class','add-button')
                        ->onClick[] = function () use ($presenter) {
                            $presenter->redirect('import');
                        };
    }
    
    public function importEventFormSubmitted($form) {
        //Local variables
        $control = true;
        
        $line = explode("\n", $form->values["text"]);
        for($i = 0; $i < count($line); $i++) {
            if($line[$i] != '') {
                $fileLine[$i] = explode('+', trim($line[$i]));
            }  
        }
        
        //Save current version of content to the session.
        $this->sessionImport->checkEvent = $fileLine;

        foreach($fileLine as $item) {
            if(!strpos($item[0], '.') && !strpos($item[0], '/')) {
                $control = false;
                $this->flashMessage('Date has to be in format DD.MM.YYYY or DD/MM/YYYY at the first position.', 'error');
                $this->redirect('import', $step = 1);
            } else {
                $date = TextDate::formatDate(trim($item[0]));
            }

            if((!is_string($item[1]) || !is_string($item[2]) || !is_string($item[4]))) {
                $control = false;
            } else {
                $name = $item[1];
                $text = $item[2];
                $place = $item[4];
            }
            
            if(!strpos($item[3], ':') && !strpos($item[3], '.')) {
                $control = flase;
                $this->flashMessage('Time has to be in format HH:MM or HH.MM at the position four.', 'error');
                $this->redirect('import', $step = 1);
            }
            else {
                $time = new \DateTime(trim($item[3]));
                $eventTime = $time->format("H:i");
            }
            
            if(isset($item[5]) && $this->validatePrice($item[5])) {
                $price = $item[5];
            } else if(isset($item[5]) && !$this->validatePrice($item[5])) {
                $control = false;
                $this->flashMessage('Price has to be a number without the currency mark.', 'error');
                $this->redirect('import', $step = 1);
            } else {
                $price = null;
            }
            
            if($form->values['state']) {
                $state = 'public';
            } else {
                $state = 'nonpublic';
            }
                
            if($control) {
                $this->eventRepository->insertEvent(array(
                    'name' => $name,
                    'text' => $text,
                    'date' => $date,
                    'time' => $eventTime,
                    'place' => $place,
                    'price' => $price,
                    'state' => $state,
                    'inserttime' => new \DateTime(),
                    'updatetime' => new \DateTime(),
                    'user_id' => $this->getUser()->getId()
                ));
            }
        }

        $this->flashMessage('Data has been imported.', 'success');
        $this->redirect('default');
     }
     
    private function validatePrice($price) {
        $pattern = "/^\d+$/";
        return (bool) preg_match($pattern, $price);
    }
}