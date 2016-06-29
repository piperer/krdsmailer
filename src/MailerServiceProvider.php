<?php

namespace Krdsmailer;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Silex\Provider\SwiftmailerServiceProvider;



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
        $app['krds.mailer.default'] = array(

             // Configure the user mailer for sending password reset and email confirmation messages.
                    'fromEmailAddress' => 'contact@krds.com',
                    'fromEmailName' => 'TestEMail',
                    'host' => 'smtp.sendgrid.net',
                    'port' => '587',
                    'username' => 'krdsphp',
                    'password' => 'JsmHDjee9lW74xdTUQjFar5',
                    'encryption' => null,
                    'auth_mode' => null
            
           
        );
        // need to register the swift mailer service provider
        $app->register(new SwiftmailerServiceProvider());

        // RabbitMQ connection
        $rabbitmq = parse_url('amqp://jwqlzqdc:Mj9chY3Xu9zJYzt_X375o4mivpJgttZX@chicken.rmq.cloudamqp.com/jwqlzqdc');
        $app->register(new AmqpServiceProvider, [
            'amqp.connections' => [
                'default' => [
                    'host'     => $rabbitmq['host'],
                    'port'     => isset($rabbitmq['port']) ? $rabbitmq['port'] : 5672,
                    'username' => $rabbitmq['user'],
                    'password' => $rabbitmq['pass'],
                    'vhost'    => substr($rabbitmq['path'], 1) ?: '/',
                ],
            ],
        ]);

        // Redis database -- using the heroku free redis server
        //$app->register(new Predis\Silex\ClientServiceProvider(), [
        //    'predis.parameters' => 'redis://h:p7nfolsc7o3d2m77v5gak5gvjup@ec2-46-137-72-173.eu-west-1.compute.amazonaws.com:12989',
        //]);
        

        // Initialize $app['krds.mailer.options'].
        $app['krds.mailer.init'] = $app->protect(function() use ($app) {
            $options = $app['krds.mailer.default'];
            if (isset($app['krds.mailer.options'])) {
                // Merge default and configured options
                $options = array_replace_recursive($options, $app['krds.mailer.options']);

               
            }

            $app['krds.mailer.options'] = $options;
            
        });

        

        

         // KRDS mailer initialization.
        $app['krds.mailer'] = function($app) {

            $options = $app['krds.mailer.default'];
            

            $app['krds.mailer.init']();
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

    public function boot(Application $app)
    {
     
    }

    protected function createConnection($connection){

        return new AMQPConnection($connection['host'], $connection['port'], $connection['username'], $connection['password'], $connection['vhost']);
    }



    
}
