<?php

namespace ComboStrap;

class UrlEndpoint
{

    public static function createFetchUrl(): Url
    {

        return self::createEndPointUrl('lib/exe/fetch.php', '_media');

    }

    public static function createDetailUrl(): Url
    {
        return self::createEndPointUrl('lib/exe/detail.php', '_detail');
    }

    public static function createEndPointUrl($relativeNormalPath, $relativeRewritePath): Url
    {

        if (Site::hasUrlRewrite()) {
            $path = $relativeRewritePath;
        } else {
            $path = $relativeNormalPath;
        }
        try {
            $urlPathBaseDir = Site::getUrlPathBaseDir();
            $path = "$urlPathBaseDir/$path";
        } catch (ExceptionNotFound $e) {
            // ok
            $path = "/$path";
        }
        $url = Url::createEmpty()
            ->setPath($path);
        if (Site::shouldEndpointUrlBeAbsolute()) {
            $url->toAbsoluteUrl();
        }
        return $url;

    }
}
