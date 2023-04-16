<?php

namespace ComboStrap;

/**
 * All fetcher that would answer an ajax call that returns a file
 * should implement it
 * TODO: The handler/fetcher should just get the {@link ExecutionContext} in a process function
 *   and pass the result (a string or a file or whatever)
 *   you get then a functional interface
 */
interface IFetcherPath extends IFetcher
{

    /**
     * Return the local path of the resource
     * (process it if needed and save it in the cache)
     * @return LocalPath
     */
    function getFetchPath(): LocalPath;

}
