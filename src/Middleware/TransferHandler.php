<?php
declare(strict_types=1);

namespace Somoza\CliAuth\Middleware;

use Psr\Http\Message\ServerRequestInterface;
use React\Http\Response;
use React\Promise\Deferred;
use Somoza\CliAuth\AuthenticationResult;

/**
 * Handles POST /transfer by resolving a Deferred instance with the value of the request. This allows clients
 * to retrieve values of the request without having to use their own middleware
 *
 * @package Somoza\CliAuth\Middleware
 */
final class TransferHandler
{
    const URI_TRANSFER = '/transfer';

    /** @var Deferred */
    private $deferred;

    /**
     * @param Deferred $deferred The promise that will be resolved for transfer of authentication data
     */
    public function __construct(Deferred $deferred)
    {
        $this->deferred = $deferred;
    }

    /**
     * @param ServerRequestInterface $request
     * @param callable $next
     * @return mixed
     */
    public function __invoke(ServerRequestInterface $request, callable $next)
    {
        $path = $request->getUri()->getPath();
        echo $path;
        if ($path === self::URI_TRANSFER && $request->getMethod() === 'POST') {
            $response = new Response(200, ['Content-Type' => 'text/html'], 'You can now close this window.');
            $this->deferred->resolve(new AuthenticationResult($request, $response));
            return $response;
        }

        return $next($request);
    }
}
