<?php

namespace ComboStrap;

/**
 * Return raw files
 */
class FetcherRaw extends FetcherAbs
{

    use FetcherRawTrait;

    const SRC_QUERY_PARAMETER = "src";
    const RAW = "raw";


    public static function createFromPath(DokuPath $dokuPath): FetcherRaw
    {
        $fetcherRaw = self::createEmpty();
        $fetcherRaw->setOriginalPath($dokuPath);
        return $fetcherRaw;
    }

    /**
     * Empty because a fetch is mostly build through an URL
     * @return FetcherRaw
     */
    public static function createEmpty(): FetcherRaw
    {
        return new FetcherRaw();
    }

    /**
     * @throws ExceptionBadArgument
     */
    public static function createFetcherFromFetchUrl(Url $fetchUrl): FetcherRaw
    {
        $fetchRaw = FetcherRaw::createEmpty();
        $fetchRaw->buildFromUrl($fetchUrl);
        return $fetchRaw;
    }


    /**
     * @return Url - an URL to download the media
     */
    function getFetchUrl(Url $url = null): Url
    {

        $url = parent::getFetchUrl($url);
        $this->addOriginalPathParametersToFetchUrl($url);

        return $url;

    }

    /**
     * @throws ExceptionBadArgument - if the media was not found
     */
    public function buildFromTagAttributes(TagAttributes $tagAttributes): FetcherRaw
    {

        $this->buildOriginalPathFromTagAttributes($tagAttributes);
        parent::buildFromTagAttributes($tagAttributes);
        return $this;

    }

    function getFetchPath(): LocalPath
    {
        return $this->getOriginalPath()->toLocalPath();
    }


    /**
     * Buster for the {@link Fetcher} interface
     * @throws ExceptionNotFound
     */
    function getBuster(): string
    {
        return FileSystems::getCacheBuster($this->getOriginalPath());
    }


    public
    function getFetcherName(): string
    {
        return self::RAW;
    }
}
