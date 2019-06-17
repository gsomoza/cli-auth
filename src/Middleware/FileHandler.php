<?php
declare(strict_types=1);

namespace Somoza\CliAuth\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\Response;
use Webmozart\Assert\Assert;
use function file_get_contents;

final class FileHandler
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
        Assert::file($sourcePath);
        $this->sourcePath = $sourcePath;
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
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
