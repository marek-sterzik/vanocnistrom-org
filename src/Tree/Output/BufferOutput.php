<?php

namespace App\Tree\Output;

class BufferOutput implements OutputInterface
{
    private string $buffer = '';

    public function __construct(private int $cols, private int $rows)
    {
    }

    public function clear(): void
    {
        $this->buffer = '';
    }

    public function getContent(bool $clear = false): string
    {
        $ret = $this->buffer;
        if ($clear) {
            $this->clear();
        }
        return $ret;
    }

    public function writeString(string $data): void
    {
        $this->buffer .= $data;
    }

    public function getTerminalSize(): array
    {
        return [$this->cols, $this->rows];
    }
}
