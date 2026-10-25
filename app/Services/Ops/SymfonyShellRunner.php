<?php

namespace App\Services\Ops;

use Symfony\Component\Process\Process;

class SymfonyShellRunner implements ShellRunner
{
    public function run(string $command, array $env = [], ?int $timeout = 3600): array
    {
        $process = Process::fromShellCommandline($command, null, $env, null, $timeout);
        $process->run();

        return ['code' => $process->getExitCode() ?? 1, 'output' => $process->getOutput(), 'error' => $process->getErrorOutput()];
    }
}
