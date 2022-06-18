<?php

namespace ComboStrap;

class UrlEndpoint
{

    const LIB_EXE_FETCH_PHP = '/lib/exe/fetch.php';
    const LIB_EXE_DETAIL_PHP = '/lib/exe/detail.php';
    const DOKU_PHP = '/doku.php';

    public static function createFetchUrl(): Url
    {
        return Url::createEmpty()->setPath(self::LIB_EXE_FETCH_PHP);
    }

    public static function createDetailUrl(): Url
    {
        return Url::createEmpty()->setPath(self::LIB_EXE_DETAIL_PHP);
    }


    public static function createComboStrapUrl(): Url
    {
        return Url::createEmpty()
            ->setScheme("https")
            ->setHost("combostrap.com");
    }

    public static function createSupportUrl(): Url
    {

        return self::createComboStrapUrl()
            ->setPath("support");

    }

    public static function createDokuUrl(): Url
    {

        return Url::createEmpty()->setPath(self::DOKU_PHP);

    }


}
