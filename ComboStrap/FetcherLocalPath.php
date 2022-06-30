<?php

namespace ComboStrap;

/**
 * Return raw files
 */
class FetcherLocalPath extends FetcherAbs implements FetcherSource
{

    use FetcherTraitLocalPath;

    const SRC_QUERY_PARAMETER = "src";
    const RAW = "raw";


    public static function createFromPath(WikiPath $dokuPath): FetcherLocalPath
    {
        $fetcherRaw = self::createEmpty();
        $fetcherRaw->setOriginalPath($dokuPath);
        return $fetcherRaw;
    }

    /**
     * Empty because a fetch is mostly build through an URL
     * @return FetcherLocalPath
     */
    public static function createEmpty(): FetcherLocalPath
    {
        return new FetcherLocalPath();
    }

    /**
     * @throws ExceptionBadArgument
     */
    public static function createLocalFromFetchUrl(Url $fetchUrl): FetcherLocalPath
    {
        $fetchRaw = FetcherLocalPath::createEmpty();
        $fetchRaw->buildFromUrl($fetchUrl);
        return $fetchRaw;
    }


    /**
     * @return Url - an URL to download the media
     */
    function getFetchUrl(Url $url = null): Url
    {

        $url = parent::getFetchUrl($url);
        $this->addLocalPathParametersToFetchUrl($url, self::$MEDIA_QUERY_PARAMETER);
        return $url;

    }

    /**
     * @param TagAttributes $tagAttributes
     * @return FetcherLocalPath
     * @throws ExceptionBadArgument - if the media/id was not found
     * @throws ExceptionBadSyntax
     * @throws ExceptionNotExists
     * @throws ExceptionNotFound
     */
    public function buildFromTagAttributes(TagAttributes $tagAttributes): FetcherLocalPath
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
