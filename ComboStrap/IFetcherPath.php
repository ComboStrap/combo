<?php

namespace ComboStrap;

/**
 * All fetcher that would answer an ajax call should
 * implements this interface
 */
interface IFetcherPath extends IFetcher
{

    /**
     * Return the local path of the resource
     * @return LocalPath
     */
    function getFetchPath(): LocalPath;

}
