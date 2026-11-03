<?php

namespace App\Services\Ops;

/** Runs a shell command with extra environment variables (so secrets never appear on the command line). */
interface ShellRunner
{
    /** @return array{code: int, output: string, error: string} */
    public function run(string $command, array $env = [], ?int $timeout = 3600): array;
}
