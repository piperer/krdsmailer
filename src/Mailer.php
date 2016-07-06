<?php


namespace KRDS\mailer;

use \Swift_Mailer;
use PhpAmqpLib\Message\AMQPMessage;

class Mailer
{
    
    /** @var \Swift_Mailer */
    protected $mailer;

    protected $amqp;
    
    protected $fromAddress;

    protected $fromName;
    
    public function __construct(\Swift_Mailer $mailer, $amqp)
    {
        $this->mailer = $mailer;
        $this->amqp  = $amqp;  
       
    }

    
    /**
     * @param string $fromAddress
     */

    public function setFromAddress($fromAddress)
    {
        $this->fromAddress = $fromAddress;
    }

   

    /**
     * @param string $fromName
     */
    
    public function setFromName($fromName)
    {
        $this->fromName = $fromName;
    }

    

    

    /**
     * Format the fromEmail parameter for the Swift_Mailer.
     *
     * @return array
     */
    
    protected function getFromEmail()
    {
    
        return array($this->fromAddress => $this->fromName);
    
    }
    
    public function sendSingleEmail($toEmail, $body, $subject = 'Transactional email',  $sgHeaders = null, $attachments = null)
    {
        if (is_array($toEmail) && count($toEmail) > 1) {
                throw new \RuntimeException('A transactional Email can be sent to only one person at a time');
            }
        
        $this->sendMessage($toEmail, $subject, $body, $sgHeaders, $attachments);
    }


    public function sendBatchEmail($data)
    {
        // need to validate the data and then json encode it. 

        $data['fromEmail'] =  $this->getFromEmail();

        $data   =   json_encode($data);

        $channel = $this->amqp->channel();
        $channel->queue_declare('email_queue', false, true, false, false);

        $msg = new AMQPMessage($data, ['delivery_mode' => 2]);
        $channel->basic_publish($msg, '', 'email_queue');

        $channel->close();
        $this->amqp->close();

        $submitted = true;
    }
    

     
    protected function sendMessage($toEmail, $subject, $body, $sgHeaders, $attachments)
    {
        try{

            $fromEmail   =   $this->getFromEmail();
       
            $toEmail    =  is_array($toEmail)?$toEmail:[$toEmail] ;

            $message = \Swift_Message::newInstance()
                ->setSubject($subject)
                ->setTo($toEmail);


            $message->setBody($body);
            $message->addPart($body, 'text/html');

            // if contains SMTPAPI header add it

            if (null !== $sgHeaders) {
                $message->getHeaders()->addTextHeader('X-SMTPAPI', json_encode($sgHeaders));
            }

            // add attachments to email

            if ($attachments !== null and is_array($attachments)) {
                foreach ($attachments as $attachment) {
                    $attach = Swift_Attachment::fromPath($attachment['file'], $attachment['mime'])->setFilename($attachment['filename']);
                    $message->attach($attach);
                }
            }

            
            $message->setFrom($fromEmail);
            $this->mailer->send($message);
        }
        catch (Exception $e) {

            throw new \Exception('Error sending email out - ' . $e->getMessage());

        }
        
    }

    
}