<?php

namespace App\Tree\Output;

class StreamOutput implements OutputInterface
{
    /**
     * @param resource $outputFd
     */
    public function __construct(protected $outputFd)
    {
    }

    public function writeString(string $data): void
    {
        fputs($this->outputFd, $data);
    }

    public function getTerminalSize(): array
    {
        return [80, 25];
    }
}
