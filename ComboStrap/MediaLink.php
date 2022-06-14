<?php
/**
 * Copyright (c) 2021. ComboStrap, Inc. and its affiliates. All Rights Reserved.
 *
 * This source code is licensed under the GPL license found in the
 * COPYING  file in the root directory of this source tree.
 *
 * @license  GPL 3 (https://www.gnu.org/licenses/gpl-3.0.en.html)
 * @author   ComboStrap <support@combostrap.com>
 *
 */

namespace ComboStrap;


/**
 * Class InternalMedia
 * Represent a markup link
 *
 *
 * @package ComboStrap
 *
 * Wrapper around {@link Doku_Handler_Parse_Media}
 *
 * Not that for dokuwiki the `type` key of the attributes is the `call`
 * and therefore determine the function in an render
 * (ie {@link \Doku_Renderer::internalmedialink()} or {@link \Doku_Renderer::externalmedialink()}
 *
 * This is a link to a media (pdf, image, ...).
 * It's used to check the media type and to
 * take over if the media type is an image
 */
abstract class MediaLink
{


    const CANONICAL = "image";


    /**
     * @deprecated 2021-06-12
     */
    const LINK_PATTERN = "{{\s*([^|\s]*)\s*\|?.*}}";
    protected MediaMarkup $mediaMarkup;


    public function __construct(MediaMarkup $mediaMarkup)
    {
        $this->mediaMarkup = $mediaMarkup;
    }


    /**
     * @param MediaMarkup $mediaMarkup
     * @return RasterImageLink|SvgImageLink|ThirdMediaLink|MediaLink
     * @throws ExceptionBadArgument
     * @throws ExceptionBadSyntax
     * @throws ExceptionNotExists
     */
    public static function createFromMediaMarkup(MediaMarkup $mediaMarkup)
    {

        /**
         * Processing
         */
        try {
            $mime = FileSystems::getMime($mediaMarkup->getPath());
        } catch (ExceptionNotFound $e) {
            // no mime
            LogUtility::error($e->getMessage());
            return new ThirdMediaLink($mediaMarkup);
        }
        switch ($mime->toString()) {
            case Mime::SVG:
                return new SvgImageLink($mediaMarkup);
            default:
                if (!$mime->isImage()) {
                    LogUtility::msg("The type ($mime) of the media markup ($mediaMarkup) is not an image", LogUtility::LVL_MSG_DEBUG, "image");
                    return new ThirdMediaLink($mediaMarkup);
                } else {
                    return new RasterImageLink($mediaMarkup);
                }
        }


    }





    /**
     * @return string - the HTML of the image inside a link if asked
     * @throws ExceptionNotFound
     */
    public
    function renderMediaTagWithLink(): string
    {

        /**
         * Link to the media
         *
         */
        $mediaLink = TagAttributes::createEmpty();
        // https://www.dokuwiki.org/config:target
        global $conf;
        $target = $conf['target']['media'];
        $mediaLink->addOutputAttributeValueIfNotEmpty("target", $target);
        if (!empty($target)) {
            $mediaLink->addOutputAttributeValue("rel", 'noopener');
        }

        /**
         * Do we add a link to the image ?
         */
        $media = $this->getPath();
        $dokuPath = $media->getPath();
        if (!($dokuPath instanceof DokuPath)) {
            LogUtility::msg("Media Link are only supported on media from the internal library ($media)", LogUtility::LVL_MSG_ERROR, self::CANONICAL);
            return "";
        }
        $linking = $this->getLinking();
        switch ($linking) {
            case MediaMarkup::LINKING_LINKONLY_VALUE: // show only a url
                $src = ml(
                    $dokuPath->getDokuwikiId(),
                    array(
                        'id' => $dokuPath->getDokuwikiId(),
                        'cache' => $media->getRequestedCache(),
                        'rev' => $dokuPath->getRevision()
                    )
                );
                $mediaLink->addOutputAttributeValue("href", $src);
                $title = $media->getTitle();
                if (empty($title)) {
                    $title = $media->getType();
                }
                return $mediaLink->toHtmlEnterTag("a") . $title . "</a>";
            case MediaMarkup::LINKING_NOLINK_VALUE:
                return $this->renderMediaTag();
            default:
            case MediaMarkup::LINKING_DIRECT_VALUE:
                //directly to the image
                $src = ml(
                    $dokuPath->getDokuwikiId(),
                    array(
                        'id' => $dokuPath->getDokuwikiId(),
                        'cache' => $media->getRequestedCache(),
                        'rev' => $dokuPath->getRevision()
                    ),
                    true
                );
                $mediaLink->addOutputAttributeValue("href", $src);
                $snippetId = "lightbox";
                $mediaLink->addClassName(StyleUtility::getStylingClassForTag($snippetId));
                $linkingClass = $this->getLinkingClass();
                if ($linkingClass !== null) {
                    $mediaLink->addClassName($linkingClass);
                }
                $snippetManager = PluginUtility::getSnippetManager();
                $snippetManager->attachJavascriptComboLibrary();
                $snippetManager->attachInternalJavascriptForSlot($snippetId);
                $snippetManager->attachCssInternalStyleSheetForSlot($snippetId);
                return $mediaLink->toHtmlEnterTag("a") . $this->renderMediaTag() . "</a>";

            case MediaMarkup::LINKING_DETAILS_VALUE:
                //go to the details media viewer
                $src = ml(
                    $dokuPath->getDokuwikiId(),
                    array(
                        'id' => $dokuPath->getDokuwikiId(),
                        'cache' => $media->getRequestedCache(),
                        'rev' => $dokuPath->getRevision()
                    ),
                    false
                );
                $mediaLink->addOutputAttributeValue("href", $src);
                return $mediaLink->toHtmlEnterTag("a") .
                    $this->renderMediaTag() .
                    "</a>";

        }


    }


    /**
     * @return string - the HTML of the image
     */
    public abstract function renderMediaTag(): string;





    public function getFetch(): Fetch
    {
        $path = $this->mediaMarkup->getPath();
        try {
            $mime = FileSystems::getMime($path);
        } catch (ExceptionNotFound $e) {
            return FetchDoku::createFromPath($path);
        }
        if ($mime->toString() === Mime::PDF) {
            return (new FetchPdf())
                ->setDokuPath($path);
        }
        return FetchDoku::createFromPath($path);

    }


}
