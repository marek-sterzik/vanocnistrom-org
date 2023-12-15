<?php

namespace App\Tree\Output;

interface OutputInterface
{
    public function writeString(string $data): void;
    public function getTerminalSize(): array;
}
