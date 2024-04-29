<?php

namespace Jeeinn\QfAppBuilder;

class Utils
{
    public static function formatMsg($str = ''): string
    {
        return '[' . date('Y-m-d H:i:s') . '] ' . $str . PHP_EOL;
    }
}