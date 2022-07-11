<?php

namespace ComboStrap;

/**
 * Return raw files
 */
class FetcherLocalPath extends IFetcherAbs implements IFetcherPath, IFetcherSource
{

    use FetcherTraitLocalPath;

    const SRC_QUERY_PARAMETER = "src";
    const RAW = "raw";


    public static function createFromPath(WikiPath $wikiPath): FetcherLocalPath
    {
        $fetcherRaw = self::createEmpty();
        $fetcherRaw->setOriginalPath($wikiPath);
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
     * @throws ExceptionNotFound - if the file does not exists (no buster can be added)
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
     * Buster for the {@link IFetcher} interface
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
