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

    /**
     * @throws ExceptionNotFound
     */
    public static function getFirstHeader(string $name, array $headers = null): string
    {

        $result = self::getHeadersForName($name, $headers);

        if (count($result) == 0) {
            throw new ExceptionNotFound("No header was found with the header name $name");
        }

        return $result[0];


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
         * See also {@link Http::getFirstHeader()}
         * if this does not work
         */
        return http_response_code();
    }

    public static function setMime(string $mime)
    {
        $contentTypeHeader = HttpResponse::HEADER_CONTENT_TYPE;
        header("$contentTypeHeader: $mime");
    }


    public static function getHeadersForName(string $name, ?array $headers): array
    {
        if ($headers === null) {
            $headers = self::getHeaders();
        }

        $result = array();
        $headerNameNormalized = trim(strtolower($name));
        foreach ($headers as $header) {
            $loc = strpos($header, ":");
            if ($loc === false) {
                continue;
            }
            $actualHeaderName = substr($header, 0, $loc);
            $actualHeaderNameNormalized = trim(strtolower($actualHeaderName));
            if ($actualHeaderNameNormalized === $headerNameNormalized) {
                $result[] = $header;
            }
        }
        return $result;
    }

    /**
     * @throws ExceptionNotExists
     */
    public static function extractHeaderValue(string $header): string
    {
        $positionDoublePointSeparator = strpos($header, ':');
        if ($positionDoublePointSeparator === false) {
            throw new ExceptionNotExists("No value found");
        }
        return trim(substr($header, $positionDoublePointSeparator + 1));
    }

    /**
     * PHP is blocking and fsockopen also.
     * Don't use it in a page rendering flow
     * https://segment.com/blog/how-to-make-async-requests-in-php/
     * @param $url
     */
    function sendAsyncRequest($url)
    {
        $parts = parse_url($url);
        $fp = fsockopen($parts['host'], isset($parts['port']) ? $parts['port'] : 80, $errno, $errstr, 30);
        $out = "GET " . $parts['path'] . "?" . $parts['query'] . " HTTP/1.1\r\n";
        $out .= "Host: " . $parts['host'] . "\r\n";
        $out .= "Content-Length: 0" . "\r\n";
        $out .= "Connection: Close\r\n\r\n";

        fwrite($fp, $out);
        fclose($fp);
    }


}
