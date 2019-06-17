<?php
declare(strict_types=1);

namespace Somoza\CliAuth;

// @TODO: why do we need to manually do this or else we get an exception!? check compatibility between ringcentral and react
require_once __DIR__.'/../../../vendor/ringcentral/psr7/src/functions.php';

use React\EventLoop\Factory as ReactFactory;
use React\Http\Server as ReactServer;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use Somoza\CliAuth\Middleware\DefaultHandler;
use Somoza\CliAuth\Middleware\TransferHandler;

final class Server
{
    const DEFAULT_PORT = 8123;

    /** @var int */
    private $port;

    /**
     * @param int $port Port the server will listen to (default: 8123)
     */
    public function __construct(int $port = null)
    {
        if (null === $port) {
            $port = self::DEFAULT_PORT;
        }
        $this->port = $port;
    }

    /**
     * Starts the local authentication server and launches a browser
     * @return PromiseInterface
     */
    public function authenticate(): PromiseInterface
    {
        $deferred = new Deferred();

        $loop = ReactFactory::create();
        // open the browser on the default page once the server started
        $loop->futureTick(
            function () {
                $open = new OpenProcessFactory();
                $url = "http://127.0.0.1:{$this->port}/";
                $open->create([$url])->run();
            }
        );
        // always stop the loop once the promise is resolved or rejected
        $promise = $deferred->promise();
        $promise->always(function () use ($loop) {
            $loop->futureTick(function () use ($loop) {
                $loop->stop();
            });
        });

        $server = new ReactServer([
            new TransferHandler($deferred),
            new DefaultHandler(),
        ]);
        $socket = new \React\Socket\Server($this->port, $loop);
        $server->listen($socket);

        $loop->run();

        return $promise;
    }
}
