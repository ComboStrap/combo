<?php

namespace ComboStrap;

use ComboStrap\Web\Url;

/**
 * A trait to share between Fetcher
 * if they depends on a {@link WikiPath}
 *
 * This trait can:
 *   * build the {@link FetcherTraitWikiPath::getSourcePath()} from {@link FetcherTraitWikiPath::buildOriginalPathFromTagAttributes() tag attributes}
 *   * add the {@link buildURLparams() url params to the fetch Url}
 *
 *
 * This is the case for the {@link FetcherSvg image}
 * and {@link FetcherRaster} but also for the {@link FetcherRailBar} that depends on the requested page
 *
 * Not all image depends on a path, that's why this is a trait to
 * share the code
 */
trait FetcherTraitWikiPath
{

    private WikiPath $path;


    /**
     * @param WikiPath $path
     * @return IFetcher
     */
    public function setSourcePath(WikiPath $path): IFetcher
    {
        $this->path = $path;
        return $this;
    }

    /**
     * @param TagAttributes $tagAttributes
     * @return IFetcher
     * @throws ExceptionBadArgument - if the wiki id (id/media) property was not found and the path was not set
     * @throws ExceptionBadSyntax - thrown by other class via the overwritten of {@link setSourcePath} (bad image)
     * @throws ExceptionNotExists - thrown by other class via the overwritten of {@link setSourceImage} (non-existing image)
     * @throws ExceptionNotFound - thrown by other class via the overwritten of {@link setSourceImage} (not found image)
     */
    public function buildOriginalPathFromTagAttributes(TagAttributes $tagAttributes): IFetcher
    {

        if (!isset($this->path)) {
            $id = $tagAttributes->getValueAndRemove(MediaMarkup::$MEDIA_QUERY_PARAMETER);
            $defaultDrive = WikiPath::MEDIA_DRIVE;
            if ($id === null) {
                $id = $tagAttributes->getValueAndRemove(FetcherRawLocalPath::SRC_QUERY_PARAMETER);
            }
            if ($id === null) {
                $id = $tagAttributes->getValueAndRemove(DokuwikiId::DOKUWIKI_ID_ATTRIBUTE);
                $defaultDrive = WikiPath::MARKUP_DRIVE;
            }
            if ($id === null) {
                throw new ExceptionBadArgument("The (" . MediaMarkup::$MEDIA_QUERY_PARAMETER . ", " . FetcherRawLocalPath::SRC_QUERY_PARAMETER . " or " . DokuwikiId::DOKUWIKI_ID_ATTRIBUTE . ") query property is mandatory and was not defined");
            }
            $drive = $tagAttributes->getValueAndRemove(WikiPath::DRIVE_ATTRIBUTE, $defaultDrive);
            $rev = $tagAttributes->getValueAndRemove(WikiPath::REV_ATTRIBUTE);
            $path = WikiPath::toValidAbsolutePath($id);
            if ($drive == WikiPath::MARKUP_DRIVE) {
                /**
                 * Markup id have by default a txt extension
                 * but they may have other
                 */
                $wikiPath = WikiPath::createMarkupPathFromPath($path, $rev);
            } else {
                $wikiPath = WikiPath::createFromPath($path, $drive, $rev);
            }

            $this->setSourcePath($wikiPath);
        }

        return $this;

    }


    public function getSourcePath(): WikiPath
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
     * We still use the {@link MediaMarkup::MEDIA_QUERY_PARAMETER}
     * to be Dokuwiki Compatible even if we can serve from other drive know
     * @param Url $url
     * @param string $wikiIdKey - the key used to set the wiki id (ie {@link MediaMarkup::$MEDIA_QUERY_PARAMETER}
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
