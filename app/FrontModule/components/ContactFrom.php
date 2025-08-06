<?php

/**
 * Description of ContactFrom
 *
 * @author David Ehrlich
 */

namespace OnePageModule;

use \Nette\Application\UI\Control,
    \Nette\Application\UI\Form;


class ContactFormControl extends Control {

    public function __construct(){
        parent::__construct();
    }

    public function render() {
        $this->template->setFile(__DIR__.'/ContactForm.latte');
        $this->template->render();
    }
    
    //Form for send email.
    protected function createComponentContactForm($name) {
        $form = new Form($this, $name);
        $form->addText('name','Jméno*')->addRule(Form::FILLED, 'Je nutné zadat vaše jméno.')->setAttribute('class','select_short');
        $form->addText('email', 'Email')->addRule(Form::EMAIL, 'Je nutné zadat vaše správný email.')->addRule(Form::FILLED, 'Je nutné zadat email.')->setAttribute('class','select_short');
        $form->addText('phone', 'Telefon')->setAttribute('class','select_short');
        $form->addTextArea('text')->addRule(Form::FILLED, 'Je nutné zadat text dotazu.');
        $form->addText('control', 'Kontrola: Mach a ')->addRule(Form::FILLED, 'Je nutné zadat odpověď na kontrolní otázku.')->setAttribute('class','select_short');

        $form->addSubmit('submit', 'Odeslat')->setAttribute('title','Odešle obsah emailem.');
        $form->onSuccess[] = array($this, 'contactFormSubmitted');
    }

    public function contactFormSubmitted(Form $form) {
        if($form->values->control == "šebestová") {
            //$mailAdmin="dyks@volny.cz";
            $mailAdmin="pavla@vykopalova.com";
            $subjectAdmin="WESPR: nova zprava z webu";
            $bodyAdmin="Dobrý den, tento text je zpráva z webu vykopalova.com.<br /><br />\n\n";
            $bodyAdmin.="jméno: ".$form->values->name."<br />\n";
            $bodyAdmin.="email: ".$form->values->email."<br />\n";
            $bodyAdmin.="telefon: ".$form->values->phone."<br />\n";
            $bodyAdmin.="text: ".htmlentities($form->values->text,ENT_COMPAT,'utf-8')."<br /><br />\n\n";
            $bodyAdmin.="-------------<br>\n";
            $bodyAdmin.="Tento email automaticky připravil Webový správce WESPR. Neodpovídejte na něj.";
            $headersAdmin="From: <no-replay@vykopalova.com>\nContent-Type: text/html; charset=utf-8\n";
            $headersAdmin.="Return-Path: <no-replay@vykopalova.com>";

            $email=$form->values->email;
            $subject="vykopalova.com: informace o zaslané zprávě";
            $body="Dobrý den,<br><br>\n\n";
            $body.="na mých webových stránkách <a href='http://www.vykopalova.com/'>www.vykopalova.com</a> byla odeslana zpráva z vašeho emailu ".$form->values->email.".<br>\n";
            $body.="Pokud tato zpráva byla skutečně poslána vámi, brzy odpovím. V jiném připadě můžete tento email ignorovat.<br><br>\n\n";
            $body.="Děkuji za váš zájem a přejeme hezký den, Pavla Vykopalová.<br>\n";
            $body.="-------------<br>\n";
            $body.="Tento email automaticky připravil Webový správce WESPR. Neodpovídejte na něj.";
            $headers="From:vykopalova.com <no-replay@vykopalova.com>\nContent-Type: text/html; charset=utf-8\n";
            $headers.="Return-Path: <no-replay@vykopalova.com>";

            if(mail($mailAdmin,$subjectAdmin,$bodyAdmin,$headersAdmin) && mail($email,$subject,$body,$headers)) {
                    $this->flashMessage('Váše zpráva byla odeslána. Brzy dostanete odpověď.', 'success');
                    $this->redirect('this');
            }
            else {
                $this->flashMessage('S odesláním nastal problém. Omlouváme se za potíže, zkuste to později znovu.', 'error');
                $this->redirect('this');
            }
        }
        else {
            $this->flashMessage('Vyplňte prosím kontrolní otázku ve spodní části formuláře.', 'error');
            $this->redirect('this');
        }

   } 

}