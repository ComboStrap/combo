<?php

namespace ComboStrap;

/**
 *
 *
 * A class that returns
 *
 * It represents a fetch of:
 *   * a {@link IFetcherPath::getFetchPath() file}
 *   * or {@link IFetcherString::getFetchString() string}
 *
 * Example
 *   * serving a svg file with different request properties (if the requested image width is set, it will generate a new svg from the source)
 *
 * The request may come from:
 *   * a {@link IFetcher::buildFromUrl() URL}
 *   * a {@link IFetcher::buildFromTagAttributes() attributes}
 *
 * TODO: The handler/fetcher should also be able to be call in a process function
 *   passing the the {@link ExecutionContext} and pass it the result (a string or a file or whatever)
 *   you get then a functional interface (Then we don't need to know the returned type
 *   and the interface {@link IFetcherPath} and {@link IFetcherString} are not needed
 *   we could also add a function that could cast the result
 *
 * The hierarchy is {@link Mime} based.
 *
 * Example:
 *   * {@link FetcherTraitImage} such as:
 *     * {@link FetcherSvg} that can process and return svg
 *     * {@link FetcherRaster} that can process and return raster image
 *     * {@link FetcherVignette} that returns a raster image from page metadata
 *     * {@link FetcherScreenshot} that returns a snapshot image from a page
 *   * {@link FetcherRawLocalPath} that returns all type of local file without processing
 *
 */
interface IFetcher
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

    /**
     * The property in the URL that identify the fetcher
     */
    public const FETCHER_KEY = "fetcher";


    /**
     * Return the URL where the resource can be fetched
     * @param Url|null $url - the url to be able to pass along in the hierarchy
     * @return Url
     */
    function getFetchUrl(Url $url = null): Url;


    /**
     * The buster that should be added to the url.
     * @return string
     * @throws ExceptionNotFound - if the buster cannot be calculated (file does not exist for instance)
     */
    function getBuster(): string;


    /**
     * @return Mime - the mime of the output
     *
     * You can also ask it via {@link IFetcher::getFetchPath()} but it will
     * perform the processing. If you want to create a cache file path with the good extension
     * this is the way to go.
     */
    public function getMime(): Mime;

    /**
     * A convenient way to build a fetcher from a URL
     * This method calls the function {@link IFetcher::buildFromTagAttributes()}
     * @param Url $url
     * @return IFetcher
     */
    public function buildFromUrl(Url $url): IFetcher;

    /**
     * @param TagAttributes $tagAttributes - the attributes
     * @return IFetcher
     */
    public function buildFromTagAttributes(TagAttributes $tagAttributes): IFetcher;

    /**
     * Get the cache value requested
     * @throws ExceptionNotFound
     * @return string
     */
    public function getRequestedCache(): string;

    /**
     * @return LocalPath - the cache if any
     * @throws ExceptionNotSupported
     */
    public function getContentCachePath(): LocalPath;

    /**
     * @return string - an unique name that is added in the fetcher key of the URL
     *
     * Note that because dokuwiki does a sanitizing on the do custom action.
     * The name should not have any space or separator (What fuck up is fucked up)
     */
    public function getFetcherName(): string;


    /**
     * @return IFetcher - process and feed the cache
     * @throws ExceptionNotSupported - if the cache is not supported
     */
    public function process(): IFetcher;

    /**
     * @return string - a label to the resource returned (used in img tag, ...)
     *
     * If the resource is:
     * - based on a local path, it may be the path name
     * - generated from a page, it may be the page title
     * ...
     */
    public function getLabel(): string;


}
