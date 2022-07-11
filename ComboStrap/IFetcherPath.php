<?php

namespace ComboStrap;

/**
 * All fetcher that would answer an ajax call should
 * implements this interface
 */
interface IFetcherPath extends IFetcher
{

    /**
     * Return the path of the resource
     * @return Path
     */
    function getFetchPath(): Path;

}
