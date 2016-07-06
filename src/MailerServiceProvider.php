<?php

namespace KRDS\mailer;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Silex\Provider\SwiftmailerServiceProvider;
use PhpAmqpLib\Connection\AMQPConnection;



class MailerServiceProvider implements ServiceProviderInterface
{
    /**
     * Registers services on the given app.
     *
     * This method should only be used to configure services and parameters.
     * It should not get services.
     *
     * @param Application $app An Application instance
     */

    public function register(Application $app)
    {
        // Default options.
        $default = [
                    'fromEmailAddress' => '*****',
                    'fromEmailName' => '*****',
                    'host' => '******',
                    'port' => '***',
                    'username' => '*****',
                    'password' => '********',
                    'encryption' => null,
                    'auth_mode' => null,
                    'rabbitmq'  => '****'
            
           
        ];
        // first validate all the configuration

        $this->validateConfigurations($default, $options);
       

       
        // register the AMQP service   

        $this->initAmqp($app);

        // need to register the swift mailer service provider

        $app->register(new SwiftmailerServiceProvider());
      
        
        // init the KRDS mailer
        
        $this->initKRDSMailer($app);
     

        
        
    }


    public function boot(Application $app)
    {
     
    }

    
    protected function createConnection($connection){

        return new AMQPConnection($connection['host'], $connection['port'], $connection['username'], $connection['password'], $connection['vhost']);
    }

    
    protected function validateConfigurations($default, $options){

        $missingConf = array_diff($default, $options);

        if(!empty($missingConf))
        {
            throw new \RuntimeException('missing configuration in `krds.mailer.options`: ' . implode(', ', $missingConf));    
        }    

        
    }

    protected function initAmqp(Application $app)
    {
        $app['amqp'] = $app->share(function($app) {
           
            $rabbitmqurl = $app['krds.mailer.options']['rabbitmq'];

            // RabbitMQ connection
            
            $rabbitmq = parse_url($rabbitmqurl);
            $defaultOpts =  [
                    'host'     => $rabbitmq['host'],
                    'port'     => isset($rabbitmq['port']) ? $rabbitmq['port'] : 5672,
                    'username' => $rabbitmq['user'],
                    'password' => $rabbitmq['pass'],
                    'vhost'    => substr($rabbitmq['path'], 1) ?: '/',
                ];

            return $this->createConnection($defaultOpts);
        });
    }

    protected function initKRDSMailer(Application $app)
    {
        
        
        $app['krds.mailer'] = function($app) {

            
            
            $app['swiftmailer.options'] = array(
                    'host' => $app['krds.mailer.options']['host'],
                    'port' => $app['krds.mailer.options']['port'],
                    'username' => $app['krds.mailer.options']['username'],
                    'password' => $app['krds.mailer.options']['password'],
                    'encryption' => $app['krds.mailer.options']['encryption'],
                    'auth_mode' => $app['krds.mailer.options']['auth_mode']
                );

            
            $missingDeps = array();
            if (!isset($app['mailer'])) $missingDeps[] = 'SwiftMailerServiceProvider';
            
           
            if (!empty($missingDeps)) {
                throw new \RuntimeException('To access the KRDS mailer you must enable the following missing dependencies: ' . implode(', ', $missingDeps));
            }

            
           
            $mailer = new Mailer($app['mailer'],$app['amqp']);
            
            if (!$app['krds.mailer.options']['fromEmailAddress']) {
            throw new \RuntimeException('Invalid configuration. Mailer fromEmail address is required when mailer is enabled.');
            }

            if (!$app['krds.mailer.options']['fromEmailName'] ) {
                throw new \RuntimeException('Invalid configuration. Mailer fromEmail name is required when mailer is enabled.');
            } 

            $mailer->setFromAddress($app['krds.mailer.options']['fromEmailAddress']);
            $mailer->setFromName($app['krds.mailer.options']['fromEmailName']);
           

            return $mailer;
        };


    }



    
}
