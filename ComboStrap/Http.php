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
     * Dokuwiki test does not {@link \TestResponse::getStatusCode()} capture the status with all php function such
     * as {@link http_response_code},
     *
     * @param int $int
     */
    public static function setStatus(int $int)
    {
        /**
         * {@link http_status} function
         * that creates
         * header('HTTP/1.1 301 Moved Permanently');
         * header('HTTP/1.0 304 Not Modified');
         * header('HTTP/1.1 404 Not Found');
         *
         * not {@link http_response_code}
         */
        http_status($int);

    }
}
