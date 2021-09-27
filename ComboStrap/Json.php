<?php


namespace ComboStrap;


class Json
{

    public static function normalized(string $string)
    {
        return json_encode(json_decode($string), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}
