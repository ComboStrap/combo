<?php


namespace ComboStrap;


class Http
{

    public static function removeHeaderIfPresent(string $key)
    {
        foreach (headers_list() as $header) {
            if (preg_match("/$key/i", $header)) {
                header_remove($key);
            }
        }

    }

    public static function getHeader(string $name)
    {

        $result = array();
        foreach (self::getHeaders() as $header) {
            if (substr($header, 0, strlen($name) + 1) == $name . ':') {
                $result[] = $header;
            }
        }

        return count($result) == 1 ? $result[0] : $result;

    }

    private static function getHeaders(): array
    {
        return (function_exists('xdebug_get_headers') ? xdebug_get_headers() : headers_list());
    }

    /**
     * Set the HTTP status
     * (Dokuwiki test does not capture all status, there is several way
     * to do it, this function centralize it)
     * @param int $int
     */
    public static function setStatus(int $int)
    {
        http_status($int);
        //header('HTTP/1.0 304 Not Modified');
    }
}
