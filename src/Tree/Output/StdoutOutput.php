<?php

namespace App\Tree\Output;

class StdoutOutput extends StreamOutput
{
    public function __construct()
    {
        parent::__construct(STDOUT);
    }

    public function writeString(string $data): void
    {
        fputs($this->outputFd, $data);
    }

    public function getTerminalSize(): array
    {
        $rows = 25;
        $cols = 80;
        $data = @exec('stty size');
        if (is_string($data)) {
            $data = trim($data);
            if (preg_match('/^[0-9]+ [0-9]+$/', $data)) {
                list($rows, $cols) = explode(' ', $data);
                $rows = (int) $rows;
                $cols = (int) $cols;
            }
        }
        return [$cols, $rows];
    }
}
