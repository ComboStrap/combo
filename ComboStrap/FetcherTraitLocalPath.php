<?php

namespace ComboStrap;

/**
 * A trait to share between Fetcher
 * if they depends on a {@link WikiPath}
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
    private WikiPath $path;


    public function setOriginalPath(WikiPath $dokuPath): Fetcher
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
                $id = $tagAttributes->getValueAndRemove(FetcherLocalPath::SRC_QUERY_PARAMETER);
            }
            if ($id === null) {
                $id = $tagAttributes->getValueAndRemove(DokuwikiId::DOKUWIKI_ID_ATTRIBUTE);
            }
            if ($id === null) {
                throw new ExceptionBadArgument("The (" . self::$MEDIA_QUERY_PARAMETER . ", " . self::SRC_QUERY_PARAMETER . " or " . DokuwikiId::DOKUWIKI_ID_ATTRIBUTE . ") query property is mandatory and was not defined");
            }
            $drive = $tagAttributes->getValueAndRemove(WikiPath::DRIVE_ATTRIBUTE, WikiPath::MEDIA_DRIVE);
            $rev = $tagAttributes->getValueAndRemove(WikiPath::REV_ATTRIBUTE);
            $this->setOriginalPath(WikiPath::create($id, $drive, $rev));
        }

        return $this;

    }


    public function getOriginalPath(): WikiPath
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
     * @param Url $url
     * @param string $wikiIdKey - the key used to set the wiki id (ie {@link FetcherTraitLocalPath::$MEDIA_QUERY_PARAMETER}
     * or {@link DokuWikiId::DOKUWIKI_ID_ATTRIBUTE}
     */
    public function addLocalPathParametersToFetchUrl(Url $url, string $wikiIdKey): void
    {

        $url->addQueryParameterIfNotActualSameValue($wikiIdKey, $this->path->getWikiId());
        if ($this->path->getDrive() !== WikiPath::MEDIA_DRIVE) {
            $url->addQueryParameter(WikiPath::DRIVE_ATTRIBUTE, $this->path->getDrive());
        }
        try {
            $rev = $this->path->getRevision();
            $url->addQueryParameter(WikiPath::REV_ATTRIBUTE, $rev);
        } catch (ExceptionNotFound $e) {
            // ok no rev
        }

    }

}
