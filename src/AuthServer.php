<?php
declare(strict_types=1);

namespace Somoza\CliAuth;

// @TODO: why do we need to manually do this or else we get an exception!?
include __DIR__.'/../vendor/ringcentral/psr7/src/functions_include.php';
include __DIR__.'/../vendor/react/promise/src/functions_include.php';
include __DIR__.'/../vendor/react/promise-stream/src/functions_include.php';

use React\EventLoop\Factory as ReactFactory;
use React\Http\Server as ReactServer;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use React\Socket\Server;
use Somoza\CliAuth\Middleware\FileHandler;
use Somoza\CliAuth\Middleware\TransferHandler;
use Somoza\CliAuth\Process\OpenProcessFactory;

/**
 * CLI Authentication server
 *
 * Example usage:
 *
 *     $storage = new stdClass(); // database, file, etc
 *     $server = new Server(8123);
 *     $server->authenticate()->then(function(AuthenticationResult $result) use ($storage) {
 *       $storage->accessToken = $result->getResponse()->getParsedBody()['accessToken'];
 *     });
 *
 * You can also pass custom middleware to the constructor as the second argument.
 *
 *
 * @package Somoza\CliAuth
 */
final class AuthServer
{
    const DEFAULT_PORT = 8123;

    /** @var int */
    private $port;

    /** @var callable[]|null */
    private $middleware;

    /**
     * @param int $port Port the server will listen to (default: 8123)
     * @param array|null $middleware Middleware stack. TransferHandler middleware will always be prepended
     */
    private function __construct(array $middleware = [], int $port = null)
    {
        $this->middleware = array_map(
            function (callable $i) {
                return $i;
            },
            $middleware
        );

        if (null === $port) {
            $port = self::DEFAULT_PORT;
        }
        $this->port = $port;
    }

    /**
     * @param string $filePath
     * @param int|null $port
     * @return AuthServer
     */
    public static function fromFile(string $filePath, int $port = null): AuthServer
    {
        return new static([new FileHandler($filePath)], $port);
    }

    /**
     * @param int|null $port
     * @return AuthServer
     */
    public static function demo(int $port = null): AuthServer
    {
        return new static([new FileHandler()], $port);
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
                $url = "http://127.0.0.1:{$this->port}/";
                (new OpenProcessFactory())->run([$url]);
            }
        );

        $promise = $deferred->promise();
        // always stop the loop once the promise is resolved or rejected
        $promise->always(
            function () use ($loop) {
                $loop->futureTick(
                    function () use ($loop) { // do it on next tick to allow serving the response
                        $loop->stop();
                    }
                );
            }
        );

        // add required middleware to the beginning of the custom middleware stack
        $middleware = $this->middleware;
        \array_unshift($middleware, new TransferHandler($deferred));

        // start the server
        $server = new ReactServer($middleware);
        $server->on(
            'error',
            function (\Exception $e) use ($deferred) {
                $deferred->reject($e);
            }
        );
        $socket = new Server($this->port, $loop);
        $server->listen($socket);
        $loop->run();

        return $promise;
    }
}
