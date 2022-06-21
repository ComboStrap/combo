<?php

namespace ComboStrap;

/**
 * A class that would return/process a path
 *
 * Example: {@link FetchImage}
 */
interface Fetch
{
    /**
     * buster got the same value
     * that the `rev` attribute (ie mtime)
     * We don't use rev as cache buster because Dokuwiki still thinks
     * that this is an old file and search in the attic
     * as seen in the function {@link mediaFN()}
     *
     * The value used by Dokuwiki for the buster is tseed.
     */
    public const CACHE_BUSTER_KEY = "tseed";
    public const FETCHER_KEY = "fetcher";


    /**
     * Return the URL where the resource can be fetched
     * @param Url|null $url - the url to be able to pass along in the hierarchy
     * @return Url
     */
    function getFetchUrl(Url $url = null): Url;

    /**
     * Return the path of the resource
     * @return Path
     */
    function getFetchPath(): Path;

    /**
     * The buster that should be added to the url.
     * @return string
     */
    function getBuster(): string;

    /**
     * @param Url $url - the url
     * @return bool - if the fetch class accepts this url
     */
    function acceptsFetchUrl(Url $url): bool;

    /**
     * @return Mime - the mime of the
     *
     * You can also ask it via {@link Fetch::getFetchPath()} but it will
     * perform the processing. If you want to create a cache file path with the good extension
     * this is the way to go.
     */
    public function getMime(): Mime;

    /**
     * A convenient way to build a fetcher from a URL
     * This method calls the function {@link Fetch::buildFromTagAttributes()}
     * @param Url $url
     * @return Fetch
     */
    public function buildFromUrl(Url $url): Fetch;

    /**
     * @param TagAttributes $tagAttributes - the attributes
     * @return Fetch
     */
    public function buildFromTagAttributes(TagAttributes $tagAttributes): Fetch;

    /**
     * @throws ExceptionNotFound
     * @return string
     */
    public function getRequestedCache(): string;

    /**
     * @return string - an unique name
     */
    public function getName(): string;
}
