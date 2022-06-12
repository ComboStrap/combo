<?php

namespace ComboStrap;

/**
 * A class that would return/process a path
 *
 * Example: {@link ImageFetch}
 */
interface Fetch
{

    /**
     * Return the URL where the resource can be fetched
     * @return Url
     */
    function getFetchUrl(): Url;

    /**
     * Return the path of the resource
     * @return Path
     */
    function getFetchPath(): Path;

    /**
     * The buster that should be added to the url
     * @return string
     */
    function getBuster(): string;

    /**
     * @param Url $url - the url
     * @return bool - if the fetch class accepts this url
     */
    function acceptsFetchUrl(Url $url): bool;

}
