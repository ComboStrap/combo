<?php

namespace ComboStrap;

/**
 * A class that returns {@link Fetcher::getFetchPath() a path}
 *
 * It represents a fetch file and possible processing attributes
 *
 * Example
 *   * svg file if the requested image width is set, it will generate it
 *
 * The request may come from:
 *   * a {@link Fetcher::buildFromUrl() URL}
 *   * a {@link Fetcher::buildFromTagAttributes() attributes}
 *
 * The hierarchy is {@link Mime} based.
 *
 * Example:
 *   * {@link FetcherTraitImage} such as:
 *     * {@link FetcherSvg} that can process and return svg
 *     * {@link FetcherRaster} that can process and return raster image
 *     * {@link FetcherVignette} that returns a raster image from page metadata
 *     * {@link FetcherSnapshot} that returns a snapshot image from a page
 *   * {@link FetcherLocalPath} that returns all type of local file without processing
 *

 */
interface Fetcher
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
     * @return Mime - the mime of the
     *
     * You can also ask it via {@link Fetcher::getFetchPath()} but it will
     * perform the processing. If you want to create a cache file path with the good extension
     * this is the way to go.
     */
    public function getMime(): Mime;

    /**
     * A convenient way to build a fetcher from a URL
     * This method calls the function {@link Fetcher::buildFromTagAttributes()}
     * @param Url $url
     * @return Fetcher
     */
    public function buildFromUrl(Url $url): Fetcher;

    /**
     * @param TagAttributes $tagAttributes - the attributes
     * @return Fetcher
     */
    public function buildFromTagAttributes(TagAttributes $tagAttributes): Fetcher;

    /**
     * @throws ExceptionNotFound
     * @return string
     */
    public function getRequestedCache(): string;

    /**
     * @return string - an unique name
     */
    public function getFetcherName(): string;
}
