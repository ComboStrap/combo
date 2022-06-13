<?php

namespace ComboStrap;

class DokuFetch extends FetchAbs
{

    public const MEDIA_QUERY_PARAMETER = "media";
    private DokuPath $path;


    public static function createFromPath(DokuPath $dokuPath): DokuFetch
    {
        return self::createEmpty()->setDokuPath($dokuPath);
    }

    /**
     * Empty because a fetch is mostly build through an URL
     * @return DokuFetch
     */
    public static function createEmpty(): DokuFetch
    {
        return new DokuFetch();
    }


    /**
     * @return Url - an URL to download the media
     * @throws ExceptionNotFound
     */
    function getFetchUrl(Url $url = null): Url
    {
        /**
         * For dokuwiki implementation, see {@link ml()}
         */
        $url = parent::getFetchUrl($url);
        return $url
            ->addQueryMediaParameter( $this->path->getDokuwikiId())
            ->addQueryParameter(DokuPath::DRIVE_ATTRIBUTE, $this->path->getDrive());

    }

    function getFetchPath(): DokuPath
    {
        return $this->path;
    }


    /**
     * Buster for the {@link Fetch} interface
     * @throws ExceptionNotFound
     */
    function getBuster(): string
    {
        $time = FileSystems::getModifiedTime($this->path);
        return strval($time->getTimestamp());
    }


    function acceptsFetchUrl(Url $url): bool
    {

        if ($url->hasProperty(DokuPath::DRIVE_ATTRIBUTE)) {
            return true;
        }
        return false;

    }

    /**
     * @throws ExceptionNotFound
     */
    public function getMime(): Mime
    {
        return FileSystems::getMime($this->path);
    }

    public function setDokuPath(DokuPath $dokuPath): DokuFetch
    {
        $this->path = $dokuPath;
        return $this;
    }

    /**
     * @throws ExceptionBadArgument
     */
    public function buildFromUrl(Url $url): DokuFetch
    {

        $id = $url->getQueryPropertyValue(self::MEDIA_QUERY_PARAMETER);
        if($id===null){
            throw new ExceptionBadArgument("The (".self::MEDIA_QUERY_PARAMETER.") query property is mandatory and was not present in the URL ($url)");
        }
        $drive = $url->getQueryPropertyValueOrDefault(DokuPath::DRIVE_ATTRIBUTE, DokuPath::MEDIA_DRIVE);
        $rev = $url->getQueryPropertyValue(DokuPath::REV_ATTRIBUTE);
        $this->path = DokuPath::create(":$id", $drive, $rev);
        return $this;

    }


}
