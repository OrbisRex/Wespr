<?php
/**
 * Description of MailManager
 *
 * @author David, 2014
 * @license MIT
 */

namespace App\WesprModule;

use Nette;
use Nette\Mail\IMailer;
use Nette\Mail\Message;

class MailManager extends Nette\Object {
    /** @var SmtpMailer */
    protected $mailer;
    /** @var string Email */
    private $email;
    /** @var string email subject */
    private $subject;
    /** @var Template Object of Latte */
    private $template;
    /** @var Message Object Message */
    private $message;
    /** @var string Sender eamil */
    private $senderEmail;
    /** @var string Sender description */
    private $senderDescription;
    
    
    public function __construct(IMailer $mailer) {
        $this->mailer = $mailer;
    }
    
    public function setMessage($email, $subject, $template) {
        $this->email = $email;
        $this->subject = $subject;
        $this->template = $template;        
    }
    
    public function setMailer($email, $description) {
        $this->senderEmail = $email;
        $this->senderDescription = $description;
        
    }
    
    private function wrapMessage() {
        
        $this->message = new Message;
        //Nette\Diagnostics\Debugger::barDump($this->context->params);
        
        $this->message->setFrom($this->senderEmail, $this->senderDescription)
            ->addTo($this->email)
            ->setSubject($this->subject)
            ->setHtmlBody($this->template);
    }
    
    public function sendMail() {
        $this->wrapMessage();
        $this->mailer->send($this->message);
    }
}
