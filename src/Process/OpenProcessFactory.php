<?php
declare(strict_types=1);

namespace Somoza\CliAuth\Process;

use React\ChildProcess\Process;
use React\EventLoop\Factory;
use Webmozart\Assert\Assert;

/**
 * Uses the system's "open" or "start" command depending on the current operating system
 */
final class OpenProcessFactory
{
    /** @var string */
    private $command;

    /**
     * OpenProcessFactory constructor.
     */
    public function __construct()
    {
        $this->command = $this->isWindows() ? 'start' : 'open';
    }

    /**
     * @param string[] $openArgs Each of the arguments to be passed to the "start" or "open" command
     * @return void
     */
    public function run(array $openArgs): void
    {
        Assert::minCount($openArgs, 1);
        Assert::allString($openArgs);

        \array_unshift($openArgs, $this->command);
        $command = \implode(' ', $openArgs);

        $loop = Factory::create();
        $process = new Process($command, null, null, []);
        $process->start($loop);

        return;
    }

    /**
     * Detects whether we're running on Windows or not
     *
     * @return bool
     */
    private function isWindows(): bool
    {
        return \strtolower(\substr(\php_uname('s'), 0, 3)) === 'win';
    }
}
