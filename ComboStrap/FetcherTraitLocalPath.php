<?php

namespace ComboStrap;

/**
 * A trait to share between Fetcher
 * if they depends on a {@link DokuPath}
 *
 * This trait can:
 *   * build the {@link FetcherTraitLocalPath::getOriginalPath()} from {@link FetcherTraitLocalPath::buildOriginalPathFromTagAttributes() tag attributes}
 *   * add the {@link buildURLparams() url params to the fetch Url}
 *
 *
 * This is the case for the {@link FetcherSvg image}
 * and {@link FetcherRaster}
 *
 * Not all image depends on a path, that's why this is a trait to
 * share the code
 */
trait FetcherTraitLocalPath
{

    public static string $MEDIA_QUERY_PARAMETER = "media";
    private DokuPath $path;


    public function setOriginalPath(DokuPath $dokuPath): Fetcher
    {
        $this->path = $dokuPath;
        return $this;
    }

    /**
     * @param TagAttributes $tagAttributes
     * @return Fetcher
     * @throws ExceptionBadArgument - if the media property was not found and the path was not set
     * @throws ExceptionBadSyntax - if the media has a bad syntax (no width, ...)
     * @throws ExceptionNotExists -  if the media does not exists
     * @throws ExceptionNotFound - if the media or any mandatory metadata (ie dimension) was not found
     */
    public function buildOriginalPathFromTagAttributes(TagAttributes $tagAttributes): Fetcher
    {

        if (!isset($this->path)) {
            $id = $tagAttributes->getValueAndRemove(self::$MEDIA_QUERY_PARAMETER);
            if ($id === null) {
                $id = $tagAttributes->getValueAndRemove(self::SRC_QUERY_PARAMETER);
            }
            if ($id === null) {
                throw new ExceptionBadArgument("The (" . self::$MEDIA_QUERY_PARAMETER . " or " . self::SRC_QUERY_PARAMETER . ") query property is mandatory and was not defined");
            }
            $drive = $tagAttributes->getValueAndRemove(DokuPath::DRIVE_ATTRIBUTE, DokuPath::MEDIA_DRIVE);
            $rev = $tagAttributes->getValueAndRemove(DokuPath::REV_ATTRIBUTE);
            $this->setOriginalPath(DokuPath::create($id, $drive, $rev));
        }

        return $this;

    }


    public function getOriginalPath(): DokuPath
    {
        return $this->path;
    }

    /**
     * @throws ExceptionNotFound
     */
    public function getMime(): Mime
    {
        return FileSystems::getMime($this->path);
    }

    /**
     * Add media and rev to url
     * For dokuwiki implementation, see {@link ml()}
     * We still use the {@link FetcherLocalPath::MEDIA_QUERY_PARAMETER}
     * to be Dokuwiki Compatible even if we can serve from other drive know
     */
    public function addLocalPathParametersToFetchUrl(Url $url): void
    {

        $url->addQueryParameterIfNotActualSameValue(self::$MEDIA_QUERY_PARAMETER, $this->path->getDokuwikiId());
        if ($this->path->getDrive() !== DokuPath::MEDIA_DRIVE) {
            $url->addQueryParameter(DokuPath::DRIVE_ATTRIBUTE, $this->path->getDrive());
        }
        try {
            $rev = $this->path->getRevision();
            $url->addQueryParameter(DokuPath::REV_ATTRIBUTE, $rev);
        } catch (ExceptionNotFound $e) {
            // ok no rev
        }

    }

}
