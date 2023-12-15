<?php

namespace App\Utility;

class CodeGenerator
{
    const CHARS="ABCDEFGHIJKLMNOPQRSTUVWXYZ2345679";
    const SUBSTITUTIONS = [
        '0' => 'O',
        '1' =>'I',
        '8' => 'B',
    ];
    const LENGTH = 8;

    public function __construct()
    {
    }

    public function generateCode(): string
    {
        $code = $this->realGenerateCode();
        return $this->canonizeCode($code);
    }

    public function canonizeCode(string $code): string
    {
        $code = strtoupper($code);
        foreach (self::SUBSTITUTIONS as $str => $replace) {
            $code = str_replace((string)$str, $replace, $code);
        }
        return $code;
    }

    private function realGenerateCode(): string
    {
        $code = '';
        $max = strlen(self::CHARS) - 1;
        for ($i = 0; $i < self::LENGTH; $i++) {
            $code .= self::CHARS[random_int(0, $max)];
        }
        return $code;
    }
}
