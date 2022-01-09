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

    public static function getStatus()
    {
        /**
         * See also {@link Http::getHeader()}
         * if this does not work
         */
        return http_response_code();
    }

    public static function setMime(string $mime)
    {
        $contentTypeHeader = Mime::HEADER_CONTENT_TYPE;
        header("$contentTypeHeader: $mime");
    }

    public static function setJsonMime()
    {
        Http::setMime(Mime::JSON);
    }

    /**
     * PHP is blocking and fsockopen also.
     * Don't use it in a page rendering flow
     * https://segment.com/blog/how-to-make-async-requests-in-php/
     * @param $url
     */
    function sendAsyncRequest($url)
    {
        $parts=parse_url($url);
        $fp = fsockopen($parts['host'],isset($parts['port'])?$parts['port']:80,$errno, $errstr, 30);
        $out = "GET ".$parts['path'] . "?" . $parts['query'] . " HTTP/1.1\r\n";
        $out.= "Host: ".$parts['host']."\r\n";
        $out.= "Content-Length: 0"."\r\n";
        $out.= "Connection: Close\r\n\r\n";

        fwrite($fp, $out);
        fclose($fp);
    }


}
