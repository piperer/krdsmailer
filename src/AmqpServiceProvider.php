<?php
namespace Krdsmailer;

use Silex\Application;
use Silex\ServiceProviderInterface;
use PhpAmqpLib\Connection\AMQPConnection;

class AmqpServiceProvider implements ServiceProviderInterface
{
    const AMQP = 'amqp';
    const AMQP_CONNECTIONS = 'amqp.connections';
    const AMQP_FACTORY = 'amqp.factory';
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
        $app[self::AMQP_CONNECTIONS] = array(
            'default' => array(
                'host' => 'localhost',
                'port' => 5672,
                'username' => 'guest',
                'password' => 'guest',
                'vhost' => '/'
            )
        );

        $app[self::AMQP_FACTORY] = $app->factory(function ($host = 'localhost', $port = 5672, $username = 'guest', $password = 'guest', $vhost = '/') use ($app) {
            return $this->createConnection($host, $port, $username, $password, $vhost);
        });

        $app[self::AMQP] = function($app) {
           
            return $this->createConnection($app[self::AMQP_CONNECTIONS]['default']);
            };
  
    }

    public function boot(Application $app)
    {
     
    }
    protected function createConnection($connection){

        return new AMQPConnection($connection['host'], $connection['port'], $connection['username'], $connection['password'], $connection['vhost']);
    }
}