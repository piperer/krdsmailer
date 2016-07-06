# Installation

```php
     composer require krds/mailer
```

# Register Provider

Then you will need to  **register** the KRDS Mailer service provider

```php
$app->register(new KRDS\mailer\MailerServiceProvider(),[
     'krds.mailer.options' => [
                    'fromEmailAddress' => '**',
                    'fromEmailName' => '**',
                    'host' => '**',
                    'port' => '587',
                    'username' => '**',
                    'password' => '**',
                    'encryption' => null,
                    'auth_mode' => null,
                    'rabbitmq'  => '**'
          ]
     ]);
```

# Sending out Emails

Using the **Transactional** method. This sends out an email immediately without using the Email Queue

```php
$app['krds.mailer']->sendSingleEmail($toEmail, $body, $subject,  $sgHeaders = null, $attachments = null)
```


Using the **Queue** method. This sends out an email to Rabbit MQ, where workers watch the queue and send them out. 

```php
$app['krds.mailer']->sendBatchEmail(['toEmail' => 'test@test.fr', 'body' => 'This is a test  batch message', 'subject' => 'subject'], 'sgHeaders' => 'testheader');
```


 
