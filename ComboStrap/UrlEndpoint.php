<?php

namespace ComboStrap;

class UrlEndpoint
{

    const LIB_EXE_FETCH_PHP = '/lib/exe/fetch.php';
    const LIB_EXE_DETAIL_PHP = '/lib/exe/detail.php';
    const LIB_EXE_RUNNER_PHP = '/lib/exe/taskrunner.php';
    const DOKU_PHP = '/doku.php';
    const LIB_EXE_AJAX_PHP = "/lib/exe/ajax.php";
    const DOKU_ENDPOINTS = [
        self::DOKU_PHP,
        self::LIB_EXE_FETCH_PHP,
        self::LIB_EXE_DETAIL_PHP,
        self::LIB_EXE_RUNNER_PHP,
        self::LIB_EXE_AJAX_PHP
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
     * @throws ExceptionBadSyntax
     * @throws ExceptionBadArgument
     */
    public static function createBaseUrl(): Url
    {
        return Url::createFromString(Site::getBaseUrl());
    }

    public static function createTaskRunnerUrl(): Url
    {
        return Url::createEmpty()->setPath(self::LIB_EXE_RUNNER_PHP);
    }

    public static function createAjaxUrl(): Url
    {
        return Url::createEmpty()->setPath(self::LIB_EXE_AJAX_PHP);
    }


}
