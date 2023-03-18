<?php

namespace ComboStrap\Web;

use ComboStrap\ExceptionBadArgument;
use ComboStrap\ExceptionBadSyntax;
use ComboStrap\LogUtility;
use ComboStrap\Site;
use ComboStrap\Web\Url;

class UrlEndpoint
{

    const LIB_EXE_FETCH_PHP = '/lib/exe/fetch.php';
    const LIB_EXE_DETAIL_PHP = '/lib/exe/detail.php';
    const LIB_EXE_RUNNER_PHP = '/lib/exe/taskrunner.php';
    const LIB_EXE_CSS_PHP = '/lib/exe/css.php';
    const DOKU_PHP = '/doku.php';
    const LIB_EXE_AJAX_PHP = "/lib/exe/ajax.php";
    const DOKU_ENDPOINTS = [
        self::DOKU_PHP,
        self::LIB_EXE_FETCH_PHP,
        self::LIB_EXE_DETAIL_PHP,
        self::LIB_EXE_RUNNER_PHP,
        self::LIB_EXE_AJAX_PHP,
        self::LIB_EXE_CSS_PHP
    ];


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


    /**
     *
     */
    public static function createBaseUrl(): Url
    {
        $url = Site::getBaseUrl();
        try {
            return Url::createFromString($url);
        } catch (ExceptionBadArgument|ExceptionBadSyntax $e) {
            LogUtility::error("The base Url ($url) is not a valid url. Empty URL returned. Error: {$e->getMessage()}", "urlendpoint",$e);
            return Url::createEmpty();
        }
    }

    public static function createTaskRunnerUrl(): Url
    {
        return Url::createEmpty()->setPath(self::LIB_EXE_RUNNER_PHP);
    }

    public static function createAjaxUrl(): Url
    {
        return Url::createEmpty()->setPath(self::LIB_EXE_AJAX_PHP);
    }

    public static function createCssUrl(): Url
    {
        return Url::createEmpty()->setPath(self::LIB_EXE_CSS_PHP);
    }


}
