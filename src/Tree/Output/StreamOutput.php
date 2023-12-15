<?php

namespace App\Tree\Output;

class StreamOutput implements OutputInterface
{
    public function __construct(private $outputFd)
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

