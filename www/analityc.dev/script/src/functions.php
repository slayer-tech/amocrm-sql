<?php
function Write($filename, $text) : void // Логгирование
{
    $file = "./logs/$filename";

    file_put_contents($file, sprintf(
        '%s%s========================================================================================================================%s',
        print_r([
            "data" => $text,
            "time" => date('d.m.Y H:i:s')
        ], true),
        PHP_EOL . PHP_EOL,
        PHP_EOL . PHP_EOL
    ), FILE_APPEND);
}
