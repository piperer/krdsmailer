##Versions

To include this in your Silex 1.x project please use krds/mailer 1.*

For Silex 2.x projects please krds/mailer dev-master

##Register Provider

First **register** the Mailer service provider

$app->register(new Krdsmailer\MailerServiceProvider()
'krds.mailer.options' => [
		            'fromEmailAddress' => '*****',
                    'fromEmailName' => '*****',
                    'host' => '******',
                    'port' => '***',
                    'username' => '*****',
                    'password' => '********',
                    'encryption' => null,
                    'auth_mode' => null,
                    'rabbitmq'  => '****'
		]
	]
);

##Sending out Emails

Using the **Transactional** method. This sends out an email immediately without using the Email Queue

$app['krds.mailer']->sendTransactionalEmail(['test@krds.fr'], 'This is a test  transactional message');


Using the **Queue** method. This sends out an email to Rabbit MQ, where workers watch the queue and send them out. 

$app['krds.mailer']->sendEmailToQueue(['test@krds.fr'], 'This is a test  transactional message');



 
