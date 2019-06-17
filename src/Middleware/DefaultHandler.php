<?php
declare(strict_types=1);

namespace Somoza\CliAuth\Middleware;

use function file_get_contents;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\Response;

final class DefaultHandler
{
    const DEFAULT_SOURCE = __DIR__.'/../../pub/index.html';

    /** @var string */
    private $sourcePath;

    /**
     * @param string $sourcePath
     */
    public function __construct(?string $sourcePath = null)
    {
        if (null === $sourcePath) {
            $sourcePath = self::DEFAULT_SOURCE;
        }
        $this->sourcePath = $sourcePath;
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $body = file_get_contents($this->sourcePath);

        return new Response(
            200,
            ['Content-Type' => 'text/html'],
            $body
        );
    }


}
