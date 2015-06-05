<?php

/*
 * This file is part of Rocketeer
 *
 * (c) Maxime Fabre <ehtnam6@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rocketeer\Services\Connections;

use Rocketeer\Exceptions\ConnectionException;
use Rocketeer\Exceptions\MissingCredentialsException;
use Rocketeer\Services\Connections\Connections\Connection;
use Rocketeer\TestCases\RocketeerTestCase;

class RemoteHandlerTest extends RocketeerTestCase
{
    /**
     * @var RemoteHandler
     */
    protected $handler;

    public function setUp()
    {
        parent::setUp();

        $this->handler = new RemoteHandler($this->app);
        unset($this->app['rocketeer.command']);
    }

    public function testCanCreateConnection()
    {
        $this->swapConnections([
            'production' => [
                'host' => 'foobar.com',
                'username' => 'foobar',
                'password' => 'foobar',
            ],
        ]);

        $connection = $this->handler->connection();

        $this->assertInstanceOf(Connection::class, $connection);
        $this->assertEquals('production', $connection->getName());
        $this->assertEquals('foobar', $connection->getUsername());
    }

    public function testThrowsExceptionIfMissingCredentials()
    {
        $this->setExpectedException(MissingCredentialsException::class);

        $this->swapConnections([
            'production' => [
                'host' => 'foobar.com',
                'username' => 'foobar',
            ],
        ]);

        $this->handler->connection();
    }

    public function testThrowsExceptionIfMissingInformations()
    {
        $this->setExpectedException(MissingCredentialsException::class);

        $this->swapConnections([
            'production' => [
                'username' => 'foobar',
                'password' => 'foobar',
            ],
        ]);

        $this->handler->connection();
    }

    public function testCachesConnections()
    {
        $this->swapConnections([
            'production' => [
                'host' => 'foobar.com',
                'username' => 'foobar',
                'password' => 'foobar',
            ],
        ]);

        $connection = $this->handler->connection();
        $this->assertInstanceOf(Connection::class, $connection);
        $this->assertEquals('production', $connection->getName());

        $this->swapConnections([
            'production' => [],
        ]);

        $connection = $this->handler->connection();
        $this->assertInstanceOf(Connection::class, $connection);
        $this->assertEquals('production', $connection->getName());
    }

    public function testThrowsExceptionIfUnableToConnect()
    {
        $this->setExpectedException(ConnectionException::class);

        $this->swapConnections([
            'production' => [
                'host' => '127.0.0.1',
                'username' => 'foobar',
                'password' => 'foobar',
            ],
        ]);

        $this->handler->run('ls');
    }

    public function testDoesntReturnWrongCredentials()
    {
        $this->swapConnections([
            'production' => [
                'servers' => [
                    [
                        'host' => 'foo.com',
                        'username' => 'foo',
                        'password' => 'foo',
                    ],
                    [
                        'host' => 'bar.com',
                        'username' => 'bar',
                        'password' => 'bar',
                    ],
                ],
            ],
        ]);

        // Setting connection to server 1
        $this->connections->setConnection('production', 1);
        $connection = $this->handler->connection('production', 1);

        $this->assertInstanceOf(Connection::class, $connection);
        $this->assertEquals('production', $connection->getName());
        $this->assertEquals('bar', $connection->getUsername());

        // Setting connection to server 0
        $this->connections->setConnection('production', 0);
        $connection = $this->handler->connection('production', 0);

        $this->assertInstanceOf(Connection::class, $connection);
        $this->assertEquals('production', $connection->getName());
        $this->assertEquals('foo', $connection->getUsername());
    }

    public function testSetsRolesOnCreation()
    {
        $this->swapConnections([
            'production' => [
                'host' => 'foobar.com',
                'username' => 'foobar',
                'password' => 'foobar',
                'roles' => ['foo', 'bar'],
            ],
        ]);

        $connection = $this->handler->connection();

        $this->assertInstanceOf(Connection::class, $connection);
        $this->assertEquals(['foo', 'bar'], $connection->getRoles());
    }

    public function testShowsConnectionDetailsOnMissingCredentials()
    {
        $this->setExpectedException(MissingCredentialsException::class, 'With credentials');

        $this->swapConnections([
            'production' => [
                'host' => 'foobar.com',
                'username' => 'foobar',
            ],
        ]);

        $this->handler->connection();
    }

    public function testCanPurgeCachedConnections()
    {
        $this->swapConnections([
            'production' => [
                'host' => 'foobar.com',
                'username' => 'foobar',
                'password' => 'foobar',
            ],
        ]);

        $connection = $this->handler->connection();
        $this->assertInstanceOf(Connection::class, $connection);
        $this->assertEquals('production', $connection->getName());
        $this->assertEquals('foobar', $connection->getUsername());

        $this->swapConnections([
            'production' => [
                'host' => 'barbaz.com',
                'username' => 'barbaz',
                'password' => 'barbaz',
            ],
        ]);

        $this->handler->disconnect();
        $connection = $this->handler->connection();
        $this->assertInstanceOf(Connection::class, $connection);
        $this->assertEquals('production', $connection->getName());
        $this->assertEquals('barbaz', $connection->getUsername());
    }

    public function testFiresEventWhenConnectedToServer()
    {
        $this->expectOutputString('connected');

        $this->events->addListener('connected.production', function () {
           echo 'connected';
        });

        $this->swapConnections([
            'production' => [
                'host' => 'foobar.com',
                'username' => 'foobar',
                'password' => 'foobar',
            ],
        ]);

        $this->handler->connection();
    }
}
